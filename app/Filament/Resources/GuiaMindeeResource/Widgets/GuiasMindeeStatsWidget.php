<?php

namespace App\Filament\Resources\GuiaMindeeResource\Widgets;

use App\Models\GuiaMindee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GuiasMindeeStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $total = GuiaMindee::count();
        $pendientes = GuiaMindee::where('estado_procesamiento', 'pendiente')->count();
        $procesados = GuiaMindee::where('estado_procesamiento', 'procesado')->count();
        $errores = GuiaMindee::where('estado_procesamiento', 'error')->count();
        $requierenRevision = GuiaMindee::where('requiere_revision', true)->count();

        return [
            Stat::make('Total de Guías', $total)
                ->description('Total registradas')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make('Pendientes', $pendientes)
                ->description('Por escanear')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Procesadas', $procesados)
                ->description('Escaneadas exitosamente')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Con Errores', $errores)
                ->description('Fallaron al escanear')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Requieren Revisión', $requierenRevision)
                ->description('Baja confianza')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning'),
        ];
    }
}
