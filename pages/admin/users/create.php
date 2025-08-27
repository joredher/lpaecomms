<div class="container py-4">
    <h1 class="mb-4">Create User</h1>
    <form method="post" action="/admin.users.store">
        <!-- form fields go here -->
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
