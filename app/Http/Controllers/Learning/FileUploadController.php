<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseStorageService;
use App\Models\LearningInfo;
use App\Models\QrInfoAssociation;
use Illuminate\Database\Eloquent\Model;
use App\Models\TextAudio;
use App\Models\Image;
use App\Models\Video;

class FileUploadController extends Controller
{
    protected $firebaseStorageService;
    public function __construct(FirebaseStorageService $firebaseStorageService)
    {
        $this->firebaseStorageService = $firebaseStorageService;
    }

    /**-- Usados para poder subir archivos --**/
    public function uploadFiles(array $data, LearningInfo $learningInfo)
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
    
         // Subir múltiples registros de audio y texto
         if (isset($data['text_audios']) && is_array($data['text_audios'])) {
             foreach ($data['text_audios'] as $audioItem) {
                 $audioUrl = $this->firebaseStorageService->uploadFile($audioItem['audio'], $this->getStorageFolder($learningInfo, 'audios'));
                
                 $textValue = isset($audioItem['text']) ? $audioItem['text'] : '';
                
                 $audioModel = new TextAudio([
                     'audio_url' => $audioUrl,
                     'text' => $textValue,
                 ]);
             
                 $this->associateFileToLearningInfo($audioModel, $learningInfo);
             }
         }
        
    }

    public function updateFiles(array $data, LearningInfo $learningInfo)
    {
        // Actualizar imágenes o crear nuevas imágenes
        if (isset($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $imageData) {
                // Verificamos si la imagen tiene un ID
                if (isset($imageData['id'])) {
                    // Buscamos la imagen existente por su ID
                    $existingImage = Image::find($imageData['id']);
        
                    if ($existingImage) {
                        // Eliminamos la imagen anterior en Firebase
                        $this->firebaseStorageService->deleteFileByUrl($existingImage->image_url);
        
                        // Subir y actualizar la imagen existente
                        $imageUrl = $this->firebaseStorageService->uploadFile($imageData['image'], $this->getStorageFolder($learningInfo, 'images'));
                        $existingImage->update(['image_url' => $imageUrl]);
                    }
                } else {
                    // Subir una nueva imagen al almacenamiento en Firebase si no se proporciona un ID
                    $imageUrl = $this->firebaseStorageService->uploadFile($imageData['image'], $this->getStorageFolder($learningInfo, 'images'));
                    $fileModel = new Image(['image_url' => $imageUrl]);
                    $this->associateFileToLearningInfo($fileModel, $learningInfo);
                }
            }
        }
        
        // Subir un nuevo video o actualizar el existente
        if (isset($data['video'])) {
            // Si se proporciona un video, maneja la carga y actualización
            $videoFile = $data['video'];
            $videoUrl = $this->firebaseStorageService->uploadFile($videoFile, $this->getStorageFolder($learningInfo, 'video'));

            if ($learningInfo->videos) {
                // Si ya existe un video, elimina el anterior y actualiza la URL
                $this->firebaseStorageService->deleteFileByUrl($learningInfo->videos->video_url);
                $learningInfo->videos->update(['video_url' => $videoUrl]);
            } else {
                // Si no existe un video, crea uno nuevo y asócialo
                $videoModel = new Video(['video_url' => $videoUrl]);
                $this->associateFileToLearningInfo($videoModel, $learningInfo);
            }
        }
        
        // Actualizar registros de audio y texto
         if (isset($data['text_audios']) && is_array($data['text_audios'])) {
            foreach ($data['text_audios'] as $audioItem) {
                if (isset($audioItem['id'])) {
                    $existingAudio = TextAudio::find($audioItem['id']);

                    if ($existingAudio) {
                        // Actualiza el texto si se proporciona
                        $existingAudio->text = isset($audioItem['text']) ? $audioItem['text'] : $existingAudio->text;

                        // Si se proporciona un nuevo archivo de audio, actualiza la URL del audio
                        if (isset($audioItem['audio'])) {
                            // Elimina el archivo de audio anterior (Firebase)
                            $this->firebaseStorageService->deleteFileByUrl($existingAudio->audio_url);

                            // Sube el nuevo archivo de audio y actualiza la URL
                            $existingAudio->audio_url = $this->firebaseStorageService->uploadFile($audioItem['audio'], $this->getStorageFolder($learningInfo, 'audios'));
                        }

                        // Guarda los cambios en el registro existente
                        $existingAudio->save();
                    }
                } else {
                    // Puedes lanzar una excepción o tomar otra acción adecuada si no se proporciona un ID
                    throw new \Exception("ID de audio faltante en la solicitud.");
                }
            }
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

    public function deleteFolder($learningInfo)
    {
        // Construimos la ruta de la carpeta en Firebase Storage
        $folderPath = 'learning/'.$learningInfo->id;

        $this->firebaseStorageService->deleteFolderInFirebaseStorage($folderPath);
    }
}
