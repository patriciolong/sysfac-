@extends('layouts.app')

@section('title', 'Editar Rol')

@section('content')
<div class="page-header" style="margin-bottom: 1.5rem;">
    <h1 class="page-title" style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">Editar Rol: {{ $role->nombre }}</h1>
</div>

<div class="card" style="background: white; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 600px;">
    
    @if ($errors->any())
        <div class="alert alert-danger" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <ul style="margin: 0; padding-left: 1.5rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('roles.update', $role) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nombre *</label>
            <input type="text" name="nombre" value="{{ old('nombre', $role->nombre) }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Descripción</label>
            <input type="text" name="descripcion" value="{{ old('descripcion', $role->descripcion) }}" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Estado *</label>
            <select name="estado" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
                <option value="activo" {{ old('estado', $role->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                <option value="inactivo" {{ old('estado', $role->estado) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" style="background: #10b981; color: white; border: none; padding: 0.5rem 1.5rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">Actualizar Rol</button>
            <a href="{{ route('roles.index') }}" style="background: #f1f5f9; color: #475569; padding: 0.5rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600;">Cancelar</a>
        </div>
    </form>
</div>
@endsection
