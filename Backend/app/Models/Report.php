<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Report extends Model
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'date_from',
        'date_to',
        'days_worked',
        'total_hours',
        'late_arrivals',
        'absences',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'details' => 'json',
        ];
    }

    /**
     * Relacionamento: Um relatório pertence a um usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gerar relatório diário
     */
    public static function generateDailyReport(int $userId, Carbon $date): ?Report
    {
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        // Obter batidas do dia
        $entries = TimeEntry::where('user_id', $userId)
            ->forDateRange($startDate, $endDate)
            ->get();

        if ($entries->isEmpty()) {
            return null; // Sem registros para o dia
        }

        $totalHours = $entries->sum('worked_hours');
        $lateArrivals = $entries->where('status', 'late')->count();

        $report = Report::updateOrCreate(
            [
                'user_id' => $userId,
                'type' => 'daily',
                'date_from' => $date,
                'date_to' => $date,
            ],
            [
                'days_worked' => $entries->whereNotNull('check_out')->count(),
                'total_hours' => $totalHours,
                'late_arrivals' => $lateArrivals,
                'absences' => 0,
                'details' => [
                    'entries' => $entries->load('user')->toArray(),
                    'generated_at' => now()->toIso8601String(),
                ],
            ]
        );

        return $report;
    }

    /**
     * Gerar relatório semanal
     */
    public static function generateWeeklyReport(int $userId, Carbon $startDate): ?Report
    {
        $endDate = $startDate->copy()->addDays(6);

        // Obter batidas da semana
        $entries = TimeEntry::where('user_id', $userId)
            ->forDateRange($startDate, $endDate)
            ->get();

        if ($entries->isEmpty()) {
            return null;
        }

        $totalHours = $entries->sum('worked_hours');
        $lateArrivals = $entries->where('status', 'late')->count();
        $daysWorked = $entries->groupBy(function ($entry) {
            return $entry->check_in->toDateString();
        })->count();

        $report = Report::updateOrCreate(
            [
                'user_id' => $userId,
                'type' => 'weekly',
                'date_from' => $startDate,
                'date_to' => $endDate,
            ],
            [
                'days_worked' => $daysWorked,
                'total_hours' => $totalHours,
                'late_arrivals' => $lateArrivals,
                'absences' => max(0, 5 - $daysWorked), // Assumindo 5 dias úteis
                'details' => [
                    'week_of' => $startDate->format('Y-m-d'),
                    'entries_count' => $entries->count(),
                    'daily_breakdown' => $entries->groupBy(function ($entry) {
                        return $entry->check_in->format('Y-m-d');
                    })->map(function ($daily) {
                        return [
                            'count' => $daily->count(),
                            'total_hours' => $daily->sum('worked_hours'),
                        ];
                    })->toArray(),
                ],
            ]
        );

        return $report;
    }

    /**
     * Gerar relatório mensal
     */
    public static function generateMonthlyReport(int $userId, int $month, int $year): ?Report
    {
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        // Obter batidas do mês
        $entries = TimeEntry::where('user_id', $userId)
            ->forDateRange($startDate, $endDate)
            ->get();

        if ($entries->isEmpty()) {
            return null;
        }

        $totalHours = $entries->sum('worked_hours');
        $lateArrivals = $entries->where('status', 'late')->count();
        $daysWorked = $entries->groupBy(function ($entry) {
            return $entry->check_in->toDateString();
        })->count();

        // Calcular ausências (dias úteis - dias trabalhados)
        $workingDays = $startDate->diffInWeekdays($endDate) + 1;
        $absences = max(0, $workingDays - $daysWorked);

        $report = Report::updateOrCreate(
            [
                'user_id' => $userId,
                'type' => 'monthly',
                'date_from' => $startDate,
                'date_to' => $endDate,
            ],
            [
                'days_worked' => $daysWorked,
                'total_hours' => $totalHours,
                'late_arrivals' => $lateArrivals,
                'absences' => $absences,
                'details' => [
                    'month' => $startDate->format('m/Y'),
                    'working_days' => $workingDays,
                    'entries_count' => $entries->count(),
                    'average_daily_hours' => $daysWorked > 0 ? round($totalHours / $daysWorked, 2) : 0,
                ],
            ]
        );

        return $report;
    }

    /**
     * Obter estatísticas de um período
     */
    public static function getStatsByPeriod(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $entries = TimeEntry::where('user_id', $userId)
            ->forDateRange($startDate, $endDate)
            ->get();

        return [
            'total_entries' => $entries->count(),
            'total_hours' => $entries->sum('worked_hours'),
            'completed' => $entries->where('status', 'completed')->count(),
            'late' => $entries->where('status', 'late')->count(),
            'open' => $entries->where('status', 'open')->count(),
            'average_daily_hours' => $entries->count() > 0 
                ? round($entries->sum('worked_hours') / $entries->groupBy(function ($e) {
                    return $e->check_in->toDateString();
                })->count(), 2)
                : 0,
        ];
    }

    /**
     * Scope para obter relatórios de um período
     */
    public function scopeForPeriod($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('date_from', [$startDate, $endDate]);
    }

    /**
     * Scope para obter relatórios de um tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
