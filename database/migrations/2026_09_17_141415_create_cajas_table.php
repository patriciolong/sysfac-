<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo')->unique()->nullable();
            $table->string('sucursal')->nullable();
            $table->integer('usuario_id')->nullable(); 
            $table->integer('punto_emision_id')->nullable();
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['ACTIVA', 'INACTIVA', 'CERRADA', 'BLOQUEADA'])->default('ACTIVA');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('caja_turnos', function (Blueprint $table) {
            $table->integer('caja_id')->nullable()->after('punto_emision_id');
        });
    }

    public function down(): void
    {
        Schema::table('caja_turnos', function (Blueprint $table) {
            $table->dropColumn('caja_id');
        });
        Schema::dropIfExists('cajas');
    }
};
