<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GuiaHistorial extends Model
{
    use HasFactory;

    protected $fillable = [
        'guia_id',
        'campo_modificado',

    ];

    public function guia()
    {
        return $this->belongsTo(Guia::class);
    }



}
