<?php

declare(strict_types=1);

namespace App\Controllers\Reports;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

final class VatReportController extends BaseController
{
    public function mushak(Request $request): Response
    {
        return $this->render('reports/vat-mushak', [
            'pageTitle'   => 'VAT Mushak Report',
            'breadcrumbs' => ['Reports' => '/reports', 'VAT Mushak' => null],
        ]);
    }

    public function vatReturn(Request $request): Response
    {
        return $this->render('reports/vat-return', [
            'pageTitle'   => 'VAT Return',
            'breadcrumbs' => ['Reports' => '/reports', 'VAT Return' => null],
        ]);
    }
}
