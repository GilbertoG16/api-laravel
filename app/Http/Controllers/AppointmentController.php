<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Appointment;
use App\Models\UnauthorizedAccess;
use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\Message;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

use App\Http\Requests\Appointment\AppointmentRequest;

class AppointmentController extends Controller
{
    protected $notification;

    public function __construct()
    {
        $this->notification = Firebase::messaging();
    }
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Número de resultados por página
        $page = $request->input('page', 1); // Página actual
        $date = $request->input('date', null); // Fecha para la paginación, en formato 'Y-m-d'
    
        $query = UnauthorizedAccess::query();
    
        // Filtrar por fecha si se proporciona
        if ($date) {
            $query->whereDate('created_at', $date);
        }
    
        // Obtener la lista de fechas únicas disponibles
        $uniqueDates = UnauthorizedAccess::query()
            ->select('created_at')
            ->groupBy('created_at')
            ->orderBy('created_at', 'desc')
            ->pluck('created_at');
    
        // Formatear las fechas únicas con Carbon
        $formattedUniqueDates = $uniqueDates->map(function ($date) {
            return \Carbon\Carbon::parse($date)->format('Y-m-d'); // Puedes personalizar el formato según tus preferencias
        });
    
        // Paginar los resultados por fecha, ordenándolos por 'updated_at'
        $unauthorizedAccess = $query->orderBy('updated_at', 'desc') // Cambiar a 'updated_at'
            ->paginate($perPage, ['*'], 'page', $page);
    
        // Extraer solo los datos esenciales y las fechas formateadas
        $essentialData = [
            'data' => $unauthorizedAccess->items(),
            'unique_dates' => $formattedUniqueDates,
        ];
    
        return response()->json($essentialData);
    }
    
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
    
        if (!$location || !$location->permission_required) {
            // Si la ubicación no requiere permiso, se asume que el usuario tiene permiso
            return true;
        }
    
        // Obtiene la fecha actual
        $currentDateTime = Carbon::now();
        $currentDate = $currentDateTime->toDateString();
    
        // Buscar el registro UnauthorizedAccess para el día actual y el usuario
        $existingUnauthorizedAccess = UnauthorizedAccess::where('user_id', $user->id)
            ->where('location_id', $locationId)
            ->whereDate('created_at', $currentDate)
            ->first();
    
        // Verificar si el usuario tiene al menos una cita confirmada en la ubicación que coincide con las horas permitidas
        $confirmedPermissions = Appointment::where('location_id', $locationId)
            ->where('user_id', $user->id)
            ->where('is_confirmed', true)
            ->where('start_time', '<=', $currentDateTime)
            ->where('end_time', '>=', $currentDateTime)
            ->count();
        
        if ($confirmedPermissions > 0) {
            // El usuario tiene al menos una cita confirmada en la ubicación dentro del rango de tiempo permitido
            return true; // Tiene permiso
        }
    
        if ($existingUnauthorizedAccess) {
            // Si existe un registro UnauthorizedAccess, actualiza su 'updated_at' a la hora actual
            $existingUnauthorizedAccess->update(['updated_at' => $currentDateTime]);
            return false; // No tiene permiso
        }
    
        // Si no existe un registro UnauthorizedAccess, créalo para el usuario y la ubicación
        UnauthorizedAccess::create([
            'user_id' => $user->id,
            'location_id' => $locationId,
            'created_at' => $currentDateTime,
            'updated_at' => $currentDateTime,
        ]);
    
        return false; // No tiene permiso
    }
    
    // Conceder acceso
    public function confirmAccess($appointmentId)
    {
        $appointment = Appointment::find($appointmentId);
    
        if (!$appointment) {
            return response()->json(['error' => 'Cita no encontrada'], 404);
        }
    
        // Verificar si la cita ya está confirmada
        if ($appointment->is_confirmed) {
            return response()->json(['message' => 'Esta cita ya está confirmada'], 200);
        }
    
        // Confirmamos el acceso actualizando el estado de is_confirmed 
        $appointment->is_confirmed = true;
        $appointment->save();
    
        // Obtenemos detalles relevantes de la cita
        $location = Location::find($appointment->location_id);
        $userId = $appointment->user_id;
    
        // Verificar si el usuario tiene un token FCM
        $user = User::find($userId);
        $fcmTokens = $user->fcmTokens;
    
        if ($fcmTokens->isNotEmpty()) {
            // Enviar la notificación
            foreach ($fcmTokens as $fcmToken) {
                $this->sendConfirmationNotification($fcmToken->token, $location, $appointment);
            }
        }
    
        return response()->json(['message' => 'Acceso confirmado'], 200);
    }
    
    
    // Función para enviar la notificación de confirmación
    public function sendConfirmationNotification($fcmToken, $location, $appointment)
    {
        // Convertir las horas a formato de 12 horas
        $startTime12H = Carbon::parse($appointment->start_time)->format('g:i A');
        $endTime12H = Carbon::parse($appointment->end_time)->format('g:i A');
    
        // Construir el mensaje de notificación
        $title = 'Confirmación de cita';
        $body = "Se confirma tu ida al sendero desde {$startTime12H} hasta {$endTime12H} en {$location->name}.";
    
        // Construir el mensaje
        $message = CloudMessage::fromArray([
            'token' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ]);
    
        // Enviar la notificación
        $this->notification->send($message);
    } 
}
