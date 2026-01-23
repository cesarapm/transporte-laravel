<?php

namespace App\Filament\Resources\GuiaMindeeResource\Pages;

use App\Filament\Resources\GuiaMindeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewGuiaMindee extends ViewRecord
{
    protected static string $resource = GuiaMindeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Imagen del Documento')
                    ->schema([
                        Infolists\Components\ImageEntry::make('archivo_original')
                            ->label('Imagen')
                            ->columnSpanFull()
                            ->height(400),
                    ])
                    ->collapsible(),

                Infolists\Components\Group::make([
                    Infolists\Components\Section::make('Estado')
                        ->schema([
                            Infolists\Components\TextEntry::make('estado_procesamiento')
                                ->label('Estado')
                                ->badge()
                                ->color(fn ($record) => $record->estado_color),

                            Infolists\Components\TextEntry::make('confianza_promedio')
                                ->label('Confianza')
                                ->suffix('%'),

                            Infolists\Components\IconEntry::make('requiere_revision')
                                ->label('Requiere Revisión')
                                ->boolean(),

                            Infolists\Components\TextEntry::make('fecha_procesamiento')
                                ->label('Procesado el')
                                ->dateTime('d/m/Y H:i'),
                        ])
                        ->columns(2),

                    Infolists\Components\Section::make('Transportista')
                        ->schema([
                            Infolists\Components\TextEntry::make('carrier_name')
                                ->label('Nombre'),

                            Infolists\Components\TextEntry::make('manifest_number')
                                ->label('Número de Manifiesto')
                                ->copyable(),

                            Infolists\Components\TextEntry::make('carrier_address')
                                ->label('Dirección')
                                ->columnSpanFull(),

                            Infolists\Components\TextEntry::make('ship_date')
                                ->label('Fecha de Envío')
                                ->date('d/m/Y'),

                            Infolists\Components\TextEntry::make('tracking_number')
                                ->label('Número de Rastreo')
                                ->copyable(),
                        ])
                        ->columns(2),
                ])
                ->columnSpan(2),

                Infolists\Components\Group::make([
                    Infolists\Components\Section::make('Remitente')
                        ->schema([
                            Infolists\Components\TextEntry::make('shipper_name')
                                ->label('Nombre'),

                            Infolists\Components\TextEntry::make('ship_phone_number')
                                ->label('Teléfono'),

                            Infolists\Components\TextEntry::make('shipper_address')
                                ->label('Dirección')
                                ->columnSpanFull(),

                            Infolists\Components\TextEntry::make('shipper_city')
                                ->label('Ciudad'),

                            Infolists\Components\TextEntry::make('shipper_zip_code')
                                ->label('Código Postal'),
                        ])
                        ->columns(2),

                    Infolists\Components\Section::make('Destinatario')
                        ->schema([
                            Infolists\Components\TextEntry::make('consignee_name')
                                ->label('Nombre'),

                            Infolists\Components\TextEntry::make('consignee_country')
                                ->label('País'),

                            Infolists\Components\TextEntry::make('consignee_address')
                                ->label('Dirección')
                                ->columnSpanFull(),

                            Infolists\Components\TextEntry::make('consignee_city')
                                ->label('Ciudad'),

                            Infolists\Components\TextEntry::make('consignee_state')
                                ->label('Estado'),

                            Infolists\Components\TextEntry::make('consignee_zip_code')
                                ->label('Código Postal'),
                        ])
                        ->columns(2),

                    Infolists\Components\Section::make('Detalles del Envío')
                        ->schema([
                            Infolists\Components\TextEntry::make('total_packages')
                                ->label('Total de Paquetes'),

                            Infolists\Components\TextEntry::make('total_weight')
                                ->label('Peso Total')
                                ->suffix(fn ($record) => ' ' . ($record->weight_unit ?? '')),

                            Infolists\Components\TextEntry::make('shipper_freight_cost')
                                ->label('Costo de Flete')
                                ->money('USD'),

                            Infolists\Components\TextEntry::make('shipper_insured_value')
                                ->label('Valor Asegurado')
                                ->money('USD'),

                            Infolists\Components\TextEntry::make('items_descripcion')
                                ->label('Items')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->columnSpan(1),
            ])
            ->columns(3);
    }
}
