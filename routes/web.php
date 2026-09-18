<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\CompraNotaCreditoController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UsuarioController;
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
    Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index')->middleware('permission:Facturación,lectura');
    Route::post('/facturacion/emitir', [FacturacionController::class, 'store'])->name('facturacion.emitir')->middleware('permission:Facturación,master');
    Route::post('/facturacion/reautorizar', [FacturacionController::class, 'reautorizar'])->name('facturacion.reautorizar')->middleware('permission:Facturación,master');
    Route::get('/facturacion/{id}', [FacturacionController::class, 'show'])->name('facturacion.show')->middleware('permission:Facturación,lectura');
    Route::get('/facturacion/{id}/pdf', [FacturacionController::class, 'pdf'])->name('facturacion.pdf')->middleware('permission:Facturación,lectura');

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
    Route::get('/productos/export', [ProductoController::class, 'export'])->name('productos.export')->middleware('permission:Productos,lectura');
    Route::get('/productos/{id}', [ProductoController::class, 'show'])->name('productos.show')->middleware('permission:Productos,lectura');
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index')->middleware('permission:Productos,lectura');
    Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store')->middleware('permission:Productos,master');
    Route::put('/productos/{id}', [ProductoController::class, 'update'])->name('productos.update')->middleware('permission:Productos,master');
    Route::post('/productos/{id}/toggle-estado', [ProductoController::class, 'toggleEstado'])->name('productos.toggleEstado')->middleware('permission:Productos,master');
    Route::delete('/productos/{id}', [ProductoController::class, 'destroy'])->name('productos.destroy')->middleware('permission:Productos,master');
    Route::post('/productos/categoria', [ProductoController::class, 'categoriaStore'])->name('productos.categoriaStore')->middleware('permission:Productos,master');

    // Compras & Entradas
    Route::get('/compras/export', [CompraController::class, 'export'])->name('compras.export')->middleware('permission:Compras,lectura');
    Route::get('/compras/notas-credito/export', [CompraNotaCreditoController::class, 'export'])->name('compras.notas-credito.export')->middleware('permission:Compras,lectura');
    Route::get('/compras/notas-credito/compra/{id}/detalles', [CompraNotaCreditoController::class, 'getCompraDetalles'])->name('compras.notas-credito.compra-detalles')->middleware('permission:Compras,lectura');
    Route::get('/compras/notas-credito/{id}', [CompraNotaCreditoController::class, 'show'])->name('compras.notas-credito.show')->middleware('permission:Compras,lectura');
    Route::get('/compras/notas-credito', [CompraNotaCreditoController::class, 'index'])->name('compras.notas-credito.index')->middleware('permission:Compras,lectura');
    Route::post('/compras/notas-credito', [CompraNotaCreditoController::class, 'store'])->name('compras.notas-credito.store')->middleware('permission:Compras,master');
    Route::post('/compras/notas-credito/{id}/anular', [CompraNotaCreditoController::class, 'anular'])->name('compras.notas-credito.anular')->middleware('permission:Compras,master');
    Route::get('/compras/{id}', [CompraController::class, 'show'])->name('compras.show')->middleware('permission:Compras,lectura');
    Route::get('/compras', [CompraController::class, 'index'])->name('compras.index')->middleware('permission:Compras,lectura');
    Route::post('/compras', [CompraController::class, 'store'])->name('compras.store')->middleware('permission:Compras,master');
    Route::post('/compras/{id}/anular', [CompraController::class, 'anular'])->name('compras.anular')->middleware('permission:Compras,master');
    Route::post('/compras/proveedor-rapido', [CompraController::class, 'storeProveedorRapido'])->name('compras.proveedorRapido')->middleware('permission:Compras,master');

    // Kardex
    Route::get('/kardex/export', [KardexController::class, 'export'])->name('kardex.export')->middleware('permission:Kardex,lectura');
    Route::get('/kardex/producto/{id}', [KardexController::class, 'productoKardex'])->name('kardex.producto')->middleware('permission:Kardex,lectura');
    Route::get('/kardex', [KardexController::class, 'index'])->name('kardex.index')->middleware('permission:Kardex,lectura');
    Route::post('/kardex/ajuste', [KardexController::class, 'storeAjuste'])->name('kardex.ajuste')->middleware('permission:Kardex,master');

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

    // Reportes & Analítica Completa
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index')->middleware('permission:Reportes,lectura');
    Route::get('/reportes/export/ventas', [ReporteController::class, 'exportVentas'])->name('reportes.export.ventas')->middleware('permission:Reportes,lectura');
    Route::get('/reportes/export/compras', [ReporteController::class, 'exportCompras'])->name('reportes.export.compras')->middleware('permission:Reportes,lectura');
    Route::get('/reportes/export/inventario', [ReporteController::class, 'exportInventario'])->name('reportes.export.inventario')->middleware('permission:Reportes,lectura');
    Route::get('/reportes/export/caja', [ReporteController::class, 'exportCaja'])->name('reportes.export.caja')->middleware('permission:Reportes,lectura');
    Route::get('/reportes/export/tributario', [ReporteController::class, 'exportTributario'])->name('reportes.export.tributario')->middleware('permission:Reportes,lectura');
});
