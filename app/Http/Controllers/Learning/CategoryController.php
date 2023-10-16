<?php

namespace App\Http\Controllers\Learning;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::all(), 200);
    }

    public function show($id)
    {
        $category = Category::find($id);
    
        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada 😕'], 404);
        }
    
        return response()->json($category, 200);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
        ]);

        $category = Category::create($data);
        return response()->json(['message' => 'Categoría creada exitosamente 😊', 'category' => $category], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada 😕'], 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
        ]);

        $category->update($data);

        return response()->json(['message' => 'Categoría actualizada exitosamente 😎', 'category' => $category], 200);
    }


    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada 😕'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Categoría eliminada exitosamente 😶‍🌫️'], 200);
    }

}
