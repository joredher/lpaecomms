<?php
$title = 'Groups';
ob_start();
?>
<h1>Groups</h1>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
