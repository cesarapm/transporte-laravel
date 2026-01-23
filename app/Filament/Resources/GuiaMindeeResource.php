<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuiaMindeeResource\Pages;
use App\Models\GuiaMindee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Services\MindeeApiService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GuiasMindeeExport;

class GuiaMindeeResource extends Resource
{
    protected static ?string $model = GuiaMindee::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationLabel = 'Guías Mindee';

    protected static ?string $modelLabel = 'Guía Mindee';

    protected static ?string $pluralModelLabel = 'Guías Mindee';

    protected static ?string $navigationGroup = 'Escaneo de Documentos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Información')
                    ->tabs([
                        // Tab 1: Archivo y Estado
                        Forms\Components\Tabs\Tab::make('Archivo')
                            ->icon('heroicon-o-document')
                            ->schema([
                                Forms\Components\Section::make('Subir Documento')
                                    ->description('Sube una imagen de la guía de envío para escanearla con Mindee')
                                    ->schema([
                                        Forms\Components\FileUpload::make('archivo_original')
                                            ->label('Imagen de la Guía')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('guias_mindee')
                                            ->visibility('public')
                                            ->required()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                            ->maxSize(10240)
                                            ->downloadable()
                                            ->preserveFilenames()
                                            ->deletable(true)
                                            ->multiple(fn ($livewire) => $livewire instanceof \App\Filament\Resources\GuiaMindeeResource\Pages\CreateGuiaMindee)
                                            ->maxFiles(fn ($livewire) => $livewire instanceof \App\Filament\Resources\GuiaMindeeResource\Pages\CreateGuiaMindee ? 50 : 1)
                                            ->reorderable(fn ($livewire) => $livewire instanceof \App\Filament\Resources\GuiaMindeeResource\Pages\CreateGuiaMindee)
                                            ->imagePreviewHeight('150')
                                            ->panelLayout('grid')
                                            ->helperText(fn ($livewire) => $livewire instanceof \App\Filament\Resources\GuiaMindeeResource\Pages\CreateGuiaMindee
                                                ? '✅ Puedes subir hasta 50 imágenes de una vez. Espera a que todas terminen de cargar antes de hacer click en Crear.'
                                                : 'Edita la imagen de esta guía (solo una imagen por registro).')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Información del Archivo')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('nombre_archivo')
                                                    ->label('Nombre del Archivo')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('tipo_mime')
                                                    ->label('Tipo')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('tamaño_formateado')
                                                    ->label('Tamaño')
                                                    ->disabled(),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Estado del Procesamiento')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Select::make('estado_procesamiento')
                                                    ->label('Estado')
                                                    ->options([
                                                        'pendiente' => 'Pendiente',
                                                        'procesado' => 'Procesado',
                                                        'error' => 'Error',
                                                        'verificado' => 'Verificado',
                                                    ])
                                                    ->required()
                                                    ->native(false)
                                                    ->default('pendiente'),

                                                Forms\Components\TextInput::make('confianza_promedio')
                                                    ->label('Confianza Promedio')
                                                    ->suffix('%')
                                                    ->disabled()
                                                    ->numeric(),

                                                Forms\Components\Toggle::make('requiere_revision')
                                                    ->label('Requiere Revisión')
                                                    ->default(false),
                                            ]),

                                        Forms\Components\Textarea::make('error_mensaje')
                                            ->label('Mensaje de Error')
                                            ->rows(2)
                                            ->disabled()
                                            ->visible(fn ($record) => $record?->estado_procesamiento === 'error'),
                                    ]),
                            ]),

                        // Tab 2: Transportista
                        Forms\Components\Tabs\Tab::make('Transportista')
                            ->icon('heroicon-o-truck')
                            ->schema([
                                Forms\Components\Section::make('Datos del Transportista')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('carrier_name')
                                                    ->label('Nombre del Transportista')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('manifest_number')
                                                    ->label('Número de Manifiesto')
                                                    ->maxLength(255),

                                                Forms\Components\Textarea::make('carrier_address')
                                                    ->label('Dirección del Transportista')
                                                    ->rows(2)
                                                    ->columnSpanFull(),

                                                Forms\Components\TextInput::make('folio_invoice_number')
                                                    ->label('Folio / Número de Factura')
                                                    ->maxLength(255),

                                                Forms\Components\DatePicker::make('ship_date')
                                                    ->label('Fecha de Envío')
                                                    ->native(false),

                                                Forms\Components\TextInput::make('tracking_number')
                                                    ->label('Número de Rastreo')
                                                    ->maxLength(255),
                                            ]),
                                    ]),
                            ]),

                        // Tab 3: Remitente
                        Forms\Components\Tabs\Tab::make('Remitente')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Section::make('Datos del Remitente')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('shipper_name')
                                                    ->label('Nombre')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('ship_phone_number')
                                                    ->label('Teléfono')
                                                    ->tel()
                                                    ->maxLength(255),

                                                Forms\Components\Textarea::make('shipper_address')
                                                    ->label('Dirección')
                                                    ->rows(2)
                                                    ->columnSpanFull(),

                                                Forms\Components\TextInput::make('shipper_suburb')
                                                    ->label('Colonia/Suburbio')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('shipper_city')
                                                    ->label('Ciudad')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('shipper_state')
                                                    ->label('Estado')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('shipper_zip_code')
                                                    ->label('Código Postal')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('shipper_country')
                                                    ->label('País')
                                                    ->maxLength(255),
                                            ]),
                                    ]),
                            ]),

                        // Tab 4: Destinatario
                        Forms\Components\Tabs\Tab::make('Destinatario')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\Section::make('Datos del Destinatario')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('consignee_name')
                                                    ->label('Nombre')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('consignee_phone_number')
                                                    ->label('Teléfono')
                                                    ->tel()
                                                    ->maxLength(255),

                                                Forms\Components\Textarea::make('consignee_address')
                                                    ->label('Dirección')
                                                    ->rows(2)
                                                    ->columnSpanFull(),

                                                Forms\Components\TextInput::make('consignee_colonia')
                                                    ->label('Colonia')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('consignee_city')
                                                    ->label('Ciudad')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('consignee_state')
                                                    ->label('Estado')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('consignee_zip_code')
                                                    ->label('Código Postal')
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('consignee_country')
                                                    ->label('País')
                                                    ->maxLength(255),
                                            ]),
                                    ]),
                            ]),

                        // Tab 5: Información del Envío
                        Forms\Components\Tabs\Tab::make('Envío')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Forms\Components\Section::make('Detalles del Envío')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('total_packages')
                                                    ->label('Total de Paquetes')
                                                    ->numeric()
                                                    ->default(1),

                                                Forms\Components\TextInput::make('shipper_box_count')
                                                    ->label('Número de Cajas')
                                                    ->numeric(),

                                                Forms\Components\TextInput::make('total_weight')
                                                    ->label('Peso Total')
                                                    ->numeric()
                                                    ->suffix(fn ($get) => $get('weight_unit') ?? ''),

                                                Forms\Components\TextInput::make('weight_unit')
                                                    ->label('Unidad de Peso')
                                                    ->maxLength(50),

                                                Forms\Components\TextInput::make('shipper_freight_cost')
                                                    ->label('Costo de Flete')
                                                    ->numeric()
                                                    ->prefix('$'),

                                                Forms\Components\TextInput::make('shipper_insured_value')
                                                    ->label('Valor Asegurado')
                                                    ->numeric()
                                                    ->prefix('$'),
                                            ]),
                                    ]),

                                Forms\Components\Section::make('Items del Envío')
                                    ->schema([
                                        Forms\Components\TagsInput::make('item_categories')
                                            ->label('Categorías de Items')
                                            ->placeholder('Ej: Ropa, Zapatos, Electrónicos')
                                            ->columnSpanFull(),

                                        Forms\Components\Repeater::make('shipment_line_items')
                                            ->label('Detalle de Items')
                                            ->schema([
                                                Forms\Components\Grid::make(4)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('description')
                                                            ->label('Descripción')
                                                            ->columnSpan(2),

                                                        Forms\Components\TextInput::make('quantity')
                                                            ->label('Cantidad')
                                                            ->numeric(),

                                                        Forms\Components\TextInput::make('weight')
                                                            ->label('Peso')
                                                            ->numeric(),
                                                    ]),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->defaultItems(0),
                                    ])
                                    ->collapsible(),
                            ]),

                        // Tab 6: Datos Técnicos
                        Forms\Components\Tabs\Tab::make('Datos Técnicos')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                Forms\Components\Section::make('Texto Extraído')
                                    ->schema([
                                        Forms\Components\Textarea::make('texto_raw')
                                            ->label('Texto OCR Completo')
                                            ->rows(6)
                                            ->disabled()
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),

                                Forms\Components\Section::make('JSON de Respuesta')
                                    ->schema([
                                        Forms\Components\Textarea::make('datos_json')
                                            ->label('Datos JSON de Mindee')
                                            ->rows(10)
                                            ->disabled()
                                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('archivo_original')
                    ->label('Imagen')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),

                Tables\Columns\TextColumn::make('manifest_number')
                    ->label('Folio/Manifiesto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->getStateUsing(fn ($record) => $record->manifest_number ?? $record->folio_invoice_number ?? '-'),

                Tables\Columns\TextColumn::make('ship_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('carrier_name')
                    ->label('Transportista')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->carrier_name)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('shipper_name')
                    ->label('Remitente')
                    ->searchable()
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('consignee_name')
                    ->label('Destinatario')
                    ->searchable()
                    ->limit(25)
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('consignee_city')
                    ->label('Ciudad Destino')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_packages')
                    ->label('Paquetes')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('shipper_freight_cost')
                    ->label('Costo Flete')
                    ->money('USD')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('estado_procesamiento')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'procesado',
                        'danger' => 'error',
                        'info' => 'verificado',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pendiente',
                        'heroicon-o-check-circle' => 'procesado',
                        'heroicon-o-x-circle' => 'error',
                        'heroicon-o-shield-check' => 'verificado',
                    ]),

                Tables\Columns\IconColumn::make('requiere_revision')
                    ->label('Revisión')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('confianza_promedio')
                    ->label('Confianza')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($state) => $state < 70 ? 'danger' : ($state < 85 ? 'warning' : 'success')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado_procesamiento')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'procesado' => 'Procesado',
                        'error' => 'Error',
                        'verificado' => 'Verificado',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('requiere_revision')
                    ->label('Requiere Revisión')
                    ->placeholder('Todos')
                    ->trueLabel('Sí')
                    ->falseLabel('No'),

                Tables\Filters\Filter::make('ship_date')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'], fn ($q) => $q->whereDate('ship_date', '>=', $data['desde']))
                            ->when($data['hasta'], fn ($q) => $q->whereDate('ship_date', '<=', $data['hasta']));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('escanear')
                    ->label('Escanear')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('primary')
                    ->visible(fn ($record) => $record->estado_procesamiento === 'pendiente')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        try {
                            $mindeeService = app(MindeeApiService::class);
                            $rutaArchivo = Storage::disk('public')->path($record->archivo_original);

                            if (!file_exists($rutaArchivo)) {
                                throw new \Exception('Archivo no encontrado');
                            }

                            $resultado = $mindeeService->procesarDocumento($rutaArchivo);

                            if ($resultado['success']) {
                                $datos = $resultado['datos'];

                                $record->update([
                                    // Transportista
                                    'carrier_name' => $datos['carrier_name'],
                                    'carrier_address' => $datos['carrier_address'],
                                    'manifest_number' => $datos['manifest_number'],
                                    'folio_invoice_number' => $datos['folio_invoice_number'],
                                    'ship_date' => $datos['ship_date'],

                                    // Remitente
                                    'shipper_name' => $datos['shipper_name'],
                                    'shipper_address' => $datos['shipper_address'],
                                    'shipper_city' => $datos['shipper_city'],
                                    'shipper_suburb' => $datos['shipper_suburb'],
                                    'shipper_zip_code' => $datos['shipper_zip_code'],
                                    'shipper_state' => $datos['shipper_state'] ?? null,
                                    'shipper_country' => $datos['shipper_country'] ?? null,
                                    'ship_phone_number' => $datos['ship_phone_number'],

                                    // Destinatario
                                    'consignee_name' => $datos['consignee_name'],
                                    'consignee_address' => $datos['consignee_address'],
                                    'consignee_colonia' => $datos['consignee_colonia'],
                                    'consignee_city' => $datos['consignee_city'],
                                    'consignee_state' => $datos['consignee_state'],
                                    'consignee_zip_code' => $datos['consignee_zip_code'],
                                    'consignee_country' => $datos['consignee_country'],
                                    'consignee_phone_number' => $datos['consignee_phone_number'] ?? null,

                                    // Envío
                                    'total_packages' => $datos['total_packages'],
                                    'shipper_box_count' => $datos['shipper_box_count'],
                                    'total_weight' => $datos['total_weight'],
                                    'weight_unit' => $datos['weight_unit'],

                                    // Costos
                                    'shipper_freight_cost' => $datos['shipper_freight_cost'],
                                    'shipper_insured_value' => $datos['shipper_insured_value'],

                                    // Items
                                    'item_categories' => $datos['item_categories'],
                                    'shipment_line_items' => $datos['shipment_line_items'],

                                    // Tracking
                                    'tracking_number' => $datos['tracking_number'],

                                    // Procesamiento
                                    'texto_raw' => $resultado['texto_completo'],
                                    'datos_json' => $resultado['respuesta_completa'] ?? null,
                                    'confianza_promedio' => $resultado['confianza'] * 100,
                                    'estado_procesamiento' => 'procesado',
                                    'fecha_procesamiento' => now(),
                                    'procesado_por' => Auth::id(),
                                    'requiere_revision' => $resultado['confianza'] < 0.85,
                                ]);

                                Notification::make()
                                    ->title('Documento escaneado exitosamente')
                                    ->success()
                                    ->send();
                            } else {
                                $record->update([
                                    'estado_procesamiento' => 'error',
                                    'error_mensaje' => $resultado['error'] ?? 'Error desconocido',
                                ]);

                                Notification::make()
                                    ->title('Error al escanear documento')
                                    ->body($resultado['error'] ?? 'Error desconocido')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            $record->update([
                                'estado_procesamiento' => 'error',
                                'error_mensaje' => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('escanear_multiples')
                        ->label('Escanear Seleccionados')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $mindeeService = app(MindeeApiService::class);
                            $exitosos = 0;
                            $fallidos = 0;
                            $errores = [];

                            \Log::info('Iniciando escaneo masivo', ['total_registros' => $records->count()]);

                            foreach ($records as $index => $record) {
                                \Log::info("Procesando registro {$index}", [
                                    'id' => $record->id,
                                    'archivo' => $record->archivo_original,
                                    'estado' => $record->estado_procesamiento
                                ]);

                                if ($record->estado_procesamiento !== 'pendiente') {
                                    \Log::warning("Registro {$record->id} omitido - estado: {$record->estado_procesamiento}");
                                    continue;
                                }

                                try {
                                    $rutaArchivo = Storage::disk('public')->path($record->archivo_original);

                                    if (!file_exists($rutaArchivo)) {
                                        $mensaje = "Archivo no encontrado: {$rutaArchivo}";
                                        \Log::error($mensaje, ['id' => $record->id]);
                                        $record->update([
                                            'estado_procesamiento' => 'error',
                                            'error_mensaje' => $mensaje,
                                        ]);
                                        $errores[] = "ID {$record->id}: Archivo no encontrado";
                                        $fallidos++;
                                        continue;
                                    }

                                    \Log::info("Procesando con Mindee", ['id' => $record->id, 'archivo' => $rutaArchivo]);
                                    $resultado = $mindeeService->procesarDocumento($rutaArchivo);

                                    if ($resultado['success']) {
                                        $datos = $resultado['datos'];

                                        $record->update([
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
                                            'shipper_state' => $datos['shipper_state'] ?? null,
                                            'shipper_country' => $datos['shipper_country'] ?? null,
                                            'ship_phone_number' => $datos['ship_phone_number'],
                                            'consignee_name' => $datos['consignee_name'],
                                            'consignee_address' => $datos['consignee_address'],
                                            'consignee_colonia' => $datos['consignee_colonia'],
                                            'consignee_city' => $datos['consignee_city'],
                                            'consignee_state' => $datos['consignee_state'],
                                            'consignee_zip_code' => $datos['consignee_zip_code'],
                                            'consignee_country' => $datos['consignee_country'],
                                            'consignee_phone_number' => $datos['consignee_phone_number'] ?? null,
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
                                            'procesado_por' => Auth::id(),
                                            'requiere_revision' => $resultado['confianza'] < 0.85,
                                        ]);

                                        \Log::info("Registro {$record->id} procesado exitosamente");
                                        $exitosos++;
                                    } else {
                                        $mensaje = $resultado['error'] ?? 'Error desconocido';
                                        \Log::error("Error procesando registro {$record->id}", ['error' => $mensaje]);
                                        $record->update([
                                            'estado_procesamiento' => 'error',
                                            'error_mensaje' => $mensaje,
                                        ]);
                                        $errores[] = "ID {$record->id}: {$mensaje}";
                                        $fallidos++;
                                    }
                                } catch (\Exception $e) {
                                    $mensaje = $e->getMessage();
                                    \Log::error("Excepción procesando registro {$record->id}", [
                                        'error' => $mensaje,
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                    $record->update([
                                        'estado_procesamiento' => 'error',
                                        'error_mensaje' => $mensaje,
                                    ]);
                                    $errores[] = "ID {$record->id}: {$mensaje}";
                                    $fallidos++;
                                }
                            }

                            \Log::info('Escaneo masivo completado', [
                                'exitosos' => $exitosos,
                                'fallidos' => $fallidos,
                                'errores' => $errores
                            ]);

                            $mensaje = "Exitosos: {$exitosos} | Fallidos: {$fallidos}";
                            if (!empty($errores)) {
                                $mensaje .= "\n\nErrores:\n" . implode("\n", array_slice($errores, 0, 5));
                                if (count($errores) > 5) {
                                    $mensaje .= "\n... y " . (count($errores) - 5) . " más. Revisa los logs.";
                                }
                            }

                            Notification::make()
                                ->title("Escaneo masivo completado")
                                ->body($mensaje)
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportar_excel')
                    ->label('Exportar Todo a Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        try {
                            $fileName = 'guias_mindee_' . now()->format('Y-m-d_His') . '.xlsx';

                            Notification::make()
                                ->info()
                                ->title('Generando archivo Excel...')
                                ->body('Por favor espera mientras se genera el archivo.')
                                ->send();

                            return Excel::download(new GuiasMindeeExport, $fileName);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error al exportar')
                                ->body('Ocurrió un error: ' . $e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuiasMindee::route('/'),
            'create' => Pages\CreateGuiaMindee::route('/create'),
            'edit' => Pages\EditGuiaMindee::route('/{record}/edit'),
            'view' => Pages\ViewGuiaMindee::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('requiere_revision', true)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
