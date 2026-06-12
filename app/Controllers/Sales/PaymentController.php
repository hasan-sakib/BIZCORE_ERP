<?php

declare(strict_types=1);

namespace App\Controllers\Sales;

use App\Core\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\SalesRepository;

/**
 * PaymentController
 *
 * Read-only views for sales payments: paginated index and single record detail.
 * Payments are created via InvoiceController::recordPayment.
 */
final class PaymentController extends BaseController
{
    public function __construct(
        private readonly SalesRepository $repo,
    ) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $filters = [
            'customer_id' => trim((string) $request->query('customer_id', '')),
            'method'      => trim((string) $request->query('method', '')),
            'date_from'   => trim((string) $request->query('date_from', '')),
            'date_to'     => trim((string) $request->query('date_to', '')),
        ];
        $page   = max(1, (int) $request->query('page', 1));
        $result = $this->repo->paginatePayments($filters, $page, 20);

        return $this->render('sales/payments/index', [
            'pageTitle'   => 'Payments Received',
            'breadcrumbs' => ['Sales' => null, 'Payments' => null],
            'payments'    => $result['items'],
            'filters'     => $filters,
            'pagination'  => $this->buildPagination($result['total'], $page, 20),
        ]);
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Request $request, int $id): Response
    {
        $payment = $this->repo->findPayment($id);

        if ($payment === null) {
            $this->error('Payment not found.');
            return $this->redirect('/sales/payments');
        }

        return $this->render('sales/payments/show', [
            'pageTitle'   => 'Payment ' . sanitize($payment['reference_no']),
            'breadcrumbs' => ['Sales' => null, 'Payments' => '/sales/payments', sanitize($payment['reference_no']) => null],
            'payment'     => $payment,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function buildPagination(int $total, int $page, int $perPage): array
    {
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset   = ($page - 1) * $perPage;

        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => $lastPage,
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }
}
