<?php

namespace App\Filament\Resources\GuiaResource\Pages;

use App\Filament\Resources\GuiaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGuia extends CreateRecord
{
    protected static string $resource = GuiaResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
