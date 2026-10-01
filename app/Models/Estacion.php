<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estacion extends Model
{
    use HasFactory;

    // Forzar el nombre de la tabla
    protected $table = 'estaciones';

    protected $fillable = [
        'nombre',
        'ubicacion',
        'capacidad_maxima_litros',
        'token_api',
        'umbral_critico',
        'umbral_bajo',
        'umbral_alto',
        'ultima_alerta_enviada_at',
    ];
    protected $casts = [
        'ultima_alerta_enviada_at' => 'datetime', // <-- Recomendado para trabajar con Carbon/now()
    ];

    public function lecturas()
    {
        return $this->hasMany(Lectura::class, 'estacion_id');
    }

    public function ultimaLectura()
    {
        return $this->hasOne(Lectura::class, 'estacion_id')->latestOfMany();
    }
}