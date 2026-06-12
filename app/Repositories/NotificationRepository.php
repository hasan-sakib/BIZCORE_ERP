<?php

declare(strict_types=1);

namespace App\Repositories;

final class NotificationRepository extends BaseRepository
{
    public function forUser(int $userId, int $limit = 20): array
    {
        return $this->fetchAll(
            'SELECT * FROM notifications
             WHERE user_id = :uid AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY created_at DESC LIMIT ' . (int) $limit,
            [':uid' => $userId],
        );
    }

    public function unreadCount(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) c FROM notifications WHERE user_id = :uid AND read_at IS NULL',
            [':uid' => $userId],
        );
        return (int) ($row['c'] ?? 0);
    }

    public function unreadForUser(int $userId, int $limit = 10): array
    {
        return $this->fetchAll(
            'SELECT * FROM notifications
             WHERE user_id = :uid AND read_at IS NULL
             ORDER BY created_at DESC LIMIT ' . (int) $limit,
            [':uid' => $userId],
        );
    }

    public function markRead(int $id, int $userId): void
    {
        $this->modify(
            'UPDATE notifications SET read_at = NOW() WHERE id = :id AND user_id = :uid',
            [':id' => $id, ':uid' => $userId],
        );
    }

    public function markAllRead(int $userId): void
    {
        $this->modify(
            'UPDATE notifications SET read_at = NOW() WHERE user_id = :uid AND read_at IS NULL',
            [':uid' => $userId],
        );
    }

    public function delete(int $id, int $userId): void
    {
        $this->modify('DELETE FROM notifications WHERE id = :id AND user_id = :uid', [':id' => $id, ':uid' => $userId]);
    }

    public function clearAll(int $userId): void
    {
        $this->modify('DELETE FROM notifications WHERE user_id = :uid', [':uid' => $userId]);
    }
}
