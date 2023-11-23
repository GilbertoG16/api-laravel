<?php

namespace App\Http\Controllers\Learning;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Learning\LearningInfoRequest;
use App\Http\Requests\Learning\LearningInfoUpdateRequest;
use App\Http\Resources\LearningInfoResourceOne; 
use App\Http\Resources\UniversitySiteResource;  
use App\Http\Resources\LearningInfoPaginateResource;  
use App\Models\LearningInfo;
use App\Models\Trivia;
use App\Models\Location;
use App\Models\QrInfoAssociation;
use App\Models\UserQrHistory;
use App\Models\TextAudio;
use App\Models\Category;
use App\Models\Image;
use App\Models\Video;
use App\Models\User;

use App\Models\Achievement;
use App\Models\UserAchievement;


use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Learning\FileUploadController;
use App\Http\Controllers\Learning\QrAssociationController;

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\UserController;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;



class LearningInfoController extends Controller
{
    protected $fileUploadController;
    protected $qrAssociationController;
    protected $userController;
    protected $appointmentController;
    public function __construct(FileUploadController $fileUploadController, QrAssociationController $qrAssociationController, UserController $userController,AppointmentController $appointmentController)
    {
        $this->fileUploadController = $fileUploadController;
        $this->qrAssociationController = $qrAssociationController;
        $this->userController = $userController;
        $this->appointmentController = $appointmentController;
    }
    //Vista con paginación para web
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $categoryId = $request->get('category_id');
    
        $learningInfo = LearningInfo::with('images')
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->whereHas('category', function ($subquery) use ($categoryId) {
                    $subquery->where('id', $categoryId);
                });
            })
            ->paginate($perPage);
    
        return LearningInfoPaginateResource::collection($learningInfo);
    }
    
    // Vista única del Learning-puede guiarse por identificador de qr o por id para poder hacer doble la vista web
    public function findByQrIdentifier($qrIdentifier)
    {
        try {
            // Buscar la instancia de la tabla por qr_identifier
            $qrAssociation = QrInfoAssociation::where('qr_identifier', $qrIdentifier)->first();
            
            if (!$qrAssociation) {
                // Si no se encuentra por qr_identifier, intenta buscar por id en el mismo LearningInfo
                $learningInfo = LearningInfo::find($qrIdentifier);
    
                if (!$learningInfo) {
                    
                    return response()->json(['message' => 'No se encontró la asociación de QR'], 404);
                }

                // Carga las relaciones en el LearningInfo
                $learningInfo->load('images', 'videos', 'text_audios', 'qrInfoAssociations', 'trivias');
                
            } else {
                // Obtiene el LearningInfo correspondiente a la asociación de QR
                $learningInfo = $qrAssociation->learningInfo;
                
                if (!$learningInfo) {
                    return response()->json(['message' => 'No se encontró el LearningInfo correspondiente'], 404);
                }
            
                // Carga las relaciones en el LearningInfo

                $learningInfo->load('images', 'videos', 'text_audios', 'qrInfoAssociations', 'trivias', 'category');

            }
            
            // Relaciona el usuario con la asociación de QR si hay un usuario autenticado
            $user = auth('sanctum')->user();
            
            if ($user && $qrAssociation && $qrAssociation->qr_identifier) {
                $this->userController->relateUserWithQrAssociation($user->id, $qrAssociation);
                // Verificamos si el usuario tiene permisos o si se requiere permisos para estar en este sitio
                $locationId = $qrAssociation->location_id;

                // Llama a la función hasPermission
                $hasPermission = $this->appointmentController->hasPermission($user, $locationId);
                
            }
            return new LearningInfoResourceOne($learningInfo);
        } catch (\Throwable $th) {
                return response()->json([
                'success' => false,
                'message' => 'Error al buscar la asociación ' . $th->getMessage(),
            ]);
        }
    }
    
    // Creación de un Learning/Algunas cosas se dividen en otros controladores como por ejemplo los files 
    public function create(LearningInfoRequest $request)
    {
        $data = $request->validated();

        // Crear la instancia de LearningInfo
        $learningInfo = LearningInfo::create([
            'name'=>$data['name'],
            'description'=>$data['description'],
            'category_id'=>$data['category_id'],
        ]);

        // Subir archivitos y asociarlos, lo hacemos con otro método para poder reutilizarlo y no saturar este controlador 😎
        $this->fileUploadController->uploadFiles($data,$learningInfo);

        // Crear asociaciones QR después dde haber subido los archivos, también en otro método (intento seguir buenas prácticas)
        $this->qrAssociationController->createQrAssociations($data['qr_associations'], $learningInfo);

        // Devolvemos el JSON por cierto cuando estoy programando esto es Viernes 13 de octubre 
        $learningInfo->load('images','videos','text_audios','qrInfoAssociations');

        return response()->json(['message'=>'Operación exitosa','learning_info'=> $learningInfo], 200);
    }

    // Traer solamente los Learnings cuyos nombres de categoría son Sitios de la Universidad
    public function universitySites(Request $request)
    {
        $categories = Category::where('name', 'Sitios de la Universidad')->get();
    
        $learningInfo = LearningInfo::with(['images', 'category'])
            ->whereIn('category_id', $categories->pluck('id'))
            ->get();
    
        // Comprobar si las imágenes se cargan correctamente
        $learningInfo->each(function ($learning) {
            $learning->load('images');
        });
    
        // Filtramos para solo traer una imagen
        $learningInfo->each(function ($learning) {
            $learning->images = $learning->images->take(1);
        });
    
        // Comprobar si no se encontraron LearningInfo
        if ($learningInfo->isEmpty()) {
            return response()->json(['message' => 'No se encontraron LearningInfo relacionados con Sitios de la Universidad'], 404);
        }
    
        return UniversitySiteResource::collection($learningInfo);
    }

    // Update de los Learning Infos
    public function update(LearningInfoUpdateRequest $request, $id)
    {
        $data = $request->validated();

        // Busca el learningInfo por su ID
        $learningInfo = LearningInfo::find($id);

        // Verificamos si el LearningInfo existe
        if(!$learningInfo) {
            return response()->json(['message'=>'No se encontró el LearningInfo correspondiente'],404);
        }

        // Actualizamos los campos del LearningInfo si se proporcionan en la solicitud
        if(isset($data['name'])) {
            $learningInfo->update([
                'name' => $data['name'],
            ]);
        }

        if(isset($data['description'])) {
            $learningInfo->update([
                'description' => $data['description'],
            ]);
        }

        if(isset($data['category_id'])) {
            $learningInfo->update([
                'category_id' => $data['category_id'],
            ]);
        }

        // Llama al método existente para subir y asociar archivos
        $this->fileUploadController->updateFiles($data, $learningInfo);

        // Llamamos al método existente para actualizar 
        $this->qrAssociationController->updateQrAssociations($data['qr_associations'], $learningInfo);
        // Devovlemos una respuesta con éxito
        return response()->json(['message'=> 'Operación exitosa', 'learning_info'=> $learningInfo], 200);

    }

    // Eliminación de Learning-Info :) 
    public function destroy($id)
    {
        $learningInfo = LearningInfo::find($id);

        if(!$learningInfo) {
            return response()->json(['Message'=>'No se ha encontrado el Learning'],404);
        }
        // Llamamos al que construye la ruta 
        $this->fileUploadController->deleteFolder($learningInfo);

        // Obtener todas las asociaciones de QrInfoAssociation
         $qrInfoAssociations = $learningInfo->qrInfoAssociations;
        
         // Obtener la trivia 
         $trivia = Trivia::where('learning_info_id', $id)->first();
         
         if($trivia) {
            $trivia->delete();
        }

        // Llamamos al método delete (Debería de eliminarse en cascada con el ELOQUENT)
        
         // Eliminar en cascada todas las relaciones asociadas a QrInfoAssociation
         foreach ($qrInfoAssociations as $association) {
             $association->userQrHistories()->delete();
             $association->delete();
         }
        // Eliminar las relaciones en cascada 
         $learningInfo->qrInfoAssociations()->delete();
         $learningInfo->videos()->delete();
         $learningInfo->images()->delete();
         $learningInfo->text_audios()->delete();
         $learningInfo->events()->delete();
         

         // Luego, eliminar el LearningInfo principal
         $learningInfo->delete();

        return response()->json(['Message'=>'Se ha eliminado correctamente 😒'],200);    
    }

    public function getQrInfoAssociations(Request $request)
    {
        $query = QrInfoAssociation::with(['location', 'learningInfo', 'userQrHistories']);
    
        // Aplicar filtros según los parámetros de la solicitud
        if ($request->has('has_trivia')) {
            $query->has('learningInfo.trivias');
        }
    
        if ($request->has('user_has_seen')) {
            $query->has('userQrHistories');
        }
    
        $qrInfoAssociations = $query->get();
    
        // Contar el total de QR y los QR vistos por el usuario
        $totalQr = QrInfoAssociation::count();
        $userSeenQr = $qrInfoAssociations->filter(function ($qrInfoAssociation) {
            return $qrInfoAssociation->userQrHistories->isNotEmpty();
        })->count();
    
        // Mapear los datos según tus requisitos
        $mappedData = $qrInfoAssociations->map(function ($qrInfoAssociation) {
            return $this->mapQrInfoAssociation($qrInfoAssociation);
        });
    
        return response()->json(['data' => $mappedData, 'total_qr' => $totalQr, 'user_seen_qr' => $userSeenQr]);
    }
    
    private function mapQrInfoAssociation($qrInfoAssociation)
    {
        return [
            'latitude' => $qrInfoAssociation->latitude,
            'longitude' => $qrInfoAssociation->longitude,
            'qr_identifier' => $qrInfoAssociation->qr_identifier,
            'location_id' => $qrInfoAssociation->location_id,
            'learning_info_id' => $qrInfoAssociation->learning_info_id,
            'name_learning' => $qrInfoAssociation->learningInfo->name,
            'description_learning' => $qrInfoAssociation->learningInfo->description,
            'category' => $qrInfoAssociation->learningInfo->category->id,
            'has_trivia' => $qrInfoAssociation->learningInfo->trivias()->exists(),
            'user_has_seen' => $qrInfoAssociation->userQrHistories->isNotEmpty(),
        ];
    }
    
    public function getImages(Request $request)
    {
        $categories = $request->input('categories');
    
        if (!$categories || !is_array($categories)) {
            return response()->json(['message' => 'Se requiere al menos una categoría.'], 400);
        }
    
        $learningInfos = LearningInfo::whereIn('category_id', $categories)
            ->with(['images', 'category'])
            ->get();
    
        $categoryImages = [];
    
        foreach ($learningInfos as $learningInfo) {
            $image = $learningInfo->images->first();
    
            if ($image) {
                $categoryImages[$learningInfo->category->name][] = [
                    'learning_info_id' => $learningInfo->id,
                    'category_id' => $learningInfo->category_id,
                    'image_url' => $image->image_url,
                ];
            }
        }
    
        return response()->json($categoryImages);
    }
    
    
}
