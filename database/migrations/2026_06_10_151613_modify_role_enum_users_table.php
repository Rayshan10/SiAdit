<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-specific: lewati saat driver lain (mis. sqlite pada saat test)
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM(
                'admin',
                'kepala_tpu',
                'kepala_uptd'
            ) NOT NULL DEFAULT 'kepala_tpu'
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM(
                'admin',
                'user'
            ) NOT NULL DEFAULT 'user'
        ");
    }
};