<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_makams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tpu_id');
            $table->foreignId('import_id');
            $table->enum('sumber', [
                'pusat',
                'cabang'
            ]);
            // data asli
            $table->string('nama')->nullable();
            $table->string('tanggal_raw')->nullable();
            // cleaned data
            $table->string('nama_clean')->nullable();
            $table->date('tanggal_clean')->nullable();
            // matching
            $table->string('id_match')->nullable();
            // audit
            $table->enum('status_audit', [
                'match_full',
                'tahun_beda',
                'tidak_ada',
                'duplikat'
            ])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_makams');
    }
};
