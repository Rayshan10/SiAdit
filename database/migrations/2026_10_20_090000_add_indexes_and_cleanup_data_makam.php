<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index untuk mempercepat query utama aplikasi (dashboard, audit,
     * pencocokan data) pada kondisi data banyak.
     */
    public function up(): void
    {
        Schema::table('data_makams', function (Blueprint $table) {
            // Query utama: filter per TPU + sumber data
            $table->index(['tpu_id', 'sumber'], 'data_makams_tpu_sumber_index');
            // Pencocokan & duplikat berdasarkan ID Match
            $table->index('id_match', 'data_makams_id_match_index');
            // Fuzzy matching berbasis nama
            $table->index('nama_clean', 'data_makams_nama_clean_index');
        });

        Schema::table('audits', function (Blueprint $table) {
            // Query hasil audit per kategori
            $table->index(
                ['audit_result_id', 'status'],
                'audits_result_status_index'
            );
        });

        Schema::table('audit_results', function (Blueprint $table) {
            // Ambil hasil audit terakhir per TPU
            $table->index(
                ['tpu_id', 'created_at'],
                'audit_results_tpu_created_index'
            );
        });

        // Kolom legacy yang tidak pernah dipakai (hasil audit kini
        // tersimpan pada tabel `audits`).
        Schema::table('data_makams', function (Blueprint $table) {
            if (Schema::hasColumn('data_makams', 'status_audit')) {
                $table->dropColumn('status_audit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_makams', function (Blueprint $table) {
            $table->dropIndex('data_makams_tpu_sumber_index');
            $table->dropIndex('data_makams_id_match_index');
            $table->dropIndex('data_makams_nama_clean_index');
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->dropIndex('audits_result_status_index');
        });

        Schema::table('audit_results', function (Blueprint $table) {
            $table->dropIndex('audit_results_tpu_created_index');
        });

        Schema::table('data_makams', function (Blueprint $table) {
            $table->enum('status_audit', [
                'match_full',
                'tahun_beda',
                'tidak_ada',
                'duplikat'
            ])->nullable();
        });
    }
};
