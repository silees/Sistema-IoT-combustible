<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// 1. Vista Pública (Acceso libre para la ciudadanía)
Route::get('/', function () {
    return view('publico');
})->name('publico');

// 2. Rutas de Autenticación (Login / Logout)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 3. Rutas Protegidas (Solo para Operadores / Administradores autenticados)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/estacion/{id}', function ($id) {
        return view('dashboard', ['estacionId' => $id]);
    })->name('admin.dashboard');
});