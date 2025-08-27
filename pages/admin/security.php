<?php
$title = 'Security';
ob_start();
?>
<h1>Security</h1>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
