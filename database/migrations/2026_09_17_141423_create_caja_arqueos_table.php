<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_arqueos', function (Blueprint $table) {
            $table->id();
            $table->integer('caja_turno_id');
            $table->integer('usuario_id');
            $table->dateTime('fecha_hora')->useCurrent();
            $table->decimal('total_billetes', 12, 2)->default(0);
            $table->decimal('total_monedas', 12, 2)->default(0);
            $table->decimal('efectivo_contado', 12, 2);
            $table->decimal('saldo_teorico', 12, 2);
            $table->decimal('diferencia', 12, 2);
            $table->string('tipo_arqueo')->default('PARCIAL'); // PARCIAL, CIERRE, CONTROL
            $table->text('observaciones')->nullable();
            $table->string('estado')->default('COMPLETADO');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_arqueos');
    }
};
