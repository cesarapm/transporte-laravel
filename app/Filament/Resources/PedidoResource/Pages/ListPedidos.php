<?php

namespace App\Filament\Resources\PedidoResource\Pages;

use App\Filament\Resources\PedidoResource;
use App\Models\Pedido;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPedidos extends ListRecords
{
    protected static string $resource = PedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PedidoResource\Widgets\PedidoStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->badge(Pedido::count()),

            'pendientes' => Tab::make('Pendientes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estatus', 'pendiente'))
                ->badge(Pedido::where('estatus', 'pendiente')->count())
                ->badgeColor('warning'),

            'pagados' => Tab::make('Pagados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estatus', 'pagado'))
                ->badge(Pedido::where('estatus', 'pagado')->count())
                ->badgeColor('success'),

            'cancelados' => Tab::make('Cancelados')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estatus', 'cancelado'))
                ->badge(Pedido::where('estatus', 'cancelado')->count())
                ->badgeColor('gray'),

            'fallidos' => Tab::make('Fallidos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estatus', 'fallido'))
                ->badge(Pedido::where('estatus', 'fallido')->count())
                ->badgeColor('danger'),
        ];
    }
}
