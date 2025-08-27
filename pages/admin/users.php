<?php
$title = 'Users';
ob_start();
?>
<h1>Users</h1>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
