<?php
$title = 'Company Profile';
ob_start();
?>
<h1>Company Profile</h1>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
