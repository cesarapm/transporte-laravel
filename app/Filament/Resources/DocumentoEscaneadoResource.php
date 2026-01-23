<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentoEscaneadoResource\Pages;
use App\Filament\Resources\DocumentoEscaneadoResource\RelationManagers;
use App\Models\DocumentoEscaneado;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use App\Services\VisionApiService;
use Illuminate\Support\Facades\Storage;

class DocumentoEscaneadoResource extends Resource
{
    protected static ?string $model = DocumentoEscaneado::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Documentos Escaneados';

    protected static ?string $modelLabel = 'Documento';

    protected static ?string $pluralModelLabel = 'Documentos Escaneados';

    protected static ?string $navigationGroup = 'Gestión de Documentos';

    // Ocultar del menú de navegación
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Archivo')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\FileUpload::make('archivo_original')
                                    ->label('Subir Documento')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('documentos_escaneados')
                                    ->visibility('public')
                                    ->required()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                                    ->maxSize(10240) // 10MB
                                    ->columnSpan(2),

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
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('nombre_archivo')
                                    ->label('Nombre del Archivo')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('tipo_mime')
                                    ->label('Tipo MIME')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('tamaños_formateado')
                                    ->label('Tamaño')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),

                Forms\Components\Section::make('Información del Documento')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('folio')
                                    ->label('Folio'),
                                Forms\Components\DatePicker::make('fecha_documento')
                                    ->label('Fecha del Documento'),
                                Forms\Components\Toggle::make('requiere_revision')
                                    ->label('Requiere Revisión'),
                            ]),
                    ]),

                Forms\Components\Section::make('Datos del Remitente')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('remitente_nombre')
                                    ->label('Nombre'),
                                Forms\Components\TextInput::make('remitente_telefono')
                                    ->label('Teléfono'),
                            ]),
                        Forms\Components\Textarea::make('remitente_direccion')
                            ->label('Dirección')
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('remitente_colonia')
                                    ->label('Colonia'),
                                Forms\Components\TextInput::make('remitente_ciudad')
                                    ->label('Ciudad'),
                                Forms\Components\TextInput::make('remitente_estado')
                                    ->label('Estado'),
                                Forms\Components\TextInput::make('remitente_cp')
                                    ->label('Código Postal'),
                            ]),
                        Forms\Components\TextInput::make('remitente_pais')
                            ->label('País')
                            ->default('US'),
                    ])->collapsed(),

                Forms\Components\Section::make('Datos del Destinatario')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('destinatario_nombre')
                                    ->label('Nombre'),
                                Forms\Components\TextInput::make('destinatario_telefono')
                                    ->label('Teléfono'),
                            ]),
                        Forms\Components\Textarea::make('destinatario_direccion')
                            ->label('Dirección')
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('destinatario_colonia')
                                    ->label('Colonia'),
                                Forms\Components\TextInput::make('destinatario_ciudad')
                                    ->label('Ciudad'),
                                Forms\Components\TextInput::make('destinatario_estado')
                                    ->label('Estado'),
                                Forms\Components\TextInput::make('destinatario_cp')
                                    ->label('Código Postal'),
                            ]),
                        Forms\Components\TextInput::make('destinatario_pais')
                            ->label('País')
                            ->default('MX'),
                    ])->collapsed(),

                Forms\Components\Section::make('Información del Envío')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('numero_cajas')
                                    ->label('Número de Cajas')
                                    ->numeric(),
                                Forms\Components\TextInput::make('tipo_contenido')
                                    ->label('Tipo de Contenido'),
                                Forms\Components\TextInput::make('peso')
                                    ->label('Peso (lb)')
                                    ->numeric()
                                    ->suffix('lb'),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('valor_asegurado')
                                    ->label('Valor Asegurado')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('costo_flete')
                                    ->label('Costo Flete')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('impuestos')
                                    ->label('Impuestos')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('total')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('$'),
                            ]),
                    ])->collapsed(),

                Forms\Components\Section::make('Notas y Observaciones')
                    ->schema([
                        Forms\Components\Textarea::make('notas_revision')
                            ->label('Notas de Revisión')
                            ->rows(3),
                        Forms\Components\Textarea::make('errores_procesamiento')
                            ->label('Errores de Procesamiento')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(2),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\ImageColumn::make('archivo_original')
                    ->label('Vista Previa')
                    ->disk('public')
                    ->height(60)
                    ->width(60),

                Tables\Columns\BadgeColumn::make('estado_procesamiento')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'procesado',
                        'primary' => 'verificado',
                        'danger' => 'error',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pendiente',
                        'heroicon-o-check-circle' => 'procesado',
                        'heroicon-o-shield-check' => 'verificado',
                        'heroicon-o-x-circle' => 'error',
                    ]),

                Tables\Columns\TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('remitente_nombre')
                    ->label('Remitente')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('destinatario_nombre')
                    ->label('Destinatario')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\IconColumn::make('requiere_revision')
                    ->label('Rev.')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('warning')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('fecha_documento')
                    ->label('F. Documento')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('F. Subida')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado_procesamiento')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'procesado' => 'Procesado',
                        'error' => 'Error',
                        'verificado' => 'Verificado',
                    ])
                    ->native(false),

                SelectFilter::make('requiere_revision')
                    ->label('Requiere Revisión')
                    ->options([
                        '1' => 'Sí',
                        '0' => 'No',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'] !== null, function (Builder $query) use ($data) {
                            return $query->where('requiere_revision', $data['value']);
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Action::make('reprocesar')
                    ->label('Reprocesar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (DocumentoEscaneado $record) {
                        try {
                            $visionService = app(VisionApiService::class);
                            $rutaCompleta = storage_path('app/public/' . $record->archivo_original);

                            if (!file_exists($rutaCompleta)) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Archivo no encontrado en el servidor')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $record->update(['estado_procesamiento' => 'pendiente']);
                            $resultado = $visionService->procesarDocumento($rutaCompleta);

                            if ($resultado['success']) {
                                $record->update([
                                    'estado_procesamiento' => 'procesado',
                                    'requiere_revision' => $resultado['confianza'] < 80
                                ]);

                                Notification::make()
                                    ->title('Reprocesado exitosamente')
                                    ->body("Confianza: {$resultado['confianza']}%")
                                    ->success()
                                    ->send();
                            } else {
                                $record->update([
                                    'estado_procesamiento' => 'error',
                                    'errores_procesamiento' => $resultado['error']
                                ]);

                                Notification::make()
                                    ->title('Error en reprocesamiento')
                                    ->body($resultado['error'])
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error interno')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (DocumentoEscaneado $record): bool =>
                        in_array($record->estado_procesamiento, ['error', 'procesado'])
                    ),

                Action::make('verificar')
                    ->label('Verificar')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->action(function (DocumentoEscaneado $record) {
                        $record->update([
                            'estado_procesamiento' => 'verificado',
                            'requiere_revision' => false
                        ]);

                        Notification::make()
                            ->title('Documento verificado')
                            ->body('El documento ha sido marcado como verificado')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (DocumentoEscaneado $record): bool =>
                        $record->estado_procesamiento === 'procesado'
                    ),

                Action::make('descargar')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (DocumentoEscaneado $record): string =>
                        asset('storage/' . $record->archivo_original)
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
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
            'index' => Pages\ListDocumentoEscaneados::route('/'),
            'create' => Pages\CreateDocumentoEscaneado::route('/create'),
            'edit' => Pages\EditDocumentoEscaneado::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('requiere_revision', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('requiere_revision', true)->count() > 0 ? 'warning' : 'primary';
    }
}
