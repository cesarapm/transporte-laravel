<?php

namespace App\Filament\Resources\GuiaResource\Pages;

use App\Filament\Resources\GuiaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;

class EditGuia extends EditRecord
{
    protected static string $resource = GuiaResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
