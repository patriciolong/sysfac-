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
        Schema::create('configuracion_empresas', function (Blueprint $table) {
            $table->id();
            $table->string('ruc', 13)->nullable();
            $table->string('razon_social', 300)->nullable();
            $table->string('nombre_comercial', 300)->nullable();
            $table->string('direccion_matriz', 300)->nullable();
            $table->string('regimen_rimpe')->nullable();
            $table->string('firma_ruta', 500)->nullable();
            $table->string('firma_clave', 255)->nullable();
            $table->integer('ambiente_sri')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_empresas');
    }
};
