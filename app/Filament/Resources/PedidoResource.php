<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PedidoResource\Pages;
use App\Filament\Resources\PedidoResource\RelationManagers;
use App\Models\Pedido;
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

class PedidoResource extends Resource
{
    protected static ?string $model = Pedido::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Pedidos';

    protected static ?string $modelLabel = 'Pedido';

    protected static ?string $pluralModelLabel = 'Pedidos';

    protected static ?string $navigationGroup = 'Gestión de Pagos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Pedido')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('carga')
                                    ->label('Monto ($)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01),
                                Forms\Components\Select::make('estatus')
                                    ->label('Estatus')
                                    ->options([
                                        'pendiente' => 'Pendiente',
                                        'pagado' => 'Pagado',
                                        'cancelado' => 'Cancelado',
                                        'fallido' => 'Fallido',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('stripe_session_id')
                                    ->label('Session ID de Stripe')
                                    ->maxLength(255)
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('stripe_payment_intent')
                                    ->label('Payment Intent ID')
                                    ->maxLength(255)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                        Forms\Components\DateTimePicker::make('fecha_cotizacion')
                            ->label('Fecha de Cotización'),
                    ]),

                Forms\Components\Section::make('Datos del Remitente')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('remitente_nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('remitente_celular')
                                    ->label('Celular')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Textarea::make('remitente_direccion')
                            ->label('Dirección')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('remitente_estado')
                                    ->label('Estado')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('remitente_ciudad')
                                    ->label('Ciudad')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('remitente_codigo_postal')
                                    ->label('Código Postal')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\TextInput::make('remitente_pais')
                            ->label('País')
                            ->required()
                            ->maxLength(255),
                    ])->collapsed(),

                Forms\Components\Section::make('Datos del Destinatario')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('destinatario_nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('destinatario_celular')
                                    ->label('Celular')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Textarea::make('destinatario_direccion')
                            ->label('Dirección')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('destinatario_estado')
                                    ->label('Estado')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('destinatario_ciudad')
                                    ->label('Ciudad')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('destinatario_codigo_postal')
                                    ->label('Código Postal')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\TextInput::make('destinatario_pais')
                            ->label('País')
                            ->required()
                            ->maxLength(255),
                    ])->collapsed(),

                Forms\Components\Section::make('Información del Paquete')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('paquete_alto')
                                    ->label('Alto (Pulgadas)')
                                    ->required()
                                    ->numeric()
                                    ->suffix('in'),
                                Forms\Components\TextInput::make('paquete_ancho')
                                    ->label('Ancho (Pulgadas)')
                                    ->required()
                                    ->numeric()
                                    ->suffix('in'),
                                Forms\Components\TextInput::make('paquete_largo')
                                    ->label('Largo (Pulgadas)')
                                    ->required()
                                    ->numeric()
                                    ->suffix('in'),
                                Forms\Components\TextInput::make('paquete_peso')
                                    ->label('Peso (lb)')
                                    ->required()
                                    ->numeric()
                                    ->suffix('lb'),
                            ]),
                        Forms\Components\TextInput::make('paquete_volumen_calculado')
                            ->label('Volumen Calculado')
                            ->required()
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])->collapsed(),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('metadata.servicio')
                                    ->label('Servicio')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record?->metadata['servicio'] ?? 'No especificado'),
                                Forms\Components\TextInput::make('metadata.tipo_envio')
                                    ->label('Tipo de Envío')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record?->metadata['tipo_envio'] ?? 'No especificado'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('metadata.user_agent')
                                    ->label('Navegador')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(function ($record) {
                                        $userAgent = $record?->metadata['user_agent'] ?? '';
                                        // Extraer información básica del user agent
                                        if (str_contains($userAgent, 'Chrome')) {
                                            preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches);
                                            return 'Chrome ' . ($matches[1] ?? 'Desconocida');
                                        }
                                        return $userAgent ? 'Otro navegador' : 'No especificado';
                                    }),
                                Forms\Components\TextInput::make('metadata.timestamp')
                                    ->label('Timestamp')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record?->metadata['timestamp'] ?? 'No especificado'),
                            ]),
                        Forms\Components\Textarea::make('metadata_raw')
                            ->label('JSON Completo')
                            ->columnSpanFull()
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->metadata ? json_encode($record->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'Sin metadata')
                            ->rows(6),
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

                Tables\Columns\TextColumn::make('carga')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\BadgeColumn::make('estatus')
                    ->label('Estatus')
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'pagado',
                        'danger' => 'fallido',
                        'gray' => 'cancelado',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pendiente',
                        'heroicon-o-check-circle' => 'pagado',
                        'heroicon-o-x-circle' => 'fallido',
                        'heroicon-o-minus-circle' => 'cancelado',
                    ]),

                Tables\Columns\TextColumn::make('remitente_nombre')
                    ->label('Remitente')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('destinatario_nombre')
                    ->label('Destinatario')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('remitente_pais')
                    ->label('De')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('destinatario_pais')
                    ->label('Para')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('stripe_session_id')
                    ->label('Session ID')
                    ->limit(15)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('fecha_cotizacion')
                    ->label('F. Cotización')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('F. Creación')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('servicio')
                    ->label('Servicio')
                    ->getStateUsing(fn ($record) => $record->metadata['servicio'] ?? 'No especificado')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                Tables\Columns\TextColumn::make('tipo_envio')
                    ->label('Tipo de Envío')
                    ->getStateUsing(fn ($record) => $record->metadata['tipo_envio'] ?? 'No especificado')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estatus')
                    ->label('Estatus')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'pagado' => 'Pagado',
                        'cancelado' => 'Cancelado',
                        'fallido' => 'Fallido',
                    ])
                    ->native(false),

                SelectFilter::make('remitente_pais')
                    ->label('País de Origen')
                    ->options([
                        'Estados Unidos' => 'Estados Unidos',
                        'México' => 'México',
                        'Canadá' => 'Canadá',
                    ]),

                SelectFilter::make('destinatario_pais')
                    ->label('País de Destino')
                    ->options([
                        'Estados Unidos' => 'Estados Unidos',
                        'México' => 'México',
                        'Canadá' => 'Canadá',
                    ]),

                SelectFilter::make('servicio')
                    ->label('Servicio')
                    ->options([
                        '123Express - Transportes México' => '123Express - Transportes México',
                        'DHL Express' => 'DHL Express',
                        'FedEx International' => 'FedEx International',
                        'UPS Worldwide' => 'UPS Worldwide',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'], function (Builder $query, $service) {
                            return $query->whereJsonContains('metadata->servicio', $service);
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Action::make('cambiar_estatus')
                    ->label('Cambiar Estatus')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('estatus')
                            ->label('Nuevo Estatus')
                            ->options([
                                'pendiente' => 'Pendiente',
                                'pagado' => 'Pagado',
                                'cancelado' => 'Cancelado',
                                'fallido' => 'Fallido',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (array $data, Pedido $record): void {
                        $record->update(['estatus' => $data['estatus']]);
                        Notification::make()
                            ->title('Estatus actualizado')
                            ->body("El pedido #{$record->id} ahora está: {$data['estatus']}")
                            ->success()
                            ->send();
                    }),

                Action::make('ver_stripe')
                    ->label('Ver en Stripe')
                    ->icon('heroicon-o-credit-card')
                    ->url(fn (Pedido $record): string =>
                        "https://dashboard.stripe.com/test/payments/{$record->stripe_session_id}"
                    )
                    ->openUrlInNewTab()
                    ->visible(fn (Pedido $record): bool => !empty($record->stripe_session_id)),

                Action::make('ver_metadata')
                    ->label('Ver Metadata')
                    ->icon('heroicon-o-information-circle')
                    ->modalHeading('Información Adicional del Pedido')
                    ->modalContent(function (Pedido $record) {
                        if (!$record->metadata) {
                            return view('filament.modals.no-metadata');
                        }

                        return view('filament.modals.metadata-viewer', [
                            'metadata' => $record->metadata,
                            'pedido' => $record
                        ]);
                    })
                    ->visible(fn (Pedido $record): bool => !empty($record->metadata)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Actualizar automáticamente cada 30 segundos
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
            'index' => Pages\ListPedidos::route('/'),
            'create' => Pages\CreatePedido::route('/create'),
            'edit' => Pages\EditPedido::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('estatus', 'pendiente')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('estatus', 'pendiente')->count() > 0 ? 'warning' : 'primary';
    }
}
