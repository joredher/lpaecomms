<?php

function audit_log(string $action, ?string $entityType = null, ?string $entityId = null, array $meta = []): void
{
    static $registered = false;

    // Register a single shutdown flush per request
    if (!$registered) {
        $registered = true;
        register_shutdown_function(function () {
            try {
                if (empty($GLOBALS['__AUDIT_BUFFER']) || !is_array($GLOBALS['__AUDIT_BUFFER'])) {
                    return;
                }

                $rows = $GLOBALS['__AUDIT_BUFFER'];
                $GLOBALS['__AUDIT_BUFFER'] = [];

                $conn = Database::getConnection();

                $values = implode(',', array_fill(0, count($rows), '(?,?,?,?,?,?,?,?,?,NOW())'));
                $sql = "INSERT INTO lpa_audit_log (user_id, action, entity_type, entity_id, method, path, ip, user_agent, meta, created_at) VALUES $values";

                $params = [];
                foreach ($rows as $r) {
                    // r is a numerically indexed array in the expected order
                    array_push($params, ...$r);
                }

                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
            } catch (Throwable $e) {
                error_log('[audit_log flush] ' . $e->getMessage());
            }
        });
    }

    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua     = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $path   = substr((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''), 0, 255);
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        // Skip obvious bots to reduce noise
        if ($ua && preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview/i', $ua)) {
            return;
        }

        $metaJson = json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (!isset($GLOBALS['__AUDIT_BUFFER']) || !is_array($GLOBALS['__AUDIT_BUFFER'])) {
            $GLOBALS['__AUDIT_BUFFER'] = [];
        }

        $GLOBALS['__AUDIT_BUFFER'][] = [
            $userId,
            $action,
            $entityType,
            $entityId,
            $method,
            $path,
            $ip,
            $ua,
            $metaJson,
        ];
    } catch (Throwable $e) {
        error_log('[audit_log enqueue] ' . $e->getMessage());
    }
}
