<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    public function consultarTracking(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string'
        ]);

        // Buscar la guía en la base de datos con su historial
        $guia = Guia::where('guia_interna', $request->codigo)
            ->with('historial') // Cargar el historial relacionado
            ->first();

        if (!$guia) {
            return response()->json(['message' => 'Guía no encontrada'], 404);
        }

        $estatus = $guia->estatus;

        // Mapeo de mensajes según estatus
        $mensajes = [
            'TIE' => 'Estado Frontera',
            'TEM' => 'Tramite Aduanal',
            'DOC' => 'Documentación Interna Mexico',
            'ENT' => 'Pedido entregado',

        ];

        if (isset($mensajes[$estatus])) {
            return response()->json([
                'message' => $mensajes[$estatus],
                'historial' => $guia->historial
            ], 200);
        }

        // Si está en tránsito (EM), hacer la consulta externa
        if ($estatus === 'EM') {
            // Log::info("EM", ['estatus' => $estatus]);

            $apiKey = env('TRACKINGMORE_API_KEY');
            $trackingNumber = trim((string) $guia->rastreo);
            $carrierCode = trim((string) $guia->paqueteria);

            $options = [
                'headers' => [
                    'Tracking-Api-Key' => $apiKey,
                    'Content-Type' => 'application/json'
                ],
                'verify' => env('APP_ENV') !== 'local'
            ];

            // Intentar crear el tracking
            $createResponse = Http::withOptions($options)->post('https://api.trackingmore.com/v4/trackings/create', [
                'tracking_number' => $trackingNumber,
                'courier_code' => $carrierCode
            ]);

            // Log::info('Se creó el tracking: ', $createResponse->json());

            if ($createResponse->failed() && ($createResponse->json()['meta']['code'] ?? null) !== 4101) {
                Log::error('Error al crear tracking: ', $createResponse->json());
                // return response()->json(['message' => 'Error al crear tracking'], 400);
                return response()->json([
                    'message' =>'Esperando Datos de Paqueteria',
                    'historial' => $guia->historial
                ], 200);
            }

            // Consultar el tracking
            $trackingResponse = Http::withOptions($options)->get("https://api.trackingmore.com/v4/trackings/get?tracking_numbers={$trackingNumber}");

            if (!$trackingResponse->successful()) {
                Log::error('Error al consultar tracking: ', $trackingResponse->json());
                return response()->json(['message' => 'Error al consultar tracking'], 400);
            }

            return response()->json([
                'tracking' => $trackingResponse->json(),
                'historial' => $guia->historial,
                'message' =>'En tránsito Mexico'
            ]);
        }
    }
}
