<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lectura extends Model
{
    use HasFactory;

    protected $fillable = [
        'estacion_id',
        'distancia_cm',
        'porcentaje',
        'litros',
        'estado',
        'ip_origen',
    ];

    public function estacion()
    {
        return $this->belongsTo(Estacion::class);
    }
}