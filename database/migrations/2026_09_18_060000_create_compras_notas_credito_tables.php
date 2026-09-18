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
        if (! Schema::hasTable('compras_notas_credito')) {
            Schema::create('compras_notas_credito', function (Blueprint $table) {
                $table->id();
                $table->integer('compra_id');
                $table->integer('proveedor_id');
                $table->integer('bodega_id');
                $table->integer('usuario_id')->default(1);
                $table->integer('movimiento_id')->nullable();
                $table->string('numero_nota_credito', 50);
                $table->string('autorizacion_sri', 49)->nullable();
                $table->date('fecha_emision');
                $table->string('motivo', 300);
                $table->enum('tipo_modificacion', ['DEVOLUCION_MERCADERIA', 'DESCUENTO_VALOR'])->default('DEVOLUCION_MERCADERIA');
                $table->decimal('subtotal_sin_impuestos', 12, 2)->default(0.00);
                $table->decimal('iva', 12, 2)->default(0.00);
                $table->decimal('total', 12, 2)->default(0.00);
                $table->text('observaciones')->nullable();
                $table->enum('estado', ['EMITIDA', 'ANULADA'])->default('EMITIDA');
                $table->timestamps();

                $table->index('compra_id');
                $table->index('proveedor_id');
                $table->index('bodega_id');
                $table->index('usuario_id');
                $table->index('movimiento_id');
            });
        }

        if (! Schema::hasTable('compras_notas_credito_detalles')) {
            Schema::create('compras_notas_credito_detalles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('nota_credito_id');
                $table->integer('producto_id');
                $table->decimal('cantidad', 12, 4);
                $table->decimal('costo_unitario', 12, 4);
                $table->decimal('costo_total', 12, 2);
                $table->decimal('tarifa_iva', 5, 2)->default(15.00);
                $table->decimal('iva_total', 12, 2)->default(0.00);
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('nota_credito_id')
                    ->references('id')
                    ->on('compras_notas_credito')
                    ->onDelete('cascade');

                $table->index('producto_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras_notas_credito_detalles');
        Schema::dropIfExists('compras_notas_credito');
    }
};
