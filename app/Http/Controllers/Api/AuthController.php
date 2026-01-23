<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validar los datos de entrada
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Obtener solo las credenciales de email y password
        $credentials = $request->only('email', 'password');

        // Intentar autenticar al usuario
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Generar un token para el usuario autenticado
            $token = $user->createToken('auth_token')->plainTextToken;

            // Retornar el token y los detalles del usuario
            return response()->json([
                'status' => 'success',
                'token' => $token,
                'user' => $user
            ], 200);
        }

        // Si las credenciales no coinciden
        return response()->json(['error' => 'Unauthorized'], 401);
    }


    public function index()
    {
        $users = User::all();


        $data = [
            'users' => $users,
            'status' => 200
        ];

        return response()->json($data, 200);
    }


    public function destroy($id)
    {
        // Encontrar el usuario por su ID
        $user = User::find($id);

        // Verificar si el usuario existe
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Almacenar el ID del usuario para la respuesta


        // Eliminar el usuario (esto también eliminará los sucursalautorizados debido a la eliminación en cascada)
        $user->delete();
        $users = User::all();


        return response()->json([
            'message' => 'Usuario eliminado correctamente',
            'users' => $users,
        ], 200);
    }







}
