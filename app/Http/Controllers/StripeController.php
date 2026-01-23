<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Pedido;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Carbon\Carbon;

class StripeController extends Controller
{
    public function createPaymentSession(Request $request)
    {
        // Log de todos los datos recibidos
        Log::info('Datos recibidos en API Stripe:', $request->all());

        // Reconfigurar Stripe API Key antes de cada operación (por seguridad)
        $stripeSecretKey = config('app.stripe_secret_key') ?? env('STRIPE_SECRET_KEY');

        if (empty($stripeSecretKey)) {
            Log::error('STRIPE_SECRET_KEY no está disponible');
            return response()->json([
                'success' => false,
                'message' => 'Configuración de pago no disponible',
            ], 500);
        }

        Stripe::setApiKey($stripeSecretKey);
        Log::info('Stripe API Key configurada para esta sesión');

        // Validaciones
        $request->validate([
            'carga' => 'required|numeric|min:1',
            'remitente.nombre' => 'required|string|max:255',
            'remitente.celular' => 'required|string|max:20',
            'remitente.direccion' => 'required|string',
            'remitente.estado' => 'required|string|max:100',
            'remitente.ciudad' => 'required|string|max:100',
            'remitente.codigo_postal' => 'required|string|max:20',
            'remitente.pais' => 'required|string|max:100',
            'destinatario.nombre' => 'required|string|max:255',
            'destinatario.celular' => 'required|string|max:20',
            'destinatario.direccion' => 'required|string',
            'destinatario.estado' => 'required|string|max:100',
            'destinatario.ciudad' => 'required|string|max:100',
            'destinatario.codigo_postal' => 'required|string|max:20',
            'destinatario.pais' => 'required|string|max:100',
            'paquete.alto' => 'required|numeric|min:0.1',
            'paquete.ancho' => 'required|numeric|min:0.1',
            'paquete.largo' => 'required|numeric|min:0.1',
            'paquete.peso' => 'required|numeric|min:0.1',
            'paquete.volumen_calculado' => 'required|numeric|min:0.1',
        ]);

        try {
            // Verificar que la API key esté configurada antes de crear la sesión
            Log::info('Iniciando creación de sesión Stripe');

            // Reconfigurar la API key por si acaso
            $stripeKey = env('STRIPE_SECRET_KEY');
            if (empty($stripeKey)) {
                throw new \Exception('Stripe API Key no configurada');
            }

            Stripe::setApiKey($stripeKey);
            Log::info('API Key reconfigurada exitosamente');
            // Crear el pedido en la base de datos
            $pedido = Pedido::create([
                'carga' => $request->carga,
                'estatus' => 'pendiente',
                'remitente_nombre' => $request->remitente['nombre'],
                'remitente_celular' => $request->remitente['celular'],
                'remitente_direccion' => $request->remitente['direccion'],
                'remitente_estado' => $request->remitente['estado'],
                'remitente_ciudad' => $request->remitente['ciudad'],
                'remitente_codigo_postal' => $request->remitente['codigo_postal'],
                'remitente_pais' => $request->remitente['pais'],
                'destinatario_nombre' => $request->destinatario['nombre'],
                'destinatario_celular' => $request->destinatario['celular'],
                'destinatario_direccion' => $request->destinatario['direccion'],
                'destinatario_estado' => $request->destinatario['estado'],
                'destinatario_ciudad' => $request->destinatario['ciudad'],
                'destinatario_codigo_postal' => $request->destinatario['codigo_postal'],
                'destinatario_pais' => $request->destinatario['pais'],
                'paquete_alto' => $request->paquete['alto'],
                'paquete_ancho' => $request->paquete['ancho'],
                'paquete_largo' => $request->paquete['largo'],
                'paquete_peso' => $request->paquete['peso'],
                'paquete_volumen_calculado' => $request->paquete['volumen_calculado'],
                'metadata' => $request->metadata,
                'fecha_cotizacion' => isset($request->metadata['fecha_cotizacion'])
                    ? Carbon::parse($request->metadata['fecha_cotizacion'])
                    : now(),
            ]);

            // Crear la sesión de Stripe
            $session = Session::create([
                'line_items' => [[
                    'price_data' => [
                        'product_data' => [
                            'name' => 'Envío - ' . $request->metadata['servicio'] ?? 'Envío Internacional',
                            'description' => $request->metadata['tipo_envio'] ?? 'Envío de paquete',
                        ],
                        'currency' => 'usd',
                        'unit_amount' => floatval($request->carga) * 100, // Stripe trabaja en centavos
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => env('STRIPE_SUCCESS_URL', 'https://www.transportes-mexico.com/success.html') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('STRIPE_CANCEL_URL', 'https://www.transportes-mexico.com/cancel.html'),
                'metadata' => [
                    'pedido_id' => $pedido->id,
                    'tipo_envio' => $request->metadata['tipo_envio'] ?? '',
                    'servicio' => $request->metadata['servicio'] ?? '',
                ],
            ]);

            // Actualizar el pedido con el session_id de Stripe
            $pedido->update([
                'stripe_session_id' => $session->id
            ]);

            Log::info('Sesión de Stripe creada exitosamente', [
                'pedido_id' => $pedido->id,
                'session_id' => $session->id,
                'amount' => $request->carga
            ]);

            return response()->json([
                'success' => true,
                'url' => $session->url,
                'session_id' => $session->id,
                'pedido_id' => $pedido->id,
                'message' => 'Sesión de pago creada exitosamente'
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Error de Stripe API: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'type' => $e->getStripeCode()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la sesión de pago: ' . $e->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error general al crear sesión de pago: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
            ], 500);
        }
    }
}
