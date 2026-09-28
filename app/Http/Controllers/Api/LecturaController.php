<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estacion;
use App\Models\Lectura;
use Illuminate\Http\Request;

class LecturaController extends Controller
{
    public function recibirLectura(Request $request)
    {
        // 1. Identificar la estación por la clave enviada en la cabecera HTTP
        $token = $request->header('X-API-KEY');
        $estacion = Estacion::where('token_api', $token)->first();

        if (!$estacion) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autorizado. Token de estación no registrado.'
            ], 401);
        }

        // 2. Validar payload
        $request->validate([
            'distancia_cm' => 'required|numeric|min:0',
        ]);

        $distancia = $request->input('distancia_cm');

        // 3. Cálculos de nivel
        $distanciaVacio = 20.0;
        $distanciaLleno = 2.0;

        $porcentaje = (($distanciaVacio - $distancia) / ($distanciaVacio - $distanciaLleno)) * 100;
        $porcentaje = max(0, min(100, $porcentaje));

        $litros = ($porcentaje / 100) * $estacion->capacidad_maxima_litros;

        // 4. Clasificación de estado
        if ($porcentaje <= 15) {
            $estado = 'Crítico';
        } elseif ($porcentaje <= 35) {
            $estado = 'Bajo';
        } else {
            $estado = 'Normal';
        }

        // 5. Guardar asignando la estación correspondiente
        $lectura = Lectura::create([
            'estacion_id' => $estacion->id,
            'distancia_cm' => $distancia,
            'porcentaje' => round($porcentaje, 2),
            'litros' => round($litros, 2),
            'estado' => $estado,
            'ip_origen' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lectura registrada para ' . $estacion->nombre,
            'data' => $lectura
        ], 201);
    }

    // Endpoint para el Dashboard Público (Devuelve el estado de TODAS las estaciones)
    public function obtenerTodasEstaciones()
    {
        $estaciones = Estacion::with('ultimaLectura')->get();
        return response()->json($estaciones);
    }

    // Endpoint para el Dashboard Técnico (Devuelve el detalle de una estación)
// Endpoint técnico (Solo para operadores autenticados)
    public function detalleEstacion($id)
    {
        $estacion = Estacion::with([
            'lecturas' => function ($query) {
                $query->latest()->take(10); // Obtiene las últimas 10 lecturas para el historial
            }
        ])->findOrFail($id);

        return response()->json($estacion);
    }
}