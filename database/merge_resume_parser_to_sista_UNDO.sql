-- =====================================================================
--  UNDO / BATALKAN semua data hasil merge resume_parser -> sista
--
--  Menghapus HANYA data yang berasal dari merge (dikenali via username
--  'rp{id}_' di sista.user). Data sista asli TIDAK tersentuh, dan struktur
--  sista TIDAK diubah (hanya DELETE baris).
-- =====================================================================

-- Kumpulkan cv hasil merge (tabel bantu di resume_parser).
DROP TABLE IF EXISTS resume_parser._undo_cv;
CREATE TABLE resume_parser._undo_cv AS
SELECT sc.id FROM sista.cv sc
JOIN sista.user u ON u.id = sc.user_id
WHERE u.username REGEXP '^rp[0-9]+_';

-- Hapus tabel anak
DELETE ch FROM sista.bahasa               ch JOIN resume_parser._undo_cv m ON m.id = ch.cv_id;
DELETE ch FROM sista.pendidikan_formal    ch JOIN resume_parser._undo_cv m ON m.id = ch.cv_id;
DELETE ch FROM sista.pendidikan_nonformal ch JOIN resume_parser._undo_cv m ON m.id = ch.cv_id;
DELETE ch FROM sista.pengalaman_kerja     ch JOIN resume_parser._undo_cv m ON m.id = ch.cv_id;
DELETE ch FROM sista.sertifikasi_profesi  ch JOIN resume_parser._undo_cv m ON m.id = ch.cv_id;
DELETE ch FROM sista.project_relevan      ch JOIN resume_parser._undo_cv m ON m.id = ch.cv_id;
DELETE ch FROM sista.cv_keahlian          ch JOIN resume_parser._undo_cv m ON m.id = ch.cv_id;

-- Hapus cv hasil merge
DELETE sc FROM sista.cv sc JOIN resume_parser._undo_cv m ON m.id = sc.id;

-- Hapus user hasil merge
DELETE u FROM sista.user u WHERE u.username REGEXP '^rp[0-9]+_';

DROP TABLE IF EXISTS resume_parser._undo_cv;
