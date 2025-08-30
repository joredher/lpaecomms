<?php
$title = 'Company Profile';
ob_start();
?>
<div class="container-account">
    <h1>Company Profile</h1>
</div>
<?php
$pageContent = ob_get_clean();
include 'includes/admin/layout.php';
