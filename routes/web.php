<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ReporteController;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistema de Facturación Electrónica (SRI Ecuador)
|--------------------------------------------------------------------------
*/

Route::get('/', [DashboardController::class, 'index']);

// Facturación POS
Route::get('/facturacion', [FacturacionController::class, 'index']);
Route::post('/facturacion/emitir', [FacturacionController::class, 'store']);

// Clientes
Route::get('/clientes', [ClienteController::class, 'index']);
Route::post('/clientes', [ClienteController::class, 'store']);

// Productos & Stock
Route::get('/productos', [ProductoController::class, 'index']);
Route::post('/productos', [ProductoController::class, 'store']);

// Compras & Entradas
Route::get('/compras', [CompraController::class, 'index']);
Route::post('/compras', [CompraController::class, 'store']);

// Kardex
Route::get('/kardex', [KardexController::class, 'index']);
Route::post('/kardex/ajuste', [KardexController::class, 'storeAjuste']);

// Caja & Arqueos
Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
Route::post('/caja', [CajaController::class, 'store'])->name('caja.store');
Route::put('/caja/{id}', [CajaController::class, 'update'])->name('caja.update');
Route::delete('/caja/{id}', [CajaController::class, 'destroy'])->name('caja.destroy');
Route::post('/caja/{id}/apertura', [CajaController::class, 'storeApertura'])->name('caja.storeApertura');
Route::post('/caja/turno/{turno_id}/movimiento', [CajaController::class, 'storeMovimiento'])->name('caja.storeMovimiento');
Route::post('/caja/turno/{turno_id}/arqueo', [CajaController::class, 'storeArqueo'])->name('caja.storeArqueo');
Route::post('/caja/turno/{turno_id}/anular', [CajaController::class, 'anularTurno'])->name('caja.anularTurno');
Route::get('/caja/turno/{turno_id}/reporte', [CajaController::class, 'reporte'])->name('caja.reporte');

// Configuración SRI
Route::get('/configuracion', [ConfiguracionController::class, 'index']);
Route::post('/configuracion', [ConfiguracionController::class, 'update']);

// Reportes
Route::get('/reportes', [ReporteController::class, 'index']);
