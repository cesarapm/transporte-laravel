<?php

namespace App\Filament\Widgets;

use App\Models\Pedido;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PedidosDashboardWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPedidos = Pedido::count();
        $pedidosPagados = Pedido::where('estatus', 'pagado')->count();
        $pedidosPendientes = Pedido::where('estatus', 'pendiente')->count();
        $ingresosTotales = Pedido::where('estatus', 'pagado')->sum('carga');

        return [
            Stat::make('Pedidos Pendientes', $pedidosPendientes)
                ->description('Esperando pago')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Ingresos del Día', '$' . number_format(
                Pedido::where('estatus', 'pagado')
                    ->whereDate('created_at', today())
                    ->sum('carga'),
                2
            ))
                ->description('Pagos procesados hoy')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([12, 15, 10, 20, 25, 30, 35]),

            Stat::make('Total Ingresos', '$' . number_format($ingresosTotales, 2))
                ->description('Acumulado total')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info')
                ->chart([100, 120, 150, 180, 200, 220, 250]),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
