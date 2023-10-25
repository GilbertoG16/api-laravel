<?php

namespace App\Http\Controllers;

use App\Models\User;

use App\Models\Profile;
use Illuminate\Http\Request;
use App\Http\Requests\PhotoUpload; 
use App\Http\Requests\UpdateProfile; 
use App\Models\QrInfoAssociation;
use App\Models\UserQrHistory;

use App\Services\FirebaseStorageService;


class UserController extends Controller
{

    protected $firebaseStorageService;

    public function __construct(FirebaseStorageService $firebaseStorageService)
    {
        $this->firebaseStorageService = $firebaseStorageService;
    }

    public function index(){
        $users = User::all();
    }

    public function updateProfile(UpdateProfile $request){
        $user = auth()->user();

        // Obtenemos el perfil del usuario
        $profile = $user->profile;

        // Si el usuario no tiene un perfil, creamos uno 
        if(!$profile) {
            $profile = $user->profile()->create([]);
        }

        // Actualizar la información del perfil de usuario
        $profile->update($request->validated());

        // Devolvemos respuesta
        return response()->json(['message'=>'Perfil actualizado exitosamente 😘😘']);
    }

    public function uploadProfilePhoto(PhotoUpload $request)
    {
        // Obtener el usuario autenticado
        $user = auth()->user();
    
        // Verificar si el usuario está autenticado
        if ($user) {
            // Verificar si la solicitud tiene un archivo de imagen válido
            if ($request->hasFile('photo')) {
                // Obtener el archivo de imagen del formulario
                $photo = $request->file('photo');
    
                // Verificar si el usuario ya tiene una foto de perfil
                if ($user->profile->profile_picture) {
                    // Llamar a la función para eliminar la foto anterior del bucket de Firebase
                    $this->firebaseStorageService->deleteFileByUrl($user->profile->profile_picture);
                
                    // Actualizar la URL de la foto de perfil en la base de datos (o eliminarla si es necesario)
                    $user->profile->update(['profile_picture' => null]);
                }
                
                //Construir la ruta
                $storagePath = "profile/{$user->id}/" . time() . '.' . $photo->getClientOriginalExtension();
                // Subir la nueva foto de perfil
                $publicUrl = $this->firebaseStorageService->uploadFile($photo, $storagePath);
    
                // Actualizar el campo de foto de perfil en el modelo de perfil con la ruta de almacenamiento
                $user->profile->update(['profile_picture' => $publicUrl]);
    
                // Devolvemos la respuesta :)
                return response()->json(['message' => 'Foto de perfil subida con éxito', 'public_url' => $publicUrl]);
            }
    
            // Si la solicitud no tiene un archivo de imagen válido, devolver una respuesta de error
            return response()->json(['error' => 'No se proporcionó una imagen válida'], 400);
        }
    
        // Si el usuario no está autenticado, devolver una respuesta de error
        return response()->json(['error' => 'Usuario no autenticado'], 401);
    }

    public function relateUserWithQrAssociation($userId, $qrAssociation)
    {
        try {
            // Obtener el usuario
            $user = User::findOrFail($userId);
    
            // Relacionar el usuario con la asociación de QR
            $userQrHistory = new UserQrHistory([
                'qr_info_association_id' => $qrAssociation->id,
            ]);
    
            $user->userQrHistories()->save($userQrHistory);
    
            return response()->json(['message' => 'Usuario relacionado con la asociación de QR exitosamente 😎'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error al relacionar el usuario con la asociación de QR: ' . $th->getMessage(),
            ]);
        }
    }

    public function userProfile(Request $request) {
        $user = auth()->user();
        
        // Selecciona los campos de perfil que deseas incluir en la respuesta
        $profile = $user->profile;
    
        // Estructura la respuesta en un arreglo limpio
        $response = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $profile->name,
            'last_name' => $profile->last_name,
            'identification' => $profile->identification,
            'birth_date' => $profile->birth_date,
            'profile_picture' => $profile->profile_picture,
            'roles' => $user->roles->pluck('name'),
        ];
    
        return response()->json($response);
    }
    
    
    
    
}
