<?php

// app/Http/Controllers/VerifyEmailController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use App\Notifications\CustomVerifyEmailNotification;
class VerifyEmailController extends Controller
{
    public function verifyEmail(Request $request, $id, $hash)
    {
        // Buscamos el usuario
        $user = User::find($id);
    
        // Verificamos que el usuario existe
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado']);
        }
    
        // Verifica que el hash proporcionado coincida con el hash esperado
        if (sha1($user->getEmailForVerification()) !== $hash) {
            return response()->json(['message' => 'Link de verificación inválido']);
        }
    
        // Verifica si el correo ya está verificado
        if (!$user->hasVerifiedEmail()) {
            // Marca el correo como verificado
            $user->markEmailAsVerified();
            return response()->json(['message' => 'Email verificado']);
        }
    
        return response()->json(['message' => 'El email ya está verificado']);
    }
    
    public function resendEmail(Request $request)
    {
        if($request->user()->hasVerifiedEmail()) {
            return response(['message'=> 'Already verified']);
        }

        $request->user()->sendEmailVerificationNotification();

        if($request->wantsJson()){
            return response(['message'=>'EmailSent']);
        }

        return back()->with('resent', true);
    }
    
}


