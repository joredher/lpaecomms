<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class NavigationController
{
    public function track()
    {
        $url = $_POST['url'] ?? '';
        if ($url !== '') {
            $_SESSION['current_page'] = $url;
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'missing url']);
        }
    }
}
