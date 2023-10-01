<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Role;
use App\Http\Requests\Register; 
use App\Http\Resources\UserResource; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{

   
    public function register(Register $request) {
    // Obtenemos los datos validados del registro
    $validatedData = $request->all();
    

    // Encriptamos la contraseña antes de guardarla en la base de datos / Lo hice pero en esta versión no es necesario, lo hace solo.
    $validatedData['password'] = Hash::make($validatedData['password']);

    // Creamos al usuario con los datos ya validados
    $user = User::create($validatedData);

    // Asigna automáticamente el rol "Cliente" al usuario
    $clienteRole = Role::where('name','cliente')->first();
    if($clienteRole) {
        $user->roles()->attach($clienteRole->id);
    }
    // Creamos el perfil del usuario 
    /*-- En proceso--*/

    $token = $user->createToken('token')->plainTextToken;

    $cookie = cookie('jwt', $token, 60 * 24);

    return response()->json([
        'user'=> new UserResource($user),
    ])->withCookie($cookie);
}

public function login(Request $request) {
    // Decirle a la request que solo acepte email y password para autenticación
    if (!Auth::attempt($request->only('email', 'password'))) {
        return response([
            'message' => 'Invalid credentials!'
        ], 401);
    }

    $user = Auth::user();

    $token = $user->createToken('token')->plainTextToken;

    $cookie = cookie('jwt', $token, 60 * 24);
    
    return response()->json([
        'user'=> new UserResource($user),
        'token' => $token,
    ])->withCookie($cookie);
}



    public function userProfile(Request $request) {
        $user = Auth::user();
        $roles = $user->roles()->pluck('name'); // Obtener los nombres de los roles
    
        return response()->json([
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
    
        $cookie = cookie()->forget('jwt');
    
        return response()->json([
            'message'=> 'Logged out successfully!'
        ])->withCookie($cookie);
    }    
}
