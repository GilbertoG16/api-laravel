<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\LearningInfo;
use App\Models\QrInfoAssociation;

class QrAssociationController extends Controller
{
    public function createQrAssociations(array $qrAssociations, LearningInfo $learningInfo)
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
              
            }
        }
    }

    public function updateQrAssociations(array $qrAssociations, LearningInfo $learningInfo)
    {
        foreach ($qrAssociations as $index => $qrAssociationData) {
            if (isset($qrAssociationData['qr_identifier'])) {
                // Actualizar un registro existente por su qr_identifier
                $existingQr = QrInfoAssociation::where('qr_identifier', $qrAssociationData['qr_identifier'])
                    ->where('learning_info_id', $learningInfo->id)
                    ->first();
    
                if ($existingQr) {
                    // Actualiza los campos necesarios
                    $existingQr->update([
                        'latitude' => $qrAssociationData['latitude'],
                        'longitude' => $qrAssociationData['longitude'],
                        'location_id' => $qrAssociationData['location_id'],
                    ]);
                }
            } else {
                // Crear un nuevo registro si no se proporciona qr_identifier
                // Combinar los valores para qr_identifier como se hacía anteriormente
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
            }
        }
    }
    
    

}
