<?php

namespace App\Filament\Resources\PedidoResource\Widgets;

use App\Models\Pedido;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PedidoStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pedidos', Pedido::count())
                ->description('Todos los pedidos registrados')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Pagos Exitosos', Pedido::where('estatus', 'pagado')->count())
                ->description('Pedidos completados')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pendientes', Pedido::where('estatus', 'pendiente')->count())
                ->description('Esperando pago')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Ingresos Totales', '$' . number_format(Pedido::where('estatus', 'pagado')->sum('carga'), 2))
                ->description('Total en pagos exitosos')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Promedio por Pedido', '$' . number_format(Pedido::where('estatus', 'pagado')->avg('carga') ?? 0, 2))
                ->description('Valor promedio de envíos pagados')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Tasa de Éxito',
                Pedido::count() > 0
                    ? number_format((Pedido::where('estatus', 'pagado')->count() / Pedido::count()) * 100, 1) . '%'
                    : '0%'
            )
                ->description('Pedidos pagados vs total')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
