<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DocumentoEscaneado extends Model
{
    protected $fillable = [
        'archivo_original',
        'nombre_archivo',
        'tipo_mime',
        'tamaño_archivo',
        'folio',
        'fecha_documento',

        // Remitente
        'remitente_nombre',
        'remitente_telefono',
        'remitente_direccion',
        'remitente_colonia',
        'remitente_ciudad',
        'remitente_estado',
        'remitente_cp',
        'remitente_pais',

        // Destinatario
        'destinatario_nombre',
        'destinatario_telefono',
        'destinatario_direccion',
        'destinatario_colonia',
        'destinatario_ciudad',
        'destinatario_estado',
        'destinatario_cp',
        'destinatario_pais',

        // Envío
        'numero_cajas',
        'tipo_contenido',
        'peso',
        'valor_asegurado',
        'valor_declarado',
        'costo_flete',
        'impuestos',
        'seguro_extra',
        'total',

        // Procesamiento
        'texto_raw',
        'confianza_ocr',
        'estado_procesamiento',
        'errores_procesamiento',
        'metadatos_vision',
        'requiere_revision',
        'notas_revision',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'peso' => 'decimal:2',
        'valor_asegurado' => 'decimal:2',
        'valor_declarado' => 'decimal:2',
        'costo_flete' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'seguro_extra' => 'decimal:2',
        'total' => 'decimal:2',
        'texto_raw' => 'array',
        'confianza_ocr' => 'array',
        'metadatos_vision' => 'array',
        'requiere_revision' => 'boolean',
    ];

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado_procesamiento', 'pendiente');
    }

    public function scopeProcesados($query)
    {
        return $query->where('estado_procesamiento', 'procesado');
    }

    public function scopeRequierenRevision($query)
    {
        return $query->where('requiere_revision', true);
    }

    // Accessors
    public function archivoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => asset('storage/' . $this->archivo_original)
        );
    }

    public function tamanosFormateado(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->tamaño_archivo / 1024, 2) . ' KB'
        );
    }

    public function totalCalculado(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->costo_flete ?? 0) + ($this->impuestos ?? 0) + ($this->seguro_extra ?? 0)
        );
    }
}
