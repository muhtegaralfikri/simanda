<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Str;

class ReportExportService
{
    /**
     * Prevents Excel Formula Injection by escaping strings starting with =, +, -, @
     */
    public function sanitizeExcelValue($value)
    {
        if (is_string($value) && strlen($value) > 0) {
            $firstChar = substr($value, 0, 1);
            if (in_array($firstChar, ['=', '+', '-', '@'])) {
                return "'".$value;
            }
        }

        return $value;
    }

    public function exportCsv(string $title, array $headers, array $rows, User $user, string $filenamePrefix = 'laporan')
    {
        $filename = Str::slug($filenamePrefix).'-'.date('Y-m-d_H-i-s').'.csv';

        ActivityLog::log(
            'export_excel',
            'Laporan',
            "Mengekspor laporan '{$title}' format CSV/Excel sebanyak ".count($rows).' baris',
            null
        );

        $headersResponse = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($title, $headers, $rows, $user) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write Title & Metadata Header
            fputcsv($file, ["SIMANDA — {$title}"]);
            fputcsv($file, ['Tanggal Cetak', date('d/m/Y H:i:s')]);
            fputcsv($file, ['Pengunduh', $user->name]);
            fputcsv($file, []); // Empty row

            // Write Table Headers
            fputcsv($file, $headers);

            // Write Rows
            foreach ($rows as $row) {
                $sanitizedRow = array_map([$this, 'sanitizeExcelValue'], $row);
                fputcsv($file, $sanitizedRow);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headersResponse);
    }

    public function exportPdfHtml(string $title, string $viewName, array $viewData, User $user, string $filenamePrefix = 'laporan')
    {
        ActivityLog::log(
            'export_pdf',
            'Laporan',
            "Mengekspor laporan '{$title}' format PDF/Printable HTML",
            null
        );

        $filename = Str::slug($filenamePrefix).'-'.date('Y-m-d_H-i-s').'.html';

        $htmlContent = view($viewName, array_merge($viewData, [
            'reportTitle' => $title,
            'exportedAt' => date('d/m/Y H:i:s'),
            'exportedBy' => $user->name,
            'isPdf' => true,
        ]))->render();

        return response($htmlContent, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}
