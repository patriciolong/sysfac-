@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1 class="page-title" style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">Gestión de Usuarios</h1>
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary" style="background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none;">
        <i class="fa-solid fa-plus"></i> Nuevo Usuario
    </a>
</div>

@if(session('success'))
<div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
    {{ session('error') }}
</div>
@endif

<div class="card" style="background: white; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    
    <!-- Filtros -->
    <form action="{{ route('usuarios.index') }}" method="GET" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre, apellido o correo..." style="flex: 1; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
        
        <select name="role" style="padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
            <option value="">Todos los roles</option>
            @foreach($roles as $rol)
                <option value="{{ $rol->id }}" {{ request('role') == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
            @endforeach
        </select>

        <select name="estado" style="padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
            <option value="">Todos los estados</option>
            <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
            <option value="inactivo" {{ request('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
        </select>

        <button type="submit" class="btn btn-secondary" style="background: #64748b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer;">Filtrar</button>
        <a href="{{ route('usuarios.index') }}" class="btn btn-light" style="background: #f1f5f9; color: #475569; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none;">Limpiar</a>
    </form>

    <!-- Tabla -->
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                    <th style="padding: 1rem;">ID</th>
                    <th style="padding: 1rem;">Usuario</th>
                    <th style="padding: 1rem;">Correo</th>
                    <th style="padding: 1rem;">Rol</th>
                    <th style="padding: 1rem;">Estado</th>
                    <th style="padding: 1rem;">Último Acceso</th>
                    <th style="padding: 1rem; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $user)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 1rem;">{{ $user->id }}</td>
                    <td style="padding: 1rem;"><strong>{{ $user->name }} {{ $user->apellido }}</strong></td>
                    <td style="padding: 1rem; color: #64748b;">{{ $user->email }}</td>
                    <td style="padding: 1rem;">
                        <span style="background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem;">
                            {{ $user->role ? $user->role->nombre : 'Sin rol' }}
                        </span>
                    </td>
                    <td style="padding: 1rem;">
                        @if($user->estado == 'activo')
                            <span style="color: #10b981;"><i class="fa-solid fa-check-circle"></i> Activo</span>
                        @else
                            <span style="color: #ef4444;"><i class="fa-solid fa-xmark-circle"></i> Inactivo</span>
                        @endif
                    </td>
                    <td style="padding: 1rem; color: #64748b; font-size: 0.875rem;">
                        {{ $user->ultimo_acceso ? \Carbon\Carbon::parse($user->ultimo_acceso)->format('d/m/Y H:i') : 'Nunca' }}
                    </td>
                    <td style="padding: 1rem; text-align: right;">
                        <div style="display: inline-flex; gap: 0.5rem;">
                            <a href="{{ route('usuarios.permisos', $user) }}" title="Permisos" style="color: #6366f1;">
                                <i class="fa-solid fa-key"></i>
                            </a>
                            <a href="{{ route('usuarios.edit', $user) }}" title="Editar" style="color: #3b82f6;">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            @if($user->id !== 1 && $user->id !== auth()->id())
                                <form action="{{ route('usuarios.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Está seguro de eliminar este usuario?');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer;" title="Eliminar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding: 2rem; text-align: center; color: #64748b;">No se encontraron usuarios.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div style="margin-top: 1.5rem;">
        {{ $usuarios->links() }}
    </div>
</div>
@endsection
