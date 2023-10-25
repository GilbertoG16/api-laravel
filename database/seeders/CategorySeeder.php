<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Edificios', 'description' => 'Categoría de edificios históricos y modernos'],
            ['name' => 'Monumentos', 'description' => 'Categoría de monumentos icónicos'],
            ['name' => 'Sitios Arqueológicos', 'description' => 'Categoría de sitios arqueológicos antiguos'],
            ['name' => 'Flora', 'description' => 'Categoría de plantas y árboles'],
            ['name' => 'Fauna', 'description' => 'Categoría de animales y vida silvestre'],
            ['name' => 'Sendero', 'description' => 'Categoría de rutas de senderismo'],
            ['name' => 'Áreas recreativas', 'description' => 'Categoría de áreas para actividades recreativas'],
            ['name' => 'Sitios de la Universidad', 'description' => 'Categoría de diversos sitios de la Univesidad Tecnológica'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
