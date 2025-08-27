<?php
$title = 'Apps';
ob_start();
?>
<h1>Apps</h1>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
