<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Guia;
use App\Models\GuiaHistorial;
use App\Models\Pedido;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;



class WebhookController extends Controller
{
    // public function trackingmore(Request $request)
    // {
    //     Log::info('Webhook TrackingMore recibido: ', $request->all());

    //     $data = $request->input('data');

    //     $trackingNumber = $data['tracking_number'] ?? null;
    //     $latestEvent = $data['latest_event'] ?? null;
    //     $deliveryStatus = $data['delivery_status'] ?? null;
    //     $latestCheckpointTime = $data['latest_checkpoint_time'] ?? now();

    //     if (!$trackingNumber || !$deliveryStatus) {
    //         Log::warning('Webhook recibido sin datos necesarios.');
    //         return response()->json(['message' => 'Datos incompletos'], 400);
    //     }

    //     // Buscar guía por número de rastreo
    //     $guias = Guia::where('rastreo', $trackingNumber)->get();

    //     if ($guias->isEmpty()) {
    //         Log::warning("Guías con rastreo {$trackingNumber} no encontradas.");
    //         return response()->json(['message' => 'Guías no encontradas'], 404);
    //     }

    //     // Mapear delivery_status de TrackingMore a tu sistema de estatus
    //     $nuevoEstatus = $deliveryStatus;

    //     // Actualizar todas las guías encontradas
    //     foreach ($guias as $guia) {
    //         // Verificar si cambió el estatus
    //         if ($guia->estatus !== $nuevoEstatus) {
    //             $guia->estatus = $nuevoEstatus;
    //             $guia->save();

    //             // Validar si ya existe un historial con ese mismo campo_modificado
    //             $eventoTexto = "TrackingMore - {$latestEvent}";

    //             $yaExisteHistorial = GuiaHistorial::where('guia_id', $guia->id)
    //                 ->where('campo_modificado', $eventoTexto)
    //                 ->exists();

    //             if (!$yaExisteHistorial) {
    //                 GuiaHistorial::create([
    //                     'guia_id' => $guia->id,
    //                     'campo_modificado' => $eventoTexto,
    //                     'created_at' => $latestCheckpointTime,
    //                 ]);

    //                 Log::info("Historial creado para guía ID {$guia->id}: '{$eventoTexto}'");
    //             } else {
    //                 Log::info("Evento ya registrado previamente para guía ID {$guia->id}: '{$eventoTexto}'");
    //             }

    //             Log::info("Actualización de guía ID {$guia->id}: Nuevo estatus '{$nuevoEstatus}', evento: '{$latestEvent}'");
    //         } else {
    //             Log::info("Guía ID {$guia->id} ya tiene estatus '{$nuevoEstatus}', sin cambios.");
    //         }
    //     }

    //     return response()->json(['message' => 'OK'], 200);

    // }
    public function trackingmore(Request $request)
{
    Log::info('Webhook RAW:', ['body' => $request->getContent()]);
    Log::info('Webhook Parsed:', $request->all());

    $trackings = $request->input('data.trackings');

    if (!$trackings || !is_array($trackings)) {
        Log::warning('Webhook sin trackings.');
        return response()->json(['message' => 'No trackings'], 200);
    }

    foreach ($trackings as $tracking) {

        $trackingNumber = $tracking['tracking_number'] ?? null;
        $latestEvent = $tracking['latest_event'] ?? null;
        $deliveryStatus = $tracking['delivery_status'] ?? null;
        $latestCheckpointTime = $tracking['latest_checkpoint_time'] ?? now();

        if (!$trackingNumber || !$deliveryStatus) {
            continue;
        }

        $guias = Guia::where('rastreo', $trackingNumber)->get();

        if ($guias->isEmpty()) {
            Log::warning("Guías con rastreo {$trackingNumber} no encontradas.");
            continue;
        }

        foreach ($guias as $guia) {

            if ($guia->estatus !== $deliveryStatus) {

                $guia->estatus = $deliveryStatus;
                $guia->save();

                $eventoTexto = "TrackingMore - {$latestEvent}";

                $yaExisteHistorial = GuiaHistorial::where('guia_id', $guia->id)
                    ->where('campo_modificado', $eventoTexto)
                    ->exists();

                if (!$yaExisteHistorial) {
                    GuiaHistorial::create([
                        'guia_id' => $guia->id,
                        'campo_modificado' => $eventoTexto,
                        'created_at' => $latestCheckpointTime,
                    ]);

                    Log::info("Historial creado para guía ID {$guia->id}");
                }
            }
        }
    }

    return response()->json(['message' => 'OK'], 200);
}


    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('stripe-signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        Log::info('Webhook Stripe recibido', [
            'headers' => $request->headers->all(),
            'payload_length' => strlen($payload)
        ]);

        try {
            if ($endpoint_secret) {
                // Verificar la firma del webhook en producción
                $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
            } else {
                // En desarrollo, solo decodificar el JSON
                $event = json_decode($payload, true);
            }
        } catch (SignatureVerificationException $e) {
            Log::error('Webhook Stripe: Firma inválida', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Firma inválida'], 400);
        } catch (\Exception $e) {
            Log::error('Webhook Stripe: Error al procesar payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Payload inválido'], 400);
        }

        Log::info('Evento Stripe procesado', [
            'type' => $event['type'],
            'id' => $event['id']
        ]);

        // Manejar el evento
        switch ($event['type']) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event['data']['object']);
                break;

            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event['data']['object']);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event['data']['object']);
                break;

            default:
                Log::info('Evento Stripe no manejado: ' . $event['type']);
        }

        return response()->json(['status' => 'success'], 200);
    }

    private function handleCheckoutSessionCompleted($session)
    {
        Log::info('Procesando checkout.session.completed', [
            'session_id' => $session['id'],
            'payment_status' => $session['payment_status']
        ]);

        $pedido = Pedido::where('stripe_session_id', $session['id'])->first();

        if (!$pedido) {
            Log::warning('Pedido no encontrado para session_id: ' . $session['id']);
            return;
        }

        // Actualizar el pedido con los datos de la sesión
        $pedido->update([
            'stripe_payment_intent' => $session['payment_intent'],
            'estatus' => $session['payment_status'] === 'paid' ? 'pagado' : 'pendiente'
        ]);

        Log::info("Pedido {$pedido->id} actualizado a estatus: {$pedido->estatus}");
    }

    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        Log::info('Procesando payment_intent.succeeded', [
            'payment_intent_id' => $paymentIntent['id']
        ]);

        $pedido = Pedido::where('stripe_payment_intent', $paymentIntent['id'])->first();

        if ($pedido) {
            $pedido->update(['estatus' => 'pagado']);
            Log::info("Pedido {$pedido->id} marcado como pagado");
        }
    }

    private function handlePaymentIntentFailed($paymentIntent)
    {
        Log::info('Procesando payment_intent.payment_failed', [
            'payment_intent_id' => $paymentIntent['id']
        ]);

        $pedido = Pedido::where('stripe_payment_intent', $paymentIntent['id'])->first();

        if ($pedido) {
            $pedido->update(['estatus' => 'fallido']);
            Log::info("Pedido {$pedido->id} marcado como fallido");
        }
    }
}
