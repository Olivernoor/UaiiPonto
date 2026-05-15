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
     * Registrar batida de ponto (suporta 4 tipos: check_in, lunch_out, lunch_in, check_out)
     */
    public function checkIn(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string|in:check_in,lunch_out,lunch_in,check_out',
            'notes' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = $request->type ?? 'check_in';
        $timestamp = Carbon::now();

        try {
            // Validar tipo de batida e a lógica associada
            $errors = TimeEntry::validateEntryType($type, $user->id, $timestamp);
            if (!empty($errors)) {
                return response()->json(['errors' => $errors], 422);
            }

            // Criar registro de batida
            $timeEntry = TimeEntry::create([
                'user_id' => $user->id,
                'type' => $type,
                'check_in' => $timestamp,
                'notes' => $request->notes ?? null,
                'location' => $request->location ?? null,
                'latitude' => $request->latitude ?? null,
                'longitude' => $request->longitude ?? null,
            ]);

            return response()->json([
                'message' => "Batida ($type) registrada com sucesso",
                'data' => $timeEntry,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao registrar batida',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar check-out do usuário
     */
    public function checkOut(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Não autenticado'], 401);
            }

            // Encontrar a última batida aberta do usuário
            $timeEntry = TimeEntry::where('user_id', $user->id)
                ->where('check_out', null)
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
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao registrar check-out',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter batidas de hoje do usuário (check_in, lunch_out, lunch_in, check_out)
     */
    public function todayEntry(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        // Obter todas as batidas de hoje
        $entries = TimeEntry::where('user_id', $user->id)
            ->whereDate('check_in', Carbon::today())
            ->orderBy('check_in')
            ->get();

        // Formatar resposta com status de cada tipo
        $status = [
            'check_in' => null,
            'lunch_out' => null,
            'lunch_in' => null,
            'check_out' => null,
        ];

        $totalEntries = [];

        foreach ($entries as $entry) {
            $checkInTime = is_string($entry->check_in) ? Carbon::parse($entry->check_in) : $entry->check_in;
            $status[$entry->type] = [
                'time' => $checkInTime->format('H:i:s'),
                'location' => $entry->location,
                'latitude' => $entry->latitude,
                'longitude' => $entry->longitude,
                'id' => $entry->id,
            ];
            $totalEntries[] = $entry;
        }

        return response()->json([
            'status' => $status,
            'entries' => $totalEntries,
        ], 200);
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

    /**
     * Exportar dados de batidas para Excel (JSON que será convertido no frontend)
     */
    public function exportExcel(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        // Obter todas as batidas do usuário
        $entries = TimeEntry::where('user_id', $user->id)
            ->orderBy('check_in')
            ->get();

        // Formatar dados para Excel
        $data = [];
        
        foreach ($entries as $entry) {
            // Converter check_in para Carbon se necessário
            $checkInTime = is_string($entry->check_in) ? Carbon::parse($entry->check_in) : $entry->check_in;
            
            $data[] = [
                'Data' => $checkInTime->format('d/m/Y'),
                'Tipo' => $this->getTypeLabel($entry->type),
                'Hora' => $checkInTime->format('H:i:s'),
                'Localização' => $entry->location ?? 'N/A',
                'Latitude' => $entry->latitude ?? 'N/A',
                'Longitude' => $entry->longitude ?? 'N/A',
                'Horas Trabalhadas' => $entry->worked_hours ?? 'N/A',
                'Status' => $entry->status,
                'Notas' => $entry->notes ?? 'N/A',
            ];
        }

        // Calcular período
        $firstEntry = $entries->first();
        $lastEntry = $entries->last();
        $startDate = $firstEntry ? (is_string($firstEntry->check_in) ? Carbon::parse($firstEntry->check_in) : $firstEntry->check_in) : null;
        $endDate = $lastEntry ? (is_string($lastEntry->check_in) ? Carbon::parse($lastEntry->check_in) : $lastEntry->check_in) : null;

        return response()->json([
            'usuario' => [
                'nome' => $user->name,
                'email' => $user->email,
                'organização' => $user->organization,
            ],
            'data_exportacao' => now()->format('d/m/Y H:i:s'),
            'periodo' => [
                'inicio' => $startDate ? $startDate->format('d/m/Y') : 'N/A',
                'fim' => $endDate ? $endDate->format('d/m/Y') : 'N/A',
            ],
            'dados' => $data,
            'total_registros' => count($data),
        ], 200);
    }

    /**
     * Converter tipo de batida para label legível
     */
    private function getTypeLabel(string $type): string
    {
        $labels = [
            'check_in' => 'Entrada',
            'lunch_out' => 'Saída Almoço',
            'lunch_in' => 'Volta Almoço',
            'check_out' => 'Saída Final',
        ];

        return $labels[$type] ?? $type;
    }
}
