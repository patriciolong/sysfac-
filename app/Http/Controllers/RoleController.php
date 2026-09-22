<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RolePermiso;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::paginate(15);

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:roles',
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'required|in:activo,inactivo',
        ]);

        Role::create($request->only('nombre', 'descripcion', 'estado'));

        return redirect()->route('roles.index')->with('success', 'Rol creado correctamente.');
    }

    public function edit(Role $role)
    {
        return view('roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'descripcion' => 'nullable|string|max:255',
            'estado' => 'required|in:activo,inactivo',
        ]);

        $role->update($request->only('nombre', 'descripcion', 'estado'));

        return redirect()->route('roles.index')->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role)
    {
        if ($role->nombre === 'Administrador') {
            return redirect()->route('roles.index')->with('error', 'No se puede eliminar el rol Administrador.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')->with('error', 'No se puede eliminar el rol porque tiene usuarios asignados.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado correctamente.');
    }

    public function permisos(Role $role)
    {
        if ($role->nombre === 'Administrador') {
            return redirect()->route('roles.index')->with('error', 'El rol Administrador tiene acceso total, no requiere configurar permisos.');
        }

        $modulos = [
            'Facturación', 'Caja', 'Productos', 'Compras',
            'Kardex', 'Clientes', 'Proveedores', 'Reportes',
            'Configuración', 'Usuarios',
        ];

        $rolePermisos = $role->permisos->keyBy('modulo');

        return view('roles.permisos', compact('role', 'modulos', 'rolePermisos'));
    }

    public function guardarPermisos(Request $request, Role $role)
    {
        if ($role->nombre === 'Administrador') {
            return redirect()->route('roles.index')->with('error', 'No se pueden modificar los permisos del rol Administrador.');
        }

        $permisosData = $request->input('permisos', []);

        $role->permisos()->delete();

        foreach ($permisosData as $modulo => $nivel) {
            if ($nivel !== 'ninguno') {
                RolePermiso::create([
                    'role_id' => $role->id,
                    'modulo' => $modulo,
                    'nivel' => $nivel,
                ]);
            }
        }

        return redirect()->route('roles.index')->with('success', 'Permisos de rol guardados correctamente.');
    }
}
