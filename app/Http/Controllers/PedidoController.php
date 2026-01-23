<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pedido::query();

        // Filtrar por estatus si se proporciona
        if ($request->has('estatus')) {
            $query->where('estatus', $request->estatus);
        }

        // Filtrar por fecha si se proporciona
        if ($request->has('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $pedidos = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($pedidos);
    }

    public function show($id)
    {
        $pedido = Pedido::find($id);

        if (!$pedido) {
            return response()->json(['message' => 'Pedido no encontrado'], 404);
        }

        return response()->json($pedido);
    }

    public function showBySessionId($sessionId)
    {
        $pedido = Pedido::where('stripe_session_id', $sessionId)->first();

        if (!$pedido) {
            return response()->json(['message' => 'Pedido no encontrado'], 404);
        }

        return response()->json($pedido);
    }

    public function updateEstatus(Request $request, $id)
    {
        $request->validate([
            'estatus' => 'required|in:pendiente,pagado,cancelado,fallido'
        ]);

        $pedido = Pedido::find($id);

        if (!$pedido) {
            return response()->json(['message' => 'Pedido no encontrado'], 404);
        }

        $estatusAnterior = $pedido->estatus;
        $pedido->estatus = $request->estatus;
        $pedido->save();

        Log::info("Estatus de pedido {$id} cambiado de '{$estatusAnterior}' a '{$request->estatus}'");

        return response()->json([
            'message' => 'Estatus actualizado correctamente',
            'pedido' => $pedido
        ]);
    }

    public function stats()
    {
        $stats = [
            'total' => Pedido::count(),
            'pendientes' => Pedido::where('estatus', 'pendiente')->count(),
            'pagados' => Pedido::where('estatus', 'pagado')->count(),
            'cancelados' => Pedido::where('estatus', 'cancelado')->count(),
            'fallidos' => Pedido::where('estatus', 'fallido')->count(),
            'total_ingresos' => Pedido::where('estatus', 'pagado')->sum('carga'),
        ];

        return response()->json($stats);
    }
}
