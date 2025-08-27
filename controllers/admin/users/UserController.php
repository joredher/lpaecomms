<?php

require_once __DIR__ . '/../../../bootstrap.php';

loadRepo('middleware/AuthMiddleware.php');

class UserController
{
    public function __construct()
    {
        AuthMiddleware::adminOnly();
    }

    public function index(): void
    {
        // Display a listing of users.
    }

    public function create(): void
    {
        // Show form for creating a new user.
    }

    public function store(): void
    {
        // Persist a newly created user.
    }

    public function edit($id): void
    {
        // Show form for editing the specified user.
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
