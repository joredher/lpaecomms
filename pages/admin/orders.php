<?php
$title = 'Orders';
ob_start();
?>
<h1>Orders</h1>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
