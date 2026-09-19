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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });

        Schema::create('role_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('modulo');
            $table->enum('nivel', ['master', 'lectura'])->default('lectura');
            $table->timestamps();

            $table->unique(['role_id', 'modulo']);
        });

        Schema::create('user_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('modulo');
            $table->enum('nivel', ['master', 'lectura', 'ninguno'])->default('ninguno');
            $table->timestamps();

            $table->unique(['user_id', 'modulo']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('apellido')->nullable()->after('name');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo')->after('remember_token');
            $table->timestamp('ultimo_acceso')->nullable()->after('estado');
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->onDelete('set null');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['apellido', 'estado', 'ultimo_acceso', 'role_id', 'deleted_at']);
        });

        Schema::dropIfExists('user_permisos');
        Schema::dropIfExists('role_permisos');
        Schema::dropIfExists('roles');
    }
};
