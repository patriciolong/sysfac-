<?php

use App\Http\Controllers\CajaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistema de Facturación Electrónica (SRI Ecuador)
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    
    Route::get('/', [DashboardController::class, 'index']);

    // Usuarios y Roles (Módulo especial Usuarios)
    Route::middleware(['permission:Usuarios,master'])->group(function () {
        Route::get('/usuarios/{usuario}/permisos', [UsuarioController::class, 'permisos'])->name('usuarios.permisos');
        Route::post('/usuarios/{usuario}/permisos', [UsuarioController::class, 'guardarPermisos'])->name('usuarios.permisos.guardar');
        Route::resource('usuarios', UsuarioController::class);
        
        Route::get('/roles/{role}/permisos', [RoleController::class, 'permisos'])->name('roles.permisos');
        Route::post('/roles/{role}/permisos', [RoleController::class, 'guardarPermisos'])->name('roles.permisos.guardar');
        Route::resource('roles', RoleController::class);
    });

    // Facturación POS
    Route::get('/facturacion', [FacturacionController::class, 'index'])->middleware('permission:Facturación,lectura');
    Route::post('/facturacion/emitir', [FacturacionController::class, 'store'])->middleware('permission:Facturación,master');

    // Clientes
    Route::get('/clientes/export', [ClienteController::class, 'export'])->name('clientes.export')->middleware('permission:Clientes,lectura');
    Route::resource('clientes', ClienteController::class)->only(['index', 'show'])->middleware('permission:Clientes,lectura');
    Route::resource('clientes', ClienteController::class)->except(['index', 'show'])->middleware('permission:Clientes,master');

    // Proveedores
    Route::get('/proveedores/export', [ProveedorController::class, 'export'])->name('proveedores.export')->middleware('permission:Proveedores,lectura');
    Route::post('/proveedores/{proveedor}/export-pdf', [ProveedorController::class, 'exportPdf'])->name('proveedores.exportPdf')->middleware('permission:Proveedores,lectura');
    Route::resource('proveedores', ProveedorController::class)->only(['index', 'show'])->middleware('permission:Proveedores,lectura');
    Route::resource('proveedores', ProveedorController::class)->except(['index', 'show'])->middleware('permission:Proveedores,master');

    // Productos & Stock
    Route::get('/productos', [ProductoController::class, 'index'])->middleware('permission:Productos,lectura');
    Route::post('/productos', [ProductoController::class, 'store'])->middleware('permission:Productos,master');

    // Compras & Entradas
    Route::get('/compras', [CompraController::class, 'index'])->middleware('permission:Compras,lectura');
    Route::post('/compras', [CompraController::class, 'store'])->middleware('permission:Compras,master');

    // Kardex
    Route::get('/kardex', [KardexController::class, 'index'])->middleware('permission:Kardex,lectura');
    Route::post('/kardex/ajuste', [KardexController::class, 'storeAjuste'])->middleware('permission:Kardex,master');

    // Caja & Arqueos
    Route::get('/caja', [CajaController::class, 'index'])->name('caja.index')->middleware('permission:Caja,lectura');
    Route::get('/caja/turno/{turno_id}/reporte', [CajaController::class, 'reporte'])->name('caja.reporte')->middleware('permission:Caja,lectura');
    
    Route::middleware(['permission:Caja,master'])->group(function () {
        Route::post('/caja', [CajaController::class, 'store'])->name('caja.store');
        Route::put('/caja/{id}', [CajaController::class, 'update'])->name('caja.update');
        Route::delete('/caja/{id}', [CajaController::class, 'destroy'])->name('caja.destroy');
        Route::post('/caja/{id}/apertura', [CajaController::class, 'storeApertura'])->name('caja.storeApertura');
        Route::post('/caja/turno/{turno_id}/movimiento', [CajaController::class, 'storeMovimiento'])->name('caja.storeMovimiento');
        Route::post('/caja/turno/{turno_id}/arqueo', [CajaController::class, 'storeArqueo'])->name('caja.storeArqueo');
        Route::post('/caja/turno/{turno_id}/anular', [CajaController::class, 'anularTurno'])->name('caja.anularTurno');
    });

    // Configuración SRI
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->middleware('permission:Configuración,lectura');
    Route::post('/configuracion', [ConfiguracionController::class, 'update'])->middleware('permission:Configuración,master');

    // Reportes
    Route::get('/reportes', [ReporteController::class, 'index'])->middleware('permission:Reportes,lectura');
});
