<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel inti profil CV — selaras dengan tabel `cv` di SISTA
        Schema::create('cv', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nama')->nullable();
            $table->text('gelar_depan')->nullable();
            $table->text('gelar_belakang')->nullable();
            $table->string('posisi')->nullable();
            $table->string('perusahaan')->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('kewarganegaraan', 100)->default('Indonesia');
            $table->string('status_kepegawaian', 100)->nullable();
            $table->text('pernah_di_wb')->nullable();
            // Urutan mengikuti SISTA: created_at berada di antara pernah_di_wb & employment_from
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->date('employment_from')->nullable();
            $table->date('employment_to')->nullable();
            $table->string('employer')->nullable();
            $table->string('employment_position')->nullable();
            $table->text('employment_desc')->nullable();
            $table->string('domisili_negara', 100)->default('Indonesia');
            $table->string('domisili_kota', 100)->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // Setara `pendidikan_formal` (sesuai SISTA)
        Schema::create('pendidikan_formal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained('cv')->cascadeOnDelete();
            $table->string('tingkat', 100)->nullable();   // dari "jenjang" hasil AI
            $table->string('institusi')->nullable();
            $table->string('jurusan')->nullable();
            $table->year('tahun_lulus')->nullable();
            $table->string('ijazah_file')->nullable();
            $table->boolean('is_visible')->default(true);
        });

        // Setara `pengalaman_kerja` (sesuai SISTA — satu-satunya tabel anak dengan timestamps)
        Schema::create('pengalaman_kerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained('cv')->cascadeOnDelete();
            $table->year('tahun')->nullable();
            $table->string('nama_kegiatan')->nullable();
            $table->text('uraian_proyek')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('negara', 100)->nullable();
            $table->string('pemberi_pekerjaan')->nullable();
            $table->string('perusahaan')->nullable();      // dari "perusahaan" hasil AI
            $table->string('pelaksana_proyek')->nullable();
            $table->text('uraian_tugas')->nullable();       // dari "deskripsi" hasil AI
            $table->date('waktu_mulai')->nullable();
            $table->date('waktu_akhir')->nullable();
            $table->string('durasi', 100)->nullable();      // dari "durasi" hasil AI
            $table->string('waktu_legacy', 100)->nullable();
            $table->string('posisi', 100)->nullable();      // dari "jabatan" hasil AI
            $table->string('status_kepegawaian', 100)->nullable();
            $table->string('referensi_file')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        // Setara `sertifikasi_profesi` (sesuai SISTA)
        Schema::create('sertifikasi_profesi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained('cv')->cascadeOnDelete();
            $table->string('nama')->nullable();            // dari "nama_sertifikasi" hasil AI
            $table->string('penerbit')->nullable();
            $table->year('tahun')->nullable();
            $table->string('file_sertifikat')->nullable();
            $table->boolean('is_visible')->default(true);
        });

        // Setara `project_relevan` (sesuai SISTA — tanpa is_visible & tanpa timestamps)
        Schema::create('project_relevan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained('cv')->cascadeOnDelete();
            $table->string('nama_project')->nullable();    // dari "nama_proyek" hasil AI
            $table->year('tahun')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('klien')->nullable();
            $table->text('fitur_proyek')->nullable();
            $table->string('posisi', 100)->nullable();
            $table->text('aktivitas')->nullable();
        });

        // Setara `cv_keahlian` — hanya punya created_at (tanpa updated_at), sesuai SISTA
        Schema::create('cv_keahlian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained('cv')->cascadeOnDelete();
            $table->string('nama_keahlian', 100);
            $table->boolean('is_visible')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        // Setara `cv_keahlian_link` — menghubungkan keahlian ke pengalaman/sertifikasi
        Schema::create('cv_keahlian_link', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('keahlian_id');
            $table->unsignedBigInteger('target_id');
            $table->enum('target_type', ['pengalaman', 'sertifikasi']);
            $table->boolean('is_visible_section')->default(true);
            $table->index('keahlian_id');
        });

        // Pendidikan Non-Formal / Pelatihan (sesuai SISTA)
        Schema::create('pendidikan_nonformal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained('cv')->cascadeOnDelete();
            $table->string('nama_pelatihan')->nullable();
            $table->string('penyelenggara')->nullable();
            $table->year('tahun')->nullable();
            $table->string('sertifikat_file')->nullable();
            $table->boolean('is_visible')->default(true);
        });

        // Kemampuan Bahasa — tanpa timestamps, sesuai SISTA
        Schema::create('bahasa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained('cv')->cascadeOnDelete();
            $table->string('bahasa', 100)->nullable();
            $table->string('speaking', 100)->nullable();
            $table->string('reading', 100)->nullable();
            $table->string('writing', 100)->nullable();
            $table->boolean('is_visible')->default(true);
        });

        // Lampiran File CV — satu baris per CV, kolom per jenis dokumen (sesuai SISTA)
        Schema::create('lampiran_cv', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->nullable()->constrained('cv')->cascadeOnDelete();
            $table->string('ktp_file')->nullable();
            $table->boolean('is_visible_ktp_file')->default(true);
            $table->string('npwp_file')->nullable();
            $table->boolean('is_visible_npwp_file')->default(true);
            $table->string('bukti_pajak')->nullable();
            $table->boolean('is_visible_bukti_pajak')->default(true);
            $table->string('foto')->nullable();
            $table->boolean('is_visible_foto')->default(true);
            $table->text('lainnya')->nullable();
            $table->boolean('is_visible_lainnya')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampiran_cv');
        Schema::dropIfExists('bahasa');
        Schema::dropIfExists('pendidikan_nonformal');
        Schema::dropIfExists('cv_keahlian_link');
        Schema::dropIfExists('cv_keahlian');
        Schema::dropIfExists('project_relevan');
        Schema::dropIfExists('sertifikasi_profesi');
        Schema::dropIfExists('pengalaman_kerja');
        Schema::dropIfExists('pendidikan_formal');
        Schema::dropIfExists('cv');
    }
};
