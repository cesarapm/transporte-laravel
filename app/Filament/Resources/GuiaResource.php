<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuiaResource\Pages;
use App\Filament\Resources\GuiaResource\RelationManagers;
use App\Filament\Resources\GuiaEvidenciasResource\RelationManagers\EvidenciasRelationManager;
use App\Models\Guia;
use App\Models\Remesa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GuiaImport;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ExportAction;
use App\Exports\GuiasExport;

use Filament\Notifications\Notification;

use ClickSend\Configuration;
use ClickSend\Api\SMSApi;
use ClickSend\Model\SmsMessage;
use ClickSend\Model\SmsMessageCollection;










class GuiaResource extends Resource
{
    protected static ?string $model = Guia::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Select::make('remesa_id')
                //     ->label('Remesa')
                //     ->options(Remesa::all()->pluck('folio', 'id'))
                //     ->searchable()
                //     ->required()
                //     ->reactive()
                //     ->afterStateUpdated(function (callable $set, $state) {
                //         // Generar el valor de 'guia_interna' basado en 'remesa_id'
                //         $set('guia_interna', self::generarGuiaInterna($state));
                //     }),
                TextInput::make('remesa')
                    ->label('Remesa')
                    ->required()
                    ->numeric(),

                TextInput::make('folio')
                    ->label('Folio')
                    ->numeric()
                    ->required(),

                TextInput::make('tel_remite')
                    ->label('Telefono de Remitente')
                    ->required()
                    ->numeric(),

                TextInput::make('npaquetes')
                    ->label('Número de Paquete')
                    ->required()
                    ->numeric(),

                TextInput::make('guia_interna')
                    ->label('Guía Interna')
                    ->visible(fn($state, $record) => $record !== null) // Solo mostrar en edición
                    ->disabled(), // Solo lectura, ya que se genera automáticamente
                // ->default(fn(callable $get) => self::generarGuiaInterna($get('remesa_id'))), // Valor por defecto
                DatePicker::make('fecha')
                    ->label('Fecha'),

                TimePicker::make('hora')
                    ->label('Hora'),


                Select::make('paqueteria')
                    ->label('Paqueteria')
                    ->options([
                        'estafetausa' => 'Estafeta',
                        'paquet' => 'Paquete Express',
                        'fedex' => 'FedEx',
                        'ups'  => 'UPS',


                    ])
                    ->visible(fn($state, $record) => $record !== null), // Solo mostrar en edición




                TextInput::make('rastreo')
                    ->label('Rastreo')
                    ->visible(fn($state, $record) => $record !== null) // Solo mostrar en edición
                    ->nullable() // Puede ser nulo
                    ->maxLength(255),



                Toggle::make('activo')
                    ->label('Activo')
                    ->visible(fn($state, $record) => $record !== null) // Solo mostrar en edición
                    ->default(true),


            ]);
    }
    // public static function generarGuiaInterna($remesaId)
    // {
    //     $remesa = Remesa::find($remesaId); // Obtener la remesa seleccionada
    //     $idGuia = (Guia::max('id') ?? 0) + 1; // Obtener el siguiente ID de la guía

    //     if ($remesa) {
    //         return "{$remesa->telefono_cliente}-{$remesa->folio}-{$idGuia}";
    //     }

    //     return "Seleccione una remesa"; // En caso de que no se haya seleccionado una remesa
    // }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('guia_interna')->label('Guía Interna'),
                Tables\Columns\TextColumn::make('remesa') // Utiliza la relación `remesa` y accede a la propiedad `folio`
                    ->label('Remesa'),
                Tables\Columns\TextColumn::make('folio') // Utiliza la relación `remesa` y accede a la propiedad `folio`
                    ->label('Folio'),
                // Tables\Columns\BooleanColumn::make('activo')->label('Activo'),
                Tables\Columns\TextColumn::make('paqueteria')
                    ->label('Paqueteria')
                    ->formatStateUsing(function ($state) {
                        $paqueteria = [
                            'estafetausa' => 'Estafeta',
                            'paquet' => 'Paquete Express',
                            'fedex' => 'FedEx',
                            'ups' => 'UPS',
                        ];

                        return $paqueteria[$state] ?? 'Desconocida'; // Si no existe la clave, retorna 'Desconocida'
                    }),
                //  Tables\Columns\TextColumn::make('estatus')->label('Estatus'),

                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'EM' => 'Pendiente de informacion Interna',
                            'TIE' => 'Guia Pendiente',
                            'TEM' => 'Tramite Aduanal',
                            'DOC' => 'Documentación Interna Mexico',
                            'transit' => 'En Tránsito México',
                            'delivered' => 'Entregado',
                            'pending' => 'Pendiente',
                            default => ucfirst($state),
                        };
                    })
                    ->sortable()
                    ->toggleable(),
            ])

            // ->headerActions([ // Aquí es donde agregas la acción al encabezado
            //     Tables\Actions\CreateAction::make()
            // ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('remesa')
                    ->label('Filtrar por Remesa')
                    ->options(
                        Guia::select('remesa') // Selecciona solo el campo remesa
                            ->distinct() // Asegúrate de obtener solo valores únicos

                            ->pluck('remesa', 'remesa') // La clave y el valor serán el mismo campo remesa
                    )
                    ->searchable(),
                Tables\Filters\SelectFilter::make('estatus')
                    ->label('Filtrar por Estatus')
                    ->options([
                        'EM' => 'Pendiente de informacion Interna',
                        'TIE' => 'Guia Penidente',
                        'TEM' => 'Tramite Aduanal',
                        'DOC' => 'Documentación Interna Mexico',
                        'transit' => 'En Tránsito México',
                        'delivered' => 'Entregado',
                        'pending' => 'Pendiente',
                    ])
                    ->searchable(),
                Tables\Filters\SelectFilter::make('guia_interna')
                    ->label('Filtrar por Guia')
                    ->options(
                        Guia::select('guia_interna') // Selecciona solo el campo remesa
                            ->distinct() // Asegúrate de obtener solo valores únicos

                            ->pluck('guia_interna', 'guia_interna') // La clave y el valor serán el mismo campo remesa
                    )
                    ->searchable()
            ])
            ->actions([
                Tables\Actions\Action::make('verRastreo')
                    ->label('Ver Rastreo')
                    ->url(fn($record) => route('rastreo.mostrar', ['numero' => $record->guia_interna]))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make(),  // Permite eliminar
            ])
            ->headerActions([ // Aquí agregamos la acción de exportación en el encabezado
                // Action::make('export_excel')
                //     ->label('Exportar Excel')
                //     ->action(function () {
                //         return Excel::download(new GuiasExport, 'guias.xlsx');
                //     }),
            ])
            ->bulkActions([
                BulkAction::make('export_excel')
                    ->label('Exportar a Excel')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        // Convertir los registros seleccionados a colección simple
                        $data = $records->map(fn($record) => [
                            'id' => $record->id,
                            'tel_remite' => $record->tel_remite,
                            'guia_interna' => $record->guia_interna,
                            'remesa' => $record->remesa,
                            'folio' => $record->folio,
                            'paqueteria' => $record->paqueteria,
                            'rastreo' => $record->rastreo,

                        ]);

                        // Descargar el archivo Excel
                        return Excel::download(new GuiasExport($data), 'registros.xlsx');
                    }),
                // BulkAction::make('delete')
                //     ->requiresConfirmation()
                //     ->action(fn(Collection $records) => $records->each->delete()),
                // BulkAction::make('toggleActivo')
                //     ->label('Activar/Desactivar')
                //     ->action(function (EloquentCollection $records) {
                //         foreach ($records as $record) {
                //             // Cambiar el valor de 'activo' para cada registro seleccionado
                //             $record->update(['activo' => !$record->activo]);
                //         }
                //     }),


                BulkAction::make('updateEstatus')
                    ->label('Actualizar Estatus')
                    ->form([
                        Select::make('estatus')
                            ->label('Estatus')
                            ->options([
                                'dorder state' => 'Estado Frontera',
                                'customs procedure' => 'Tramite Aduanal',
                                'internal documentation mexico' => 'Documentación Interna Mexico',
                                'transit' => 'En tránsito Mexico',
                                'delivered' => 'Entregado',

                            ])
                            ->required(),
                    ])

                    ->action(function (EloquentCollection $records, array $data) {
                        $estatusMensajes = [
                            'dorder state' => 'Estado Frontera',
                            'customs procedure' => 'Tramite Aduanal',
                            'internal documentation mexico' => 'Documentación Interna Mexico',
                            'transit' => 'En tránsito Mexico',
                            'delivered' => 'Entregado',
                        ];
                        foreach ($records as $record) {
                            $record->update(['estatus' => $data['estatus']]);
                            $record->historial()->create([
                                'guia_id' => $record->id, // Relaciona con la guía actual
                                'campo_modificado' => $estatusMensajes[$data['estatus']] ?? 'Estatus desconocido', // Mensaje basado en el estatus
                                'created_at' => now(),
                            ]);
                        }
                        Notification::make()
                            ->title('Estatus actualizado correctamente')
                            ->success()
                            ->send();
                    }),





                BulkAction::make('mandarMensaje')
                    ->label('Mandar Mensaje')
                    ->action(function (Collection $records) {
                        $config = Configuration::getDefaultConfiguration()
                            ->setUsername(env('CLICKSEND_USERNAME'))
                            ->setPassword(env('CLICKSEND_API_KEY'));

                        $apiInstance = new SMSApi(new \GuzzleHttp\Client(), $config);

                        foreach ($records as $record) {
                            if (!empty($record->tel_remite)) {
                                $telefono = $record->tel_remite;
                                $guia = $record->guia_interna;

                                $mensajeTexto = "Puedes rastrear tu paquete en: https://www.transportes-mexico.com/rastreo/?numero={$guia}";

                                $mensaje = new SmsMessage();
                                $mensaje->setBody($mensajeTexto);
                                $mensaje->setTo('+1' . $telefono); // Asegúrate que tenga formato correcto
                                $mensaje->setSource("sdk");

                                $mensajeCollection = new SmsMessageCollection();
                                $mensajeCollection->setMessages([$mensaje]);

                                try {
                                    $response = $apiInstance->smsSendPost($mensajeCollection);
                                    Log::info("SMS enviado a {$telefono}: " . json_encode($response));
                                } catch (\Exception $e) {
                                    Log::error("Error al enviar SMS a {$telefono}: " . $e->getMessage());
                                }
                            }
                        }

                        Notification::make()
                            ->title('Mensajes enviados')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),







                BulkAction::make('registrarTracking')
                    ->label('Registrar en TrackingMore')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            $carrierCode = $record->paqueteria;
                            $trackingNumber = $record->rastreo;

                            if (!$carrierCode || !$trackingNumber) {
                                Log::warning("Datos incompletos para el registro de tracking: {$record->id}");
                                continue;
                            }

                            // $options = [
                            //     'headers' => [
                            //         'Content-Type' => 'application/json',
                            //         'Tracking-Api-Key' => env('TRACKINGMORE_API_KEY'),
                            //     ]
                            // ];
                            $options = [
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'Authorization' => 'Bearer ' . env('TRACKINGMORE_API_KEY'),
                                ]
                            ];

                            // $createResponse = Http::withOptions($options)->post('https://api.trackingmore.com/v4/trackings/create', [
                            //     'tracking_number' => $trackingNumber,
                            //     'courier_code' => $carrierCode,
                            // ]);
                            $createResponse = Http::withOptions($options)
                                ->post('https://api.trackingmore.com/v4/trackings', [
                                    'tracking_number' => $trackingNumber,
                                    'courier_code' => $carrierCode,
                                ]);

                            if ($createResponse->failed() && ($createResponse->json()['meta']['code'] ?? null) !== 4101) {
                                Log::error("❌ Error al crear tracking para {$trackingNumber}: ", $createResponse->json());
                            } else {
                                Log::info("✅ Tracking creado para {$trackingNumber}");
                            }
                        }

                        Notification::make()
                            ->title('Tracking registrado')
                            ->body('Los registros fueron enviados a TrackingMore.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()


            ]);
        // ->exportActions([ // Aquí agregamos la acción de exportación
        //     ExportAction::make('exportToExcel') // Le damos un nombre a la acción
        //         ->label('Exportar a Excel') // Etiqueta para la acción
        //         ->action(function () {
        //             return Excel::download(new GuiasExport, 'guias.xlsx'); // Llama al exportador y genera el archivo
        //         }),
        // ])
        // ->actions([
        //     Action::make('export_excel')
        //         ->label('Exportar Excel')
        //         // ->icon('heroicon-o-download')
        //         ->action(function () {
        //             return Excel::download(new GuiasExport, 'guias.xlsx');
        //         }),
        // ]);
    }

    public static function getRelations(): array
    {
        return [
            EvidenciasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuias::route('/'),
            'create' => Pages\CreateGuia::route('/create'),
            'edit' => Pages\EditGuia::route('/{record}/edit'),
        ];
    }
}
