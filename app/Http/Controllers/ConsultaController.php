<?php

namespace App\Http\Controllers;



use Illuminate\Http\Request;

class ConsultaController extends Controller
{
    public function index()
    {
        return view('consulta'); // Asegúrate de que consulta.blade.php exista en resources/views
    }
}
