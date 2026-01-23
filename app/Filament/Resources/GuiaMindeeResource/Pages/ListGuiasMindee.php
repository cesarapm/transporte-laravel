<?php

namespace App\Filament\Resources\GuiaMindeeResource\Pages;

use App\Filament\Resources\GuiaMindeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions\Action;
use Illuminate\Support\Facades\Storage;

class ListGuiasMindee extends ListRecords
{
    protected static string $resource = GuiaMindeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Guía (Sube Múltiples Aquí)')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('success')
                ->modalHeading('Nueva Guía - Sube Una o Múltiples Imágenes')
                ->modalDescription('✅ MÉTODO RECOMENDADO: Puedes subir 1 o hasta 30 imágenes desde aquí. Espera a que todas terminen de cargar antes de hacer click en Crear.')
                ->createAnother(false),

            Action::make('exportar_google_drive')
                ->label('Exportar lista de Google Drive')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Exportar de Google Drive')
                ->modalDescription('¿Deseas exportar la lista de escaneo de Google Drive?')
                ->modalSubmitActionLabel('Exportar')
                ->action(function () {
                    try {
                        // Mostrar notificación de inicio
                        \Filament\Notifications\Notification::make()
                            ->info()
                            ->title('Exportando...')
                            ->body('Por favor espera, se está exportando la lista de escaneo de Google Drive.')
                            ->send();

                        // Hacer el GET request al webhook de n8n
                        $response = \Illuminate\Support\Facades\Http::timeout(60)->get('https://n8n.webgeoapm.com/webhook/48f905ab-06fe-4539-b026-7df95c8b55af');

                        if ($response->successful()) {
                            // Notificación de éxito
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('✅ Exportación completada')
                                ->body('La lista de escaneo tarda unos minutos en verse reflejado espere 5 a 10 minutos. Se te enviara un email a tu correo cuando la tarea haya terminado.')
                                ->persistent()
                                ->send();
                        } else {
                            throw new \Exception('El servidor respondió con código: ' . $response->status());
                        }

                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('❌ Error en la exportación')
                            ->body('Ocurrió un error al exportar: ' . $e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }),

            /* DESACTIVADO TEMPORALMENTE POR BUG DE FILAMENT
            Action::make('subir_multiples')
                ->label('Subir Múltiples')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('info')
                ->modalHeading('Subir Múltiples Guías')
                ->modalDescription('Sube varias imágenes de guías. IMPORTANTE: Espera a que todas las imágenes se carguen antes de hacer click en Subir.')
                ->modalWidth('2xl')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('archivos')
                        ->label('Imágenes de Guías')
                        ->multiple()
                        ->image()
                        ->directory('guias_mindee')
                        ->visibility('public')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                        ->maxSize(10240)
                        ->maxFiles(50)
                        ->reorderable()
                        ->panelLayout('grid')
                        ->imagePreviewHeight('250')
                        ->loadingIndicatorPosition('center')
                        ->removeUploadedFileButtonPosition('center')
                        ->uploadProgressIndicatorPosition('center')
                        ->helperText('⚠️ IMPORTANTE: Espera a que TODAS las imágenes digan "Upload complete" en verde.')
                        ->required(),

                    \Filament\Forms\Components\Section::make()
                        ->schema([
                            \Filament\Forms\Components\TextInput::make('cantidad_esperada')
                                ->label('📝 ¿Cuántas imágenes SELECCIONASTE?')
                                ->helperText('Escribe el número de imágenes que acabas de seleccionar. Ejemplo: si seleccionaste 30 archivos, escribe 30')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(50)
                                ->required()
                                ->placeholder('Ejemplo: 30')
                                ->suffix('imágenes')
                                ->live(),

                            \Filament\Forms\Components\Placeholder::make('advertencia')
                                ->hiddenLabel()
                                ->content(function ($get) {
                                    $esperadas = (int)($get('cantidad_esperada') ?? 0);
                                    if ($esperadas > 0) {
                                        return new \Illuminate\Support\HtmlString(
                                            '<div style="background: #fef3c7; color: #92400e; padding: 15px; border-radius: 8px; text-align: center; border: 2px solid #f59e0b;">
                                                ⚠️ <strong>DEBES VER EXACTAMENTE ' . $esperadas . ' MINIATURAS ARRIBA</strong><br>
                                                Si ves menos, ESPERA o cierra y vuelve a abrir este modal<br>
                                                Todas deben decir "Upload complete" en verde
                                            </div>'
                                        );
                                    }
                                    return '';
                                }),
                        ])
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $cantidad_esperada = (int)($data['cantidad_esperada'] ?? 0);
                    $archivos = $data['archivos'] ?? [];
                    $archivos_recibidos = is_array($archivos) ? count($archivos) : 0;

                    \Illuminate\Support\Facades\Log::info('=== SUBIR MÚLTIPLES GUÍAS INICIADO ===', [
                        'cantidad_esperada' => $cantidad_esperada,
                        'archivos_recibidos' => $archivos_recibidos,
                        'archivos_tipo' => gettype($archivos),
                    ]);

                    // VALIDACIÓN: Verificar que se recibieron todos los archivos
                    if ($archivos_recibidos < $cantidad_esperada) {
                        \Illuminate\Support\Facades\Log::warning('⚠️ NO SE RECIBIERON TODOS LOS ARCHIVOS', [
                            'esperadas' => $cantidad_esperada,
                            'recibidas' => $archivos_recibidos,
                            'faltantes' => $cantidad_esperada - $archivos_recibidos
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('⚠️ Error: Archivos Incompletos')
                            ->body("Seleccionaste {$cantidad_esperada} archivos pero solo se recibieron {$archivos_recibidos}. CIERRA este modal, vuelve a abrirlo y espera más tiempo antes de hacer click en Subir.")
                            ->persistent()
                            ->send();

                        throw new \Exception("Solo se recibieron {$archivos_recibidos} de {$cantidad_esperada} archivos. Intenta de nuevo.");
                    }

                    $creados = 0;
                    $fallidos = 0;
                    $errores = [];

                    \Illuminate\Support\Facades\Log::info('Archivos a procesar', [
                        'total' => count($archivos),
                        'archivos' => $archivos
                    ]);

                    foreach ($archivos as $index => $archivo) {
                        try {
                            \Illuminate\Support\Facades\Log::info("Procesando archivo {$index}", [
                                'archivo' => $archivo,
                                'ruta_relativa' => $archivo
                            ]);

                            $rutaCompleta = Storage::disk('public')->path($archivo);

                            \Illuminate\Support\Facades\Log::info("Verificando archivo {$index}", [
                                'ruta_completa' => $rutaCompleta,
                                'existe' => file_exists($rutaCompleta),
                                'tamaño' => file_exists($rutaCompleta) ? filesize($rutaCompleta) : 'N/A'
                            ]);

                            if (!file_exists($rutaCompleta)) {
                                throw new \Exception("Archivo no encontrado: {$rutaCompleta}");
                            }

                            $fileInfo = pathinfo($rutaCompleta);

                            $guia = \App\Models\GuiaMindee::create([
                                'archivo_original' => $archivo,
                                'nombre_archivo' => $fileInfo['basename'] ?? null,
                                'tipo_mime' => mime_content_type($rutaCompleta),
                                'tamaño_archivo' => filesize($rutaCompleta),
                                'estado_procesamiento' => 'pendiente',
                            ]);

                            \Illuminate\Support\Facades\Log::info("Guía creada exitosamente", [
                                'id' => $guia->id,
                                'archivo' => $guia->archivo_original,
                                'nombre' => $guia->nombre_archivo
                            ]);

                            $creados++;
                        } catch (\Exception $e) {
                            $mensaje = $e->getMessage();
                            \Illuminate\Support\Facades\Log::error("Error creando guía para archivo {$index}", [
                                'archivo' => $archivo,
                                'error' => $mensaje,
                                'trace' => $e->getTraceAsString()
                            ]);
                            $errores[] = "Archivo " . ($index + 1) . ": {$mensaje}";
                            $fallidos++;
                        }
                    }

                    \Illuminate\Support\Facades\Log::info('=== SUBIR MÚLTIPLES COMPLETADO ===', [
                        'total_archivos' => count($archivos),
                        'creados' => $creados,
                        'fallidos' => $fallidos,
                        'errores' => $errores
                    ]);

                    $mensaje = "{$creados} guías subidas exitosamente";
                    if ($fallidos > 0) {
                        $mensaje .= " | {$fallidos} fallaron";
                        if (!empty($errores)) {
                            $mensaje .= "\n\nErrores:\n" . implode("\n", array_slice($errores, 0, 5));
                            if (count($errores) > 5) {
                                $mensaje .= "\n... y " . (count($errores) - 5) . " más. Revisa los logs.";
                            }
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Carga completada')
                        ->body($mensaje)
                        ->success()
                        ->send();
                }),
            */
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GuiaMindeeResource\Widgets\GuiasMindeeStatsWidget::class,
        ];
    }
}
