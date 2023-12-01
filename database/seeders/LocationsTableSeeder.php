<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $Locations = [
            ['name'=>'Zona 1', 'description'=>'Zona que abarca toda la entrada de Ricardo J Alfaro hasta la Clínica UTP'],
            ['name'=>'Zona 2', 'description'=>'Zona que abarca desde el edificio 3 hasta Edificio 1'],
            ['name'=>'Zona 3', 'description'=>'Zona que abarca desde Postgrado hassta las áreas recreativas'],
            ['name'=>'Zona 4', 'description'=>'Zona que abarca desde la cafetería central hasta el Edificio de investigación'],
            ['name'=>'Zona 5', 'description'=>'Todo el sendero UTP', 'permission_required'=>true],
        ];  


        foreach($Locations as $location) {
            Location::create($location);
        }
    }
}
