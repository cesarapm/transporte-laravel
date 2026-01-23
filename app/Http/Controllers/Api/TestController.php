<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class TestController extends Controller
{
    public function hello()
    {
        return response()->json(['message' => 'Hello, World!']);
    }
}
