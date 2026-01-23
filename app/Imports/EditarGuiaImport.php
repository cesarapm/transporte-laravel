<?php
namespace App\Imports;


use App\Models\Guia;
use App\Models\GuiaHistorial;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EditarGuiaImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $guia = Guia::find($row['id']);

        if ($guia) {
            $cambios = [];

            if ($guia->paqueteria !== $row['paqueteria']) {
                $cambios[] = "Paquetería de '{$guia->paqueteria}' a '{$row['paqueteria']}'";
            }
            if ($guia->rastreo !== $row['rastreo']) {
                $cambios[] = "Rastreo de '{$guia->rastreo}' a '{$row['rastreo']}'";
            }

            if (!empty($cambios)) {
                $guia->update([
                    'paqueteria' => $row['paqueteria'],
                    'rastreo' => $row['rastreo'],
                    'estatus' => 'EM',
                ]);

                // GuiaHistorial::create([
                //     'guia_id' => $guia->id,
                //     'campo_modificado' => 'Entransito Mexico',
                //     'created_at' => now(),
                // ]);

                Log::info("Guía ID {$guia->id} actualizada: " . implode(', ', $cambios));

                // LLAMAR A TRACKINGMORE
                try {
                    $carrierCode = $row['paqueteria']; // Puedes usar un mapeo interno
                    $trackingNumber = $row['rastreo'];

                    $options = [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Tracking-Api-Key' => env('TRACKINGMORE_API_KEY') // Usa tu clave real en .env
                        ]
                    ];

                    $createResponse = Http::withOptions($options)->post('https://api.trackingmore.com/v4/trackings/create', [
                        'tracking_number' => $trackingNumber,
                        'courier_code' => $carrierCode
                    ]);

                    if ($createResponse->failed() && ($createResponse->json()['meta']['code'] ?? null) !== 4101) {
                        Log::error('Error al crear tracking: ', $createResponse->json());
                    } else {
                        Log::info("Tracking creado exitosamente para la guía ID {$createResponse}");
                    }
                } catch (\Throwable $e) {
                    Log::error("Excepción al crear tracking: " . $e->getMessage());
                }
            }
        } else {
            Log::warning("Guía ID {$row['id']} no encontrada. No se realizó ninguna edición.");
        }

        return $guia;
    }


}
