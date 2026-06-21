<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExcelService
{
    public function download(string $filename, array $headers, array $rows, string $sheetTitle = 'Sheet1'): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle);

        // Header row
        $col = 'A';
        foreach ($headers as $header) {
            $cell = $sheet->getCell($col . '1');
            $cell->setValue($header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $headerRange = 'A1:' . (chr(ord('A') + count($headers) - 1)) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3864']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Data rows
        $rowNum = 2;
        foreach ($rows as $row) {
            $col = 'A';
            foreach (array_values($row) as $value) {
                $sheet->getCell($col . $rowNum)->setValue($value);
                $col++;
            }
            $rowNum++;
        }

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $content = ob_get_clean();

        return new Response($content, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    public function fromArray(array $data, string $sheetTitle = 'Data'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle);
        $sheet->fromArray($data, null, 'A1');
        return $spreadsheet;
    }
}
