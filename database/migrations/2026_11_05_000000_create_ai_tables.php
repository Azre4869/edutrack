<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel ai_run dan ai_insight untuk lapisan Analisis AI EduTrack.
     */
    public function up(): void
    {
        // 1. AI RUN
        Schema::create('ai_run', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_log')->nullable();
            $table->integer('id_semester');
            $table->string('model_version', 50)->default('1.0.0-kmeans-iforest');
            $table->json('parameter')->nullable();
            $table->json('metrik')->nullable();
            $table->enum('status', ['Running', 'Success', 'Failed'])->default('Success');
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->foreign('id_log', 'fk_ai_run_log')
                ->references('id_log')->on('analisis_log')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_semester', 'fk_ai_run_semester')
                ->references('id_semester')->on('semester')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        // 2. AI INSIGHT
        Schema::create('ai_insight', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_run');
            $table->integer('id_siswa');
            $table->integer('id_semester');
            $table->integer('cluster_id')->default(0);
            $table->string('cluster_label', 100);
            $table->decimal('cluster_distance', 8, 4)->nullable();
            $table->decimal('pca_x', 8, 4)->nullable();
            $table->decimal('pca_y', 8, 4)->nullable();
            $table->decimal('anomaly_score', 5, 4)->default(0.0);
            $table->boolean('is_anomaly')->default(false);
            $table->json('alasan')->nullable();
            $table->decimal('slope_nilai', 6, 3)->default(0.0);
            $table->decimal('prediksi_nilai', 5, 2)->default(0.0);
            $table->decimal('slope_kehadiran', 6, 3)->default(0.0);
            $table->decimal('prediksi_kehadiran', 5, 2)->default(0.0);
            $table->boolean('berpotensi_turun')->default(false);
            $table->boolean('data_cukup')->default(true);
            $table->timestamp('dihitung_pada')->useCurrent();

            $table->index(['id_siswa', 'id_semester'], 'idx_ai_insight_siswa_semester');

            $table->foreign('id_run', 'fk_ai_insight_run')
                ->references('id')->on('ai_run')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_siswa', 'fk_ai_insight_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_semester', 'fk_ai_insight_semester')
                ->references('id_semester')->on('semester')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_insight');
        Schema::dropIfExists('ai_run');
    }
};
