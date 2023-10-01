<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\PhotoUpload; 

use Kreait\Laravel\Firebase\Facades\Firebase;

class UserController extends Controller
{
    public function index(){
        $users = User::all();
    }

public function uploadProfilePhoto(PhotoUpload $request)
{
    // Verificar si la solicitud tiene un archivo de imagen válido
    if ($request->hasFile('photo')) {
        // Obtener el archivo de imagen del formulario
        $photo = $request->file('photo');

        // Obtener el id del usuario autenticado
        $userId = auth()->id();

        // Generar un nombre único para la imagen
        $fileName = 'profile_' . $userId . '_' . time() . '.' . $photo->getClientOriginalExtension();

        // Crear la ruta para almacenar la imagen en Firebase Storage
        $storagePath = 'profile/' . $userId . '/' . $fileName;

        // Subir la imagen a Firebase Storage
        $firebaseStorage = Firebase::storage();
        $fileContents = file_get_contents($photo->getRealPath());
        $firebaseStorage->getBucket()->upload($fileContents, ['name' => $storagePath]);

        // Obtener la URL pública para el archivo subido utilizando Firebase Admin SDK
        $file = $firebaseStorage->getBucket()->object($storagePath);
        $publicUrl = $file->signedUrl(new \DateTime('+1 day'));

        // Obtener el perfil del usuario autenticado
        $profile = auth()->user()->profile;

        // Actualizar el campo de foto de perfil en el modelo de perfil con la ruta de almacenamiento
        $profile->update(['profile_picture' => $publicUrl]);

        // Puedes devolver la URL pública en la respuesta JSON
        return response()->json(['message' => 'Foto de perfil subida con éxito', 'public_url' => $publicUrl]);
    }

    // Si la solicitud no tiene un archivo de imagen válido, devolver una respuesta de error
    return response()->json(['error' => 'No se proporcionó una imagen válida'], 400);
}

}
