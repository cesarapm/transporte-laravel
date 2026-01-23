<?php

namespace App\Filament\Resources\GuiaEvidenciasResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class EvidenciasRelationManager extends RelationManager
{
    protected static string $relationship = 'evidencias';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                //    Forms\Components\FileUpload::make('paths')
                //             ->label('Evidencias')
                //             ->multiple()
                //             ->image()
                //             ->directory('evidencias') // se guardan en storage/app/public/evidencias
                //             ->reorderable()
                //             ->required(),
                Forms\Components\FileUpload::make('paths')
                    ->label('Evidencias')
                    ->multiple()
                    ->image()
                    ->directory('evidencias')
                    ->reorderable()
                    ->openable() // 👈 permite abrir al editar
                    ->downloadable(), // 👈 permite descargar

                Forms\Components\Select::make('tipo')
                    ->options([
                        'imagen' => 'Imagen',
                        'pdf' => 'PDF',
                        'otro' => 'Otro',
                    ])
                    ->default('imagen'),

                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('evidencias')
            ->columns([
                Tables\Columns\ImageColumn::make('paths')
                    ->label('Evidencias')
                    ->circular()
                    ->stacked()
                    ->limit(3),

                Tables\Columns\TextColumn::make('tipo')->label('Tipo'),
                Tables\Columns\TextColumn::make('descripcion')->label('Descripción'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
                Tables\Actions\CreateAction::make()
                    ->visible(fn($livewire) => $livewire->getOwnerRecord()->evidencias()->count() === 0),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Borrar físicamente los archivos antes de eliminar el registro
                        if (is_array($record->paths)) {
                            foreach ($record->paths as $path) {
                                Storage::disk('public')->delete($path);
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
