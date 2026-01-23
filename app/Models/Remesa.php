<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Remesa extends Model
{
    use HasFactory;

    protected $fillable = ['folio', 'nombre_cliente','telefono_cliente'];


}
