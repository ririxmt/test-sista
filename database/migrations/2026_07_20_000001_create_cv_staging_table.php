<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_staging', function (Blueprint $table) {
            $table->id();

            // Nullable: baru diisi setelah data dicocokkan ke user existing (manual/otomatis by email)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Path file CV asli (PDF/JPG/PNG) yang diupload — sebelumnya tidak disimpan sama sekali
            $table->string('source_file_path');
            $table->string('source_file_original_name')->nullable();
            $table->string('source_mime_type', 100)->nullable();

            // Output mentah dari AI, apa adanya, sebelum di-mapping ke tabel relasional
            $table->json('raw_parsed_json');

            // Ringkasan email/nama dari hasil parsing, untuk pencocokan cepat tanpa decode JSON
            $table->string('parsed_email')->nullable()->index();
            $table->string('parsed_name')->nullable();

            // Hasil perbandingan field-by-field vs data existing milik user (bukan cuma "jumlah item")
            $table->json('diff_summary')->nullable();

            $table->enum('review_status', ['pending_review', 'approved', 'rejected'])
                ->default('pending_review')
                ->index();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_staging');
    }
};
