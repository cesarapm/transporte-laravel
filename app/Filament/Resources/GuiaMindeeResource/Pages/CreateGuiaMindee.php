<?php

namespace App\Filament\Resources\GuiaMindeeResource\Pages;

use App\Filament\Resources\GuiaMindeeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use App\Models\GuiaMindee;

class CreateGuiaMindee extends CreateRecord
{
    protected static string $resource = GuiaMindeeResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // LOGS COMENTADOS PARA AHORRAR MEMORIA
        // Log::info('=== INICIANDO CREACIÓN DE GUÍAS ===', [
        //     'data' => $data,
        //     'archivo_original_tipo' => gettype($data['archivo_original'] ?? null)
        // ]);

        // Verificar si archivo_original es un array (múltiples archivos)
        $archivos = $data['archivo_original'] ?? [];

        // Si no es array, convertirlo a array
        if (!is_array($archivos)) {
            $archivos = [$archivos];
        }

        // Log::info('Archivos a procesar', [
        //     'total' => count($archivos),
        //     'archivos' => $archivos
        // ]);

        $registrosCreados = 0;
        $primeraGuia = null;

        foreach ($archivos as $index => $archivoRuta) {
            try {
                // Log::info("Procesando archivo {$index}", ['ruta' => $archivoRuta]);

                $rutaCompleta = Storage::disk('public')->path($archivoRuta);

                if (!file_exists($rutaCompleta)) {
                    // Log::error("Archivo no encontrado: {$rutaCompleta}");
                    continue;
                }

                $fileInfo = pathinfo($rutaCompleta);

                $datosGuia = [
                    'archivo_original' => $archivoRuta,
                    'nombre_archivo' => $fileInfo['basename'] ?? null,
                    'tipo_mime' => mime_content_type($rutaCompleta),
                    'tamaño_archivo' => filesize($rutaCompleta),
                    'estado_procesamiento' => 'pendiente',
                ];

                // Copiar otros campos del formulario si existen (excepto archivo_original)
                foreach ($data as $key => $value) {
                    if ($key !== 'archivo_original' && !isset($datosGuia[$key])) {
                        $datosGuia[$key] = $value;
                    }
                }

                $guia = GuiaMindee::create($datosGuia);

                // Log::info("Guía creada exitosamente", [
                //     'id' => $guia->id,
                //     'archivo' => $guia->archivo_original
                // ]);

                if ($primeraGuia === null) {
                    $primeraGuia = $guia;
                }

                $registrosCreados++;

            } catch (\Exception $e) {
                Log::error("Error creando guía para archivo {$index}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Log::info('Creación completada', [
        //     'total_archivos' => count($archivos),
        //     'registros_creados' => $registrosCreados
        // ]);

        if ($registrosCreados > 1) {
            Notification::make()
                ->title('Guías creadas')
                ->body("{$registrosCreados} guías fueron creadas exitosamente")
                ->success()
                ->send();
        }

        // Retornar la primera guía creada (requerido por Filament)
        return $primeraGuia ?? GuiaMindee::create([
            'archivo_original' => $data['archivo_original'][0] ?? null,
            'estado_procesamiento' => 'pendiente',
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Este método ya no se usa porque handleRecordCreation maneja todo
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Guía creada exitosamente';
    }
}
