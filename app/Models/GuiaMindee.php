<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class GuiaMindee extends Model
{
    protected $table = 'guias_mindee';

    protected $fillable = [
        // Archivo
        'archivo_original',
        'nombre_archivo',
        'tipo_mime',
        'tamaño_archivo',

        // Información del transportista
        'carrier_name',
        'carrier_address',
        'manifest_number',
        'folio_invoice_number',
        'ship_date',

        // Remitente
        'shipper_name',
        'shipper_address',
        'shipper_city',
        'shipper_suburb',
        'shipper_zip_code',
        'shipper_state',
        'shipper_country',
        'ship_phone_number',

        // Destinatario
        'consignee_name',
        'consignee_address',
        'consignee_colonia',
        'consignee_city',
        'consignee_state',
        'consignee_zip_code',
        'consignee_country',
        'consignee_phone_number',

        // Información del envío
        'total_packages',
        'shipper_box_count',
        'total_weight',
        'weight_unit',

        // Costos
        'shipper_freight_cost',
        'shipper_insured_value',

        // Items (JSON)
        'item_categories',
        'shipment_line_items',

        // Tracking
        'tracking_number',

        // Firma
        'signature',
        'agent_signature',

        // Procesamiento
        'texto_raw',
        'datos_json',
        'confianza_promedio',
        'estado_procesamiento',
        'error_mensaje',
        'requiere_revision',
        'fecha_procesamiento',
        'procesado_por',
    ];

    protected $casts = [
        'ship_date' => 'date',
        'fecha_procesamiento' => 'datetime',
        'item_categories' => 'array',
        'shipment_line_items' => 'array',
        'datos_json' => 'array',
        'confianza_promedio' => 'decimal:2',
        'total_packages' => 'integer',
        'shipper_box_count' => 'integer',
        'total_weight' => 'decimal:2',
        'shipper_freight_cost' => 'decimal:2',
        'shipper_insured_value' => 'decimal:2',
        'requiere_revision' => 'boolean',
    ];

    /**
     * Boot del modelo para manejar eventos
     */
    protected static function boot()
    {
        parent::boot();

        // Cuando se actualiza el registro y cambia el archivo
        static::updating(function ($guia) {
            if ($guia->isDirty('archivo_original') && $guia->getOriginal('archivo_original')) {
                // Borrar el archivo antiguo
                $archivoAntiguo = $guia->getOriginal('archivo_original');
                if (Storage::disk('public')->exists($archivoAntiguo)) {
                    Storage::disk('public')->delete($archivoAntiguo);
                }
            }
        });

        // Cuando se elimina el registro
        static::deleting(function ($guia) {
            // Borrar el archivo asociado
            if ($guia->archivo_original && Storage::disk('public')->exists($guia->archivo_original)) {
                Storage::disk('public')->delete($guia->archivo_original);
            }
        });
    }

    /**
     * Formatear tamaño de archivo
     */
    protected function tamañoFormateado(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->tamaño_archivo) return '-';

                $bytes = $this->tamaño_archivo;
                $units = ['B', 'KB', 'MB', 'GB'];
                $factor = floor((strlen($bytes) - 1) / 3);

                return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
            }
        );
    }

    /**
     * Obtener descripción de items
     */
    protected function itemsDescripcion(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->shipment_line_items)) {
                    return $this->item_categories ? implode(', ', $this->item_categories) : '-';
                }

                $descripciones = array_filter(array_column($this->shipment_line_items, 'description'));
                return !empty($descripciones) ? implode(', ', $descripciones) : '-';
            }
        );
    }

    /**
     * Badge de estado
     */
    public function getEstadoColorAttribute()
    {
        return match($this->estado_procesamiento) {
            'procesado' => 'success',
            'pendiente' => 'warning',
            'error' => 'danger',
            'verificado' => 'info',
            default => 'gray'
        };
    }

    /**
     * Verificar si requiere atención
     */
    public function requiereAtencion(): bool
    {
        return $this->requiere_revision ||
               $this->estado_procesamiento === 'error' ||
               $this->confianza_promedio < 0.7;
    }

    /**
     * Relación con usuario que procesó
     */
    public function procesador()
    {
        return $this->belongsTo(User::class, 'procesado_por');
    }

    /**
     * Scopes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado_procesamiento', 'pendiente');
    }

    public function scopeProcesados($query)
    {
        return $query->where('estado_procesamiento', 'procesado');
    }

    public function scopeConErrores($query)
    {
        return $query->where('estado_procesamiento', 'error');
    }

    public function scopeRequierenRevision($query)
    {
        return $query->where('requiere_revision', true);
    }

    public function scopeBajaConfianza($query)
    {
        return $query->where('confianza_promedio', '<', 0.7);
    }
}
