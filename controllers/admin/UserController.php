<?php

require_once __DIR__ . '/../../bootstrap.php';

loadRepo('middleware/AuthMiddleware.php');

class UserController
{
    public function __construct()
    {
        AuthMiddleware::adminOnly();
    }

    public function index(): void
    {
        $title = 'Users';
        $pageContent = 'pages/admin/users/index.php';
        include 'includes/admin/layout.php';
    }

    public function create(): void
    {
        $title = 'Create User';
        $pageContent = 'pages/admin/users/create.php';
        include 'includes/admin/layout.php';
    }

    public function store(): void
    {
        // Persist a newly created user.
    }

    public function edit($id): void
    {
        $userId = $id;
        $title = 'Edit User';
        $pageContent = 'pages/admin/users/edit.php';
        include 'includes/admin/layout.php';
    }

    public function update($id): void
    {
        // Update the specified user in storage.
    }

    public function destroy($id): void
    {
        // Remove the specified user.
    }
}
