<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserQrHistory;
use App\Models\User;
use App\Models\AchievementRule;
use App\Models\Achievement;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class UserQrHistoryController extends Controller
{
        
    public function index(Request $request)
    {
        // Obtén la fecha de la solicitud GET, o usa la fecha actual si no se proporciona.
        $date = $request->input('date', now()->format('Y-m-d'));
    
        $subquery = DB::table('user_qr_histories')
            ->select('user_id', DB::raw('MAX(created_at) as latest_created_at'))
            ->groupBy('user_id');
    
        $histories = DB::table('user_qr_histories')
            ->select('users.id as user_id', 'users.email', 'qr_info_associations.qr_identifier', 'qr_info_associations.latitude', 'qr_info_associations.longitude', 'user_qr_histories.created_at')
            ->join('users', 'user_qr_histories.user_id', '=', 'users.id')
            ->join('qr_info_associations', 'user_qr_histories.qr_info_association_id', '=', 'qr_info_associations.id')
            ->joinSub($subquery, 'latest_histories', function ($join) {
                $join->on('user_qr_histories.user_id', '=', 'latest_histories.user_id')
                    ->on('user_qr_histories.created_at', '=', 'latest_histories.latest_created_at');
            })
            ->whereDate('user_qr_histories.created_at', $date)
            ->orderBy('user_qr_histories.created_at', 'desc')
            ->paginate(10); // Puedes ajustar el número de elementos por página según tus necesidades
    
        // Formatea la hora exacta y agrega los datos al resultado.
        $formattedHistories = $histories->map(function ($history) {
            $formattedTime = Carbon::parse($history->created_at)->format('h:i A');
            return [
                'user_id' => $history->user_id,
                'email' => $history->email,
                'qr_identifier' => $history->qr_identifier,
                'latitude' => $history->latitude,
                'longitude' => $history->longitude,
                'hora_exacta' => $formattedTime
            ];
        });
    
        return response()->json($formattedHistories);
    }

    public function show($userId, Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        // Buscamos el usuario por su ID
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Buscamos el historial del usuario en la fecha dada y lo paginamos
        $userHistory = UserQrHistory::where('user_id', $userId)
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->paginate(); // Utilizamos el método paginate para obtener los registros paginados

        // Formateamos la respuesta para incluir la hora exacta y otros detalles
        $formattedHistory = $userHistory->map(function ($history) {
            return [
                'user_id' => $history->user_id,
                'email' => $history->user->email,
                'qr_identifier' => $history->qrInfoAssociation->qr_identifier,
                'latitude' => $history->qrInfoAssociation->latitude,
                'longitude' => $history->qrInfoAssociation->longitude,
                'hora_exacta' => Carbon::parse($history->created_at)->format('h:i A')
            ];
        });

        return response()->json($formattedHistory);
    }
    public function checkAndAssignAchievement( $qr_identifier)
{
    $user = auth()->user(); 
    // Recupera las reglas de logro que contienen la condición SQL
    $achievementRules = AchievementRule::all();
    foreach ($achievementRules as $rule) {
        // Reemplaza ? en la consulta SQL con valores reales
        $sqlCondition = str_replace('?', $$user->id, $rule->sql_condition);
        $sqlCondition = str_replace('?', $qr_identifier, $sqlCondition);
        // Ejecuta la consulta SQL
        $result = DB::select(DB::raw($sqlCondition));
        if (count($result) === 1 && (int)$result[0]->count === 1) {
            // Si la condición se cumple, asigna el logro al usuario
            $user = User::find($user->id);
            $achievement = Achievement::find($rule->achievement_id);
            if ($user && $achievement) {
                // Asigna el logro al usuario
                $user->achievements()->syncWithoutDetaching([$achievement->id]);
            }
        }
    }
}
         
}
