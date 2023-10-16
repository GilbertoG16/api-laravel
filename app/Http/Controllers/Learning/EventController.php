<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Carbon;


use App\Http\Requests\Learning\LearningEventRequest;
use App\Http\Requests\Learning\UpdateLearningEventRequest;
class EventController extends Controller
{

    public function index()
    {
        // Obtener todos los eventos paginados
        $events = Event::paginate(10); // Cambia el número 10 según tus necesidades de paginación
    
        // Retornar la respuesta en formato JSON
        return response()->json(['events' => $events], 200);
    }

    public function create(LearningEventRequest $request)
    {
        $data = $request->validated();
        // Formateamos las fechas con Carbon
        $startEvent = Carbon::parse($data['start_event']);
        $endEvent = Carbon::parse($data['end_event']);

        $event = Event::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'start_event' => $startEvent,
            'end_event' => $endEvent,
            'learning_info_id' => $data['learning_info_id'],
        ]);

        return response()->json(['message' => 'Evento creado con éxito 😍', 'event' => $event], 201);
    }

    public function update(UpdateLearningEventRequest $request, $id)
    {
        // Validamos la solicitud
        $data = $request->validated();

        // Buscamos el evento por ID
        $event = Event::findOrFail($id);

        // Formateamos las fechas con Carbon
        $startEvent = Carbon::parse($data['start_event']);
        $endEvent = Carbon::parse($data['end_event']);

        // Actualizamos los campos del evento
        $event->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'start_event' => $startEvent,
            'end_event' => $endEvent,
            'learning_info_id' => $data['learning_info_id'],
        ]);

        // Enviamos la respuesta formato JSON
        return response()->json(['message' => 'Evento actualizado con éxito 😍', 'event' => $event], 200);
    }

    public function destroy($id)
    {
        // Buscamos el evento por ID
        $event = Event::findOrFail($id);

        // Eliminamos el evento
        $event->delete();

        // Enviamos la respuesta formato JSON
        return response()->json(['message' => 'Evento eliminado con éxito 👋']);
    }

}
