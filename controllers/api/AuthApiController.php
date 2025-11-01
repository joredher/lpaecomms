<?php

require_once __DIR__ . '/BaseApiController.php';
require_once __DIR__ . '/../../services/AuthService.php';

class AuthApiController extends BaseApiController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function login(): void
    {
        $result = $this->authService->attemptLogin(
            $_POST['username'] ?? '',
            $_POST['password'] ?? ''
        );
        $this->respond($result);
    }

    public function register(): void
    {
        $result = $this->authService->register($_POST);
        $this->respond($result);
    }

    public function logout(): void
    {
        $result = $this->authService->logout();
        $this->respond($result);
    }
}
