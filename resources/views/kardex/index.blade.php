@extends('layouts.app')

@section('title', 'Kardex de Inventario')

@section('content')

<!-- KARDEX HEADER -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
        <div>
            <h1 style="margin-bottom: 0.15rem;"><i class="fa-solid fa-chart-line text-primary"></i> Kardex de Movimientos de Inventario</h1>
            <p style="margin: 0; font-size: 0.85rem;">Consulte la trazabilidad completa de entradas por compras y salidas por ventas o ajustes.</p>
        </div>
        <button class="btn-card-action btn-warning btn-sm" onclick="openModal('modalAjusteKardex')">
            <i class="fa-solid fa-sliders"></i> Registrar Ajuste de Stock
        </button>
    </div>
</div>

<!-- FILTERS CARD -->
<div class="data-card" style="padding: 0.85rem 1.25rem; margin-bottom: 1.15rem;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; align-items: end;">
        <div>
            <label class="form-label" style="font-size: 0.775rem;">Filtrar por Bodega</label>
            <select class="form-control">
                @foreach($bodegas as $bod)
                <option value="{{ $bod }}">{{ $bod }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size: 0.775rem;">Buscar Producto</label>
            <input type="text" class="form-control" placeholder="Nombre o código...">
        </div>
        <div>
            <label class="form-label" style="font-size: 0.775rem;">Fecha Inicio</label>
            <input type="date" class="form-control" value="{{ date('Y-m-01') }}">
        </div>
        <div>
            <button class="btn-card-action btn-primary" style="width: 100%; height: 38px;">
                <i class="fa-solid fa-filter"></i> Aplicar Filtros
            </button>
        </div>
    </div>
</div>

<!-- KARDEX MOVEMENTS TABLE -->
<div class="data-card">
    <div class="data-card-header">
        <h2><i class="fa-solid fa-list-check text-primary"></i> Historial de Movimientos (`vw_inventario_kardex`)</h2>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Fecha & Hora</th>
                    <th>Bodega</th>
                    <th>Concepto</th>
                    <th>Referencia</th>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th style="text-align: right;">Variación</th>
                    <th style="text-align: right;">Stock Final</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimientos as $mov)
                <tr>
                    <td><strong>{{ $mov['fecha_movimiento'] }}</strong></td>
                    <td>{{ $mov['bodega'] }}</td>
                    <td><strong>{{ $mov['concepto_movimiento'] }}</strong></td>
                    <td style="font-family: monospace; font-size: 0.8rem;">{{ $mov['referencia'] }}</td>
                    <td>{{ $mov['producto'] }} <small style="color: #64748b;">({{ $mov['codigo_producto'] }})</small></td>
                    <td>
                        @if($mov['tipo'] == 'INGRESO')
                            <span class="badge badge-success"><i class="fa-solid fa-arrow-down"></i> INGRESO</span>
                        @else
                            <span class="badge badge-danger"><i class="fa-solid fa-arrow-up"></i> EGRESO</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: 700; color: {{ $mov['variacion_stock'] > 0 ? 'var(--success)' : 'var(--danger)' }};">
                        {{ $mov['variacion_stock'] > 0 ? '+'.$mov['variacion_stock'] : $mov['variacion_stock'] }}
                    </td>
                    <td style="text-align: right; font-weight: 700;">{{ $mov['stock_resultante'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL AJUSTE DE STOCK -->
<div class="modal-backdrop" id="modalAjusteKardex">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-sliders text-warning"></i> Registrar Ajuste Manual de Inventario</h2>
            <button class="btn-close" onclick="closeModal('modalAjusteKardex')">&times;</button>
        </div>
        <form action="{{ url('/kardex/ajuste') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Producto a Ajustar</label>
                <select name="producto_id" class="form-control">
                    @foreach($productos as $p)
                    <option value="{{ $p->id }}">{{ $p->nombre }} (Stock Actual: {{ $p->stock_total }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo de Movimiento</label>
                <select name="tipo_movimiento_id" class="form-control">
                    <option value="5">AJUSTE INGRESO (Sumar Stock)</option>
                    <option value="6">AJUSTE EGRESO (Restar Stock por Daño / Pérdida)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Cantidad a Ajustar</label>
                <input type="number" step="0.01" name="cantidad" class="form-control" placeholder="Ej: 5" required>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones / Motivo del Ajuste</label>
                <textarea name="observaciones" class="form-control" rows="2" placeholder="Ej: Conteo físico mensual o caducidad"></textarea>
            </div>
            <div style="display: flex; gap: 0.65rem; margin-top: 1.25rem;">
                <button type="submit" class="btn-card-action btn-warning" style="flex: 1;">Guardar Ajuste</button>
                <button type="button" class="btn-card-action btn-secondary" onclick="closeModal('modalAjusteKardex')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

@endsection
