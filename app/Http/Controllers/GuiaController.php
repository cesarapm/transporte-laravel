<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GuiaController extends Controller
{
    public function mostrar($numero)
    {
        return view('Filament.guia.rastreo', [
            'numero' => $numero,
        ]);
    }
}
