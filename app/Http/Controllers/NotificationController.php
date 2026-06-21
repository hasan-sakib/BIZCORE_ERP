<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends BaseController
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(): View
    {
        $notifications = $this->notificationService->paginate(Auth::id());
        return view('notifications.index', compact('notifications'));
    }

    public function markRead(int $id): RedirectResponse
    {
        $this->notificationService->markRead($id, Auth::id());
        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        $this->notificationService->markAllRead(Auth::id());
        $this->success('All notifications marked as read.');
        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->notificationService->delete($id, Auth::id());
        return back();
    }
}
