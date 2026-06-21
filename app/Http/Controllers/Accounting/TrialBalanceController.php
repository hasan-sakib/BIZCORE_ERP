<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\BaseController;
use App\Services\AccountingService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TrialBalanceController extends BaseController
{
    public function __construct(
        private readonly AccountingService $accountingService,
        private readonly PdfService        $pdfService,
    ) {}

    public function index(Request $request): View
    {
        $asOf   = $request->get('as_of', now()->format('Y-m-d'));
        $report = $this->accountingService->getTrialBalance($asOf);
        return view('accounting.trial-balance.index', compact('report', 'asOf'));
    }

    public function pdf(Request $request): Response
    {
        $asOf   = $request->get('as_of', now()->format('Y-m-d'));
        $report = $this->accountingService->getTrialBalance($asOf);
        return $this->pdfService->download('accounting.trial-balance.pdf', compact('report', 'asOf'), "trial-balance-{$asOf}.pdf");
    }
}
