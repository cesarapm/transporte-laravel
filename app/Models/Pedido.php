<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $fillable = [
        'carga',
        'stripe_session_id',
        'stripe_payment_intent',
        'estatus',
        'remitente_nombre',
        'remitente_celular',
        'remitente_direccion',
        'remitente_estado',
        'remitente_ciudad',
        'remitente_codigo_postal',
        'remitente_pais',
        'destinatario_nombre',
        'destinatario_celular',
        'destinatario_direccion',
        'destinatario_estado',
        'destinatario_ciudad',
        'destinatario_codigo_postal',
        'destinatario_pais',
        'paquete_alto',
        'paquete_ancho',
        'paquete_largo',
        'paquete_peso',
        'paquete_volumen_calculado',
        'metadata',
        'fecha_cotizacion',
    ];

    protected $casts = [
        'carga' => 'decimal:2',
        'paquete_alto' => 'decimal:2',
        'paquete_ancho' => 'decimal:2',
        'paquete_largo' => 'decimal:2',
        'paquete_peso' => 'decimal:2',
        'paquete_volumen_calculado' => 'decimal:2',
        'metadata' => 'array',
        'fecha_cotizacion' => 'datetime',
    ];

    // Scopes para consultar por estatus
    public function scopePendientes($query)
    {
        return $query->where('estatus', 'pendiente');
    }

    public function scopePagados($query)
    {
        return $query->where('estatus', 'pagado');
    }

    public function scopeCancelados($query)
    {
        return $query->where('estatus', 'cancelado');
    }
}
