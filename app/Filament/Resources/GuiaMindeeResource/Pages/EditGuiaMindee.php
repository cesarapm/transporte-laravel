<?php

namespace App\Filament\Resources\GuiaMindeeResource\Pages;

use App\Filament\Resources\GuiaMindeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditGuiaMindee extends EditRecord
{
    protected static string $resource = GuiaMindeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),

            Actions\Action::make('reescanear')
                ->label('Re-escanear')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('¿Re-escanear documento?')
                ->modalDescription('Esto sobrescribirá los datos actuales con nueva información de Mindee')
                ->action(function () {
                    try {
                        $mindeeService = app(\App\Services\MindeeApiService::class);
                        $rutaArchivo = Storage::disk('public')->path($this->record->archivo_original);

                        if (!file_exists($rutaArchivo)) {
                            throw new \Exception('Archivo no encontrado');
                        }

                        $resultado = $mindeeService->procesarDocumento($rutaArchivo);

                        if ($resultado['success']) {
                            $datos = $resultado['datos'];

                            $this->record->update([
                                'carrier_name' => $datos['carrier_name'],
                                'carrier_address' => $datos['carrier_address'],
                                'manifest_number' => $datos['manifest_number'],
                                'folio_invoice_number' => $datos['folio_invoice_number'],
                                'ship_date' => $datos['ship_date'],
                                'shipper_name' => $datos['shipper_name'],
                                'shipper_address' => $datos['shipper_address'],
                                'shipper_city' => $datos['shipper_city'],
                                'shipper_suburb' => $datos['shipper_suburb'],
                                'shipper_zip_code' => $datos['shipper_zip_code'],
                                'ship_phone_number' => $datos['ship_phone_number'],
                                'consignee_name' => $datos['consignee_name'],
                                'consignee_address' => $datos['consignee_address'],
                                'consignee_colonia' => $datos['consignee_colonia'],
                                'consignee_city' => $datos['consignee_city'],
                                'consignee_state' => $datos['consignee_state'],
                                'consignee_zip_code' => $datos['consignee_zip_code'],
                                'consignee_country' => $datos['consignee_country'],
                                'total_packages' => $datos['total_packages'],
                                'shipper_box_count' => $datos['shipper_box_count'],
                                'total_weight' => $datos['total_weight'],
                                'weight_unit' => $datos['weight_unit'],
                                'shipper_freight_cost' => $datos['shipper_freight_cost'],
                                'shipper_insured_value' => $datos['shipper_insured_value'],
                                'item_categories' => $datos['item_categories'],
                                'shipment_line_items' => $datos['shipment_line_items'],
                                'tracking_number' => $datos['tracking_number'],
                                'texto_raw' => $resultado['texto_completo'],
                                'datos_json' => $resultado['respuesta_completa'] ?? null,
                                'confianza_promedio' => $resultado['confianza'] * 100,
                                'estado_procesamiento' => 'procesado',
                                'fecha_procesamiento' => now(),
                                'procesado_por' => auth()->id(),
                                'requiere_revision' => $resultado['confianza'] < 0.85,
                                'error_mensaje' => null,
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Re-escaneo exitoso')
                                ->success()
                                ->send();

                            return redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                        } else {
                            throw new \Exception($resultado['error'] ?? 'Error desconocido');
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error al re-escanear')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Guía actualizada exitosamente';
    }
}
