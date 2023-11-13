<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\FcmToken;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;


use Exception; 
class FcmTokenController extends Controller
{
    protected $notification;

    public function __construct()
    {
        $this->notification = Firebase::messaging(); //Instancia de Firebase
    }
    
    public function storeFcmToken(Request $request)
    {

        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = auth()->user(); // Obtenemos el usuario autenticado

        $token = $request->input('fcm_token');

        // Guardamos el token en la base de datos para el usuario actual
        $user->fcmTokens()->updateOrCreate(['token'=>$token]);
    
        return response()->json(['message'=>'Token almacenado correctamente']);
    }
 
/* 
    public function sendNotification()
    {
        Este es un ejemplo, aquí en realidad tienes que tomar el token del dispositivo alojado en la nueva tabla creada 
        fcm_token, aparte hacemos una instancia de Firebase en el constructor
        $Fcmtoken = "token de dispositivo";
        $title = "Hola";
        $body = "Hola puerquito dormilón";
        $message = CloudMessage::fromArray([
            'token' => $Fcmtoken,
            'notification'=> [
                'title' => $title,
                'body'=> $body,
            ],
        ]);

        $this->notification->send($message);
        return response()->json(['message' => 'Notificación enviada con éxito']);
    }
    */

}
