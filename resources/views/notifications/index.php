<?php ob_start(); ?>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-bell me-2 text-primary"></i>All Notifications</h6>
        <?php if (!empty($notifications)): ?>
            <form method="POST" action="/notifications/mark-all-read" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">Mark All Read</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-bell-slash fa-3x mb-3 opacity-25"></i>
                <p>No notifications yet.</p>
            </div>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($notifications as $notif): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start <?= $notif['read_at'] === null ? 'bg-light' : '' ?>">
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?= sanitize($notif['title']) ?></div>
                            <div class="text-muted small"><?= sanitize($notif['message']) ?></div>
                            <div class="text-muted small mt-1">
                                <i class="fas fa-clock me-1"></i><?= sanitize($notif['created_at']) ?>
                                <?php if ($notif['read_at'] === null): ?>
                                    <span class="badge bg-primary ms-2">New</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex gap-1 ms-3">
                            <?php if ($notif['read_at'] === null): ?>
                                <form method="POST" action="/notifications/<?= (int) $notif['id'] ?>/mark-read">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-outline-secondary btn-xs" title="Mark read"><i class="fas fa-check"></i></button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" action="/notifications/<?= (int) $notif['id'] ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn btn-outline-danger btn-xs" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
