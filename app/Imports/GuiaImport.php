<?php
namespace App\Imports;

use App\Models\Guia;
use App\Models\GuiaHistorial;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Log;

class GuiaImport implements ToModel, SkipsOnFailure
{
    use SkipsFailures;

    public $errores = []; // Almacenar errores para mostrarlos luego

    public function model(array $row)
    {
        try {
            // Generamos el valor de guia_interna
            $guia_interna = $row[1] . '-' . $row[2] . '-' . $row[3]; // Cambiar según tu lógica

            // Verificamos si ya existe la guia_interna en la base de datos
            $existingGuia = Guia::where('guia_interna', $guia_interna)->first();

            if ($existingGuia) {
                // Si ya existe, almacenamos el error y no intentamos guardarla
                $this->errores[] = "La guía interna {$guia_interna} ya existe en la base de datos.";
                Log::warning("Guía duplicada detectada: {$guia_interna}");

                // Opcional: Puedes retornar null para que Laravel Excel continúe sin detener el proceso
                return null;
            }

            // Intentamos guardar la guía si no existe
            $guia = new Guia([
                'remesa' => $row[0],
                'tel_remite' => $row[1],
                'folio' => $row[2],
                'npaquetes' => $row[3],
                'guia_interna' => $guia_interna, // Usamos el valor generado para guia_interna
            ]);

            // Si se guardó correctamente, guardamos el historial
            $guia->save(); // Guardar en la base de datos

            // Verificamos si se guardó correctamente
            // if ($guia->id) {
            //     GuiaHistorial::create([
            //         'guia_id' => $guia->id,
            //         'campo_modificado' => 'Capturado',
            //         'created_at' => now(),
            //     ]);
            // } else {
            //     throw new \Exception('No se pudo guardar la guía.');
            // }

            return $guia;
        } catch (\Exception $e) {
            // Si hay un error, lo almacenamos en el array $errores
            $this->errores[] = "Error en la fila {$row[0]}: " . $e->getMessage();
            Log::error('Error en la importación de guía: ' . $e->getMessage());

            // Devuelvo null para que Laravel Excel no detenga el proceso
            return null;
        }
    }

    public function onFailure(Failure ...$failures)
    {
        // Esta función captura los errores de las filas fallidas
        foreach ($failures as $failure) {
            // Almacenamos el error en el array $errores
            $this->errores[] = "Error en la fila {$failure->row()}: {$failure->errors()[0]}";
        }
    }
}
