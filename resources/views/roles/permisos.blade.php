@extends('layouts.app')

@section('title', 'Permisos de Rol')

@section('content')
<div class="page-header" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 class="page-title" style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">Permisos: {{ $role->nombre }}</h1>
    </div>
    <a href="{{ route('roles.index') }}" class="btn btn-light" style="background: #f1f5f9; color: #475569; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none;">Volver</a>
</div>

<div class="card" style="background: white; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    
    <form action="{{ route('roles.permisos.guardar', $role) }}" method="POST">
        @csrf
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.5rem;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                        <th style="padding: 1rem;">Módulo</th>
                        <th style="padding: 1rem; text-align: center;">Sin acceso</th>
                        <th style="padding: 1rem; text-align: center;">Solo lectura</th>
                        <th style="padding: 1rem; text-align: center;">Master</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($modulos as $modulo)
                        @php
                            $nivel = isset($rolePermisos[$modulo]) ? $rolePermisos[$modulo]->nivel : 'ninguno';
                        @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 1rem; font-weight: 500; color: #334155;">{{ $modulo }}</td>
                            <td style="padding: 1rem; text-align: center;">
                                <input type="radio" name="permisos[{{ $modulo }}]" value="ninguno" {{ $nivel === 'ninguno' ? 'checked' : '' }}>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <input type="radio" name="permisos[{{ $modulo }}]" value="lectura" {{ $nivel === 'lectura' ? 'checked' : '' }}>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <input type="radio" name="permisos[{{ $modulo }}]" value="master" {{ $nivel === 'master' ? 'checked' : '' }}>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" style="background: #10b981; color: white; border: none; padding: 0.5rem 1.5rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600;" onsubmit="return confirm('¿Guardar los cambios en los permisos?');">Guardar Permisos</button>
            <a href="{{ route('roles.index') }}" style="background: #f1f5f9; color: #475569; padding: 0.5rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600;">Cancelar</a>
        </div>
    </form>
</div>
@endsection
