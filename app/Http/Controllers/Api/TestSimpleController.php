<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestSimpleController extends Controller
{
    public function test()
    {
        return response()->json(['message' => 'Test success!']);
    }
}
