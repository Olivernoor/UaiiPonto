<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Responder a todos os preflight OPTIONS requests
Route::options('{any}', function () {
    return response()->json(null, 200);
})->where('any', '.*');

// Status da API
Route::get('/', function () {
    return response()->json(['message' => 'UaiiPonto API - Rodando']);
});

// Rota de teste - mostra como usar a API
Route::get('/register', function () {
    return response()->json([
        'message' => 'Use POST para registrar',
        'method' => 'POST',
        'endpoint' => '/register',
        'body' => [
            'name' => 'Seu Nome',
            'email' => 'seu@email.com',
            'password' => 'senha123',
            'organization' => 'Sua Organização'
        ]
    ]);
});

Route::get('/login', function () {
    return response()->json([
        'message' => 'Use POST para fazer login',
        'method' => 'POST',
        'endpoint' => '/login',
        'body' => [
            'email' => 'seu@email.com',
            'password' => 'senha123'
        ]
    ]);
});

// Rotas públicas de autenticação (POST)
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Rotas protegidas (requer autenticação)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
});
