@extends('layouts.app')

@section('title', 'Caja Activa')

@section('content')
<div class="hero-banner">
    <div>
        <h1>Caja Activa: {{ $turno->caja->nombre ?? 'Principal' }}</h1>
        <p>Sesión #{{ $turno->id }} | Apertura: {{ $turno->fecha_apertura }} | Responsable: {{ $turno->usuario->nombres }}</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn-card-action btn-success" onclick="openModal('modalIngreso')"><i class="fa-solid fa-plus"></i> Ingreso Manual</button>
        <button class="btn-card-action btn-danger" onclick="openModal('modalEgreso')"><i class="fa-solid fa-minus"></i> Egreso Manual</button>
        <a href="{{ route('caja.arqueo', $turno->id) }}" class="btn-card-action btn-warning" style="color:black"><i class="fa-solid fa-scale-balanced"></i> Arqueo / Cierre</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem; padding:1rem; background:#d1fae5; color:#065f46; border-radius:8px;">{{ session('success') }}</div>
@endif

<div class="metrics-grid">
    <div class="metric-card">
        <div>
            <div class="metric-label">Saldo Inicial</div>
            <div class="metric-val">$ {{ number_format($turno->monto_inicial, 2) }}</div>
        </div>
        <div class="icon-box icon-sky"><i class="fa-solid fa-money-bill-wave"></i></div>
    </div>
    <div class="metric-card">
        <div>
            <div class="metric-label">Ventas Efectivo</div>
            <div class="metric-val text-success">$ {{ number_format($turno->ventas_efectivo, 2) }}</div>
        </div>
        <div class="icon-box icon-green"><i class="fa-solid fa-cash-register"></i></div>
    </div>
    <div class="metric-card">
        <div>
            <div class="metric-label">Ingresos Manuales</div>
            <div class="metric-val">$ {{ number_format($ingresos_manuales, 2) }}</div>
        </div>
        <div class="icon-box icon-blue"><i class="fa-solid fa-arrow-down"></i></div>
    </div>
    <div class="metric-card">
        <div>
            <div class="metric-label">Egresos Manuales</div>
            <div class="metric-val text-danger">$ {{ number_format($egresos_manuales, 2) }}</div>
        </div>
        <div class="icon-box icon-rose"><i class="fa-solid fa-arrow-up"></i></div>
    </div>
    <div class="metric-card" style="background-color: var(--primary); color: white;">
        <div>
            <div class="metric-label" style="color:#bfdbfe;">Saldo Teórico Caja</div>
            <div class="metric-val" style="color:white;">$ {{ number_format($saldo_teorico, 2) }}</div>
        </div>
        <div class="icon-box" style="background: rgba(255,255,255,0.2); color:white;"><i class="fa-solid fa-vault"></i></div>
    </div>
</div>

<div class="data-card">
    <div class="data-card-header">
        <h2><i class="fa-solid fa-list"></i> Últimos Movimientos</h2>
    </div>
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th>Método Pago</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($turno->movimientos()->latest('id')->take(10)->get() as $mov)
                <tr>
                    <td>{{ date('H:i', strtotime($mov->fecha_hora)) }}</td>
                    <td>
                        <span class="badge badge-{{ $mov->tipo === 'INGRESO' ? 'success' : 'danger' }}">{{ $mov->tipo }}</span>
                    </td>
                    <td>{{ $mov->concepto }}</td>
                    <td>{{ $mov->metodoPago->nombre ?? 'Efectivo' }}</td>
                    <td style="font-weight:700; color: {{ $mov->tipo === 'INGRESO' ? 'var(--success)' : 'var(--danger)' }}">
                        {{ $mov->tipo === 'INGRESO' ? '+' : '-' }} $ {{ number_format($mov->monto, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ingreso -->
<div class="modal-backdrop" id="modalIngreso">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Registrar Ingreso Manual</h2>
            <button class="btn-close" onclick="closeModal('modalIngreso')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('caja.storeMovimiento', $turno->id) }}" method="POST">
            @csrf
            <input type="hidden" name="tipo" value="INGRESO">
            <!-- default efectivo for simplicity, since it's a cash module mostly -->
            <input type="hidden" name="metodo_pago_id" value="1"> 
            <div class="form-group">
                <label class="form-label">Categoría</label>
                <select name="categoria" class="form-control" required>
                    <option value="Reposición">Reposición de fondo</option>
                    <option value="Anticipo">Anticipo</option>
                    <option value="Otros">Otros</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Concepto</label>
                <input type="text" name="concepto" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Monto ($)</label>
                <input type="number" step="0.01" min="0.01" name="monto" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group" style="text-align: right">
                <button type="submit" class="btn-card-action btn-success">Guardar Ingreso</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Egreso -->
<div class="modal-backdrop" id="modalEgreso">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Registrar Egreso Manual</h2>
            <button class="btn-close" onclick="closeModal('modalEgreso')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('caja.storeMovimiento', $turno->id) }}" method="POST">
            @csrf
            <input type="hidden" name="tipo" value="EGRESO">
            <input type="hidden" name="metodo_pago_id" value="1"> 
            <div class="form-group">
                <label class="form-label">Categoría</label>
                <select name="categoria" class="form-control" required>
                    <option value="Pago Proveedor">Pago Proveedor</option>
                    <option value="Gastos">Gastos Operativos</option>
                    <option value="Retiro">Retiro de Efectivo</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Concepto</label>
                <input type="text" name="concepto" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Monto ($)</label>
                <input type="number" step="0.01" min="0.01" name="monto" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group" style="text-align: right">
                <button type="submit" class="btn-card-action btn-danger">Guardar Egreso</button>
            </div>
        </form>
    </div>
</div>
@endsection
