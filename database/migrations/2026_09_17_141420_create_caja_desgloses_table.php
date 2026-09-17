<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_desgloses', function (Blueprint $table) {
            $table->id();
            $table->integer('caja_turno_id');
            $table->integer('caja_arqueo_id')->nullable();
            $table->enum('tipo_operacion', ['APERTURA', 'ARQUEO', 'CIERRE']);
            $table->enum('tipo_moneda', ['BILLETE', 'MONEDA']);
            $table->decimal('denominacion', 8, 2);
            $table->integer('cantidad');
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_desgloses');
    }
};
