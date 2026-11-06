<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tindak_lanjut') && !Schema::hasColumn('tindak_lanjut', 'id_guru')) {
            Schema::table('tindak_lanjut', function (Blueprint $table) {
                $table->integer('id_guru')->nullable()->after('id_user');
                $table->foreign('id_guru', 'fk_tl_guru')
                    ->references('id_guru')->on('guru')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tindak_lanjut') && Schema::hasColumn('tindak_lanjut', 'id_guru')) {
            Schema::table('tindak_lanjut', function (Blueprint $table) {
                $table->dropForeign('fk_tl_guru');
                $table->dropColumn('id_guru');
            });
        }
    }
};
