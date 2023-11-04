<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Appointment;
use App\Models\UnauthorizedAccess;
use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

use App\Http\Requests\Appointment\AppointmentRequest;

class AppointmentController extends Controller
{
    public function create(AppointmentRequest $request)
    {
        //Validamos la fecha y la ubicación 
        
        // Obtenemos el usuario 
        $user = auth()->user();

        // Obtener el start_time proporcionado por el usuario
        $start_time = $request->input('start_time');

        // Calcular el end_time sumando las horas definidas en el .env
        $appointmentIntervalHours = env('APPOINTMENT_INTERVAL_HOURS', 2);
        $end_time = Carbon::parse($start_time)->addHours($appointmentIntervalHours);

        // Obtener el número de citas en el mismo lugar y rango de tiempo
        $locationId = $request->input('location_id');
        $countAppointments = Appointment::where('location_id', $locationId)
            ->where('start_time', '<', $end_time)
            ->where('end_time', '>', $start_time)
            ->count();
           
        // Obtener la cantidad máxima de personas permitidas del .env
        $maxPeoplePerInterval = env('MAX_PEOPLE_PER_INTERVAL', 15);

        if ($countAppointments >= $maxPeoplePerInterval) {
            return response()->json(['error' => 'No hay disponibilidad en ese horario.'], 400);
        }

        // Crear la cita
        Appointment::create([
            'start_time' => $start_time,
            'end_time' => $end_time,
            'user_id' => $user->id,
            'location_id' => $locationId,
            'is_confirmed' => false,
        ]);

        return response()->json(['message' => 'Cita creada con éxito.'], 200);
    
    }

    public function hasPermission($user, $locationId)
    {
        // Verificamos si la ubicación requiere permiso
        $location = Location::find($locationId);
    
        if ($location && $location->permission_required) {
            // Verificamos si el usuario tiene permisos confirmados
            $confirmedPermissions = Appointment::where('location_id', $locationId)
                ->where('user_id', $user->id)
                ->where('is_confirmed', true)
                ->count();
    
            Log::info("Confirmed Permissions: " . $confirmedPermissions);
    
            if ($confirmedPermissions > 0) {
                // El usuario tiene permisos confirmados, ahora verificamos si la cita ha caducado
                $currentDateTime = Carbon::now();
    
                // Obtener la cita más cercana en el tiempo para este usuario y esta ubicación
                $nextAppointment = Appointment::where('location_id', $locationId)
                    ->where('user_id', $user->id)
                    ->where('is_confirmed', true)
                    ->where('end_time', '>', $currentDateTime)
                    ->orderBy('start_time', 'asc')
                    ->first();
    
                Log::info("Next Appointment: " . json_encode($nextAppointment));
    
                if (!$nextAppointment) {
                    // No hay citas futuras confirmadas para este usuario en esta ubicación
                    Log::info("Unauthorized Access Created");
                    UnauthorizedAccess::create([
                        'user_id' => $user->id,
                        'location_id' => $locationId,
                    ]);
    
                    return false; // No tiene permiso
                }
    
                // Verificar si la próxima cita ha caducado
                if ($currentDateTime->greaterThanOrEqualTo($nextAppointment->start_time)) {
                    // La cita ha caducado, el usuario no tiene acceso
                    Log::info("Unauthorized Access Created");
                    UnauthorizedAccess::create([
                        'user_id' => $user->id,
                        'location_id' => $locationId,
                
                    ]);
    
                    return false; // No tiene permiso
                }
    
                // La cita no ha caducado, el usuario tiene acceso
                return true;
            }
    
            // El usuario no tiene permisos confirmados, registrar como Unauthorized Access
            Log::info("Unauthorized Access Created");
            UnauthorizedAccess::create([
                'user_id' => $user->id,
                'location_id' => $locationId,
            ]);
    
            return false; // No tiene permiso
        }
    
        // Si la ubicación no requiere permiso, se asume que el usuario tiene permiso
        return true;
    }
    
    
    
    // Conceder acceso
    public function confirmAccess($appointmentId)
    {
        $appointmentId = Appointment::find($appointmentId);

        if(!$appointmentId) {
            return response()->json(['error'=>'Cita no encontrada'],404);
        }

        // Confirmamos el acceso actualizando el estado de is_confirmed 
        $appointmentId->is_confirmed = true;
        $appointmentId->save();

        return response()->json(['message'=>'Acceso confirmado'], 200);
    }

    
}
