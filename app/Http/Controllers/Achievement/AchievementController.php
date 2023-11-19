<?php
namespace App\Http\Controllers\Achievement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AchievementRule;
use App\Models\UserQrHistory;
use App\Models\User;
use App\Models\UserAchievement;
use App\Http\Requests\Achievement\AchievementRequest;
use App\Models\Achievement;
use App\Services\FirebaseStorageService;
use Illuminate\Support\Facades\DB;
class AchievementController extends Controller
{
    protected $firebaseStorageService;
    public function __construct(FirebaseStorageService $firebaseStorageService)
    {
        $this->firebaseStorageService = $firebaseStorageService;
    }
    public function create(AchievementRequest $request)
    {
        $data = $request->validated();
    // Crear la instancia de Achievement
    $achievement = new Achievement([
        'id' => $data['id'],
        'name' => $data['name'],
        'description' => $data['description'],
    ]);
    $achievement->save();
    // Subir imágenes y asociarlas
    $this->uploadImageAchievement($achievement, $request);
        return response()->json(['message' => 'Operación exitosa', 'achievement' => $achievement], 200);
    }
    
    public function uploadImageAchievement(Achievement $achievement, AchievementRequest $request)
    {
        // Asegurémonos de que el logro exista
        if ($achievement) {
            // Verificar si la solicitud tiene un archivo de imagen válido
            if ($request->hasFile('image')) {
                // Obtener el archivo de imagen del formulario
                $photo = $request->file('image');
                // Verificar si el logro ya tiene una foto asociada
                if ($achievement->photo_url) {
                    // Llamar a la función para eliminar la foto anterior del bucket de Firebase
                    $this->firebaseStorageService->deleteFileByUrl($achievement->photo_url);
                    
                    // Actualizar la URL de la foto en la base de datos (o eliminarla si es necesario)
                    $achievement->update(['photo_url' => null]);
                }
                //Construir la ruta
                $storagePath = "logro_img/{$achievement->id}/" . time() . '.' . $photo->getClientOriginalExtension();
                // Subir la nueva foto de perfil
                $publicUrl = $this->firebaseStorageService->uploadFile($photo, $storagePath);
                // Actualizar el campo de foto de perfil en el modelo de logro con la ruta de almacenamiento
                $achievement->update(['photo_url' => $publicUrl]);
                // Devolvemos la respuesta :)
                return response()->json(['message' => 'Foto de perfil subida con éxito', 'public_url' => $publicUrl]);
            }
        } else {
            // El logro no se encontró
            return response()->json(['error' => 'Logro no encontrado'], 404);
        }
        // Si la solicitud no tiene un archivo de imagen válido, devolver una respuesta de error
        return response()->json(['error' => 'No se proporcionó una imagen válida'], 400);
    }
    public function assignAchievement($user, $achievementId) {
        $achievement = Achievement::find($achievementId);
    
        if (!$user || !$achievement) {
            return response()->json(['error' => 'Usuario o logro no encontrado'], 404);
        }
    
        $existingUserAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->first();
    
        if (!$existingUserAchievement) {
            $userAchievement = new UserAchievement([
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
                // Otros campos relacionados con los logros
            ]);
            $userAchievement->save();
            return response()->json(['message' => 'Logro asignado al usuario', 'userAchievement' => $userAchievement], 200);
        }
    
        return response()->json(['message' => 'El usuario ya tiene el logro'], 200);
    }
}
?>