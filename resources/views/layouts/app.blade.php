<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Facturación Electrónica') - Naturista</title>
    <!-- Google Fonts & Font Awesome Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Custom Modern CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    @stack('styles')
</head>
<body class="sidebar-layout">

    <!-- SIDEBAR -->
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-header">
            <a href="{{ url('/') }}" class="brand-logo">
                <i class="fa-solid fa-leaf" style="color: #10b981;"></i>
                <span class="brand-text">NATURISTA</span>
            </a>
            <button class="toggle-sidebar-btn" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ url('/') }}" class="sidebar-link {{ Request::is('/') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i> <span>Dashboard</span>
            </a>

            <div class="sidebar-section">Operaciones</div>
            @if(auth()->check() && auth()->user()->hasPermission('Facturación'))
                <a href="{{ url('/facturacion') }}" class="sidebar-link {{ Request::is('facturacion*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-invoice-dollar"></i> <span>Facturación</span>
                </a>
                <a href="{{ url('/notas-credito') }}" class="sidebar-link {{ Request::is('notas-credito*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-circle-minus"></i> <span>Notas de Crédito</span>
                </a>
            @else
                <a href="#" class="sidebar-link text-muted" onclick="alert('No tienes permisos para acceder a Facturación.'); return false;" style="opacity: 0.6;">
                    <i class="fa-solid fa-file-invoice-dollar"></i> <span>Facturación 🔒</span>
                </a>
                <a href="#" class="sidebar-link text-muted" onclick="alert('No tienes permisos para acceder a Notas de Crédito.'); return false;" style="opacity: 0.6;">
                    <i class="fa-solid fa-file-circle-minus"></i> <span>Notas de Crédito 🔒</span>
                </a>
            @endif
            
            @if(auth()->check() && auth()->user()->hasPermission('Caja'))
                <a href="{{ url('/caja') }}" class="sidebar-link {{ Request::is('caja*') ? 'active' : '' }}">
                    <i class="fa-solid fa-cash-register"></i> <span>Módulo Caja</span>
                </a>
            @else
                <a href="#" class="sidebar-link text-muted" onclick="alert('No tienes permisos para acceder a Caja.'); return false;" style="opacity: 0.6;">
                    <i class="fa-solid fa-cash-register"></i> <span>Módulo Caja 🔒</span>
                </a>
            @endif

            <div class="sidebar-section">Inventario</div>
            @if(auth()->check() && auth()->user()->hasPermission('Productos'))
                <a href="{{ url('/productos') }}" class="sidebar-link {{ Request::is('productos*') ? 'active' : '' }}">
                    <i class="fa-solid fa-boxes-stacked"></i> <span>Productos</span>
                </a>
            @else
                <a href="#" class="sidebar-link text-muted" onclick="alert('No tienes permisos para acceder a Productos.'); return false;" style="opacity: 0.6;">
                    <i class="fa-solid fa-boxes-stacked"></i> <span>Productos 🔒</span>
                </a>
            @endif

            @if(auth()->check() && auth()->user()->hasPermission('Compras'))
                <a href="{{ url('/compras') }}" class="sidebar-link {{ (Request::is('compras') || Request::is('compras/*')) && !Request::is('compras/notas-credito*') ? 'active' : '' }}">
                    <i class="fa-solid fa-cart-shopping"></i> <span>Compras</span>
                </a>
                <a href="{{ url('/compras/notas-credito') }}" class="sidebar-link {{ Request::is('compras/notas-credito*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-circle-minus"></i> <span>NC Compras</span>
                </a>
            @else
                <a href="#" class="sidebar-link text-muted" onclick="alert('No tienes permisos para acceder a Compras.'); return false;" style="opacity: 0.6;">
                    <i class="fa-solid fa-cart-shopping"></i> <span>Compras 🔒</span>
                </a>
            @endif

            @if(auth()->check() && auth()->user()->hasPermission('Kardex'))
                <a href="{{ url('/kardex') }}" class="sidebar-link {{ Request::is('kardex*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i> <span>Kardex</span>
                </a>
            @else
                <a href="#" class="sidebar-link text-muted" onclick="alert('No tienes permisos para acceder a Kardex.'); return false;" style="opacity: 0.6;">
                    <i class="fa-solid fa-chart-line"></i> <span>Kardex 🔒</span>
                </a>
            @endif

            <div class="sidebar-section">Directorio</div>
            @if(auth()->check() && auth()->user()->hasPermission('Clientes'))
                <a href="{{ url('/clientes') }}" class="sidebar-link {{ Request::is('clientes*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i> <span>Clientes</span>
                </a>
            @else
                <a href="#" class="sidebar-link text-muted" onclick="alert('No tienes permisos para acceder a Clientes.'); return false;" style="opacity: 0.6;">
                    <i class="fa-solid fa-users"></i> <span>Clientes 🔒</span>
                </a>
            @endif

            @if(auth()->check() && auth()->user()->hasPermission('Proveedores'))
                <a href="{{ url('/proveedores') }}" class="sidebar-link {{ Request::is('proveedores*') ? 'active' : '' }}">
                    <i class="fa-solid fa-truck-field"></i> <span>Proveedores</span>
                </a>
            @else
                <a href="#" class="sidebar-link text-muted" onclick="alert('No tienes permisos para acceder a Proveedores.'); return false;" style="opacity: 0.6;">
                    <i class="fa-solid fa-truck-field"></i> <span>Proveedores 🔒</span>
                </a>
            @endif

            <div class="sidebar-section">Sistema</div>
            @if(auth()->check() && auth()->user()->hasPermission('Reportes'))
                <a href="{{ url('/reportes') }}" class="sidebar-link {{ Request::is('reportes*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie"></i> <span>Reportes</span>
                </a>
            @else
                <a href="#" class="sidebar-link text-muted" onclick="alert('No tienes permisos para acceder a Reportes.'); return false;" style="opacity: 0.6;">
                    <i class="fa-solid fa-chart-pie"></i> <span>Reportes 🔒</span>
                </a>
            @endif

            @if(auth()->check() && auth()->user()->hasPermission('Configuración'))
                <a href="{{ url('/configuracion') }}" class="sidebar-link {{ Request::is('configuracion*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gears"></i> <span>Configuración</span>
                </a>
            @else
                <a href="#" class="sidebar-link text-muted" onclick="alert('No tienes permisos para acceder a Configuración.'); return false;" style="opacity: 0.6;">
                    <i class="fa-solid fa-gears"></i> <span>Configuración 🔒</span>
                </a>
            @endif

            @if(auth()->check() && auth()->user()->hasPermission('Usuarios', 'master'))
                <div class="sidebar-section">Seguridad</div>
                <a href="{{ url('/usuarios') }}" class="sidebar-link {{ Request::is('usuarios*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i> <span>Usuarios</span>
                </a>
                <a href="{{ url('/roles') }}" class="sidebar-link {{ Request::is('roles*') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-shield"></i> <span>Roles</span>
                </a>
            @endif
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content-area">
        <!-- TOP HEADER -->
        <header class="app-header">
            <div class="header-container">
                <div class="header-left">
                    <button class="mobile-toggle" onclick="toggleSidebar()">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>

                <div class="header-actions">
                    <div class="caja-status-pill">
                        @php
                            $turnoActivo = \App\Models\CajaTurno::getTurnoActivo();
                        @endphp
                        @if($turnoActivo)
                            <span class="dot-green"></span>
                            <span>Caja: <strong>ABIERTA</strong> ({{ $turnoActivo->caja ? $turnoActivo->caja->nombre : '001-001' }})</span>
                        @else
                            <span class="dot-red" style="background-color: #ef4444; width: 8px; height: 8px; border-radius: 50%; box-shadow: 0 0 8px #ef4444;"></span>
                            <span style="color: #ef4444;">Caja: <strong>CERRADA</strong></span>
                        @endif
                    </div>
                    <div class="user-pill" style="display: flex; gap: 15px; align-items: center;">
                        @if(auth()->check())
                            <div>
                                <i class="fa-solid fa-circle-user"></i>
                                <span><strong>{{ auth()->user()->name }} {{ auth()->user()->apellido }}</strong> ({{ auth()->user()->role ? auth()->user()->role->nombre : 'Sin Rol' }})</span>
                            </div>
                            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                                @csrf
                                <button type="submit" style="background: none; border: none; cursor: pointer; color: #ef4444;" title="Cerrar Sesión">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <main class="main-wrapper">
            @yield('content')
        </main>
    </div>

    <!-- SCRIPTS -->
    <script>
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
        }

        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('show');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('show');
            }
        }

        // Close on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal-backdrop.show').forEach(m => m.classList.remove('show'));
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
