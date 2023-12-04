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

    $user->notify(new CustomVerifyEmailNotification);
    return response()->json([
        'user'=> new UserResource($user),
    ]);
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

     // Obtenemos el perfil del usuario
     $profile = $user->profile;

     // Si el usuario no tiene un perfil, creamos uno 
     if(!$profile) {
         $profile = $user->profile()->create([]);
     }

    $token = $user->createToken('token')->plainTextToken;

    $cookie = cookie('jwt', $token, 60 * 24);
    
    return response()->json([
        'user'=> new UserResource($user),
        'token' => $token,
    ])->withCookie($cookie);
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
    
        $frontendUrl = config('app.url');

        $resetLink = "{$frontendUrl}/reset-password/{$user->id}/{$token}";
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
    
    public function changePassword(Request $request) { 
        // Usamos sactum para tomar al usuario 
        $user = auth()->user();

        // Validar las reglas de validación para las contraseñas 
        $request->validate([
            'old_password'=> 'required',
            'password'=> 'required|confirmed|min:6'
        ]);

        // Verificamos si la contraseña anterior coincide
        if(!Hash::check($request->old_password, $user->password)) {
            return response()->json(['error'=>'La contraseña anterior es incorrecta',400]);
        }

        // Restablecer la contraseña con hash
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message'=>'Contraseña restablecida con éxito']);
    }
}
