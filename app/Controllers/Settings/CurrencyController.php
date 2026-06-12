<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

final class CurrencyController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->render('settings/currencies', [
            'pageTitle'   => 'Currencies',
            'breadcrumbs' => ['Settings' => '/settings', 'Currencies' => null],
        ]);
    }

    public function store(Request $request): Response
    {
        $this->error('Currency management not yet implemented.');
        return $this->redirect('/settings/currencies');
    }

    public function update(Request $request, int $id): Response
    {
        $this->error('Currency management not yet implemented.');
        return $this->redirect('/settings/currencies');
    }
}
