<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LecturaController;

// 1. Recepción de datos desde el ESP32
Route::post('/lecturas', [LecturaController::class, 'recibirLectura']);

// 2. Consulta pública para la ciudadanía
Route::get('/estaciones/publicas', [LecturaController::class, 'obtenerTodasEstaciones']);

// 3. Consulta técnica para el Dashboard (PÚBLICA O LIBRE PARA EL FETCH)
Route::get('/estacion/{id}', [LecturaController::class, 'detalleEstacion']);


Route::post('/estacion/{id}/umbrales', [LecturaController::class, 'actualizarUmbrales']);


// Rutas de exportación de reportes
Route::get('/estacion/{id}/exportar/csv', [LecturaController::class, 'exportarLecturasCsv']);
Route::get('/estacion/{id}/reporte/resumen', [LecturaController::class, 'obtenerReporteResumen']);