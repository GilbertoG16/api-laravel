<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Learning\LearningInfoRequest;
use App\Http\Requests\Learning\LearningInfoUpdateRequest;
use App\Http\Resources\LearningInfoResourceOne; 
use App\Http\Resources\UniversitySiteResource;  
use App\Http\Resources\LearningInfoPaginateResource;  
use App\Models\LearningInfo;
use App\Models\Location;
use App\Models\QrInfoAssociation;
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
                $learningInfo->load('images', 'videos', 'text_audios', 'qrInfoAssociations', 'trivias');
                
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
}
