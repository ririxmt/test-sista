-- =====================================================================
--  MERGE data  resume_parser  ->  sista   (DATA SAJA, TANPA FILE)
--  Jalankan di phpMyAdmin (tab SQL). Kedua DB harus di server MySQL yang sama.
--
--  AMAN: hanya INSERT (menambah), TIDAK menghapus/mengubah data sista yang ada.
--  WAJIB: BACKUP database `sista` dulu (Export) sebelum menjalankan ini.
--
--  Cara kerja: ID di-remap (auto-increment baru) supaya tidak bentrok dengan
--  data sista. File lampiran DI-SKIP (kolom file diisi NULL) karena storage
--  sista tidak ada di komputer ini.
-- =====================================================================

-- ---------- Tabel bantu pemetaan ID (dipakai juga untuk UNDO) ----------
DROP TABLE IF EXISTS resume_parser._map_user;
DROP TABLE IF EXISTS resume_parser._map_cv;
CREATE TABLE resume_parser._map_user (old_id INT PRIMARY KEY, new_id INT);
CREATE TABLE resume_parser._map_cv   (old_id INT PRIMARY KEY, new_id INT);

-- =====================================================================
-- 1) USER  ->  sista.user  (buat user baru untuk tiap talent yang punya CV)
--    username dibuat unik: 'rp{old_id}_{nama}'. Password disalin apa adanya.
-- =====================================================================
ALTER TABLE sista.user ADD COLUMN _rp_old_id INT NULL;

INSERT INTO sista.user
  (name, username, email, password, role_id, kategori_user, is_active, status,
   date_created, last_login, statement, password_version, updated_at, _rp_old_id)
SELECT
  LEFT(COALESCE(NULLIF(u.name,''), 'Talent'), 128),
  LEFT(CONCAT('rp', u.id, '_', REPLACE(LOWER(COALESCE(NULLIF(u.name,''),'talent')), ' ', '')), 64),
  LEFT(COALESCE(u.email, ''), 255),
  u.password,
  2,               -- role_id talent (2 = talent standar di sista)
  'non_dosen',
  1,               -- is_active
  'active',
  UNIX_TIMESTAMP(),
  0,               -- last_login
  0,               -- statement
  0,               -- password_version
  NOW(),
  u.id
FROM resume_parser.users u
WHERE u.id IN (SELECT DISTINCT user_id FROM resume_parser.cv WHERE user_id IS NOT NULL);

INSERT INTO resume_parser._map_user (old_id, new_id)
SELECT _rp_old_id, id FROM sista.user WHERE _rp_old_id IS NOT NULL;

ALTER TABLE sista.user DROP COLUMN _rp_old_id;

-- =====================================================================
-- 2) CV  ->  sista.cv  (user_id di-remap ke user sista yang baru)
-- =====================================================================
ALTER TABLE sista.cv ADD COLUMN _rp_old_id INT NULL;

INSERT INTO sista.cv
  (user_id, nama, gelar_depan, gelar_belakang, posisi, perusahaan, tempat_lahir,
   tanggal_lahir, kewarganegaraan, status_kepegawaian, pernah_di_wb, created_at,
   employment_from, employment_to, employer, employment_position, employment_desc,
   domisili_negara, domisili_kota, updated_at, _rp_old_id)
SELECT
  mu.new_id, c.nama, c.gelar_depan, c.gelar_belakang, c.posisi, c.perusahaan, c.tempat_lahir,
  c.tanggal_lahir, c.kewarganegaraan, c.status_kepegawaian, c.pernah_di_wb, c.created_at,
  c.employment_from, c.employment_to, c.employer, c.employment_position, c.employment_desc,
  c.domisili_negara, c.domisili_kota, c.updated_at, c.id
FROM resume_parser.cv c
JOIN resume_parser._map_user mu ON mu.old_id = c.user_id;

INSERT INTO resume_parser._map_cv (old_id, new_id)
SELECT _rp_old_id, id FROM sista.cv WHERE _rp_old_id IS NOT NULL;

ALTER TABLE sista.cv DROP COLUMN _rp_old_id;

-- =====================================================================
-- 3) TABEL ANAK  (cv_id di-remap; kolom file diisi NULL; id auto-increment baru)
-- =====================================================================
INSERT INTO sista.bahasa (cv_id, bahasa, speaking, reading, writing, is_visible)
SELECT m.new_id, b.bahasa, b.speaking, b.reading, b.writing, b.is_visible
FROM resume_parser.bahasa b JOIN resume_parser._map_cv m ON m.old_id = b.cv_id;

INSERT INTO sista.pendidikan_formal (cv_id, tingkat, institusi, jurusan, tahun_lulus, ijazah_file, is_visible)
SELECT m.new_id, p.tingkat, p.institusi, p.jurusan, p.tahun_lulus, NULL, p.is_visible
FROM resume_parser.pendidikan_formal p JOIN resume_parser._map_cv m ON m.old_id = p.cv_id;

INSERT INTO sista.pendidikan_nonformal (cv_id, nama_pelatihan, penyelenggara, tahun, sertifikat_file, is_visible)
SELECT m.new_id, p.nama_pelatihan, p.penyelenggara, p.tahun, NULL, p.is_visible
FROM resume_parser.pendidikan_nonformal p JOIN resume_parser._map_cv m ON m.old_id = p.cv_id;

INSERT INTO sista.pengalaman_kerja
  (cv_id, tahun, nama_kegiatan, uraian_proyek, lokasi, negara, pemberi_pekerjaan, perusahaan,
   pelaksana_proyek, uraian_tugas, waktu_mulai, waktu_akhir, durasi, waktu_legacy, posisi,
   status_kepegawaian, referensi_file, is_visible, created_at, updated_at)
SELECT
  m.new_id, e.tahun, e.nama_kegiatan, e.uraian_proyek, e.lokasi, e.negara, e.pemberi_pekerjaan, e.perusahaan,
  e.pelaksana_proyek, e.uraian_tugas, e.waktu_mulai, e.waktu_akhir, e.durasi, e.waktu_legacy, e.posisi,
  e.status_kepegawaian, NULL, e.is_visible, e.created_at, e.updated_at
FROM resume_parser.pengalaman_kerja e JOIN resume_parser._map_cv m ON m.old_id = e.cv_id;

INSERT INTO sista.sertifikasi_profesi (cv_id, nama, penerbit, tahun, file_sertifikat, is_visible)
SELECT m.new_id, s.nama, s.penerbit, s.tahun, NULL, s.is_visible
FROM resume_parser.sertifikasi_profesi s JOIN resume_parser._map_cv m ON m.old_id = s.cv_id;

INSERT INTO sista.project_relevan (cv_id, nama_project, tahun, lokasi, klien, fitur_proyek, posisi, aktivitas)
SELECT m.new_id, pr.nama_project, pr.tahun, pr.lokasi, pr.klien, pr.fitur_proyek, pr.posisi, pr.aktivitas
FROM resume_parser.project_relevan pr JOIN resume_parser._map_cv m ON m.old_id = pr.cv_id;

INSERT INTO sista.cv_keahlian (cv_id, nama_keahlian, is_visible, created_at)
SELECT m.new_id, k.nama_keahlian, k.is_visible, k.created_at
FROM resume_parser.cv_keahlian k JOIN resume_parser._map_cv m ON m.old_id = k.cv_id;

-- =====================================================================
-- 4) CEK HASIL
-- =====================================================================
SELECT
  (SELECT COUNT(*) FROM resume_parser._map_user) AS user_dipindah,
  (SELECT COUNT(*) FROM resume_parser._map_cv)   AS cv_dipindah;

-- Catatan:
--  * lampiran_cv TIDAK dipindah (format & file tidak kompatibel). Kolom file
--    di tabel anak diisi NULL — file diurus manual/terpisah nanti.
--  * JANGAN drop tabel resume_parser._map_user / _map_cv sampai kamu yakin —
--    keduanya dipakai oleh script UNDO (lihat file undo).
