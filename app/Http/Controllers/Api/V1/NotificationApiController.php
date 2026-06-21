<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends BaseApiController
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        return $this->paginate($this->notificationService->paginate($this->currentUser()?->id));
    }

    public function unreadCount(): JsonResponse
    {
        return $this->success(['count' => $this->notificationService->unreadCount($this->currentUser()?->id)]);
    }

    public function markRead(int $id): JsonResponse
    {
        $this->notificationService->markRead($id, $this->currentUser()?->id);
        return $this->success(['message' => 'Notification marked as read.']);
    }

    public function markAllRead(): JsonResponse
    {
        $this->notificationService->markAllRead($this->currentUser()?->id);
        return $this->success(['message' => 'All notifications marked as read.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->notificationService->delete($id, $this->currentUser()?->id);
        return $this->success(['message' => 'Notification deleted.']);
    }
}
