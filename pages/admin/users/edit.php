<?php $userId = $userId ?? null; ?>
<div class="container py-4">
    <h1 class="mb-4">Edit User</h1>
    <form method="post" action="/admin.users.update?id=<?= htmlspecialchars((string)$userId) ?>">
        <!-- form fields go here -->
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="">
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="">
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
