<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TimeEntry extends Model
{
    /** @use HasFactory<\Database\Factories\TimeEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'check_in',
        'check_out',
        'status',
        'notes',
        'worked_hours',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'datetime',
            'check_out' => 'datetime',
        ];
    }

    /**
     * Relacionamento: Uma batida pertence a um usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calcular horas trabalhadas entre check_in e check_out
     */
    public function calculateWorkedHours(): float
    {
        if (!$this->check_in || !$this->check_out) {
            return 0;
        }

        $diffInMinutes = $this->check_in->diffInMinutes($this->check_out);
        return round($diffInMinutes / 60, 2);
    }

    /**
     * Validar se o check_in é válido (não pode ser no futuro)
     */
    public static function validateCheckIn(Carbon $checkIn): array
    {
        $errors = [];

        if ($checkIn->isFuture()) {
            $errors[] = 'Check-in não pode ser no futuro';
        }

        // Verificar se há duplicatas no mesmo dia
        $existingEntry = self::where('user_id', auth()->id())
            ->whereDate('check_in', $checkIn->toDateString())
            ->whereNull('check_out')
            ->first();

        if ($existingEntry) {
            $errors[] = 'Você já possui um check-in aberto para hoje';
        }

        return $errors;
    }

    /**
     * Validar se o check_out é válido
     */
    public static function validateCheckOut(TimeEntry $timeEntry, Carbon $checkOut): array
    {
        $errors = [];

        if ($checkOut->isFuture()) {
            $errors[] = 'Check-out não pode ser no futuro';
        }

        if ($checkOut->isBefore($timeEntry->check_in)) {
            $errors[] = 'Check-out não pode ser anterior ao check-in';
        }

        // Máximo de 12 horas por dia
        $workedHours = $timeEntry->check_in->diffInHours($checkOut);
        if ($workedHours > 12) {
            $errors[] = 'Não é permitido trabalhar mais de 12 horas por dia';
        }

        return $errors;
    }

    /**
     * Determinar status da batida
     */
    public function determineStatus(): string
    {
        if (!$this->check_in || !$this->check_out) {
            return 'open';
        }

        $expectedCheckInTime = Carbon::createFromTime(8, 0); // Horário esperado: 08:00
        $actualCheckInTime = $this->check_in->copy()->setDateFrom(Carbon::now());

        if ($actualCheckInTime->isAfter($expectedCheckInTime)) {
            return 'late';
        }

        return 'completed';
    }

    /**
     * Obter horas trabalhadas hoje para um usuário
     */
    public static function getTodayWorkedHours(int $userId): float
    {
        return self::where('user_id', $userId)
            ->whereDate('check_in', Carbon::today())
            ->sum('worked_hours');
    }

    /**
     * Scopefunções para queries comuns
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->whereDate('check_in', $date);
    }

    public function scopeForDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('check_in', [$startDate->startOfDay(), $endDate->endOfDay()]);
    }

    public function scopeCheckedOut($query)
    {
        return $query->whereNotNull('check_out');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('check_out');
    }
}
