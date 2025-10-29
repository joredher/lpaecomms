<?php
// scripts/migrate_payment_meta.php
// Run once to backfill lpa_invoices payment fields.

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: text/plain');

try {
    $db = Database::getConnection();

    $queries = [
        "UPDATE lpa_invoices SET lpa_inv_payment_method = 'card' WHERE (lpa_inv_payment_method IS NULL OR lpa_inv_payment_method = '') AND lpa_inv_status = 'A'",
        "UPDATE lpa_invoices SET lpa_inv_payment_method = 'cod'  WHERE (lpa_inv_payment_method IS NULL OR lpa_inv_payment_method = '') AND lpa_inv_status <> 'A'",
        "UPDATE lpa_invoices SET lpa_inv_save_info = COALESCE(lpa_inv_save_info, 0)",
        "UPDATE lpa_invoices SET lpa_inv_card_brand = NULL, lpa_inv_card_last4 = NULL WHERE lpa_inv_payment_method = 'cod'"
    ];

    foreach ($queries as $sql) {
        $affected = $db->exec($sql);
        echo "OK: {$affected} rows - " . substr($sql, 0, 80) . "...\n";
    }

    // Show a quick summary by method
    $stmt = $db->query("SELECT lpa_inv_payment_method AS method, COUNT(*) AS cnt FROM lpa_invoices GROUP BY lpa_inv_payment_method");
    echo "\nSummary by method:\n";
    foreach ($stmt->fetchAll() as $row) {
        echo sprintf(" - %s: %d\n", $row['method'] ?? 'NULL', (int)$row['cnt']);
    }

    echo "\nDone.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Migration failed: ' . $e->getMessage() . "\n";
}

