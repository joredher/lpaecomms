<?php
$title = 'Rules';
ob_start();
?>
<div class="container-account">
    <h1>Rules</h1>
</div>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
