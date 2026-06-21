<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\BaseController;
use App\Services\AccountingService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FinancialStatementController extends BaseController
{
    public function __construct(
        private readonly AccountingService $accountingService,
        private readonly PdfService        $pdfService,
    ) {}

    public function incomeStatement(Request $request): View
    {
        $from   = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to     = $request->get('to', now()->format('Y-m-d'));
        $report = $this->accountingService->getIncomeStatement($from, $to);
        return view('accounting.statements.income', compact('report', 'from', 'to'));
    }

    public function balanceSheet(Request $request): View
    {
        $asOf   = $request->get('as_of', now()->format('Y-m-d'));
        $report = $this->accountingService->getBalanceSheet($asOf);
        return view('accounting.statements.balance-sheet', compact('report', 'asOf'));
    }

    public function incomeStatementPdf(Request $request): Response
    {
        $from   = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to     = $request->get('to', now()->format('Y-m-d'));
        $report = $this->accountingService->getIncomeStatement($from, $to);
        return $this->pdfService->download('accounting.statements.income-pdf', compact('report', 'from', 'to'), "income-statement-{$from}-{$to}.pdf");
    }

    public function balanceSheetPdf(Request $request): Response
    {
        $asOf   = $request->get('as_of', now()->format('Y-m-d'));
        $report = $this->accountingService->getBalanceSheet($asOf);
        return $this->pdfService->download('accounting.statements.balance-sheet-pdf', compact('report', 'asOf'), "balance-sheet-{$asOf}.pdf");
    }
}
