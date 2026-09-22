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
        if (Schema::hasTable('compras_facturas')) {
            Schema::table('compras_facturas', function (Blueprint $table) {
                if (! Schema::hasColumn('compras_facturas', 'xml_path')) {
                    $table->string('xml_path', 500)->nullable()->after('observaciones');
                }
                if (! Schema::hasColumn('compras_facturas', 'xml_nombre_original')) {
                    $table->string('xml_nombre_original', 255)->nullable()->after('xml_path');
                }
            });
        }

        if (Schema::hasTable('compras_notas_credito')) {
            Schema::table('compras_notas_credito', function (Blueprint $table) {
                if (! Schema::hasColumn('compras_notas_credito', 'xml_path')) {
                    $table->string('xml_path', 500)->nullable()->after('observaciones');
                }
                if (! Schema::hasColumn('compras_notas_credito', 'xml_nombre_original')) {
                    $table->string('xml_nombre_original', 255)->nullable()->after('xml_path');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('compras_facturas')) {
            Schema::table('compras_facturas', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('compras_facturas', 'xml_path')) {
                    $cols[] = 'xml_path';
                }
                if (Schema::hasColumn('compras_facturas', 'xml_nombre_original')) {
                    $cols[] = 'xml_nombre_original';
                }
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('compras_notas_credito')) {
            Schema::table('compras_notas_credito', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('compras_notas_credito', 'xml_path')) {
                    $cols[] = 'xml_path';
                }
                if (Schema::hasColumn('compras_notas_credito', 'xml_nombre_original')) {
                    $cols[] = 'xml_nombre_original';
                }
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
