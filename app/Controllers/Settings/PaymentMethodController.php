<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

final class PaymentMethodController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->render('settings/payment-methods', [
            'pageTitle'   => 'Payment Methods',
            'breadcrumbs' => ['Settings' => '/settings', 'Payment Methods' => null],
        ]);
    }

    public function store(Request $request): Response
    {
        $this->error('Payment method management not yet implemented.');
        return $this->redirect('/settings/payment-methods');
    }

    public function update(Request $request, int $id): Response
    {
        $this->error('Payment method management not yet implemented.');
        return $this->redirect('/settings/payment-methods');
    }

    public function destroy(int $id): Response
    {
        $this->error('Payment method management not yet implemented.');
        return $this->redirect('/settings/payment-methods');
    }
}
