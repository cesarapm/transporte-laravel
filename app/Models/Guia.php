<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guia extends Model
{
    protected $fillable = [
        'remesa',
        'folio',
        'tel_remite',
        'guia_interna',
        'fecha',
        'hora',
        'estatus',
        'activo',
        'paqueteria',
        'npaquetes',
        'rastreo',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($guia) {
            if (empty($guia->guia_interna)) {
                // Generar el valor de guia_interna con guiones como separador
                $guia->guia_interna = $guia->tel_remite . '-' . $guia->folio . '-' . $guia->npaquetes;
            }
        });
        static::deleting(function ($guia) {
            foreach ($guia->evidencias as $evidencia) {
                // Borrar cada archivo del array paths
                foreach (array_filter($evidencia->paths ?? []) as $path) {
                    Storage::disk('public')->delete($path);
                }

                // Borrar el registro de la evidencia
                $evidencia->delete();
            }
        });
    }
    public function historial()
    {
        return $this->hasMany(GuiaHistorial::class);
    }
        public function evidencias()
    {
        return $this->hasMany(GuiaEvidencia::class);
    }

}
