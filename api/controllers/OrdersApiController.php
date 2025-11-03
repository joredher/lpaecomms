<?php

declare(strict_types=1);

require_once __DIR__ . '/../../repositories/invoice/InvoiceRepository.php';

final class OrdersApiController
{
    public static function index(): void
    {
        $user = api_require_user();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(50, max(1, (int)($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $repo = new InvoiceRepository();
        $orders = $repo->getInvoicesByUser((int)$user['id'], $offset, $perPage);
        $total = $repo->countInvoicesByUser((int)$user['id']);

        $hasMore = ($offset + count($orders)) < $total;
        $nextPage = $hasMore ? $page + 1 : null;

        ApiResponder::success([
            'orders' => $orders,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'hasMore' => $hasMore,
            'nextPage' => $nextPage,
        ]);
    }
}
