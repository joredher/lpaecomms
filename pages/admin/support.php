<?php
$title = 'Support';
ob_start();
?>
<div class="container-account">
    <h1>Support</h1>
</div>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
