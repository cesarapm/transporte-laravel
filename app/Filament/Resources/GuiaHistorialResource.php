<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuiaHistorialResource\Pages;
use App\Filament\Resources\GuiaHistorialResource\RelationManagers;
use App\Models\GuiaHistorial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;

class GuiaHistorialResource extends Resource
{
    protected static ?string $model = GuiaHistorial::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Historial de la Guia';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('guia.guia_interna')
                ->label('Guia Interna')
                ->sortable()
                ->searchable(),
                TextColumn::make('campo_modificado')
                ->label('Estado')
                ->sortable()
                ->searchable(),
                TextColumn::make('created_at')
                ->label('Fecha')
                ->sortable()
                ->searchable(),
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                    // Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListGuiaHistorials::route('/'),
            'create' => Pages\CreateGuiaHistorial::route('/create'),
            'edit' => Pages\EditGuiaHistorial::route('/{record}/edit'),
        ];
    }
}
