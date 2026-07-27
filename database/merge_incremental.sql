-- =====================================================================
--  MERGE  resume_parser -> sista   (SATU SCRIPT UNTUK SEMUA)
--
--  * SISTA TIDAK DIUBAH strukturnya — script ini HANYA INSERT data ke sista.
--    Tidak ada ALTER/ubah kolom. Semua pelacakan pakai username 'rp{id}_'
--    + tabel bantu DI resume_parser (bukan di sista).
--  * Aman di-Go BERULANG. Otomatis hanya memindahkan CV yang BELUM ada di sista
--    (yang sudah ada dilewati — tidak menduplikat).
--
--  Cara pakai: paste ke tab SQL phpMyAdmin -> Go. Ulangi tiap ada CV baru.
-- =====================================================================

-- (0) Peta CV yang SUDAH ada di sista SEBELUM run ini (tabel bantu di resume_parser).
DROP TABLE IF EXISTS resume_parser._map_cv;
CREATE TABLE resume_parser._map_cv AS
SELECT c.id AS old_cv_id, sc.id AS new_cv_id
FROM resume_parser.cv c
JOIN sista.user su ON su.username REGEXP CONCAT('^rp', c.user_id, '_')
JOIN sista.cv   sc ON sc.user_id = su.id;

-- (1) USER baru: talent yang punya CV tapi belum ada di sista.
INSERT INTO sista.user
  (name, username, email, password, role_id, kategori_user, is_active, status,
   date_created, last_login, statement, password_version, updated_at)
SELECT
  LEFT(COALESCE(NULLIF(u.name,''), 'Talent'), 128),
  LEFT(CONCAT('rp', u.id, '_', REPLACE(LOWER(COALESCE(NULLIF(u.name,''),'talent')), ' ', '')), 64),
  LEFT(COALESCE(u.email, ''), 255),
  u.password, 2, 'non_dosen', 1, 'active',
  UNIX_TIMESTAMP(), 0, 0, 0, NOW()
FROM resume_parser.users u
WHERE u.id IN (SELECT user_id FROM resume_parser.cv WHERE user_id IS NOT NULL)
  AND NOT EXISTS (SELECT 1 FROM sista.user su WHERE su.username REGEXP CONCAT('^rp', u.id, '_'));

-- (2) CV baru: yang belum ada di peta (0).
INSERT INTO sista.cv
  (user_id, nama, gelar_depan, gelar_belakang, posisi, perusahaan, tempat_lahir,
   tanggal_lahir, kewarganegaraan, status_kepegawaian, pernah_di_wb, created_at,
   employment_from, employment_to, employer, employment_position, employment_desc,
   domisili_negara, domisili_kota, updated_at)
SELECT
  su.id, c.nama, c.gelar_depan, c.gelar_belakang, c.posisi, c.perusahaan, c.tempat_lahir,
  c.tanggal_lahir, c.kewarganegaraan, c.status_kepegawaian, c.pernah_di_wb, c.created_at,
  c.employment_from, c.employment_to, c.employer, c.employment_position, c.employment_desc,
  c.domisili_negara, c.domisili_kota, c.updated_at
FROM resume_parser.cv c
JOIN sista.user su ON su.username REGEXP CONCAT('^rp', c.user_id, '_')
WHERE c.id NOT IN (SELECT old_cv_id FROM resume_parser._map_cv);

-- (3) Peta CV BARU run ini = mapping sekarang DIKURANGI peta (0).
DROP TABLE IF EXISTS resume_parser._run_cv;
CREATE TABLE resume_parser._run_cv AS
SELECT c.id AS old_cv_id, sc.id AS new_cv_id
FROM resume_parser.cv c
JOIN sista.user su ON su.username REGEXP CONCAT('^rp', c.user_id, '_')
JOIN sista.cv   sc ON sc.user_id = su.id
WHERE c.id NOT IN (SELECT old_cv_id FROM resume_parser._map_cv);

-- (4) TABEL ANAK — hanya untuk CV baru run ini (cv_id di-remap; file di-NULL).
INSERT INTO sista.bahasa (cv_id, bahasa, speaking, reading, writing, is_visible)
SELECT r.new_cv_id, b.bahasa, b.speaking, b.reading, b.writing, b.is_visible
FROM resume_parser.bahasa b JOIN resume_parser._run_cv r ON r.old_cv_id = b.cv_id;

INSERT INTO sista.pendidikan_formal (cv_id, tingkat, institusi, jurusan, tahun_lulus, ijazah_file, is_visible)
SELECT r.new_cv_id, p.tingkat, p.institusi, p.jurusan, p.tahun_lulus, NULL, p.is_visible
FROM resume_parser.pendidikan_formal p JOIN resume_parser._run_cv r ON r.old_cv_id = p.cv_id;

INSERT INTO sista.pendidikan_nonformal (cv_id, nama_pelatihan, penyelenggara, tahun, sertifikat_file, is_visible)
SELECT r.new_cv_id, p.nama_pelatihan, p.penyelenggara, p.tahun, NULL, p.is_visible
FROM resume_parser.pendidikan_nonformal p JOIN resume_parser._run_cv r ON r.old_cv_id = p.cv_id;

INSERT INTO sista.pengalaman_kerja
  (cv_id, tahun, nama_kegiatan, uraian_proyek, lokasi, negara, pemberi_pekerjaan, perusahaan,
   pelaksana_proyek, uraian_tugas, waktu_mulai, waktu_akhir, durasi, waktu_legacy, posisi,
   status_kepegawaian, referensi_file, is_visible, created_at, updated_at)
SELECT
  r.new_cv_id, e.tahun, e.nama_kegiatan, e.uraian_proyek, e.lokasi, e.negara, e.pemberi_pekerjaan, e.perusahaan,
  e.pelaksana_proyek, e.uraian_tugas, e.waktu_mulai, e.waktu_akhir, e.durasi, e.waktu_legacy, e.posisi,
  e.status_kepegawaian, NULL, e.is_visible, e.created_at, e.updated_at
FROM resume_parser.pengalaman_kerja e JOIN resume_parser._run_cv r ON r.old_cv_id = e.cv_id;

INSERT INTO sista.sertifikasi_profesi (cv_id, nama, penerbit, tahun, file_sertifikat, is_visible)
SELECT r.new_cv_id, s.nama, s.penerbit, s.tahun, NULL, s.is_visible
FROM resume_parser.sertifikasi_profesi s JOIN resume_parser._run_cv r ON r.old_cv_id = s.cv_id;

INSERT INTO sista.project_relevan (cv_id, nama_project, tahun, lokasi, klien, fitur_proyek, posisi, aktivitas)
SELECT r.new_cv_id, pr.nama_project, pr.tahun, pr.lokasi, pr.klien, pr.fitur_proyek, pr.posisi, pr.aktivitas
FROM resume_parser.project_relevan pr JOIN resume_parser._run_cv r ON r.old_cv_id = pr.cv_id;

INSERT INTO sista.cv_keahlian (cv_id, nama_keahlian, is_visible, created_at)
SELECT r.new_cv_id, k.nama_keahlian, k.is_visible, k.created_at
FROM resume_parser.cv_keahlian k JOIN resume_parser._run_cv r ON r.old_cv_id = k.cv_id;

-- (5) Hasil
SELECT (SELECT COUNT(*) FROM resume_parser._run_cv) AS cv_baru_dipindah_run_ini,
       (SELECT COUNT(*) FROM resume_parser._map_cv) + (SELECT COUNT(*) FROM resume_parser._run_cv) AS total_cv_di_sista;
