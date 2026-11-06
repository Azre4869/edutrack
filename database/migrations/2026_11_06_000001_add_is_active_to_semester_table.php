<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('semester') && !Schema::hasColumn('semester', 'is_active')) {
            Schema::table('semester', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('tahun_ajaran');
            });

            // Tandai semester genap/terbaru sebagai aktif jika belum ada yang aktif
            $latest = DB::table('semester')->orderBy('id_semester', 'desc')->first();
            if ($latest) {
                DB::table('semester')->where('id_semester', $latest->id_semester)->update(['is_active' => true]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('semester') && Schema::hasColumn('semester', 'is_active')) {
            Schema::table('semester', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
