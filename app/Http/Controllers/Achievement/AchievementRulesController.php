<?php
namespace App\Http\Controllers\Achievement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Achievement\AchievementRulesRequest;
use App\Models\AchievementRule;
class AchievementRulesController extends Controller
{
    public function create(AchievementRulesRequest $request)
    {
        $data = $request->validated();
    // Crear la instancia de Achievement
    $achievementRules = AchievementRule::create([
        'achievement_id' => $data['achievement_id'],
        'name' => $data['name'],
        'description' => $data['description'],
        'sql_condition' => $data['sql_condition'],
    ]);
    if($achievementRules){
        return response()->json(['message' => 'Operación exitosa', 'achievement' => $achievementRules], 200);
    }
        return response()->json(['message' => 'Operación fallida', 'achievement' => $achievementRules], 400);   
    }
}
?>