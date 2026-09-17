@extends('layouts.app')

@section('title', 'Arqueo de Caja')

@section('content')
<div class="hero-banner" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div>
        <h1>Arqueo y Cierre de Caja</h1>
        <p>Sesión #{{ $turno->id }} | Compare el saldo físico con el saldo del sistema.</p>
    </div>
    <a href="{{ route('caja.panel', $turno->id) }}" class="btn-card-action btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Volver al Panel
    </a>
</div>

@php
    $ingresos_manuales = $turno->movimientos()->where('tipo', 'INGRESO')->whereNull('venta_id')->sum('monto');
    $egresos_manuales = $turno->movimientos()->where('tipo', 'EGRESO')->whereNull('venta_id')->sum('monto');
    $saldo_teorico = $turno->monto_inicial + $turno->ventas_efectivo + $ingresos_manuales - $egresos_manuales;
@endphp

<form action="{{ route('caja.storeArqueo', $turno->id) }}" method="POST">
    @csrf
    
    <div class="pos-layout">
        <!-- Izquierda: Desglose Físico -->
        <div>
            <div class="data-card" style="margin-bottom: 1rem;">
                <h3 style="margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                    <i class="fa-solid fa-money-bill"></i> Conteo de Billetes
                </h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    @foreach([100, 50, 20, 10, 5, 1] as $denominacion)
                    <div style="display:flex; align-items:center; gap:5px; background:#f8fafc; padding:0.5rem; border-radius:6px; border:1px solid #e2e8f0;">
                        <span style="font-weight:600; width:45px;">${{$denominacion}}</span>
                        <input type="number" name="desgloses[{{$denominacion}}]" class="form-control calc-input billete-input" min="0" value="0" data-val="{{$denominacion}}" style="padding:0.25rem;">
                        <span style="width:60px; text-align:right; color:#64748b" class="subtotal-lbl">$0.00</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="data-card">
                <h3 style="margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                    <i class="fa-solid fa-coins"></i> Conteo de Monedas
                </h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    @foreach([1, 0.50, 0.25, 0.10, 0.05, 0.01] as $denominacion)
                    <div style="display:flex; align-items:center; gap:5px; background:#f8fafc; padding:0.5rem; border-radius:6px; border:1px solid #e2e8f0;">
                        <span style="font-weight:600; width:45px;">${{number_format($denominacion,2)}}</span>
                        <input type="number" name="desgloses[{{$denominacion}}]" class="form-control calc-input moneda-input" min="0" value="0" data-val="{{$denominacion}}" style="padding:0.25rem;">
                        <span style="width:60px; text-align:right; color:#64748b" class="subtotal-lbl">$0.00</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Derecha: Resumen -->
        <div>
            <div class="data-card" style="background:#f8fafc; border:2px solid #e2e8f0;">
                <h3 style="margin-bottom: 1rem;"><i class="fa-solid fa-calculator"></i> Comparación</h3>
                
                <table style="width:100%; margin-bottom:1rem; font-size:0.9rem;">
                    <tr><td style="padding:4px 0">Saldo Inicial</td><td style="text-align:right; font-weight:600">${{number_format($turno->monto_inicial, 2)}}</td></tr>
                    <tr><td style="padding:4px 0">Ventas en Efectivo</td><td style="text-align:right; font-weight:600">${{number_format($turno->ventas_efectivo, 2)}}</td></tr>
                    <tr><td style="padding:4px 0">Ingresos Manuales</td><td style="text-align:right; font-weight:600">${{number_format($ingresos_manuales, 2)}}</td></tr>
                    <tr><td style="padding:4px 0; border-bottom:1px solid #cbd5e1">Egresos Manuales</td><td style="text-align:right; font-weight:600; border-bottom:1px solid #cbd5e1">${{number_format($egresos_manuales, 2)}}</td></tr>
                    
                    <tr><td style="padding:8px 0; font-size:1.1rem; color:var(--primary); font-weight:800">SALDO TEÓRICO</td><td style="text-align:right; font-size:1.1rem; color:var(--primary); font-weight:800" id="lbl-teorico">${{number_format($saldo_teorico, 2)}}</td></tr>
                    
                    <tr><td style="padding:4px 0; margin-top:10px">Efectivo Físico Contado</td><td style="text-align:right; font-weight:600; font-size:1.1rem" id="lbl-fisico">$0.00</td></tr>
                    
                    <tr>
                        <td style="padding:8px 0; font-size:1.2rem; font-weight:800">DIFERENCIA</td>
                        <td style="text-align:right; font-size:1.2rem; font-weight:800" id="lbl-diferencia">$0.00</td>
                    </tr>
                </table>

                <input type="hidden" name="saldo_teorico" value="{{ $saldo_teorico }}">
                <input type="hidden" name="efectivo_contado" id="input_fisico" value="0">
                <input type="hidden" name="total_billetes" id="input_billetes" value="0">
                <input type="hidden" name="total_monedas" id="input_monedas" value="0">

                <div class="form-group">
                    <label class="form-label">Tipo de Acción</label>
                    <select name="tipo_arqueo" class="form-control" style="background-color: var(--primary-light); border-color:var(--primary); color:var(--primary); font-weight:bold;">
                        <option value="PARCIAL">Arqueo de Control (Mantener Abierta)</option>
                        <option value="CIERRE">Cierre Definitivo de Caja</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Observaciones (Opcional)</label>
                    <textarea name="observaciones" class="form-control" rows="2" placeholder="Justifique si hay diferencias..."></textarea>
                </div>

                <button type="submit" class="btn-card-action btn-primary" style="width:100%; padding:0.8rem; font-size:1rem; margin-top:10px;" onclick="return confirm('¿Confirma guardar este arqueo?')">
                    <i class="fa-solid fa-save"></i> Guardar Arqueo
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    const inputs = document.querySelectorAll('.calc-input');
    const lblFisico = document.getElementById('lbl-fisico');
    const lblDiferencia = document.getElementById('lbl-diferencia');
    const inputFisico = document.getElementById('input_fisico');
    const inputBilletes = document.getElementById('input_billetes');
    const inputMonedas = document.getElementById('input_monedas');
    
    const saldoTeorico = {{ $saldo_teorico }};

    function recalculate() {
        let tb = 0;
        document.querySelectorAll('.billete-input').forEach(inp => {
            let q = parseInt(inp.value) || 0;
            let v = parseFloat(inp.getAttribute('data-val'));
            let sub = q * v;
            inp.nextElementSibling.textContent = '$' + sub.toFixed(2);
            tb += sub;
        });

        let tm = 0;
        document.querySelectorAll('.moneda-input').forEach(inp => {
            let q = parseInt(inp.value) || 0;
            let v = parseFloat(inp.getAttribute('data-val'));
            let sub = q * v;
            inp.nextElementSibling.textContent = '$' + sub.toFixed(2);
            tm += sub;
        });

        let total = tb + tm;
        let diff = total - saldoTeorico;

        lblFisico.textContent = '$' + total.toFixed(2);
        inputFisico.value = total.toFixed(2);
        inputBilletes.value = tb.toFixed(2);
        inputMonedas.value = tm.toFixed(2);
        
        lblDiferencia.textContent = (diff > 0 ? '+' : '') + '$' + diff.toFixed(2);
        if (diff === 0) {
            lblDiferencia.style.color = 'var(--success)';
        } else if (diff < 0) {
            lblDiferencia.style.color = 'var(--danger)';
        } else {
            lblDiferencia.style.color = 'var(--warning)';
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', recalculate);
    });
    
    recalculate();
</script>
@endpush
@endsection
