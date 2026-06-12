<?php
/**
 * Reusable pagination component.
 * Expects: $pagination array from paginate() helper, plus $_GET query params preserved.
 */
if (empty($pagination) || $pagination['total_pages'] <= 1) return;

$current = $pagination['current_page'];
$total   = $pagination['total_pages'];
$perPage = $pagination['per_page'];
$window  = 2;

function pageUrl(int $page): string {
    $params = array_merge($_GET, ['page' => $page]);
    return '?' . http_build_query($params);
}
?>

<nav aria-label="Page navigation">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="text-muted small">
            Showing <?= number_format($pagination['from'] ?? 0) ?> – <?= number_format($pagination['to'] ?? 0) ?>
            of <?= number_format($pagination['total']) ?> results
        </div>

        <ul class="pagination pagination-sm mb-0">
            <!-- Previous -->
            <li class="page-item <?= $current <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $current > 1 ? pageUrl($current - 1) : '#' ?>" aria-label="Previous">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>

            <!-- First page -->
            <?php if ($current > $window + 2): ?>
                <li class="page-item"><a class="page-link" href="<?= pageUrl(1) ?>">1</a></li>
                <?php if ($current > $window + 3): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Windowed pages -->
            <?php for ($p = max(1, $current - $window); $p <= min($total, $current + $window); $p++): ?>
                <li class="page-item <?= $p === $current ? 'active' : '' ?>">
                    <a class="page-link" href="<?= pageUrl($p) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>

            <!-- Last page -->
            <?php if ($current < $total - $window - 1): ?>
                <?php if ($current < $total - $window - 2): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?= pageUrl($total) ?>"><?= $total ?></a></li>
            <?php endif; ?>

            <!-- Next -->
            <li class="page-item <?= $current >= $total ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $current < $total ? pageUrl($current + 1) : '#' ?>" aria-label="Next">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </div>
</nav>
