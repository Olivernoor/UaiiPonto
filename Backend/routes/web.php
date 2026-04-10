<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\ReportController;

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

    // ============================================
    // Rotas de Batida de Ponto (Time Entry)
    // ============================================
    
    // Check-in e Check-out
    Route::post('/time-entries/check-in', [TimeEntryController::class, 'checkIn']);
    Route::post('/time-entries/check-out', [TimeEntryController::class, 'checkOut']);
    
    // Obter batida de hoje
    Route::get('/time-entries/today', [TimeEntryController::class, 'todayEntry']);
    
    // Histórico de batidas do usuário autenticado
    Route::get('/time-entries/history', [TimeEntryController::class, 'history']);
    
    // Obter batidas de um período para um usuário específico
    Route::get('/time-entries/user/{userId}/range', [TimeEntryController::class, 'rangeByUser']);
    
    // Deletar uma batida
    Route::delete('/time-entries/{entryId}', [TimeEntryController::class, 'delete']);
    
    // Listar todas as batidas (apenas admin/manager)
    Route::get('/time-entries', [TimeEntryController::class, 'getAllEntries']);

    // ============================================
    // Rotas de Relatórios
    // ============================================
    
    // Relatório diário de presença
    Route::get('/reports/daily', [ReportController::class, 'dailyReport']);
    
    // Relatório semanal consolidado
    Route::get('/reports/weekly', [ReportController::class, 'weeklyReport']);
    
    // Relatório mensal
    Route::get('/reports/monthly', [ReportController::class, 'monthlyReport']);
    
    // Exportar relatório em PDF
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf']);
    
    // Exportar relatório em Excel
    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel']);
});
