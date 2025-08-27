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

$title    = 'Dashboard';
$backUrl  = $_SESSION['previous_page'] ?? '/home';
$userName = $_SESSION['user']['firstname'] ?? null;
?>

<div class="container-account">
    <div class="d-flex justify-content-between mb-5">
        <a href="<?= htmlspecialchars($backUrl) ?>" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="/assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
        <?php if ($userName): ?>
            <span class="text-muted fw-bolder">Welcome! <strong class="text-white"><?= htmlspecialchars($userName) ?></strong></span>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="bg-white rounded shadow-sm p-4">
                <h6 class="text-primary fw-semibold mb-4">Admin Modules</h6>
                <ul class="list-unstyled">
                    <?php foreach ($modules as $module): ?>
                        <li class="mb-2">
                            <a href="<?= htmlspecialchars($module['route']) ?>" class="text-decoration-none text-dark fw-medium">
                                <?= htmlspecialchars($module['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="col-md-9">
            <div class="bg-white rounded shadow-sm p-4">
                <h1 class="mb-0">Dashboard</h1>
                <p class="mt-3 mb-0">Select a module from the menu to begin.</p>
            </div>
        </div>
    </div>
</div>
