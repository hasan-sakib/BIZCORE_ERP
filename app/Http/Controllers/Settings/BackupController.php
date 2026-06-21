<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\BaseController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BackupController extends BaseController
{
    public function index(): View
    {
        $backups = collect(Storage::disk('local')->files('backups'))
            ->map(fn ($path) => [
                'path'    => $path,
                'name'    => basename($path),
                'size'    => Storage::disk('local')->size($path),
                'created' => Storage::disk('local')->lastModified($path),
            ])
            ->sortByDesc('created')
            ->values();

        return view('settings.backup', compact('backups'));
    }

    public function create(): RedirectResponse
    {
        try {
            Artisan::call('db:backup');
            $this->success('Backup created successfully.');
        } catch (\Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());
        }

        return back();
    }

    public function download(Request $request)
    {
        $file = $request->validate(['file' => ['required', 'string']])['file'];
        $path = 'backups/' . basename($file);

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $file = $request->validate(['file' => ['required', 'string']])['file'];
        $path = 'backups/' . basename($file);
        Storage::disk('local')->delete($path);
        $this->success('Backup deleted.');
        return back();
    }
}
