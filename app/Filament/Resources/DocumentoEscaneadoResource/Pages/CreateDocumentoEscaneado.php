<?php

namespace App\Filament\Resources\DocumentoEscaneadoResource\Pages;

use App\Filament\Resources\DocumentoEscaneadoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Services\VisionApiService;
use Illuminate\Support\Facades\Storage;

class CreateDocumentoEscaneado extends CreateRecord
{
    protected static string $resource = DocumentoEscaneadoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Aseguramos valores por defecto para campos obligatorios
        $data['estado_procesamiento'] = $data['estado_procesamiento'] ?? 'pendiente';
        $data['requiere_revision'] = false;

        // Si hay archivo subido, extraemos metadatos
        if (!empty($data['archivo_original'])) {
            $rutaCompleta = storage_path('app/public/' . $data['archivo_original']);

            if (file_exists($rutaCompleta)) {
                // Extraemos metadatos del archivo
                $info = pathinfo($rutaCompleta);
                $data['nombre_archivo'] = $info['basename'] ?? '';
                $data['tipo_mime'] = mime_content_type($rutaCompleta) ?? '';
                $data['tamaño_archivo'] = filesize($rutaCompleta) ?? 0;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $documento = $this->record;

        // Verificamos si tenemos archivo para procesar
        if (!empty($documento->archivo_original)) {
            try {
                $visionService = app(VisionApiService::class);
                $rutaCompleta = storage_path('app/public/' . $documento->archivo_original);

                if (file_exists($rutaCompleta)) {
                    // Procesamos con OCR
                    $resultado = $visionService->procesarDocumento($rutaCompleta);

                    if ($resultado['success']) {
                        // Actualizamos el documento con los datos extraídos
                        $documento->update([
                            'estado_procesamiento' => 'procesado',
                            'folio' => $resultado['datos']['folio'] ?? null,
                            'fecha_documento' => $resultado['datos']['fecha_documento'] ?? null,
                            'remitente_nombre' => $resultado['datos']['remitente_nombre'] ?? null,
                            'remitente_telefono' => $resultado['datos']['remitente_telefono'] ?? null,
                            'remitente_direccion' => $resultado['datos']['remitente_direccion'] ?? null,
                            'remitente_colonia' => $resultado['datos']['remitente_colonia'] ?? null,
                            'remitente_ciudad' => $resultado['datos']['remitente_ciudad'] ?? null,
                            'remitente_estado' => $resultado['datos']['remitente_estado'] ?? null,
                            'remitente_cp' => $resultado['datos']['remitente_cp'] ?? null,
                            'remitente_pais' => $resultado['datos']['remitente_pais'] ?? 'US',
                            'destinatario_nombre' => $resultado['datos']['destinatario_nombre'] ?? null,
                            'destinatario_telefono' => $resultado['datos']['destinatario_telefono'] ?? null,
                            'destinatario_direccion' => $resultado['datos']['destinatario_direccion'] ?? null,
                            'destinatario_colonia' => $resultado['datos']['destinatario_colonia'] ?? null,
                            'destinatario_ciudad' => $resultado['datos']['destinatario_ciudad'] ?? null,
                            'destinatario_estado' => $resultado['datos']['destinatario_estado'] ?? null,
                            'destinatario_cp' => $resultado['datos']['destinatario_cp'] ?? null,
                            'destinatario_pais' => $resultado['datos']['destinatario_pais'] ?? 'MX',
                            'numero_cajas' => $resultado['datos']['numero_cajas'] ?? null,
                            'tipo_contenido' => $resultado['datos']['tipo_contenido'] ?? null,
                            'peso' => $resultado['datos']['peso'] ?? null,
                            'valor_asegurado' => $resultado['datos']['valor_asegurado'] ?? null,
                            'valor_declarado' => $resultado['datos']['valor_declarado'] ?? null,
                            'costo_flete' => $resultado['datos']['costo_flete'] ?? null,
                            'impuestos' => $resultado['datos']['impuestos'] ?? null,
                            'seguro_extra' => $resultado['datos']['seguro_extra'] ?? null,
                            'total' => $resultado['datos']['total'] ?? null,
                            'texto_raw' => $resultado['texto_completo'] ?? null,
                            'confianza_ocr' => json_encode($resultado['confianza_detallada'] ?? []),
                            'requiere_revision' => $resultado['confianza'] < 80,
                            'metadatos_vision' => json_encode($resultado['metadatos'] ?? [])
                        ]);

                        Notification::make()
                            ->title('¡Documento procesado exitosamente!')
                            ->body("OCR completado con {$resultado['confianza']}% de confianza. " .
                                   ($resultado['confianza'] < 80 ? 'Se requiere revisión manual.' : ''))
                            ->success()
                            ->persistent()
                            ->send();

                    } else {
                        // Error en el procesamiento
                        $documento->update([
                            'estado_procesamiento' => 'error',
                            'errores_procesamiento' => $resultado['error'] ?? 'Error desconocido en el procesamiento OCR',
                            'requiere_revision' => true
                        ]);

                        Notification::make()
                            ->title('Error en el procesamiento OCR')
                            ->body($resultado['error'] ?? 'Error desconocido')
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                } else {
                    Notification::make()
                        ->title('Archivo no encontrado')
                        ->body('No se pudo encontrar el archivo subido para procesar')
                        ->warning()
                        ->send();
                }

            } catch (\Exception $e) {
                // Error crítico
                $documento->update([
                    'estado_procesamiento' => 'error',
                    'errores_procesamiento' => 'Error crítico: ' . $e->getMessage(),
                    'requiere_revision' => true
                ]);

                Notification::make()
                    ->title('Error crítico en el procesamiento')
                    ->body('Excepción: ' . $e->getMessage())
                    ->danger()
                    ->persistent()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        // Redirigimos a la página de edición para permitir correcciones
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Documento guardado. Procesando con OCR...';
    }
}
