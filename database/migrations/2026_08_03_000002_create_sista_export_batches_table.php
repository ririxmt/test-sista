<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat tiap paket ZIP export ke SISTA.
     */
    public function up(): void
    {
        Schema::create('sista_export_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 100)->unique();
            $table->timestamp('exported_at')->nullable();
            $table->unsignedBigInteger('exported_by')->nullable();
            $table->integer('total_cv')->default(0);
            $table->integer('total_files')->default(0);
            $table->string('zip_filename')->nullable();
            $table->enum('status', ['processing', 'success', 'failed'])->default('processing');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sista_export_batches');
    }
};
