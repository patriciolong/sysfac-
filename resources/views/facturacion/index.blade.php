@extends('layouts.app')

@section('title', 'Punto de Venta y Facturación SRI')

@section('content')

<!-- CONTROLES DE PESTAÑAS -->
<div class="data-card" style="padding: 0; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
    <div style="display: flex; gap: 1rem; padding: 0 1rem; background-color: #f8fafc; border-radius: 8px 8px 0 0;">
        <button id="tab-creacion" class="btn-tab active" onclick="switchMainTab('creacion')" style="padding: 1rem; border: none; background: transparent; font-weight: 600; color: var(--primary); border-bottom: 3px solid var(--primary); cursor: pointer;">
            <i class="fa-solid fa-cash-register"></i> Creación de Facturas
        </button>
        <button id="tab-historial" class="btn-tab" onclick="switchMainTab('historial')" style="padding: 1rem; border: none; background: transparent; font-weight: 600; color: #64748b; border-bottom: 3px solid transparent; cursor: pointer;">
            <i class="fa-solid fa-file-invoice"></i> Historial de Facturas
            @if($pendientes > 0)
                <span class="badge badge-warning" style="margin-left: 5px; font-size: 0.75rem;">{{ $pendientes }} PENDIENTES</span>
            @endif
        </button>
    </div>
</div>

<!-- CONTENIDO DE PESTAÑAS -->
<div id="content-creacion" style="display: block;">
    @include('facturacion._creacion')
</div>

<div id="content-historial" style="display: none;">
    @include('facturacion._historial')
</div>

@endsection

@push('scripts')
<script>
    function switchMainTab(tab) {
        document.getElementById('content-creacion').style.display = tab === 'creacion' ? 'block' : 'none';
        document.getElementById('content-historial').style.display = tab === 'historial' ? 'block' : 'none';
        
        const btnCreacion = document.getElementById('tab-creacion');
        const btnHistorial = document.getElementById('tab-historial');
        
        if (tab === 'creacion') {
            btnCreacion.style.color = 'var(--primary)';
            btnCreacion.style.borderBottom = '3px solid var(--primary)';
            btnHistorial.style.color = '#64748b';
            btnHistorial.style.borderBottom = '3px solid transparent';
        } else {
            btnHistorial.style.color = 'var(--primary)';
            btnHistorial.style.borderBottom = '3px solid var(--primary)';
            btnCreacion.style.color = '#64748b';
            btnCreacion.style.borderBottom = '3px solid transparent';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('tab') && urlParams.get('tab') === 'historial') {
            switchMainTab('historial');
        } else if (urlParams.has('page') || urlParams.has('numero') || urlParams.has('cliente') || urlParams.has('estado')) {
            switchMainTab('historial');
        }
    });
</script>
@endpush
