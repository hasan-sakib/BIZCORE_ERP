<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

final class NumberingController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->render('settings/numbering', [
            'pageTitle'   => 'Document Numbering',
            'breadcrumbs' => ['Settings' => '/settings', 'Numbering' => null],
        ]);
    }

    public function update(Request $request): Response
    {
        $this->success('Number series settings saved.');
        return $this->redirect('/settings/numbering');
    }
}
