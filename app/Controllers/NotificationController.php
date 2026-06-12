<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\NotificationRepository;

final class NotificationController extends BaseController
{
    public function __construct(private readonly NotificationRepository $notifications) {}

    public function index(Request $request): Response
    {
        $user          = $this->currentUser();
        $notifications = $user ? $this->notifications->forUser($user->id, 50) : [];

        return $this->render('notifications/index', [
            'pageTitle'     => 'Notifications',
            'breadcrumbs'   => ['Notifications' => null],
            'notifications' => $notifications,
        ]);
    }

    public function unread(Request $request): Response
    {
        $user  = $this->currentUser();
        $items = $user ? $this->notifications->unreadForUser($user->id, 10) : [];
        $count = $user ? $this->notifications->unreadCount($user->id) : 0;

        return $this->json(['success' => true, 'count' => $count, 'notifications' => $items]);
    }

    public function unreadCount(Request $request): Response
    {
        $user  = $this->currentUser();
        $count = $user ? $this->notifications->unreadCount($user->id) : 0;

        return $this->json(['success' => true, 'count' => $count]);
    }

    public function markRead(Request $request, int $id): Response
    {
        $user = $this->currentUser();
        if ($user) {
            $this->notifications->markRead($id, $user->id);
        }
        return $this->json(['success' => true]);
    }

    public function markAllRead(Request $request): Response
    {
        $user = $this->currentUser();
        if ($user) {
            $this->notifications->markAllRead($user->id);
        }
        return $this->json(['success' => true]);
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $this->currentUser();
        if ($user) {
            $this->notifications->delete($id, $user->id);
        }
        return $this->json(['success' => true]);
    }

    public function clearAll(Request $request): Response
    {
        $user = $this->currentUser();
        if ($user) {
            $this->notifications->clearAll($user->id);
        }
        return $this->json(['success' => true]);
    }

    public function preferences(Request $request): Response
    {
        return $this->render('notifications/preferences', [
            'pageTitle'   => 'Notification Preferences',
            'breadcrumbs' => ['Notifications' => '/notifications', 'Preferences' => null],
        ]);
    }

    public function updatePreferences(Request $request): Response
    {
        $this->success('Notification preferences saved.');
        return $this->redirect('/notifications/preferences');
    }
}
