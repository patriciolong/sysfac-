@extends('layouts.app')

@section('title', 'Apertura de Caja')

@section('content')
<div class="hero-banner">
    <div>
        <h1>Apertura de Caja: {{ $caja->nombre }}</h1>
        <p>Declare el desglose de efectivo para el saldo inicial.</p>
    </div>
    <a href="{{ route('caja.index') }}" class="btn-card-action btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
</div>

<form action="{{ route('caja.storeApertura', $caja->id) }}" method="POST">
    @csrf
    
    <div class="action-grid">
        <!-- Detalle de Billetes -->
        <div class="data-card">
            <h3 style="margin-bottom: 1rem;"><i class="fa-solid fa-money-bill"></i> Billetes</h3>
            @foreach([100, 50, 20, 10, 5, 1] as $denominacion)
            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                <label style="width: 80px;">$ {{ $denominacion }}</label>
                <input type="number" name="desgloses[{{$denominacion}}]" class="form-control calc-input" min="0" value="0" data-val="{{$denominacion}}">
                <span style="width: 100px; text-align:right" class="subtotal-lbl">$ 0.00</span>
            </div>
            @endforeach
        </div>

        <!-- Detalle de Monedas -->
        <div class="data-card">
            <h3 style="margin-bottom: 1rem;"><i class="fa-solid fa-coins"></i> Monedas</h3>
            @foreach([1, 0.50, 0.25, 0.10, 0.05, 0.01] as $denominacion)
            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                <label style="width: 80px;">$ {{ number_format($denominacion, 2) }}</label>
                <input type="number" name="desgloses[{{$denominacion}}]" class="form-control calc-input" min="0" value="0" data-val="{{$denominacion}}">
                <span style="width: 100px; text-align:right" class="subtotal-lbl">$ 0.00</span>
            </div>
            @endforeach
        </div>
        
        <!-- Resumen -->
        <div class="data-card" style="display:flex; flex-direction:column; justify-content:space-between">
            <div>
                <h3 style="margin-bottom: 1rem;">Resumen y Observaciones</h3>
                
                <div class="form-group">
                    <label class="form-label">Usuario</label>
                    <select name="usuario_id" class="form-control" required>
                        @foreach($usuarios as $user)
                        <option value="{{ $user->id }}">{{ $user->nombres }} {{ $user->apellidos }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3" placeholder="Detalles de la apertura..."></textarea>
                </div>
            </div>

            <div style="background: var(--primary-light); padding: 1.5rem; border-radius: var(--radius-md); text-align:center;">
                <div style="font-size: 0.9rem; font-weight: 700; color: var(--primary); text-transform:uppercase;">Total Saldo Inicial</div>
                <div id="total-saldo" style="font-size: 2.5rem; font-weight: 800; color: var(--primary);">$ 0.00</div>
                <input type="hidden" name="saldo_inicial" id="saldo_inicial_input" value="0">
            </div>
            
            <button type="submit" class="btn-card-action btn-primary btn-lg" style="margin-top: 1rem;">
                <i class="fa-solid fa-lock-open"></i> ABRIR CAJA
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    const inputs = document.querySelectorAll('.calc-input');
    const totalEl = document.getElementById('total-saldo');
    const inputTotal = document.getElementById('saldo_inicial_input');

    inputs.forEach(input => {
        input.addEventListener('input', function() {
            let total = 0;
            inputs.forEach(inp => {
                let qty = parseInt(inp.value) || 0;
                let val = parseFloat(inp.getAttribute('data-val'));
                let sub = qty * val;
                inp.nextElementSibling.textContent = '$ ' + sub.toFixed(2);
                total += sub;
            });
            totalEl.textContent = '$ ' + total.toFixed(2);
            inputTotal.value = total.toFixed(2);
        });
    });
</script>
@endpush
@endsection
