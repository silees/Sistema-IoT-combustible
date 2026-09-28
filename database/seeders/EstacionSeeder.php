<?php

namespace Database\Seeders;

use App\Models\Estacion;
use Illuminate\Database\Seeder;

class EstacionSeeder extends Seeder
{
    public function run(): void
    {
        Estacion::create([
            'nombre' => 'Estación Central',
            'ubicacion' => 'Av. Montes #450',
            'capacidad_maxima_litros' => 5.0,
            'token_api' => 'TokenEstacionCentral2026',
        ]);

        Estacion::create([
            'nombre' => 'Estación Sur',
            'ubicacion' => 'Av. Ballivián Calle 12',
            'capacidad_maxima_litros' => 10.0,
            'token_api' => 'TokenEstacionSur2026',
        ]);
    }
}