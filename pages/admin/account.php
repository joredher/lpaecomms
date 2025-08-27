<?php

$modules = [
    ['route' => '/admin.company_profile', 'title' => 'Company Profile'],
    ['route' => '/admin.reports', 'title' => 'Reports'],
    ['route' => '/admin.apps', 'title' => 'Apps'],
    ['route' => '/admin.groups', 'title' => 'Groups'],
    ['route' => '/admin.rules', 'title' => 'Rules'],
    ['route' => '/admin.security', 'title' => 'Security'],
    ['route' => '/admin.support', 'title' => 'Support'],
    ['route' => '/admin.data_migration', 'title' => 'Data Migration'],
];

$title = 'Dashboard';
?>

<h1 class="mb-4">Dashboard</h1>
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
    <?php foreach ($modules as $module): ?>
        <div class="col">
            <a class="card h-100 text-decoration-none text-dark" href="<?= htmlspecialchars($module['route']) ?>">
                <div class="card-body d-flex align-items-center justify-content-center">
                    <h5 class="card-title mb-0"><?= htmlspecialchars($module['title']) ?></h5>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
