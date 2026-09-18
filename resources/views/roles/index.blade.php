@extends('layouts.app')

@section('title', 'Roles')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1 class="page-title" style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">Gestión de Roles</h1>
    <a href="{{ route('roles.create') }}" class="btn btn-primary" style="background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none;">
        <i class="fa-solid fa-plus"></i> Nuevo Rol
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
    
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                    <th style="padding: 1rem;">ID</th>
                    <th style="padding: 1rem;">Nombre</th>
                    <th style="padding: 1rem;">Descripción</th>
                    <th style="padding: 1rem;">Estado</th>
                    <th style="padding: 1rem; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 1rem;">{{ $role->id }}</td>
                    <td style="padding: 1rem;"><strong>{{ $role->nombre }}</strong></td>
                    <td style="padding: 1rem; color: #64748b;">{{ $role->descripcion }}</td>
                    <td style="padding: 1rem;">
                        @if($role->estado == 'activo')
                            <span style="color: #10b981;"><i class="fa-solid fa-check-circle"></i> Activo</span>
                        @else
                            <span style="color: #ef4444;"><i class="fa-solid fa-xmark-circle"></i> Inactivo</span>
                        @endif
                    </td>
                    <td style="padding: 1rem; text-align: right;">
                        <div style="display: inline-flex; gap: 0.5rem;">
                            <a href="{{ route('roles.permisos', $role) }}" title="Permisos" style="color: #6366f1;">
                                <i class="fa-solid fa-key"></i>
                            </a>
                            <a href="{{ route('roles.edit', $role) }}" title="Editar" style="color: #3b82f6;">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            @if($role->nombre !== 'Administrador')
                                <form action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('¿Está seguro de eliminar este rol?');" style="margin:0;">
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
                    <td colspan="5" style="padding: 2rem; text-align: center; color: #64748b;">No se encontraron roles.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $roles->links() }}
    </div>
</div>
@endsection
