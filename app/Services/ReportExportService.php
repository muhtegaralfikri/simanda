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

    public function exportExcel(string $title, array $headers, array $rows, User $user, string $filenamePrefix = 'laporan')
    {
        $filename = Str::slug($filenamePrefix).'-'.date('Y-m-d_H-i-s').'.xls';

        ActivityLog::log(
            'export_excel',
            'Laporan',
            "Mengekspor laporan '{$title}' format Excel sebanyak ".count($rows).' baris',
            null
        );

        $headersResponse = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($title, $headers, $rows, $user) {
            $file = fopen('php://output', 'w');

            // Write UTF-8 BOM for Excel compatibility
            fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF));

            $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            $html .= '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            $html .= '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Laporan</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            $html .= '<style>';
            $html .= 'body { font-family: Arial, sans-serif; }';
            $html .= '.title { font-size: 14pt; font-weight: bold; margin-bottom: 10px; }';
            $html .= '.meta { font-size: 10pt; color: #555555; margin-bottom: 15px; }';
            $html .= 'table { border-collapse: collapse; width: 100%; }';
            $html .= 'th { background-color: #1e293b; color: #ffffff; font-weight: bold; border: 1px solid #cbd5e1; padding: 8px; text-align: left; }';
            $html .= 'td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; }';
            $html .= '.number { text-align: right; }';
            $html .= '</style></head><body>';

            $html .= '<div class="title">SIMANDA — '.e($title).'</div>';
            $html .= '<div class="meta"><strong>Tanggal Cetak:</strong> '.date('d/m/Y H:i:s').' | <strong>Pengunduh:</strong> '.e($user->name).'</div>';
            $html .= '<table><thead><tr>';

            foreach ($headers as $header) {
                $html .= '<th>'.e($header).'</th>';
            }
            $html .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $sanitized = $this->sanitizeExcelValue($cell);
                    $isNum = is_numeric($cell);
                    $class = $isNum ? ' class="number"' : '';
                    $html .= '<td'.$class.'>'.e($sanitized).'</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table></body></html>';

            fwrite($file, $html);
            fclose($file);
        };

        return response()->stream($callback, 200, $headersResponse);
    }

    public function exportCsv(string $title, array $headers, array $rows, User $user, string $filenamePrefix = 'laporan')
    {
        $filename = Str::slug($filenamePrefix).'-'.date('Y-m-d_H-i-s').'.csv';

        ActivityLog::log(
            'export_excel',
            'Laporan',
            "Mengekspor laporan '{$title}' format CSV sebanyak ".count($rows).' baris',
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
