<?php

namespace App\Filament\Resources\GuiaHistorialResource\Pages;

use App\Filament\Resources\GuiaHistorialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuiaHistorial extends EditRecord
{
    protected static string $resource = GuiaHistorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
