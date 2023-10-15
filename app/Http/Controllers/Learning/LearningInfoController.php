<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Learning\LearningInfoRequest;
use App\Http\Resources\LearningInfoResourceOne;  
use App\Models\LearningInfo;
use App\Models\QrInfoAssociation;
use App\Models\TextAudio;
use App\Models\Image;
use App\Models\Video;
use App\Services\FirebaseStorageService;
use Illuminate\Database\Eloquent\Model;

class LearningInfoController extends Controller
{
    public function __construct(FirebaseStorageService $firebaseStorageService)
    {
        $this->firebaseStorageService = $firebaseStorageService;
    }

    public function findByQrIdentifier($qrIdentifier)
    {
        try {
            // Buscamos la instancia de la tabla por qr_identifier
            $qrAssociation = QrInfoAssociation::where('qr_identifier',$qrIdentifier)->first();

            if (!$qrAssociation){
                return response()->json(['message'=> 'No se encontró la asociación de QR'], 404);
            }
            // Obtener el LearningInfo correspondiente a la asociación de QR
            $learningInfo = $qrAssociation->learningInfo;

            if(!$learningInfo) {
                return response()->json(['message'=>'No se encontró el LearningInfo correspondiente'],404);
            }
            $learningInfo->load('images','videos','text_audios','qrInfoAssociations');

            return new LearningInfoResourceOne($learningInfo);
        } catch (\Throwable $th) {
            return response()->json([
                'succes'=> false,
                'message'=>'Error al buscar la asociación ' . $e->getMessage(),
            ]);
        }
    }
    public function create(LearningInfoRequest $request)
    {
        $data = $request->validated();

        // Crear la instancia de LearningInfo
        $learningInfo = LearningInfo::create([
            'name'=>$data['name'],
            'description'=>$data['description'],
            'category_id'=>$data['category_id'],
        ]);

        // Subir archivitos y asociarlos, lo hacemos con otro método porke después podemos reutilizar la lógica tú sabes 😎
        $this->uploadFiles($data, $learningInfo);

        // Crear asociaciones QR después dde haber subido los archivos, también en otro método (intento seguir buenas prácticas)
        $this->createQrAssociations($data['qr_associations'], $learningInfo);

        // Devolvemos el JSON por cierto cuando estoy programando esto es Viernes 13 de octubre 
        $learningInfo->load('images','videos','text_audios','qrInfoAssociations');

        return response()->json(['message'=>'Operación exitosa','learning_info'=> $learningInfo], 200);
    }
    /*-- Creación de qr asociaciones --*/
    protected function createQrAssociations(array $qrAssociations, LearningInfo $learningInfo)
    {
        foreach ($qrAssociations as $index => $qrAssociationData) {
            // Verificar si las claves necesarias están presentes en $qrAssociationData
            if (isset($qrAssociationData['latitude'], $qrAssociationData['longitude'], $qrAssociationData['location_id'])) {
                // Combinamos los valores para qr_identifier
                $qrIdentifier = sprintf('%s_%d_%d', $learningInfo->name, $index + 1, $qrAssociationData['location_id']);

                // Verificamos si ya existe el qr_identifier
                $existingQr = QrInfoAssociation::where('qr_identifier', $qrIdentifier)->first();

                // Si ya existe, agregamos un sufijo adicional
                if ($existingQr) {
                    $qrIdentifier = $qrIdentifier . '_' . uniqid();
                }

                QrInfoAssociation::create([
                    'latitude' => $qrAssociationData['latitude'],
                    'longitude' => $qrAssociationData['longitude'],
                    'qr_identifier' => $qrIdentifier,
                    'location_id' => $qrAssociationData['location_id'],
                    'learning_info_id' => $learningInfo->id,
                ]);
            } else {
                // Manejar la situación donde una o más claves necesarias no están presentes en $qrAssociationData
                // Puede lanzar una excepción, devolver un error, o tomar otra acción según tus necesidades.
            }
        }
    }

    /**-- Usados para poder subir archivos --**/
    protected function uploadFiles(array $data, LearningInfo $learningInfo)
    {
        foreach ($data['images'] as $image) {
            $imageUrl = $this->firebaseStorageService->uploadFile($image, $this->getStorageFolder($learningInfo, 'images'));
            $fileModel = new Image(['image_url' => $imageUrl]);
            $this->associateFileToLearningInfo($fileModel, $learningInfo);
        }
    
        // Subir video
        if (isset($data['video']) && $data['video']) {
            $videoUrl = $this->firebaseStorageService->uploadFile($data['video'], $this->getStorageFolder($learningInfo, 'video'));
            $videoModel = new Video(['video_url' => $videoUrl]);
            $this->associateFileToLearningInfo($videoModel, $learningInfo);
        }
    
        // Subir audio
        if (isset($data['audio']) && $data['audio']) {
            $audioUrl = $this->firebaseStorageService->uploadFile($data['audio'], $this->getStorageFolder($learningInfo, 'audios'));
            
            // Asegúrate de que $data['text'] contiene el valor deseado para el campo 'text'
            $textValue = isset($data['text']) ? $data['text'] : ''; 
            
            $audioModel = new TextAudio([
                'audio_url' => $audioUrl,
                'text' => $textValue,
            ]);
        
            $this->associateFileToLearningInfo($audioModel, $learningInfo);
        }
        
    }
    
    protected function associateFileToLearningInfo(Model $fileModel, LearningInfo $learningInfo)
    {
        // Obtenemos el nombre de la relación dinámicamente
        $relationName = $fileModel->getTable();

        // Asociamos el archivo al learning info
        $learningInfo->{$relationName}()->save($fileModel);
    }
    
      protected function getStorageFolder(LearningInfo $learningInfo, $fileType)
    {
        return "learning/{$learningInfo->id}/{$fileType}/";
    }
}
