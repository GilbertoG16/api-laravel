<?php

namespace App\Services;

use Kreait\Laravel\Firebase\Facades\Firebase;
use Google\Cloud\Core\Exception\NotFoundException;
class FirebaseStorageService
{
    public function uploadFile($file, $storagePath)
{    
    // Generar un nombre único para el archivo
    $uniqueFileName = uniqid() . '_' . $file->getClientOriginalName();

    // Subir el archivo a Firebase Storage
    $firebaseStorage = Firebase::storage();
    $fileContents = file_get_contents($file->getRealPath());
    $firebaseStorage->getBucket()->upload($fileContents, ['name' => $storagePath . $uniqueFileName]);
    
    // Obtener la URL pública firmando sin expiración
    $fileObject = $firebaseStorage->getBucket()->object($storagePath . $uniqueFileName);
    $publicUrl = $fileObject->signedUrl(new \DateTime('3000-01-01T00:00:00Z'));  // 3000-01-01 representa "sin expiración"
    
    return $publicUrl;
}

    
    public function deleteFileByUrl($fileUrl)
    {
        // Obtener la ruta del archivo a partir de la URL
        $filePath = $this->extractPathFromUrl($fileUrl);

        if ($filePath) {
            try {
                // Intentamos eliminar el archivo, si no weno pue 😒
                $firebaseStorage = Firebase::storage();
                $fileObject = $firebaseStorage->getBucket()->object($filePath);
                $fileObject->delete();
            } catch (NotFoundException $e) {
  
            }
        }
    }

    private function extractPathFromUrl($fileUrl)
    {
        $parts = parse_url($fileUrl);

        if (isset($parts['path'])) {
            $pathParts = explode('/', $parts['path']);
            
            // Eliminar el primer elemento vacío y el nombre del bucket
            array_shift($pathParts);
            array_shift($pathParts);

            // La ruta del archivo será lo que queda
            return implode('/', $pathParts);
        }

        return null;
    }
    
}
