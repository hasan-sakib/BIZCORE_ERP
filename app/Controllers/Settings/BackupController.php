<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

final class BackupController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->render('settings/backup', [
            'pageTitle'   => 'Backup & Restore',
            'breadcrumbs' => ['Settings' => '/settings', 'Backup' => null],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->error('Backup is not yet implemented.');
        return $this->redirect('/settings/backup');
    }

    public function restore(Request $request, int $id): Response
    {
        $this->error('Restore is not yet implemented.');
        return $this->redirect('/settings/backup');
    }

    public function destroy(int $id): Response
    {
        $this->error('Delete backup is not yet implemented.');
        return $this->redirect('/settings/backup');
    }
}
