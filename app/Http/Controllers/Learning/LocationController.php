<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QrInfoAssociation;
use App\Models\LearningInfo;
use App\Models\Image;
use Illuminate\Support\Collection;
use App\Http\Requests\LocationRequest;
use App\Http\Requests\LocationUpdate; 
use App\Models\Location;

class LocationController extends Controller
{
    // Traer todos las ubicaciones
    public function index()
    {
        $locations = Location::all();
        return response()->json(['Sitios'=> $locations],200);
    }
    // Crear nuevas ubicaciones
    public function store(LocationRequest $request)
    {
        // Obtener los datos validados de la request
        $validatedData = $request->all();
        
        // Creamos el temita
        $location = Location::create($validatedData);

        // Devolvemos la respuesta 
        return response()->json(['Ubicación creada '=>$location],201);
    }
    // Hacer update de la información de esas ubicaciones
    public function update (LocationUpdate $request, $id)
    {   
        // Obtener los datos validados de la request
        $validatedData = $request->all();
        // Encontramos la ubicación por su ID
        $location = Location::find($id);

        // Verifica si la ubicación existe
        if(!$location){
            return response()->json(['message'=>'Ubicación no encontrada'],404);
        }
        // Actualiza los campos necesarios 
        $location ->update($validatedData);

        return response()->json(['location'=>$location],200);
    }
    // Eliminar una ubicación 
    public function destroy($id)
    {
        // Buscamos la ubicación a ver si existe
        $location = Location::find($id);

        if(!$location){
            return response()->json(['message'=>'Ubicación no encontrada'],404);
        }
        // La borramos de la base de datos
        $location->delete();

        return response()->json(['message'=>'Ubicación eliminada con éxito'], 200);
    }

    // Estética para los locations...
    public function getAllLearningsWithImages()
    {
        // Obtener todas las ubicaciones con sus imágenes asociadas
        $locationsWithImages = Location::with(['qrInfoAssociations.learningInfo.images'])->get();
    
        $data = [];
    
        // Iterar sobre cada ubicación
        foreach ($locationsWithImages as $location) {
            // Inicializar un conjunto para almacenar los IDs de los aprendizajes ya incluidos
            $includedLearnings = [];
    
            // Inicializar un conjunto para almacenar las imágenes únicas
            $uniqueImages = new Collection();
    
            // Iterar sobre cada QR asociado a la ubicación
            foreach ($location->qrInfoAssociations as $qrInfoAssociation) {
                // Obtener el learning asociado al QR
                $learning = $qrInfoAssociation->learningInfo;
    
                // Verificar si ya hemos incluido una imagen para este learning
                if (!in_array($learning->id, $includedLearnings)) {
                    // Obtener las imágenes del learning y barajarlas aleatoriamente
                    $shuffledImages = $learning->images->shuffle();
    
                    // Tomar solo la primera de manera aleatoria
                    $image = $shuffledImages->first();
    
                    // Asegurarse de que la imagen no se haya agregado previamente
                    if ($image) {
                        $uniqueImages->push($image);
    
                        // Agregar el ID del learning al conjunto de aprendizajes incluidos
                        $includedLearnings[] = $learning->id;
                    }
                }
            }
    
            // Agregar los datos al array final
            $data[] = [
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'description' =>$location->description,
                    'images' => $uniqueImages,
                ],
            ];
        }
    
        return response()->json(['data' => $data]);
    }   
}
