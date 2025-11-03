<?php
declare(strict_types=1);

require_once __DIR__ . '/../../repositories/ProductRepository.php';

final class CatalogueApiController
{
    public static function filters(): void
    {
        $repo = new ProductRepository();
        $categories = array_map(static fn($row) => [
            'id' => (int)$row['lpa_category_ID'],
            'name' => $row['lpa_category_name'],
        ], $repo->getCategories());

        $types = array_map(static fn($row) => [
            'id' => (int)$row['lpa_type_ID'],
            'name' => $row['lpa_type_name'],
        ], $repo->getTypes());

        ApiResponder::success([
            'filters' => [
                'categories' => $categories,
                'types' => $types,
            ],
        ]);
    }

    public static function index(): void
    {
        $conn = Database::getConnection();
        $repo = new ProductRepository();

        $search = trim((string)($_GET['search'] ?? $_GET['q'] ?? ''));
        $categoryFilter = self::normaliseList($_GET['category'] ?? $_GET['categories'] ?? []);
        $typeFilter = self::normaliseList($_GET['type'] ?? $_GET['types'] ?? []);

        $minPrice = self::toNullableFloat($_GET['min_price'] ?? $_GET['minPrice'] ?? null);
        $maxPrice = self::toNullableFloat($_GET['max_price'] ?? $_GET['maxPrice'] ?? null);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(60, max(1, (int)($_GET['per_page'] ?? $_GET['limit'] ?? 12)));
        $offset = ($page - 1) * $perPage;

        $conditions = ["(s.lpa_stock_status IN ('P','A','D') OR (s.lpa_stock_status = 'S' AND s.lpa_stock_publish_at <= NOW()))"];
        $params = [];

        if ($search !== '') {
            $conditions[] = "(s.lpa_stock_name LIKE ? OR c.lpa_category_name LIKE ? OR t.lpa_type_name LIKE ?)";
            $like = "%$search%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $hasGroupFilter = !empty($categoryFilter) || !empty($typeFilter);

        if (!empty($typeFilter)) {
            $placeholders = implode(',', array_fill(0, count($typeFilter), '?'));
            $conditions[] = "s.lpa_fk_type_ID IN ($placeholders)";
            foreach ($typeFilter as $val) {
                $params[] = $val;
            }
        }

        if (!empty($categoryFilter)) {
            $placeholders = implode(',', array_fill(0, count($categoryFilter), '?'));
            $conditions[] = "s.lpa_fk_category_ID IN ($placeholders)";
            foreach ($categoryFilter as $val) {
                $params[] = $val;
            }
        }

        if ($hasGroupFilter && $minPrice !== null) {
            $conditions[] = 's.lpa_stock_price >= ?';
            $params[] = $minPrice;
        }

        if ($hasGroupFilter && $maxPrice !== null) {
            $conditions[] = 's.lpa_stock_price <= ?';
            $params[] = $maxPrice;
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $baseFrom = " FROM lpa_stock s" .
            " JOIN lpa_category c ON s.lpa_fk_category_ID = c.lpa_category_ID" .
            " JOIN lpa_type t ON s.lpa_fk_type_ID = t.lpa_type_ID" .
            " LEFT JOIN (" .
            "     SELECT ii.lpa_fk_stock_ID, SUM(ii.lpa_invitem_qty) AS purchased" .
            "     FROM lpa_invoice_items ii" .
            "     JOIN lpa_invoices i ON i.lpa_invoices_ID = ii.lpa_fk_invoices_ID" .
            "     AND i.lpa_inv_status = 'A'" .
            "     GROUP BY ii.lpa_fk_stock_ID" .
            " ) p ON p.lpa_fk_stock_ID = s.lpa_stock_ID";

        $countQuery = 'SELECT COUNT(*)' . $baseFrom . $where;
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $query = 'SELECT s.lpa_stock_ID, s.lpa_stock_name, s.lpa_stock_slug, s.lpa_stock_price, s.lpa_stock_status, '
            . 's.lpa_stock_image, s.lpa_stock_onhand, s.lpa_stock_publish_at, '
            . 'c.lpa_category_name, t.lpa_type_name, '
            . 'COALESCE(p.purchased, 0) AS purchased'
            . $baseFrom
            . $where
            . ' ORDER BY s.lpa_stock_ID DESC'
            . ' LIMIT :offset, :limit';

        $stmt = $conn->prepare($query);
        $index = 1;
        foreach ($params as $param) {
            $stmt->bindValue($index, $param, is_numeric($param) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
            $index++;
        }
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();

        foreach ($products as &$product) {
            if (empty($product['lpa_stock_slug'])) {
                $product['lpa_stock_slug'] = $repo->ensureSlug((int)$product['lpa_stock_ID'], $product['lpa_stock_name'] ?? '');
            }
        }
        unset($product);

        $items = array_map(static function ($row) {
            $onHand = (int)($row['lpa_stock_onhand'] ?? 0);
            $purchased = (int)($row['purchased'] ?? 0);
            $available = max(0, $onHand - $purchased);
            $status = strtoupper((string)($row['lpa_stock_status'] ?? ''));
            if ($status === 'S' && !empty($row['lpa_stock_publish_at']) && strtotime($row['lpa_stock_publish_at']) <= time()) {
                $status = 'P';
            }

            return [
                'id' => (int)$row['lpa_stock_ID'],
                'name' => $row['lpa_stock_name'],
                'slug' => $row['lpa_stock_slug'],
                'price' => (float)$row['lpa_stock_price'],
                'currency' => 'AUD',
                'image' => getProductImageUrl($row['lpa_stock_image'] ?? ''),
                'category' => $row['lpa_category_name'],
                'type' => $row['lpa_type_name'],
                'status' => $status,
                'availability' => [
                    'onHand' => $onHand,
                    'purchased' => $purchased,
                    'available' => $available,
                    'isAvailable' => $available > 0 && $status !== 'D',
                ],
            ];
        }, $products);

        ApiResponder::success([
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => (int)ceil($total / $perPage),
            'items' => $items,
        ]);
    }

    private static function normaliseList($value): array
    {
        if (is_array($value)) {
            $list = $value;
        } elseif (is_string($value) && $value !== '') {
            $list = explode(',', $value);
        } elseif ($value === null || $value === '') {
            return [];
        } else {
            $list = [(string)$value];
        }

        $clean = [];
        foreach ($list as $item) {
            $item = trim((string)$item);
            if ($item === '') {
                continue;
            }
            if (ctype_digit($item)) {
                $clean[] = (int)$item;
            } else {
                $clean[] = $item;
            }
        }

        return $clean;
    }

    private static function toNullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float)$value;
        }

        return null;
    }
}
