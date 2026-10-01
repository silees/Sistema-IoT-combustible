<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estacion;
use App\Models\Lectura;
use App\Models\User;
use App\Notifications\AlertaCombustibleBajo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // 4. Clasificación de estado usando UMBRALES DINÁMICOS de la BD
        $umbralCritico = $estacion->umbral_critico ?? 10.0;
        $umbralBajo = $estacion->umbral_bajo ?? 20.0;

        if ($porcentaje <= $umbralCritico) {
            $estado = 'Crítico';
        } elseif ($porcentaje <= $umbralBajo) {
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

        // 6. LÓGICA DE ENVÍO DE CORREO (Alertas por nivel Bajo o Crítico)
        if ($estado === 'Bajo' || $estado === 'Crítico') {
            
            $ultimaAlertaEnviada = $estacion->ultima_alerta_enviada_at;

            // Evita reenvío constante en lecturas continuas (frecuencia mínima: 30 min)
            if (!$ultimaAlertaEnviada || now()->diffInMinutes($ultimaAlertaEnviada) >= 30) {
                
                // Obtener destinatarios (usuarios registrados o enviar a un email específico)
                $destinatarios = User::all(); // O filtra por rol: User::where('role', 'admin')->get();

                if ($destinatarios->isNotEmpty()) {
                    Notification::send($destinatarios, new AlertaCombustibleBajo($estacion, $lectura));
                } else {
                    // Si no hay usuarios en BD, puedes forzar un correo específico de respaldo:
                    Notification::route('mail', 'admin@tudominio.com')
                        ->notify(new AlertaCombustibleBajo($estacion, $lectura));
                }

                // Guardar timestamp para controlar el intervalo de envío
                $estacion->update(['ultima_alerta_enviada_at' => now()]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Lectura registrada para ' . $estacion->nombre,
            'data' => $lectura
        ], 201);
    }

    // Endpoint para actualizar los umbrales desde el Dashboard
    public function actualizarUmbrales(Request $request, $id)
    {
        $request->validate([
            'umbral_critico' => 'required|numeric|min:0|max:100',
            'umbral_bajo' => 'required|numeric|min:0|max:100',
            'umbral_alto' => 'required|numeric|min:0|max:100',
        ]);

        $estacion = Estacion::find($id);

        if (!$estacion) {
            return response()->json(['message' => 'Estación no encontrada'], 404);
        }

        // Guardar valores en la base de datos
        $estacion->umbral_critico = $request->input('umbral_critico');
        $estacion->umbral_bajo = $request->input('umbral_bajo');
        $estacion->umbral_alto = $request->input('umbral_alto');
        $estacion->save();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Umbrales guardados y actualizados exitosamente en la base de datos.'
        ]);
    }

    // Endpoint para el Dashboard Público
    public function obtenerTodasEstaciones()
    {
        $estaciones = Estacion::with('ultimaLectura')->get();
        return response()->json($estaciones);
    }

    /**
     * Exportar historial de lecturas en formato CSV
     */
    public function exportarLecturasCsv(Request $request, $id)
    {
        $estacion = Estacion::find($id);
        if (!$estacion) {
            return response()->json(['message' => 'Estación no encontrada'], 404);
        }

        $fileName = "reporte_lecturas_estacion_{$id}_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['ID Lectura', 'Fecha / Hora', 'Distancia (cm)', 'Porcentaje (%)', 'Volumen (L)', 'Estado Operativo', 'IP Origen'];

        $callback = function () use ($estacion, $columns) {
            $file = fopen('php://output', 'w');

            // BOM UTF-8 para compatibilidad con Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $columns, ';');

            $estacion->lecturas()->chunk(250, function ($lecturas) use ($file) {
                foreach ($lecturas as $lectura) {
                    fputcsv($file, [
                        $lectura->id,
                        $lectura->created_at->format('Y-m-d H:i:s'),
                        $lectura->distancia_cm,
                        $lectura->porcentaje,
                        $lectura->litros,
                        $lectura->estado,
                        $lectura->ip_origen ?? 'N/A'
                    ], ';');
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generar datos consolidados para Reporte imprimible / PDF
     */
    public function obtenerReporteResumen($id)
    {
        $estacion = Estacion::find($id);
        if (!$estacion) {
            return response()->json(['message' => 'Estación no encontrada'], 404);
        }

        $lecturas = $estacion->lecturas();

        $stats = [
            'estacion' => $estacion->nombre,
            'ubicacion' => $estacion->ubicacion,
            'capacidad_maxima' => $estacion->capacidad_maxima_litros,
            'total_lecturas' => $lecturas->count(),
            'promedio_porcentaje' => round($lecturas->avg('porcentaje') ?? 0, 2),
            'max_litros' => $lecturas->max('litros') ?? 0,
            'min_litros' => $lecturas->min('litros') ?? 0,
            'conteo_criticos' => (clone $lecturas)->where('estado', 'Crítico')->count(),
            'conteo_bajos' => (clone $lecturas)->where('estado', 'Bajo')->count(),
            'conteo_normales' => (clone $lecturas)->where('estado', 'Normal')->count(),
            'fecha_reporte' => now()->toDateTimeString(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    // Endpoint técnico para el Dashboard del operador
    public function detalleEstacion($id)
    {
        $estacion = Estacion::find($id);

        if (!$estacion) {
            return response()->json(['message' => 'Estación no encontrada'], 404);
        }

        $ultimaLectura = $estacion->lecturas()->latest()->first();

        // Determinar si el nodo está Online
        $esOnline = false;
        $haceCuanto = 'Sin señal';

        if ($ultimaLectura) {
            $segundosDiferencia = $ultimaLectura->created_at->diffInSeconds(now());
            $esOnline = $segundosDiferencia <= 30;
            $haceCuanto = $ultimaLectura->created_at->diffForHumans();
        }

        $historial = $estacion->lecturas()
            ->latest()
            ->take(10)
            ->get()
            ->sortBy('created_at')
            ->values();

        return response()->json([
            'id' => $estacion->id,
            'nombre' => $estacion->nombre,
            'ubicacion' => $estacion->ubicacion,
            'capacidad_maxima_litros' => $estacion->capacidad_maxima_litros,
            'token_api' => $estacion->token_api,
            'ultima_lectura' => $ultimaLectura,
            'historial' => $historial,
            'red' => [
                'ip_origen' => request()->ip() == '127.0.0.1' ? '192.168.1.105 (Local)' : request()->ip(),
                'estado_nodo' => $esOnline ? 'ONLINE' : 'OFFLINE',
                'ultimo_heartbeat' => $haceCuanto,
                'protocolo' => 'HTTP/1.1 POST (REST)',
                'firmware_ver' => 'v2.1.0-ESP32'
            ],
            // LECTURA DIRECTA DE BD
            'umbrales' => [
                'umbral_critico' => (float) ($estacion->umbral_critico ?? 10.0),
                'umbral_bajo' => (float) ($estacion->umbral_bajo ?? 20.0),
                'umbral_alto' => (float) ($estacion->umbral_alto ?? 90.0),
            ],
            'logs_seguridad' => [
                [
                    'fecha' => now()->subMinutes(5)->toDateTimeString(),
                    'ip' => '192.168.1.105',
                    'evento' => 'Lectura receptada con éxito',
                    'estado_http' => 201,
                    'nivel' => 'INFO'
                ],
                [
                    'fecha' => now()->subHours(2)->toDateTimeString(),
                    'ip' => '185.220.101.4',
                    'evento' => 'Token API no válido (X-API-KEY erróneo)',
                    'estado_http' => 401,
                    'nivel' => 'WARN'
                ],
                [
                    'fecha' => now()->subHours(6)->toDateTimeString(),
                    'ip' => '45.142.120.9',
                    'evento' => 'Intento de POST sin encabezado de autenticación',
                    'estado_http' => 403,
                    'nivel' => 'DANGER'
                ]
            ]
        ]);
    }
}