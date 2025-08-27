<?php
$title = 'Groups';
ob_start();
?>
<div class="container-account">
    <h1>Groups</h1>
</div>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
