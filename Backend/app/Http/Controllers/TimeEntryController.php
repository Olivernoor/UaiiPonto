<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TimeEntryController extends Controller
{
    /**
     * Registrar check-in do usuário
     */
    public function checkIn(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $checkIn = Carbon::now();

        // Validar check-in
        $errors = TimeEntry::validateCheckIn($checkIn);
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        // Criar registro de batida
        $timeEntry = TimeEntry::create([
            'user_id' => $user->id,
            'check_in' => $checkIn,
            'notes' => $request->notes ?? null,
        ]);

        return response()->json([
            'message' => 'Check-in registrado com sucesso',
            'data' => $timeEntry,
        ], 201);
    }

    /**
     * Registrar check-out do usuário
     */
    public function checkOut(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        // Encontrar batida aberta do usuário
        $timeEntry = TimeEntry::where('user_id', $user->id)
            ->whereNull('check_out')
            ->latest('check_in')
            ->first();

        if (!$timeEntry) {
            return response()->json(['message' => 'Nenhuma batida aberta encontrada'], 404);
        }

        $checkOut = Carbon::now();

        // Validar check-out
        $errors = TimeEntry::validateCheckOut($timeEntry, $checkOut);
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        // Atualizar registro
        $timeEntry->check_out = $checkOut;
        $timeEntry->worked_hours = $timeEntry->calculateWorkedHours();
        $timeEntry->status = $timeEntry->determineStatus();
        $timeEntry->save();

        return response()->json([
            'message' => 'Check-out registrado com sucesso',
            'data' => $timeEntry,
        ], 200);
    }

    /**
     * Obter batida de hoje do usuário
     */
    public function todayEntry(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $entry = TimeEntry::where('user_id', $user->id)
            ->whereDate('check_in', Carbon::today())
            ->first();

        if (!$entry) {
            return response()->json(['message' => 'Nenhuma batida para hoje'], 404);
        }

        return response()->json($entry, 200);
    }

    /**
     * Obter histórico de batidas do usuário (últimos 30 dias)
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $days = $request->query('days', 30);
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        $entries = TimeEntry::where('user_id', $user->id)
            ->forDateRange(Carbon::now()->subDays($days), Carbon::now())
            ->orderBy('check_in', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($entries, 200);
    }

    /**
     * Obter batidas de um período específico (para gestores)
     */
    public function rangeByUser(Request $request, int $userId): JsonResponse
    {
        // Verificar se o usuário autenticado é admin ou gestor
        $authUser = Auth::user();
        if (!$authUser || ($authUser->role !== 'admin' && $authUser->role !== 'manager' && $authUser->id !== $userId)) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
        $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date);

        $entries = TimeEntry::where('user_id', $userId)
            ->forDateRange($startDate, $endDate)
            ->orderBy('check_in', 'desc')
            ->get();

        return response()->json([
            'user_id' => $userId,
            'period' => [
                'start' => $request->start_date,
                'end' => $request->end_date,
            ],
            'entries' => $entries,
            'total_hours' => $entries->sum('worked_hours'),
        ], 200);
    }

    /**
     * Deletar batida (apenas admins ou a própria do usuário)
     */
    public function delete(Request $request, int $entryId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $entry = TimeEntry::find($entryId);

        if (!$entry) {
            return response()->json(['message' => 'Batida não encontrada'], 404);
        }

        // Verificar permissão: apenas admin ou o próprio usuário
        if ($user->id !== $entry->user_id && $user->role !== 'admin') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $entry->delete();

        return response()->json(['message' => 'Batida deletada com sucesso'], 200);
    }

    /**
     * Listar todas as batidas (apenas para admins/gestores)
     */
    public function getAllEntries(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || ($user->role !== 'admin' && $user->role !== 'manager')) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 20);
        $userId = $request->query('user_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = TimeEntry::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($startDate && $endDate) {
            $start = Carbon::createFromFormat('Y-m-d', $startDate);
            $end = Carbon::createFromFormat('Y-m-d', $endDate);
            $query->forDateRange($start, $end);
        }

        $entries = $query->with('user')
            ->orderBy('check_in', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($entries, 200);
    }
}
