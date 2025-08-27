<?php
$title = 'Reports';
ob_start();
?>
<h1>Reports</h1>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
