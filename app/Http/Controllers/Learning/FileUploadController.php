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
