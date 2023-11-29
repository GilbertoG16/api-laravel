<?php

namespace App\Http\Controllers\Achievement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AchievementRule;
use App\Models\UserQrHistory;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\Achievement_Type;
use App\Http\Requests\Achievement\AchievementRequest;
use App\Http\Requests\Achievement\UpdateAchievementRequest;
use App\Models\QrInfoAssociation;
use App\Models\Trivia;
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
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $achievements = Achievement::select(
            'achievements.*',
            'achievement_types.tipo as achievement_type'
        )
            ->leftJoin('achievement_types', 'achievements.achievement_type_id', '=', 'achievement_types.id')
            ->addSelect(DB::raw('CASE 
        WHEN achievement_type_id = 1 THEN (SELECT name FROM trivias WHERE trivias.id = achievements.id_asociacion)
        WHEN achievement_type_id = 2 THEN (SELECT qr_identifier FROM qr_info_associations WHERE qr_info_associations.id = achievements.id_asociacion)
        ELSE ""
    END AS additional_info'))
            ->paginate($perPage);

        return response()->json($achievements);
    }


    public function create(AchievementRequest $request)
    {
        $data = $request->validated();
        // Crear la instancia de Achievement
        $achievement = new Achievement([
            'name' => $data['name'],
            'description' => $data['description'],
            'achievement_type_id' => $data['achievement_type_id'],
            'id_asociacion' => $data['id_asociacion'],
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
            if ($request->hasFile('photo_url')) {
                // Obtener el archivo de imagen del formulario
                $photo = $request->file('photo_url');
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
    public function assignAchievement($user, $achievementId)
    {
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
    public function update(UpdateAchievementRequest $request, $id)
    {
        $data = $request->validated();

        // Buscar el achievement por su ID
        $achievement = Achievement::find($id);

        // Verificar si el Achievement existe
        if (!$achievement) {
            return response()->json(['message' => 'No se encontró el Achievement correspondiente'], 404);
        }

        // Actualizar los campos del Achievement si se proporcionan en la solicitud
        if (isset($data['name'])) {
            $achievement->update([
                'name' => $data['name'],
            ]);
        }

        if (isset($data['description'])) {
            $achievement->update([
                'description' => $data['description'],
            ]);
        }

        if (isset($data['achievement_type_id'])) {
            $achievement->update([
                'achievement_type_id' => $data['achievement_type_id'],
            ]);
        }
        if (isset($data['id_asociacion'])) {
            $achievement->update([
                'id_asociacion' => $data['id_asociacion'],
            ]);
        }

        // Llamar al método existente para subir y asociar archivos
        $this->updateImageAchievement($achievement, $request);

        // Devolver una respuesta exitosa
        return response()->json(['message' => 'Operación exitosa', 'request' => $data, 'achievement' => $achievement], 200);
    }
    public function updateImageAchievement(Achievement $achievement, UpdateAchievementRequest $request)
    {
        // Asegurémonos de que el logro exista
        if ($achievement) {
            // Verificar si la solicitud tiene un archivo de imagen válido
            if ($request->hasFile('photo_url')) {
                // Obtener el archivo de imagen del formulario
                $photo = $request->file('photo_url');
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
    public function destroy($id)
    {
        // Buscar el logro por su ID
        $achievement = Achievement::find($id);

        // Verificar si el logro existe
        if (!$achievement) {
            return response()->json(['message' => 'No se encontró el logro correspondiente'], 404);
        }

        try {
            // Eliminar la imagen del almacenamiento (si existe)
            if ($achievement->photo_url) {
                $this->firebaseStorageService->deleteFileByUrl($achievement->photo_url);
            }

            // Eliminar el logro
            $achievement->delete();

            return response()->json(['message' => 'Logro eliminado exitosamente'], 200);
        } catch (\Exception $e) {
            // Manejar cualquier error que pueda ocurrir durante la eliminación
            return response()->json(['error' => 'Ocurrió un error al eliminar el logro: ' . $e->getMessage()], 500);
        }
    }

    public function getAchievementById($id)
    {
        $achievement = Achievement::select(
            'achievements.*',
            'achievement_types.tipo as achievement_type'
        )
            ->leftJoin('achievement_types', 'achievements.achievement_type_id', '=', 'achievement_types.id')
            ->addSelect(DB::raw('CASE 
            WHEN achievement_type_id = 1 THEN (SELECT name FROM trivias WHERE trivias.id = achievements.id_asociacion)
            WHEN achievement_type_id = 2 THEN (SELECT qr_identifier FROM qr_info_associations WHERE qr_info_associations.id = achievements.id_asociacion)
            ELSE ""
        END AS additional_info'))
            ->where('achievements.id', $id)
            ->first();

        if (!$achievement) {
            return response()->json(['message' => 'Logro no encontrado'], 404);
        }

        return response()->json($achievement);
    }
    public function getAchievementsType()
    {
        $types = Achievement_Type::all();
        return response()->json($types);
    }
    public function getAchievementAssociation()
    {
        $qrInfoAssociations = QrInfoAssociation::all();
        $trivias = Trivia::all();

        return response()->json([
            'qrInfoAssociations' => $qrInfoAssociations,
            'trivias' => $trivias,
        ]);
    }
}
