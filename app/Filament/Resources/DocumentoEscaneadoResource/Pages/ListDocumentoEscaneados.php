<?php

namespace App\Filament\Resources\DocumentoEscaneadoResource\Pages;

use App\Filament\Resources\DocumentoEscaneadoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentoEscaneados extends ListRecords
{
    protected static string $resource = DocumentoEscaneadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
