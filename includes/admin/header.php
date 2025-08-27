<?php
// Admin header navigation with module links.
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/admin">Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav"
                aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/admin/users">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/orders">Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/products">Products</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="moreControls" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        More controls
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="moreControls">
                        <li><a class="dropdown-item" href="/admin/reports">Reports</a></li>
                        <li><a class="dropdown-item" href="/admin/settings">Settings</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
