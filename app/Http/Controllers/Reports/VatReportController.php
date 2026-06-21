<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\TaxRecord;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class VatReportController extends BaseController
{
    public function __construct(private readonly PdfService $pdfService) {}

    public function index(Request $request): View
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->get('to', now()->format('Y-m-d'));

        $outputVat = Invoice::whereBetween('invoice_date', [$from, $to])
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('SUM(vat_amount) as total_vat, COUNT(*) as count')
            ->first();

        $taxRecords = TaxRecord::whereBetween('tax_date', [$from, $to])->get();

        return view('reports.vat', compact('outputVat', 'taxRecords', 'from', 'to'));
    }

    public function pdf(Request $request): Response
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->get('to', now()->format('Y-m-d'));

        $outputVat = Invoice::whereBetween('invoice_date', [$from, $to])
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('SUM(vat_amount) as total_vat, COUNT(*) as count')
            ->first();

        $taxRecords = TaxRecord::whereBetween('tax_date', [$from, $to])->get();

        return $this->pdfService->download(
            'reports.vat-pdf',
            compact('outputVat', 'taxRecords', 'from', 'to'),
            "vat-report-{$from}-{$to}.pdf"
        );
    }
}
