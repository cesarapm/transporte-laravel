<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    public function proxyTracking(Request $request)
    {
        // Validar el parámetro 'codigo'
        $request->validate([
            'codigo' => 'required|string'
        ]);

        // Buscar la guía con su historial ordenado
        $guia = Guia::where('guia_interna', $request->codigo)
            ->with(['historial' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->first();

        if (!$guia) {
            return response()->json(['message' => 'Guía no encontrada'], 404);
        }

        $trackingNumber = trim((string) $guia->rastreo);
        $carrierCode = trim((string) $guia->paqueteria);

        if ($trackingNumber && $carrierCode) {
            $apiKey = env('TRACKINGMORE_API_KEY');

            // $options = [
            //     'headers' => [
            //         'Tracking-Api-Key' => $apiKey,
            //         'Content-Type' => 'application/json'
            //     ],
            //     'verify' => env('APP_ENV') !== 'local'  // Deshabilitar la verificación SSL en local
            // ];

            // // Consultar tracking en TrackingMore
            // $trackingResponse = Http::withOptions($options)->get("https://api.trackingmore.com/v4/trackings/get?tracking_numbers={$trackingNumber}");
            // $trackingResponse = Http::withHeaders([
            //     'Authorization' => 'Bearer ' . $apiKey,
            //     'Content-Type' => 'application/json'
            // ])->get("https://api.trackingmore.com/v4/trackings/{$trackingNumber}");
            $trackingResponse = Http::withHeaders([
    'Tracking-Api-Key' => $apiKey,
    'Content-Type' => 'application/json'
])->get('https://api.trackingmore.com/v4/trackings/get', [
    'tracking_numbers' => $trackingNumber
]);




            Log::info("Informacion '{$trackingResponse}'");
            // Verificar si la respuesta fue exitosa
            if ($trackingResponse->successful()) {
                $data = $trackingResponse->json();
                $deliveryStatus = $data['data'][0]['delivery_status'] ?? null;
                Log::info("Consulta de Guia ID {$guia->id}: '{$trackingResponse}'");
                // Solo si está en tránsito
                Log::info("Estado '{$deliveryStatus}'");
                // Solo si el estado de la entrega es 'transit'
                if ($deliveryStatus === 'transit') {
                    return response()->json([
                        'tracking' => $data,
                        'historial' => $guia->historial
                    ]);
                }
            } else {
                Log::error('Error al consultar tracking: ', $trackingResponse->json());
            }
        }

        // Si no se consulta tracking o no está en tránsito, solo devolver el historial
        return response()->json([
            'historial' => $guia->historial
        ]);
    }
    public function evidencia($codigo)
    {
        $guia = Guia::with('evidencias')->where('guia_interna', $codigo)->first();

        if (!$guia) {
            return response()->json(['message' => 'No existe la guía'], 404);
        }

        return response()->json($guia);
    }
}
