<?php

namespace App\Services;

use App\Models\Report;
use App\Models\TimeEntry;
use Carbon\Carbon;

class ReportExportService
{
    /**
     * Exportar relatório para CSV
     */
    public static function exportToCsv(Report $report, string $fileName = 'relatorio.csv'): string
    {
        $entries = TimeEntry::where('user_id', $report->user_id)
            ->forDateRange($report->date_from, $report->date_to)
            ->get();

        $filePath = storage_path("reports/{$fileName}");

        // Criar diretório se não existir
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $handle = fopen($filePath, 'w');

        // BOM para suportar UTF-8 no Excel
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        fputcsv($handle, ['Relatório de Batida de Ponto - UaiiPonto']);
        fputcsv($handle, []);
        fputcsv($handle, ['Usuário:' . $report->user->name]);
        fputcsv($handle, ['Período:' . $report->date_from->format('d/m/Y') . ' até ' . $report->date_to->format('d/m/Y')]);
        fputcsv($handle, ['Tipo:' . ucfirst($report->type)]);
        fputcsv($handle, []);

        // Cabeçalho da tabela
        fputcsv($handle, ['Data', 'Check-In', 'Check-Out', 'Horas Trabalhadas', 'Status']);

        // Dados
        foreach ($entries as $entry) {
            $date = $entry->check_in ? $entry->check_in->format('d/m/Y') : '-';
            $checkIn = $entry->check_in ? $entry->check_in->format('H:i:s') : '-';
            $checkOut = $entry->check_out ? $entry->check_out->format('H:i:s') : 'Aberto';
            $hours = $entry->worked_hours ?? '0';
            $status = ucfirst($entry->status);

            fputcsv($handle, [$date, $checkIn, $checkOut, $hours, $status]);
        }

        // Resumo
        fputcsv($handle, []);
        fputcsv($handle, ['Resumo']);
        fputcsv($handle, ['Dias Trabalhados', $report->days_worked]);
        fputcsv($handle, ['Total de Horas', $report->total_hours]);
        fputcsv($handle, ['Atrasos', $report->late_arrivals]);
        fputcsv($handle, ['Ausências', $report->absences]);

        fclose($handle);

        return $filePath;
    }

    /**
     * Gerar dados estruturados para exportação em PDF (frontend)
     * Retorna um array com dados prontos para ser renderizado no frontend
     */
    public static function generatePdfExportData(Report $report): array
    {
        $entries = TimeEntry::where('user_id', $report->user_id)
            ->forDateRange($report->date_from, $report->date_to)
            ->get();

        return [
            'title' => 'Relatório de Batida de Ponto',
            'user' => [
                'name' => $report->user->name,
                'email' => $report->user->email,
            ],
            'period' => [
                'start' => $report->date_from->format('d/m/Y'),
                'end' => $report->date_to->format('d/m/Y'),
                'type' => ucfirst($report->type),
            ],
            'entries' => $entries->map(function ($entry) {
                return [
                    'date' => $entry->check_in ? $entry->check_in->format('d/m/Y') : '-',
                    'check_in' => $entry->check_in ? $entry->check_in->format('H:i:s') : '-',
                    'check_out' => $entry->check_out ? $entry->check_out->format('H:i:s') : 'Aberto',
                    'worked_hours' => $entry->worked_hours ?? 0,
                    'status' => $entry->status,
                ];
            })->toArray(),
            'summary' => [
                'days_worked' => $report->days_worked,
                'total_hours' => $report->total_hours,
                'late_arrivals' => $report->late_arrivals,
                'absences' => $report->absences,
            ],
            'generated_at' => $report->created_at->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Gerar dados estruturados para exportação em Excel (frontend)
     */
    public static function generateExcelExportData(Report $report): array
    {
        return self::generatePdfExportData($report);
    }
}

