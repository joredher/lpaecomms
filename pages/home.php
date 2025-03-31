<?php

$path_body_images =  "../assets/images/body_page/";
$searchPageLink = "";

?>

<div class="frame-parent">
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
            <form action="<?php echo $searchPageLink; ?>" method="get">
                <input
                        type="text"
                        name="query"
                        placeholder="What are you looking for?"
                        class="what-are-you"
                        style="border: none; outline: none; background: transparent; width: 85%; height: 100%; padding-left: 18px;"
                >
                <button type="submit" class="frame-item" style="border: none; cursor: pointer;">
                    <img class="search-icon" src="<?php echo $path_body_images ?>Search.svg" alt="Search Icon">
                </button>
            </form>
        </div>
<!--        <div class="what-are-you-looking-for-parent">-->
<!--            <div class="what-are-you">What are you looking for?</div>-->
<!--            <div class="frame-item">-->
<!--            </div>-->
<!--            <img class="search-icon" alt="" src="--><?php //echo $path_body_images ?><!--Search.svg">-->
<!--        </div>-->
    </div>
    <div class="frame-inner">
    </div>
    <img class="image-icon" alt="" src="<?php echo $path_body_images?>image.png">
    <img class="vector-icon" alt="" src="<?php echo $path_body_images?>Vector 186.svg">
    <img class="frame-child1" alt="" src="<?php echo $path_body_images?>Vector 187.svg">
</div>
