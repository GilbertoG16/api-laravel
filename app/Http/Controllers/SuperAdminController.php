<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Http\Requests\RegisterSuperAdmin; 
use App\Http\Requests\UpdateUser;
use Illuminate\Support\Facades\Hash;


class SuperAdminController extends Controller
{
    public function index(Request $request){
        // Establecer el número predeterminado de elementos por página
        $perPage = $request->query('perPage', 10);

        // Obtener la lista de usuarios paginada
        $users = User::with('roles:name')->paginate($perPage);

        return response()->json($users);
    }

    public function register(RegisterSuperAdmin $request)
    {
        try {
            // Validar los datos de la solicitud
            $validatedData = $request->validated();

            // Encriptar la contraseña antes de guardarla
            $validatedData['password'] = Hash::make($validatedData['password']);

            // Crear el usuario
            $user = User::create($validatedData);

            // Asignar roles según la solicitud del superadmin
            $rolesToAssign = $request->input('roles', []);

            // Verificar si los roles existen
            $existingRoles = Role::pluck('name')->toArray();
            foreach ($rolesToAssign as $role) {
                if (!in_array($role, $existingRoles)) {
                    return response()->json(['message' => "El rol '$role' no existe."], 422);
                }
            }

            // Asignar roles al nuevo usuario
            $user->roles()->attach(Role::whereIn('name', $rolesToAssign)->pluck('id')->toArray());

            return response()->json(['message' => 'Registro exitoso😍']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error interno del servidor', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateUser $request, $id)
    {
        try {
            // Validar los datos de la solicitud
            $validatedData = $request->validated();
    
            // Buscar el usuario por ID
            $user = User::find($id);
    
            if (!$user) {
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }
    
            // Actualizar los datos del usuario
            $user->update($validatedData);
    
            // Actualizar roles directamente en el controlador
            $rolesToAssign = $request->input('roles', []);
            $existingRoles = Role::pluck('name')->toArray();
    
            foreach ($rolesToAssign as $role) {
                if (!in_array($role, $existingRoles)) {
                    return response()->json(['message' => "El rol '$role' no existe."], 422);
                }
            }
    
            $user->roles()->sync(Role::whereIn('name', $rolesToAssign)->pluck('id')->toArray());
    
            return response()->json(['message' => 'Usuario actualizado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error interno del servidor', 'error' => $e->getMessage()], 500);
        }
    }
    
    public function show($id){
        $user = User::with('roles:name')->find($id);

        if(!$user) {
            return response()->json(['message'=>'Usuario no encontrado'], 404);
        }
        return response()->json($user);
    }

    public function destroy($id){
        // Buscar el usuario por ID
        $user = User::find($id);

        if(!$user){
            return response()->json(['message'=>'Usuario no encontrado'],404);
        }

        // Eliminar el usuario
        $user->delete();

        return response()->json(['message'=>'Usuario eliminado exitosamente']);
    }

    public function getAllRoles(){
        $roles = Role::getAllRoles();

        return response()->json($roles);
    }

    /*-- Funciones para configurar el status de los usuarios --*/
    public function ban($id) {
        $user = User::find($id);
    
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
    
        // Verificar el estado actual del usuario y cambiarlo
        if ($user->status === 'active') {
            $user->update(['status' => 'banned']);
            $message = 'Usuario baneado exitosamente 😍';
        } elseif ($user->status === 'banned') {
            $user->update(['status' => 'active']);
            $message = 'Usuario desbaneado exitosamente 😎';
        }
    
        return response()->json(['message' => $message]);
    }
}
