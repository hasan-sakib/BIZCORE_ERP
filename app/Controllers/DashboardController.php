<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Services\ReportService;

class DashboardController extends BaseController
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    public function index(Request $request): Response
    {
        $user     = $this->currentUser();
        $branchId = $user?->branchId ?? 0;

        try {
            $metrics = $this->reportService->getDashboardMetrics($branchId);
        } catch (\Throwable) {
            $metrics = [];
        }

        return $this->render('dashboard/index', [
            'pageTitle'   => 'Dashboard',
            'metrics'     => $metrics,
            'currentUser' => $user,
        ]);
    }
}
