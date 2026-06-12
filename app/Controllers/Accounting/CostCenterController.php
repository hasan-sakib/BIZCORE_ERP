<?php

declare(strict_types=1);

namespace App\Controllers\Accounting;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

final class CostCenterController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->render('accounting/cost-centers/index', [
            'pageTitle'   => 'Cost Centers',
            'breadcrumbs' => ['Accounting' => null, 'Cost Centers' => null],
        ]);
    }

    public function store(Request $request): Response
    {
        $this->error('Cost centers are not yet implemented.');
        return $this->redirect('/accounting/cost-centers');
    }

    public function update(Request $request, int $id): Response
    {
        $this->error('Cost centers are not yet implemented.');
        return $this->redirect('/accounting/cost-centers');
    }

    public function destroy(int $id): Response
    {
        $this->error('Cost centers are not yet implemented.');
        return $this->redirect('/accounting/cost-centers');
    }
}
