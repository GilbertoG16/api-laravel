<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
            return response()->json(['messge'=>'Ubicación no encontrada'],404);
        }
        // La borramos de la base de datos
        $location->delete();

        return response()->json(['message'=>'Ubicación eliminada con éxito'], 200);
    }
}
