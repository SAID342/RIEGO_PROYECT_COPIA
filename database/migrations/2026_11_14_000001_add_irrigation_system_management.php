<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sistemas_riego', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('riego_activo')->default(false);
            $table->timestamps();
            $table->index(['parcela_id', 'nombre']);
        });

        Schema::table('riegos', function (Blueprint $table) {
            $table->foreignId('sistema_riego_id')->nullable()->constrained('sistemas_riego')->nullOnDelete();
            $table->index(['sistema_riego_id', 'fecha']);
        });

        Schema::table('medicions', function (Blueprint $table) {
            $table->foreignId('sistema_riego_id')->nullable()->constrained('sistemas_riego')->cascadeOnDelete();
            $table->string('sensor_id', 100)->nullable();
            $table->string('tipo', 40)->nullable();
            $table->decimal('valor', 12, 4)->nullable();
            $table->string('unidad', 24)->nullable();
            $table->timestamp('fecha_lectura')->nullable()->index();
            $table->boolean('es_prueba')->default(true);
            $table->index(['sistema_riego_id', 'tipo', 'fecha_lectura']);
        });

        Schema::table('configuracions', function (Blueprint $table) {
            $table->foreignId('sistema_riego_id')->nullable()->unique()->constrained('sistemas_riego')->cascadeOnDelete();
            $table->string('modo', 20)->default('manual');
            $table->string('unidad_nivel_agua', 24)->default('%');
            $table->decimal('humedad_suelo_minima', 5, 2)->nullable();
            $table->decimal('humedad_suelo_maxima', 5, 2)->nullable();
            $table->decimal('temperatura_relevante', 5, 2)->nullable();
            $table->decimal('nivel_agua_minimo', 12, 4)->nullable();
            $table->json('dias_permitidos')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->unsignedSmallInteger('duracion_maxima_min')->nullable();
            $table->unsignedSmallInteger('pausa_minima_min')->nullable();
        });

        Schema::table('comandos', function (Blueprint $table) {
            $table->foreignId('sistema_riego_id')->nullable()->constrained('sistemas_riego')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 30)->nullable();
            $table->string('resultado', 30)->default('no_conectado');
            $table->text('detalle')->nullable();
            $table->timestamp('solicitado_en')->useCurrent();
            $table->timestamp('procesado_en')->nullable();
            $table->index(['sistema_riego_id', 'solicitado_en']);
        });
    }

    public function down(): void
    {
        Schema::table('comandos', function (Blueprint $table) {
            $table->dropIndex(['sistema_riego_id', 'solicitado_en']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('sistema_riego_id');
            $table->dropColumn(['accion', 'resultado', 'detalle', 'solicitado_en', 'procesado_en']);
        });

        Schema::table('configuracions', function (Blueprint $table) {
            $table->dropForeign(['sistema_riego_id']);
            $table->dropUnique(['sistema_riego_id']);
            $table->dropColumn('sistema_riego_id');
            $table->dropColumn([
                'modo',
                'unidad_nivel_agua',
                'humedad_suelo_minima',
                'humedad_suelo_maxima',
                'temperatura_relevante',
                'nivel_agua_minimo',
                'dias_permitidos',
                'hora_inicio',
                'hora_fin',
                'duracion_maxima_min',
                'pausa_minima_min',
            ]);
        });

        Schema::table('medicions', function (Blueprint $table) {
            $table->dropIndex(['sistema_riego_id', 'tipo', 'fecha_lectura']);
            $table->dropConstrainedForeignId('sistema_riego_id');
            $table->dropColumn(['sensor_id', 'tipo', 'valor', 'unidad', 'fecha_lectura', 'es_prueba']);
        });

        Schema::table('riegos', function (Blueprint $table) {
            $table->dropIndex(['sistema_riego_id', 'fecha']);
            $table->dropConstrainedForeignId('sistema_riego_id');
        });

        Schema::dropIfExists('sistemas_riego');
    }
};
