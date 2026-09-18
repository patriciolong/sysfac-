@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
<div class="page-header" style="margin-bottom: 1.5rem;">
    <h1 class="page-title" style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">Editar Usuario: {{ $usuario->name }}</h1>
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

    <form action="{{ route('usuarios.update', $usuario) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nombre *</label>
            <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Apellido</label>
            <input type="text" name="apellido" value="{{ old('apellido', $usuario->apellido) }}" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Correo Electrónico *</label>
            <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Rol *</label>
            <select name="role_id" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
                <option value="">Seleccione un rol</option>
                @foreach($roles as $rol)
                    <option value="{{ $rol->id }}" {{ old('role_id', $usuario->role_id) == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Estado *</label>
            <select name="estado" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
                <option value="activo" {{ old('estado', $usuario->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                <option value="inactivo" {{ old('estado', $usuario->estado) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 1.5rem 0;">
        <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">Deje los campos de contraseña en blanco si no desea cambiarla.</p>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nueva Contraseña</label>
            <input type="password" name="password" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Confirmar Nueva Contraseña</label>
            <input type="password" name="password_confirmation" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;">
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" style="background: #10b981; color: white; border: none; padding: 0.5rem 1.5rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">Actualizar Usuario</button>
            <a href="{{ route('usuarios.index') }}" style="background: #f1f5f9; color: #475569; padding: 0.5rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600;">Cancelar</a>
        </div>
    </form>
</div>
@endsection
