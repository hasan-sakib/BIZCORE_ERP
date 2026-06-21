<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

class PdfService
{
    public function generate(string $view, array $data = [], string $filename = 'document.pdf'): Response
    {
        $html = view($view, $data)->render();
        $pdf  = $this->buildPdf($html);

        return new Response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function download(string $view, array $data = [], string $filename = 'document.pdf'): Response
    {
        $html = view($view, $data)->render();
        $pdf  = $this->buildPdf($html);

        return new Response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function html(string $html, string $filename = 'document.pdf'): Response
    {
        $pdf = $this->buildPdf($html);

        return new Response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    private function buildPdf(string $html): Dompdf
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return $pdf;
    }
}
