<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LecturaController;

// 1. Recepción de datos desde el ESP32 (Protegido por X-API-KEY en el Controller)
Route::post('/lecturas', [LecturaController::class, 'recibirLectura']);

// 2. Consulta pública para la ciudadanía (Acceso libre)
Route::get('/estaciones/publicas', [LecturaController::class, 'obtenerTodasEstaciones']);

// 3. Consulta técnica (Protegida para usuarios autenticados)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/estaciones/{id}', [LecturaController::class, 'detalleEstacion']);
});