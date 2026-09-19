<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\UserPermiso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->filled('buscar')) {
            $busqueda = $request->buscar;
            $query->where(function ($q) use ($busqueda) {
                $q->where('name', 'like', "%{$busqueda}%")
                    ->orWhere('apellido', 'like', "%{$busqueda}%")
                    ->orWhere('email', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role_id', $request->role);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $usuarios = $query->paginate(15)->withQueryString();
        $roles = Role::where('estado', 'activo')->get();

        return view('usuarios.index', compact('usuarios', 'roles'));
    }

    public function create()
    {
        $roles = Role::where('estado', 'activo')->get();

        return view('usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'estado' => 'required|in:activo,inactivo',
        ]);

        User::create([
            'name' => $request->name,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'estado' => $request->estado,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        $roles = Role::where('estado', 'activo')->get();

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($usuario->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'estado' => 'required|in:activo,inactivo',
        ]);

        $data = [
            'name' => $request->name,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'estado' => $request->estado,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === 1) {
            return redirect()->route('usuarios.index')->with('error', 'No se puede eliminar al administrador principal.');
        }

        if ($usuario->id === auth()->id()) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }

    public function permisos(User $usuario)
    {
        if ($usuario->id === 1) {
            return redirect()->route('usuarios.index')->with('error', 'El administrador principal tiene acceso total, no requiere configurar permisos.');
        }

        $modulos = [
            'Facturación', 'Caja', 'Productos', 'Compras',
            'Kardex', 'Clientes', 'Proveedores', 'Reportes',
            'Configuración', 'Usuarios',
        ];

        // Obtener permisos del rol
        $rolePermisos = $usuario->role ? $usuario->role->permisos->keyBy('modulo') : collect();

        // Obtener permisos específicos del usuario
        $userPermisos = $usuario->permisos->keyBy('modulo');

        return view('usuarios.permisos', compact('usuario', 'modulos', 'rolePermisos', 'userPermisos'));
    }

    public function guardarPermisos(Request $request, User $usuario)
    {
        if ($usuario->id === 1) {
            return redirect()->route('usuarios.index')->with('error', 'No se pueden modificar los permisos del administrador principal.');
        }

        $permisosData = $request->input('permisos', []);

        // Borrar todos los permisos específicos actuales
        $usuario->permisos()->delete();

        // Guardar los nuevos (solo si no es heredado)
        foreach ($permisosData as $modulo => $nivel) {
            if ($nivel !== 'heredado') {
                UserPermiso::create([
                    'user_id' => $usuario->id,
                    'modulo' => $modulo,
                    'nivel' => $nivel,
                ]);
            }
        }

        return redirect()->route('usuarios.index')->with('success', 'Permisos guardados correctamente.');
    }
}
