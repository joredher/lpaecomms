<?php
$title = 'Reports';
ob_start();
?>
<div class="container-account">
    <h1>Reports</h1>
</div>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
