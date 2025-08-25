<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class NavigationController
{
    public function track()
    {
        $url = $_POST['url'] ?? '';
        $prev = $_POST['prev'] ?? null;

        if ($url !== '') {
            if ($prev !== null) {
                $_SESSION['previous_page'] = $prev;
            } elseif (isset($_SESSION['current_page'])) {
                $_SESSION['previous_page'] = $_SESSION['current_page'];
            }

            $_SESSION['current_page'] = $url;

            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'missing url']);
        }
    }
}
