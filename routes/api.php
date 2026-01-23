<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\TestSimpleController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\DocumentoEscaneadoController;
use Symfony\Component\Routing\Router;

// Ruta para el inicio de sesión
Route::post('/login', [AuthController::class, 'login']);

Route::get('/hello', [TestController::class, 'hello']);
Route::get('/test', [TestSimpleController::class, 'test']);
// Route::post('consulta-tracking', [TrackingController::class, 'consultarTracking']);

// Rutas protegidas
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware('auth:sanctum')->get('/users', [AuthController::class, 'index']);
Route::middleware('auth:sanctum')->delete('/users/{id}', [AuthController::class, 'destroy']);

Route::post('/webhook/trackingmore', [WebhookController::class, 'trackingmore']);
Route::post('/webhook/stripe', [WebhookController::class, 'stripe']);

Route::post('/proxy-tracking', [TrackingController::class, 'proxyTracking']);
Route::get('/evidencia/{codigo}', [TrackingController::class, 'evidencia']);

Route::post('/stripe', [StripeController::class, 'createPaymentSession']);

// Rutas para pedidos
Route::get('/pedidos', [PedidoController::class, 'index']);
Route::get('/pedidos/stats', [PedidoController::class, 'stats']);
Route::get('/pedidos/{id}', [PedidoController::class, 'show']);
Route::get('/pedidos/session/{sessionId}', [PedidoController::class, 'showBySessionId']);
Route::put('/pedidos/{id}/estatus', [PedidoController::class, 'updateEstatus']);

// Rutas para Documentos Escaneados
Route::prefix('documentos')->group(function () {
    Route::get('/', [DocumentoEscaneadoController::class, 'index']);
    Route::post('/upload', [DocumentoEscaneadoController::class, 'upload']);
    Route::get('/{id}', [DocumentoEscaneadoController::class, 'show']);
    Route::put('/{id}', [DocumentoEscaneadoController::class, 'update']);
    Route::delete('/{id}', [DocumentoEscaneadoController::class, 'destroy']);
    Route::post('/{id}/reprocesar', [DocumentoEscaneadoController::class, 'reprocesar']);
    Route::post('/{id}/verificar', [DocumentoEscaneadoController::class, 'verificar']);
});









