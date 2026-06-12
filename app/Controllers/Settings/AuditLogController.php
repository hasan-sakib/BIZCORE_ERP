<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;

/**
 * AuditLogController
 *
 * Read-only paginated view of the audit_logs table.
 */
final class AuditLogController extends BaseController
{
    private const PER_PAGE = 30;

    // =========================================================================
    // Actions
    // =========================================================================

    public function index(Request $request): Response
    {
        $page  = max(1, (int) $request->query('page', 1));
        $logs  = [];
        $total = 0;

        try {
            $pdo = app(\PDO::class);

            // Verify the table exists before querying.
            $check = $pdo->query("SHOW TABLES LIKE 'audit_logs'")->fetchAll();

            if (!empty($check)) {
                $perPage = self::PER_PAGE;
                $offset  = ($page - 1) * $perPage;

                $countStmt = $pdo->query('SELECT COUNT(*) AS total FROM audit_logs');
                $total     = (int) ($countStmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0);

                $stmt = $pdo->prepare(
                    <<<SQL
                    SELECT
                        al.*,
                        u.name AS user_name
                    FROM audit_logs al
                    LEFT JOIN users u ON u.id = al.user_id
                    ORDER BY al.created_at DESC
                    LIMIT {$perPage} OFFSET {$offset}
                    SQL
                );
                $stmt->execute();
                $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\Throwable) {
            // Table missing or query error — show empty state gracefully.
        }

        $lastPage  = $total > 0 ? (int) ceil($total / self::PER_PAGE) : 1;
        $from      = $total > 0 ? ($page - 1) * self::PER_PAGE + 1 : 0;
        $to        = min($page * self::PER_PAGE, $total);

        $pagination = [
            'total'        => $total,
            'per_page'     => self::PER_PAGE,
            'current_page' => $page,
            'total_pages'  => $lastPage,
            'from'         => $from,
            'to'           => $to,
        ];

        return $this->render('settings/audit-logs', [
            'pageTitle'   => 'Audit Logs',
            'breadcrumbs' => ['Settings' => '/settings', 'Audit Logs' => null],
            'logs'        => $logs,
            'pagination'  => $pagination,
            'activeTab'   => 'audit-logs',
        ]);
    }

    public function show(int $id): Response
    {
        return $this->redirect('/settings/audit-logs');
    }

    public function export(Request $request): Response
    {
        $this->error('Export is not yet configured.');
        return $this->redirect('/settings/audit-logs');
    }
}
