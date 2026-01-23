<?php

namespace App\Filament\Resources\GuiaHistorialResource\Pages;

use App\Filament\Resources\GuiaHistorialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuiaHistorials extends ListRecords
{
    protected static string $resource = GuiaHistorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
