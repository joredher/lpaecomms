<?php
require_once 'includes/config.php';
$conn = Database::getConnection();
$path_body_images =  "../assets/images/body_page/";
$searchPageLink = "";

?>

<div class="frame-parent container-fluid">
    <div class="frame-group">
        <div class="explore-logic-peripherals-au-parent">
            <div class="explore-logic-peripherals">Explore Logic Peripherals AU</div>
            <div class="frame-container">
                <div class="parent">
                    <div class="div">50+</div>
                    <div class="peripherals-parts">Peripherals Parts</div>
                </div>
                <div class="frame-child">
                </div>
                <div class="parent">
                    <div class="div">100+</div>
                    <div class="peripherals-parts">Customers</div>
                </div>
            </div>
        </div>
        <div class="what-are-you-looking-for-parent">
            <form method="get">
                <label>
                    <input
                            type="text"
                            name="query"
                            placeholder="What are you looking for?"
                            class="what-are-you"
                            style="border: none; outline: none; background: transparent; height: 100%; padding-left: 18px;"
                    >
                </label>
                <button type="submit" class="frame-item" style="border: none; cursor: pointer;">
                    <img class="search-icon" src="<?= $path_body_images ?>Search.svg" alt="Search Icon">
                </button>
            </form>
        </div>
<!--        <div class="what-are-you-looking-for-parent">-->
<!--            <div class="what-are-you">What are you looking for?</div>-->
<!--            <div class="frame-item">-->
<!--            </div>-->
<!--            <img class="search-icon" alt="" src="--><!--Search.svg">-->
<!--        </div>-->
    </div>
    <div class="frame-inner">
    </div>
    <img class="image-icon" alt="" src="<?= $path_body_images?>image.png">
    <img class="vector-icon" alt="" src="<?= $path_body_images?>Vector 186.svg">
    <img class="frame-child1" alt="" src="<?= $path_body_images?>Vector 187.svg">
</div>

<div class="about-us-section">
    <div class="about-us-container container">
        <div class="about-us-item text-center">
            <b class="feature-title">About us</b>
            <div class="about-us-subtitle">Proudly designed for Australia by Logic Peripherals</div>
        </div>
        <div class="features-list row">
            <div class="about-us-item col-12 col-md-4">
                <div class="feature-item">
                    <img class="feature-icon" alt="" src="<?= $path_body_images?>Bulb1.svg">
                    <b class="feature-title">Large Assortment</b>
                </div>
                <div class="feature-description">we offer many different types of products with fewer variations in each category.</div>
            </div>
            <div class="about-us-item col-12 col-md-4">
                <div class="feature-item">
                    <img class="feature-icon" alt="" src="<?= $path_body_images?>Box1.svg">
                    <b class="feature-title">Fast & Free Shipping</b>
                </div>
                <div class="feature-description">4-day or less delivery time, free shipping and an expedited delivery option.</div>
            </div>
            <div class="about-us-item col-12 col-md-4">
                <div class="feature-item">
                    <img class="feature-icon" alt="" src="<?= $path_body_images?>TelephoneOutbound1.svg">
                    <b class="feature-title">24/7 Support</b>
                </div>
                <div class="feature-description">answers to any business related inquiry 24/7 and in real-time.</div>
            </div>
        </div>
    </div>
</div>

<div class="features-section container">
    <div class="feature-grid">
        <div class="feature-card">
            <img src="<?= $path_body_images?>assortment.png" alt="Large Assortment">
        </div>
        <div class="feature-card">
            <img src="<?= $path_body_images?>fast_free_shipping.png" alt="Fast & Free Shipping">
        </div>
        <div class="feature-card">
            <img src="<?= $path_body_images?>24_7_support.png" alt="24/7 Support">
        </div>
        <div class="feature-card">
            <img src="<?= $path_body_images?>proudly_australian.png" alt="Proudly Australian">
        </div>
    </div>
</div>

