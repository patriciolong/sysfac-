<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id();
            $table->integer('caja_turno_id');
            $table->enum('tipo', ['INGRESO', 'EGRESO']);
            $table->string('categoria')->nullable();
            $table->string('concepto');
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 12, 2);
            $table->integer('metodo_pago_id')->nullable();
            $table->integer('usuario_id');
            $table->integer('venta_id')->nullable();
            $table->dateTime('fecha_hora')->useCurrent();
            $table->string('comprobante')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_movimientos');
    }
};
