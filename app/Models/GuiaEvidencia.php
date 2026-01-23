<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuiaEvidencia extends Model
{


    protected $fillable = [
        'guia_id',
        'paths',
        'tipo',
        'descripcion',
    ];

    protected $casts = [
        'paths' => 'array',
    ];
    public function guia()
    {
        return $this->belongsTo(Guia::class);
    }
}
