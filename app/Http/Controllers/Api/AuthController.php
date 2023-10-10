<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Http\Requests\Register; 
use App\Http\Resources\UserResource; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Registered;
use App\Notifications\CustomVerifyEmailNotification;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    public function register(Register $request) 
    {
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
    Profile::create(['user_id'=>$user->id]);

    
    $token = $user->createToken('token')->plainTextToken;

    $cookie = cookie('jwt', $token, 60 * 24);


    $user->notify(new CustomVerifyEmailNotification);
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

    // Verificar si el correo electrónico está verificado
    if (!$user->hasVerifiedEmail()) {
        return response([
            'message' => 'Email not verified!'
        ], 401);
    }

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
    // Resetear contraseña
    
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }
    
        $token = app('auth.password.broker')->createToken($user);
    
        
        $resetLink = "Haz clic en el siguiente enlace para restablecer tu contraseña:\n\nhttp://tu-aplicacion.com/reset-password/$user->id/$token";

    
        // Envía el correo electrónico directamente sin usar la clase Mailable
        Mail::to($user->email)->send(new ResetPasswordMail($resetLink));
    
        return response()->json(['message' => 'Reset link sent to email']);
    }
    
    public function resetPassword(Request $request, $id, $token)
    {
        // Obtener el usuario mediante el ID
        $user = User::findOrFail($id);
    
        // Validar el token
        if (!Password::tokenExists($user, $token)) {
            return response()->json(['error' => 'Token no válido'], 400);
        }
    
        // Verificar las reglas de validación para la nueva contraseña
        $request->validate([
            'password' => 'required|confirmed|min:6',
        ]);
    
        // Restablecer la contraseña con hash
        $user->password = Hash::make($request->password);
        $user->save();
    
        // Eliminar el token utilizado
        Password::deleteToken($user);
    
        event(new PasswordReset($user));
    
        return response()->json(['message' => 'Contraseña restablecida con éxito']);
    }
    
    
        
}
