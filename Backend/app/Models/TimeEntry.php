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
        'type',
        'check_in',
        'check_out',
        'status',
        'notes',
        'worked_hours',
        'location',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
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

        // Converter para Carbon se necessário
        $checkIn = $this->check_in instanceof Carbon 
            ? $this->check_in 
            : Carbon::parse($this->check_in);
        
        $checkOut = $this->check_out instanceof Carbon 
            ? $this->check_out 
            : Carbon::parse($this->check_out);

        $diffInMinutes = $checkIn->diffInMinutes($checkOut);
        return round($diffInMinutes / 60, 2);
    }

    /**
     * Validar tipo de batida e sequência
     */
    public static function validateEntryType(string $type, int $userId, Carbon $timestamp): array
    {
        $errors = [];

        if ($timestamp->isFuture()) {
            $errors[] = 'Batida não pode ser no futuro';
        }

        // Obter a última batida do dia do usuário
        $lastEntry = self::where('user_id', $userId)
            ->whereDate('check_in', $timestamp->toDateString())
            ->latest('check_in')
            ->first();

        // Validar sequência de tipos
        if ($type === 'check_in') {
            // Não pode ter check_in se já existe um aberto
            if ($lastEntry && !$lastEntry->check_out) {
                $errors[] = 'Você já possui uma batida aberta. Finalize-a primeiro.';
            }
        } elseif ($type === 'lunch_out') {
            // Precisa ter check_in aberto
            if (!$lastEntry || $lastEntry->check_out || $lastEntry->type !== 'check_in') {
                $errors[] = 'Você precisa fazer check-in primeiro';
            }
        } elseif ($type === 'lunch_in') {
            // Precisa ter lunch_out aberto (sem check_out)
            if (!$lastEntry || $lastEntry->check_out || $lastEntry->type !== 'lunch_out') {
                $errors[] = 'Você precisa fazer saída para almoço primeiro';
            }
        } elseif ($type === 'check_out') {
            // Precisa ter lunch_in aberto OU check_in aberto (se não saiu para almoço)
            if (!$lastEntry || $lastEntry->check_out) {
                $errors[] = 'Nenhuma batida aberta encontrada';
            }
            if ($lastEntry && $lastEntry->type !== 'lunch_in' && $lastEntry->type !== 'check_in') {
                $errors[] = 'Sequência de batida inválida';
            }
        }

        return $errors;
    }

    /**
     * Validar se o check_in é válido (não pode ser no futuro)
     */
    public static function validateCheckIn(Carbon $checkIn, ?int $userId = null): array
    {
        $errors = [];

        if ($checkIn->isFuture()) {
            $errors[] = 'Check-in não pode ser no futuro';
        }

        // Verificar se há duplicatas no mesmo dia
        if ($userId !== null) {
            $existingEntry = self::where('user_id', $userId)
                ->whereDate('check_in', $checkIn->toDateString())
                ->whereNull('check_out')
                ->first();

            if ($existingEntry) {
                $errors[] = 'Você já possui um check-in aberto para hoje';
            }
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

        // Converter check_in para Carbon se necessário
        $checkIn = $timeEntry->check_in instanceof Carbon 
            ? $timeEntry->check_in 
            : Carbon::parse($timeEntry->check_in);

        if ($checkOut->isBefore($checkIn)) {
            $errors[] = 'Check-out não pode ser anterior ao check-in';
        }

        // Máximo de 12 horas por dia
        $workedHours = $checkIn->diffInHours($checkOut);
        if ($workedHours > 12) {
            $errors[] = 'Não é permitido trabalhar mais de 12 horas por dia';
        }

        return $errors;
    }

    /**
     * Determinar status da batida
     */
    /**
     * Determinar status da batida
     */
    public function determineStatus(): string
    {
        if (!$this->check_in || !$this->check_out) {
            return 'open';
        }

        // Converter para Carbon se necessário
        $checkIn = $this->check_in instanceof Carbon 
            ? $this->check_in 
            : Carbon::parse($this->check_in);

        $expectedCheckInTime = Carbon::createFromTime(8, 0); // Horário esperado: 08:00
        $actualCheckInTime = $checkIn->copy()->setDateFrom(Carbon::now());

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
