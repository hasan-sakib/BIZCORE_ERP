<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function send(int $userId, string $title, string $message, string $type = 'info', ?string $actionUrl = null): Notification
    {
        return Notification::create([
            'user_id'    => $userId,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'action_url' => $actionUrl,
            'is_read'    => false,
        ]);
    }

    public function broadcast(array $userIds, string $title, string $message, string $type = 'info', ?string $actionUrl = null): int
    {
        $records = array_map(fn($id) => [
            'user_id'    => $id,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'action_url' => $actionUrl,
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $userIds);

        DB::table('notifications')->insert($records);

        return count($records);
    }

    public function markRead(int $notificationId, int $userId): void
    {
        Notification::where('id', $notificationId)->where('user_id', $userId)->update(['is_read' => true]);
    }

    public function markAllRead(int $userId): void
    {
        Notification::where('user_id', $userId)->where('is_read', false)->update(['is_read' => true]);
    }

    public function delete(int $notificationId, int $userId): void
    {
        Notification::where('id', $notificationId)->where('user_id', $userId)->delete();
    }

    public function paginate(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function unreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->where('is_read', false)->count();
    }
}
