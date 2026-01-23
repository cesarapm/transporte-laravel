<?php

namespace App\Filament\Resources\PedidoResource\Widgets;

use Filament\Widgets\ChartWidget;

class PedidoStatsWidget extends ChartWidget
{
    protected static ?string $heading = 'Chart';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
