<?php
$title = 'Users';
ob_start();
?>
<div class="container-account">
    <h1>Users</h1>
</div>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
