<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Achievement_type;

class Achievement_TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $achievement_types = [
            ['tipo' => 'Trivias'],
            ['tipo' => 'Escaneados'],
        ];
        foreach ($achievement_types as $achievement_type) {
            Achievement_type::create($achievement_type);
        }
    }
}
