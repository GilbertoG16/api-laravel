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
    public function getUserAppointments()
    {
        // Obtener el usuario autenticado
        $user = auth()->user();
    
        // Obtener las citas del usuario ordenadas por la fecha de inicio
        $userAppointments = $user->appointments()
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($appointment) {
                // Formatear la fecha y hora a un formato de 12 horas
                $appointment->start_time = Carbon::parse($appointment->start_time)->format('Y-m-d h:i:s A');
                $appointment->end_time = Carbon::parse($appointment->end_time)->format('Y-m-d h:i:s A');
                return $appointment;
            });
    
        // Retornar las citas del usuario en formato JSON con fechas en formato de 12 horas
        return response()->json(['appointments' => $userAppointments], 200);
    }
    public function cancelUserAppointment($id)
    {
        // Obtener el usuario autenticado
        $user = auth()->user();
    
        // Buscar la cita que el usuario intenta cancelar
        $appointment = $user->appointments()->find($id);
    
        // Verificar si la cita existe para ese usuario
        if (!$appointment) {
            return response()->json(['error' => 'La cita no existe o no pertenece a este usuario.'], 404);
        }
    
        // Cancelar la cita eliminándola de la base de datos
        $appointment->delete();
    
        return response()->json(['message' => 'Cita cancelada correctamente.'], 200);
    }

    public function create(AppointmentRequest $request)
    {
        // Obtener el usuario autenticado
        $user = auth()->user();
    
        // Obtener el start_time proporcionado por el usuario
        $start_time = Carbon::parse($request->input('start_time'));
        $locationId = $request->input('location_id');
    
        // Verificar si se requiere cita para esta ubicación
        $location = Location::find($locationId);
        if ($location && $location->permission_required) {
            // Validar si la fecha ingresada es anterior al día actual
            $today = now()->startOfDay();
            $selectedDate = $start_time->copy()->startOfDay();
    
            if ($selectedDate < $today) {
                return response()->json(['error' => 'La fecha seleccionada ya pasó.'], 400);
            }
    
            // Validar si el horario seleccionado está dentro del rango permitido (7 am - 4 pm)
            $startTime = $start_time->copy()->startOfDay()->addHours(7);
            $endTime = $start_time->copy()->startOfDay()->addHours(16);
    
            if ($start_time < $startTime || $start_time > $endTime) {
                return response()->json(['error' => 'El horario permitido es de 7 am a 4 pm.'], 400);
            }
    
            // Calcular el end_time sumando las horas definidas en el .env
            $appointmentIntervalHours = env('APPOINTMENT_INTERVAL_HOURS', 2);
            $end_time = $start_time->copy()->addHours($appointmentIntervalHours);
    
            // Obtener el número de citas en el mismo lugar y rango de tiempo
            $countAppointments = Appointment::where('location_id', $locationId)
                ->where('start_time', '<', $end_time)
                ->where('end_time', '>', $start_time)
                ->count();
    
            // Obtener la cantidad máxima de personas permitidas del .env
            $maxPeoplePerInterval = env('MAX_PEOPLE_PER_INTERVAL', 15);
    
            if ($countAppointments >= $maxPeoplePerInterval) {
                return response()->json(['error' => 'No hay disponibilidad en ese horario.'], 400);
            }
    
            // Verificar la cantidad de citas para la semana actual
            $userAppointmentsThisWeek = $user->appointments()
                ->whereYear('start_time', $start_time->year)
                ->where('start_time', '>=', $start_time->copy()->startOfWeek()) // Comenzar la semana actual
                ->where('start_time', '<=', $start_time->copy()->endOfWeek()) // Finalizar la semana actual
                ->count();
    
            // Verificar si ya hay una cita esta semana
            if ($userAppointmentsThisWeek > 0) {
                return response()->json(['error' => 'Solo puedes tener una cita por semana.'], 400);
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
        } else {
            return response()->json(['message' => 'No necesita cita para ir a este sitio.'], 200);
        }
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
