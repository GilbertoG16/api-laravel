<?php

namespace App\Http\Controllers\Achievement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Achievement_type;
class AchievementTypeController extends Controller
{
    public function create(Request $request){
        {
            $data = $request->validate([
                'tipo' => 'required|string',   
            ]);
    
            $achievement_type = Achievement_type::create($data);
            return response()->json(['message' => 'Categoría creada exitosamente 😊', 'category' => $achievement_type], 201);
        }
    }
}
