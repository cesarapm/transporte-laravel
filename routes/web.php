<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConsultaController;




// Route::get('/consulta', [ConsultaController::class, 'index']);
// Route::post('/consulta/track', [ConsultaController::class, 'trackPackage'])->name('consulta.trackPackage');

use App\Http\Controllers\TrackingController;

Route::get('/consulta', [ConsultaController::class, 'index'])->name('consulta.index');

// Ruta para procesar la consulta de tracking con la guía interna
Route::post('/consulta', [TrackingController::class, 'consultarTracking'])->name('consulta.tracking');
Route::get('/', function () {
    return view('welcome');
});
use App\Http\Controllers\GuiaController;

Route::get('/rastreo/{numero}', [GuiaController::class, 'mostrar'])->name('rastreo.mostrar');