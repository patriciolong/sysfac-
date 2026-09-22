@extends('layouts.app')

@section('title', 'Caja')

@push('styles')
<style>
    .caja-tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 0;
    }
    .caja-tab {
        padding: 0.75rem 1.25rem;
        font-weight: 600;
        color: var(--text-muted);
        text-decoration: none;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
    }
    .caja-tab:hover {
        color: var(--primary);
    }
    .caja-tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }
    
    /* Layout utilities for forms */
    .caja-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    @media (max-width: 900px) { .caja-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem; padding:1rem; background:#d1fae5; color:#065f46; border-radius:8px;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:1rem; padding:1rem; background:#fee2e2; color:#b91c1c; border-radius:8px;">{{ session('error') }}</div>
@endif

<div class="hero-banner">
    <div>
        <h1>Módulo de Caja</h1>
        <p>Gestión de efectivo, aperturas, cierres e historial.</p>
    </div>
</div>

<div class="caja-tabs">
    <a href="{{ route('caja.index', ['tab' => 'apertura']) }}" class="caja-tab {{ $active_tab == 'apertura' ? 'active' : '' }}" {{ $turnoActual ? 'style=display:none;' : '' }}>
        <i class="fa-solid fa-key"></i> Apertura
    </a>
    <a href="{{ route('caja.index', ['tab' => 'control']) }}" class="caja-tab {{ $active_tab == 'control' ? 'active' : '' }}" {{ !$turnoActual ? 'style=display:none;' : '' }}>
        <i class="fa-solid fa-cash-register"></i> Control de Caja
    </a>
    <a href="{{ route('caja.index', ['tab' => 'historial']) }}" class="caja-tab {{ $active_tab == 'historial' ? 'active' : '' }}">
        <i class="fa-solid fa-list-ul"></i> Historial / Reportes
    </a>
    <a href="{{ route('caja.index', ['tab' => 'configuracion']) }}" class="caja-tab {{ $active_tab == 'configuracion' ? 'active' : '' }}">
        <i class="fa-solid fa-gears"></i> Configuración
    </a>
</div>

<!-- ================= APERTURA ================= -->
@if($active_tab == 'apertura')
    <form action="{{ route('caja.storeApertura', $caja->id) }}" method="POST">
        @csrf
        <div class="caja-grid">
            <div>
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
            </div>
            
            <div>
                <div class="data-card" style="height: 100%; display:flex; flex-direction:column; justify-content:space-between">
                    <div>
                        <h3 style="margin-bottom: 1rem;">Resumen de Apertura</h3>
                        
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
                    
                    <button type="submit" class="btn-card-action btn-primary btn-lg" style="margin-top: 1rem; width:100%">
                        <i class="fa-solid fa-lock-open"></i> ABRIR CAJA
                    </button>
                </div>
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
@endif

<!-- ================= CONTROL DE CAJA ================= -->
@if($active_tab == 'control' && $turnoActual)
    <div style="display:flex; gap:10px; margin-bottom: 1.5rem">
        <button class="btn-card-action btn-success" onclick="openModal('modalIngreso')"><i class="fa-solid fa-plus"></i> Ingreso Manual</button>
        <button class="btn-card-action btn-danger" onclick="openModal('modalEgreso')"><i class="fa-solid fa-minus"></i> Egreso Manual</button>
        <button class="btn-card-action btn-warning" style="color:black" onclick="document.getElementById('arqueo-section').scrollIntoView({behavior: 'smooth'})"><i class="fa-solid fa-scale-balanced"></i> Cerrar Caja</button>
    </div>

    <div class="metrics-grid">
        <div class="metric-card">
            <div>
                <div class="metric-label">Saldo Inicial</div>
                <div class="metric-val">$ {{ number_format($turnoActual->monto_inicial, 2) }}</div>
            </div>
            <div class="icon-box icon-sky"><i class="fa-solid fa-money-bill-wave"></i></div>
        </div>
        <div class="metric-card">
            <div>
                <div class="metric-label">Ventas Efectivo</div>
                <div class="metric-val text-success">$ {{ number_format($turnoActual->ventas_efectivo, 2) }}</div>
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
                    @foreach($turnoActual->movimientos()->latest('id')->take(10)->get() as $mov)
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

    <!-- SECCION ARQUEO -->
    <div id="arqueo-section" style="margin-top: 3rem; padding-top: 2rem; border-top: 2px dashed var(--border-color)">
        <h2 style="margin-bottom: 1.5rem"><i class="fa-solid fa-scale-balanced"></i> Arqueo y Cierre Definitivo</h2>
        <form action="{{ route('caja.storeArqueo', $turnoActual->id) }}" method="POST">
            @csrf
            <div class="caja-grid">
                <div>
                    <div class="data-card" style="margin-bottom: 1rem;">
                        <h3 style="margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                            <i class="fa-solid fa-money-bill"></i> Conteo de Billetes
                        </h3>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                            @foreach([100, 50, 20, 10, 5, 1] as $denominacion)
                            <div style="display:flex; align-items:center; gap:5px; background:#f8fafc; padding:0.5rem; border-radius:6px; border:1px solid #e2e8f0;">
                                <span style="font-weight:600; width:45px;">${{$denominacion}}</span>
                                <input type="number" name="desgloses[{{$denominacion}}]" class="form-control calc-input-arqueo billete-input" min="0" value="0" data-val="{{$denominacion}}" style="padding:0.25rem;">
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
                                <input type="number" name="desgloses[{{$denominacion}}]" class="form-control calc-input-arqueo moneda-input" min="0" value="0" data-val="{{$denominacion}}" style="padding:0.25rem;">
                                <span style="width:60px; text-align:right; color:#64748b" class="subtotal-lbl">$0.00</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div>
                    <div class="data-card" style="background:#f8fafc; border:2px solid #e2e8f0;">
                        <h3 style="margin-bottom: 1rem;"><i class="fa-solid fa-calculator"></i> Comparación</h3>
                        
                        <table style="width:100%; margin-bottom:1rem; font-size:0.9rem;">
                            <tr><td style="padding:4px 0">Saldo Inicial</td><td style="text-align:right; font-weight:600">${{number_format($turnoActual->monto_inicial, 2)}}</td></tr>
                            <tr><td style="padding:4px 0">Ventas en Efectivo</td><td style="text-align:right; font-weight:600">${{number_format($turnoActual->ventas_efectivo, 2)}}</td></tr>
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
                            <label class="form-label">Observaciones (Opcional)</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Justifique si hay diferencias..."></textarea>
                        </div>

                        <button type="submit" class="btn-card-action btn-danger" style="width:100%; padding:1rem; font-size:1.1rem; margin-top:10px;" onclick="return confirm('¿Confirma cerrar la caja con estos valores?')">
                            <i class="fa-solid fa-lock"></i> Procesar Cierre Definitivo
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Modales para Control -->
    <div class="modal-backdrop" id="modalIngreso">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Registrar Ingreso Manual</h2>
                <button class="btn-close" type="button" onclick="closeModal('modalIngreso')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('caja.storeMovimiento', $turnoActual->id) }}" method="POST">
                @csrf
                <input type="hidden" name="tipo" value="INGRESO">
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

    <div class="modal-backdrop" id="modalEgreso">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Registrar Egreso Manual</h2>
                <button class="btn-close" type="button" onclick="closeModal('modalEgreso')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('caja.storeMovimiento', $turnoActual->id) }}" method="POST">
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

    @push('scripts')
    <script>
        const arqueoInputs = document.querySelectorAll('.calc-input-arqueo');
        const lblFisico = document.getElementById('lbl-fisico');
        const lblDiferencia = document.getElementById('lbl-diferencia');
        const inputFisico = document.getElementById('input_fisico');
        const inputBilletes = document.getElementById('input_billetes');
        const inputMonedas = document.getElementById('input_monedas');
        
        const saldoTeorico = {{ $saldo_teorico }};

        function recalculateArqueo() {
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

        arqueoInputs.forEach(input => {
            input.addEventListener('input', recalculateArqueo);
        });
        
        recalculateArqueo();
    </script>
    @endpush
@endif

<!-- ================= HISTORIAL ================= -->
@if($active_tab == 'historial')
    <div class="data-card">
        <div class="data-card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h2><i class="fa-solid fa-list-ul"></i> Historial de Sesiones</h2>
        </div>

        <form method="GET" action="{{ route('caja.index') }}" style="display:flex; gap:10px; margin-bottom:1rem; align-items:end;">
            <input type="hidden" name="tab" value="historial">
            <div class="form-group" style="margin-bottom:0">
                <label>Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label>Fecha Fin</label>
                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label>Usuario</label>
                <select name="usuario_id" class="form-control">
                    <option value="">-- Todos --</option>
                    @foreach($usuarios as $usr)
                        <option value="{{ $usr->id }}" {{ request('usuario_id') == $usr->id ? 'selected' : '' }}>{{ $usr->nombres }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-card-action btn-primary"><i class="fa-solid fa-search"></i> Buscar</button>
            <a href="{{ route('caja.index', ['tab' => 'historial']) }}" class="btn-card-action btn-secondary"><i class="fa-solid fa-eraser"></i> Limpiar</a>
        </form>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th># Sesión</th>
                        <th>Fecha Apertura</th>
                        <th>Usuario</th>
                        <th>Saldo Inicial</th>
                        <th>Saldo Final Real</th>
                        <th>Diferencia</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historial as $h)
                    <tr>
                        <td><strong>{{ $h->id }}</strong></td>
                        <td>{{ $h->fecha_apertura }}</td>
                        <td>{{ $h->usuario->nombres }}</td>
                        <td>$ {{ number_format($h->monto_inicial, 2) }}</td>
                        <td>$ {{ number_format($h->efectivo_real, 2) }}</td>
                        <td>
                            @if($h->estado === 'CERRADA')
                                <span style="color: {{ $h->diferencia < 0 ? 'red' : ($h->diferencia > 0 ? 'blue' : 'green') }}">
                                    $ {{ number_format($h->diferencia, 2) }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $h->estado === 'ABIERTA' ? 'success' : ($h->estado === 'ANULADA' ? 'danger' : 'secondary') }}">{{ $h->estado }}</span>
                        </td>
                        <td>
                            @if($h->estado === 'CERRADA' || $h->estado === 'ABIERTA')
                                <button type="button" onclick="imprimirTicketCaja({{ $h->id }})" class="btn-card-action btn-sm btn-primary" title="Imprimir en Ticketera Térmica"><i class="fa-solid fa-receipt"></i> Ticket</button>
                                <a href="{{ route('caja.turnoTicketHtml', $h->id) }}" target="_blank" class="btn-card-action btn-sm btn-secondary" title="Ver Ticket Web 80mm"><i class="fa-solid fa-file-invoice"></i></a>
                                <a href="{{ route('caja.reporte', $h->id) }}" target="_blank" class="btn-card-action btn-sm btn-secondary" title="Descargar PDF Completo"><i class="fa-solid fa-file-pdf"></i></a>
                                <form action="{{ route('caja.anularTurno', $h->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de anular esta sesión de caja? Esta acción no se puede deshacer.');">
                                    @csrf
                                    <button type="submit" class="btn-card-action btn-sm btn-danger" title="Anular"><i class="fa-solid fa-ban"></i></button>
                                </form>
                            @else
                                <span style="color:gray;"><i class="fa-solid fa-ban"></i> Anulada</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1rem;">
            {{ $historial->appends(request()->query())->links() }}
        </div>
    </div>
@endif

<!-- ================= CONFIGURACIÓN ================= -->
@if($active_tab == 'configuracion')
    <div class="data-card">
        <div class="data-card-header">
            <h2><i class="fa-solid fa-gears"></i> Cajas del Sistema</h2>
            <button class="btn-card-action btn-primary" onclick="openModal('modalNuevaCaja')">
                <i class="fa-solid fa-plus"></i> Nueva Caja
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>Sucursal</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- solo ejemplo porque es la unica -->
                    <tr>
                        <td>{{ $caja->nombre }}</td>
                        <td>{{ $caja->codigo }}</td>
                        <td>{{ $caja->sucursal }}</td>
                        <td><span class="badge badge-success">{{ $caja->estado }}</span></td>
                        <td>
                            <button class="btn-card-action btn-sm btn-secondary" onclick="openModal('modalEditarCaja')"><i class="fa-solid fa-edit"></i> Editar</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Editar Caja -->
    <div class="modal-backdrop" id="modalEditarCaja">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Caja</h2>
                <button class="btn-close" type="button" onclick="closeModal('modalEditarCaja')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('caja.update', $caja->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="{{ $caja->nombre }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Código</label>
                    <input type="text" name="codigo" class="form-control" value="{{ $caja->codigo }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Sucursal</label>
                    <input type="text" name="sucursal" class="form-control" value="{{ $caja->sucursal }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Usuario Responsable</label>
                    <select name="usuario_id" class="form-control" required>
                        @foreach($usuarios as $usr)
                        <option value="{{ $usr->id }}" {{ $caja->usuario_id == $usr->id ? 'selected' : '' }}>{{ $usr->nombres }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-control" required>
                        <option value="ACTIVA" {{ $caja->estado == 'ACTIVA' ? 'selected' : '' }}>Activa</option>
                        <option value="INACTIVA" {{ $caja->estado == 'INACTIVA' ? 'selected' : '' }}>Inactiva</option>
                    </select>
                </div>
                <div class="form-group" style="text-align: right">
                    <button type="submit" class="btn-card-action btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Nueva Caja (solo ilustrativo en esta vista simplificada) -->
    <div class="modal-backdrop" id="modalNuevaCaja">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Crear Caja</h2>
                <button class="btn-close" type="button" onclick="closeModal('modalNuevaCaja')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('caja.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Código</label>
                    <input type="text" name="codigo" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Sucursal</label>
                    <input type="text" name="sucursal" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Usuario Responsable</label>
                    <select name="usuario_id" class="form-control" required>
                        <option value="1">Juan Carlos Pérez</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-control" required>
                        <option value="ACTIVA">Activa</option>
                        <option value="INACTIVA">Inactiva</option>
                    </select>
                </div>
                <div class="form-group" style="text-align: right">
                    <button type="submit" class="btn-card-action btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script>
    async function imprimirTicketCaja(turnoId) {
        try {
            const res = await fetch(`/caja/turno/${turnoId}/ticket-data`);
            const data = await res.json();
            if (!data.success) {
                alert('No se pudieron obtener los datos de la sesión.');
                return;
            }

            const printRes = await fetch('http://127.0.0.1:8080/print_cierre_caja', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data.ticket_data)
            });

            const printJson = await printRes.json();
            if (printJson.status === 'ok') {
                alert('✅ Comprobante de cierre enviado correctamente a la ticketera.');
            } else {
                alert('⚠️ Error de impresión: ' + (printJson.message || 'Desconocido'));
            }
        } catch (e) {
            console.warn('Print server offline:', e);
            if (confirm('⚠️ SysFact_Printer no está en ejecución en http://127.0.0.1:8080.\n\n¿Desea abrir el ticket web para imprimir desde el navegador?')) {
                window.open(`/caja/turno/${turnoId}/ticket-html`, '_blank', 'width=450,height=650');
            }
        }
    }
</script>
@endpush

@endsection
