<?php
$path_footer_images =  "../assets/images/";

?>
<div class="site-footer">
    <div class="footer-branding">
        <div class="footer-column">
            <b class="footer-title">LOGIC PERIPHERALS </b>
            <div class="footer-description">
                <p class="footer-description-line">We help you to fix</p>
                <p class="footer-description-line">your device</p>
            </div>
        </div>
        <div class="footer-socials">
            <img class="social-icon" alt="" src="<?= $path_footer_images ?>gg_facebook.svg">
            <img class="social-icon" alt="" src="<?= $path_footer_images ?>ri_instagram-fill.svg">
            <img class="social-icon" alt="" src="<?= $path_footer_images ?>X.svg">
        </div>
    </div>
    <div class="footer-links">
        <div class="footer-column">
            <b class="footer-heading">Information</b>
            <div class="footer-link">About</div>
            <div class="footer-link">Product</div>
            <div class="footer-link">Blog</div>
        </div>
        <div class="footer-column">
            <b class="footer-heading">Contact</b>
            <div class="footer-link">Getting Started</div>
            <div class="footer-link">Pricing</div>
            <div class="footer-link">Resources</div>
        </div>
    </div>
    <div class="footer-credits"><?= date("Y") ?> all Right Reserved Term of use LP-AU</div>
</div>
