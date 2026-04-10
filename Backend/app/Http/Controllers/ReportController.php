<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\TimeEntry;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    /**
     * Gerar e retornar relatório diário
     */
    public function dailyReport(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date_format:Y-m-d',
            'user_id' => 'nullable|integer', // Para gestores visualizarem relatório de outro usuário
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $date = $request->date ? Carbon::createFromFormat('Y-m-d', $request->date) : Carbon::today();
        $userId = $request->user_id ?? $user->id;

        // Verificar permissão: apenas admin/manager podem ver relatório de outro usuário
        if ($userId !== $user->id && $user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        // Gerar ou obter relatório do cache
        $report = Report::generateDailyReport($userId, $date);

        if (!$report) {
            return response()->json(['message' => 'Nenhum registro para o dia solicitado'], 404);
        }

        return response()->json($report, 200);
    }

    /**
     * Gerar e retornar relatório semanal
     */
    public function weeklyReport(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date_format:Y-m-d',
            'user_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $startDate = $request->start_date 
            ? Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfWeek()
            : Carbon::now()->startOfWeek();

        $userId = $request->user_id ?? $user->id;

        // Verificar permissão
        if ($userId !== $user->id && $user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $report = Report::generateWeeklyReport($userId, $startDate);

        if (!$report) {
            return response()->json(['message' => 'Nenhum registro para a semana solicitada'], 404);
        }

        return response()->json($report, 200);
    }

    /**
     * Gerar e retornar relatório mensal
     */
    public function monthlyReport(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $validator = Validator::make($request->all(), [
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|min:2020',
            'user_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;
        $userId = $request->user_id ?? $user->id;

        // Verificar permissão
        if ($userId !== $user->id && $user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $report = Report::generateMonthlyReport($userId, $month, $year);

        if (!$report) {
            return response()->json(['message' => 'Nenhum registro para o mês solicitado'], 404);
        }

        return response()->json($report, 200);
    }

    /**
     * Obter estatísticas de um período
     */
    public function periodStats(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'user_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
        $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date);
        $userId = $request->user_id ?? $user->id;

        // Verificar permissão
        if ($userId !== $user->id && $user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $stats = Report::getStatsByPeriod($userId, $startDate, $endDate);

        return response()->json([
            'period' => [
                'start' => $request->start_date,
                'end' => $request->end_date,
            ],
            'user_id' => $userId,
            'stats' => $stats,
        ], 200);
    }

    /**
     * Listar todos os relatórios de um usuário
     */
    public function listReports(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $userId = $request->user_id ?? $user->id;

        // Verificar permissão
        if ($userId !== $user->id && $user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        $type = $request->query('type'); // 'daily', 'weekly', 'monthly'
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 20);

        $query = Report::where('user_id', $userId);

        if ($type) {
            $query->ofType($type);
        }

        $reports = $query->orderBy('date_from', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($reports, 200);
    }

    /**
     * Exportar relatório em PDF
     */
    public function exportPdf(Request $request): JsonResponse|BinaryFileResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:daily,weekly,monthly',
            'date' => 'nullable|date_format:Y-m-d',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|min:2020',
            'user_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = $request->type;
        $userId = $request->user_id ?? $user->id;

        // Verificar permissão
        if ($userId !== $user->id && $user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        // Obter o relatório
        $report = $this->getReport($type, $userId, $request);

        if (!$report) {
            return response()->json(['message' => 'Nenhum dado para exportar'], 404);
        }

        // Retornar dados estruturados para o frontend gerar o PDF
        $data = ReportExportService::generatePdfExportData($report);

        return response()->json([
            'message' => 'Dados para exportação em PDF gerados com sucesso',
            'type' => 'pdf',
            'data' => $data,
        ], 200);
    }

    /**
     * Exportar relatório em Excel
     */
    public function exportExcel(Request $request): JsonResponse|BinaryFileResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:daily,weekly,monthly',
            'date' => 'nullable|date_format:Y-m-d',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|min:2020',
            'user_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = $request->type;
        $userId = $request->user_id ?? $user->id;

        // Verificar permissão
        if ($userId !== $user->id && $user->role !== 'admin' && $user->role !== 'manager') {
            return response()->json(['message' => 'Acesso negado'], 403);
        }

        // Obter o relatório
        $report = $this->getReport($type, $userId, $request);

        if (!$report) {
            return response()->json(['message' => 'Nenhum dado para exportar'], 404);
        }

        try {
            // Gerar CSV (mais simples e universal)
            $fileName = "relatorio_{$type}_{$report->id}_{$userId}.csv";
            $filePath = ReportExportService::exportToCsv($report, $fileName);

            return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Fallback: retornar dados estruturados para o frontend
            $data = ReportExportService::generateExcelExportData($report);

            return response()->json([
                'message' => 'Dados para exportação em Excel gerados com sucesso',
                'type' => 'excel',
                'data' => $data,
                'hint' => 'Use uma biblioteca JavaScript como SheetJS para converter para Excel',
            ], 200);
        }
    }

    /**
     * Função auxiliar para obter relatório
     */
    private function getReport(string $type, int $userId, Request $request): ?Report
    {
        switch ($type) {
            case 'daily':
                $date = $request->date ? Carbon::createFromFormat('Y-m-d', $request->date) : Carbon::today();
                $report = Report::generateDailyReport($userId, $date);
                break;

            case 'weekly':
                $startDate = $request->start_date ? Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfWeek() : Carbon::now()->startOfWeek();
                $report = Report::generateWeeklyReport($userId, $startDate);
                break;

            case 'monthly':
                $month = $request->month ?? Carbon::now()->month;
                $year = $request->year ?? Carbon::now()->year;
                $report = Report::generateMonthlyReport($userId, $month, $year);
                break;

            default:
                return null;
        }

        return $report ?? null;
    }

    /**
     * Função auxiliar para obter dados do relatório
     */
    private function getReportData(string $type, int $userId, Request $request): ?array
    {
        $report = $this->getReport($type, $userId, $request);
        return $report ? $report->toArray() : null;
    }
}
