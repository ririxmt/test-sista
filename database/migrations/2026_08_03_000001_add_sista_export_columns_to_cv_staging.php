<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom kontrol export ke SISTA pada cv_staging.
     * ALTER (menambah kolom) — data lama tetap utuh.
     */
    public function up(): void
    {
        Schema::table('cv_staging', function (Blueprint $table) {
            $table->unsignedBigInteger('cv_id')->nullable()->after('user_id');
            $table->enum('sista_export_status', ['not_exported', 'exported', 'changed_after_export'])
                ->default('not_exported')->after('review_note');
            $table->string('sista_export_batch', 100)->nullable()->after('sista_export_status');
            $table->timestamp('sista_exported_at')->nullable()->after('sista_export_batch');
            $table->char('sista_export_hash', 64)->nullable()->after('sista_exported_at');

            $table->index(['review_status', 'sista_export_status'], 'idx_sista_export');
            $table->index('cv_id', 'idx_cv_staging_cv_id');
        });
    }

    public function down(): void
    {
        Schema::table('cv_staging', function (Blueprint $table) {
            $table->dropIndex('idx_sista_export');
            $table->dropIndex('idx_cv_staging_cv_id');
            $table->dropColumn([
                'cv_id',
                'sista_export_status',
                'sista_export_batch',
                'sista_exported_at',
                'sista_export_hash',
            ]);
        });
    }
};
