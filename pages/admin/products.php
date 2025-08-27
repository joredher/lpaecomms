<?php
$title = 'Products';
ob_start();
?>
<h1>Products</h1>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
