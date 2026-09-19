<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('inventario_bodegas')) {
            Schema::table('inventario_bodegas', function (Blueprint $table) {
                if (! Schema::hasColumn('inventario_bodegas', 'codigo')) {
                    $table->string('codigo', 30)->nullable()->unique()->after('id');
                }
                if (! Schema::hasColumn('inventario_bodegas', 'descripcion')) {
                    $table->text('descripcion')->nullable()->after('ubicacion');
                }
                if (! Schema::hasColumn('inventario_bodegas', 'responsable')) {
                    $table->string('responsable', 255)->nullable()->after('descripcion');
                }
                if (! Schema::hasColumn('inventario_bodegas', 'telefono')) {
                    $table->string('telefono', 50)->nullable()->after('responsable');
                }
                if (! Schema::hasColumn('inventario_bodegas', 'estado')) {
                    $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO')->after('telefono');
                }
                if (! Schema::hasColumn('inventario_bodegas', 'es_principal')) {
                    $table->boolean('es_principal')->default(false)->after('estado');
                }
                if (! Schema::hasColumn('inventario_bodegas', 'created_at')) {
                    $table->timestamps();
                }
            });

            // Asignar códigos iniciales y marcar principal si no los tienen
            $bodegas = DB::table('inventario_bodegas')->orderBy('id')->get();
            foreach ($bodegas as $idx => $b) {
                $updates = [];
                if (empty($b->codigo)) {
                    $updates['codigo'] = 'BOD-0'.($idx + 1);
                }
                if (empty($b->estado)) {
                    $updates['estado'] = 'ACTIVO';
                }
                if ($idx === 0 && ! $b->es_principal) {
                    $updates['es_principal'] = true;
                }
                if (empty($b->created_at)) {
                    $updates['created_at'] = now();
                    $updates['updated_at'] = now();
                }
                if (! empty($updates)) {
                    DB::table('inventario_bodegas')->where('id', $b->id)->update($updates);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inventario_bodegas')) {
            Schema::table('inventario_bodegas', function (Blueprint $table) {
                $colsToDrop = [];
                foreach (['codigo', 'descripcion', 'responsable', 'telefono', 'estado', 'es_principal', 'created_at', 'updated_at'] as $col) {
                    if (Schema::hasColumn('inventario_bodegas', $col)) {
                        $colsToDrop[] = $col;
                    }
                }
                if (! empty($colsToDrop)) {
                    $table->dropColumn($colsToDrop);
                }
            });
        }
    }
};
