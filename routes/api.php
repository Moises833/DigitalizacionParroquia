<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/actas', [\App\Http\Controllers\ActaBautizoController::class, 'index']);
Route::post('/actas', [\App\Http\Controllers\ActaBautizoController::class, 'store']);
Route::post('/actas/transcribir-imagen', [\App\Http\Controllers\ActaBautizoController::class, 'transcribirImagen']);
Route::get('/libros', function () {
    return response()->json(\App\Models\Libro::all(['id', 'numero_libro']));
});

// Rutas de Respaldo y Restauración
Route::get('/respaldos/descargar', [\App\Http\Controllers\RespaldoController::class, 'descargarSqlite']);
Route::post('/respaldos/restaurar', [\App\Http\Controllers\RespaldoController::class, 'restaurar']);
Route::get('/respaldos/exportar-csv', [\App\Http\Controllers\RespaldoController::class, 'exportarCsv']);
