<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$backUrl = $_SESSION['previous_page'];
?>


<div class="container py-5">
    <div class="mb-5">
        <a href="<?= $backUrl ?>" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="../assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
    </div>
    <div class="container my-5">
        <div class="bg-white rounded-4 shadow d-flex flex-column flex-md-row p-5">

            <!-- Contact info box (left column) -->
            <div class="col-12 col-md-4">
                <div class="d-flex mb-4 flex-column">
                    <div class="icons-phone-parent">
                        <div class="icons-phone">
                            <img alt="" src="/assets/images/icons-phone.svg">
                        </div>
                        <div class="call-to-us">Call To Us</div>
                    </div>
                    <div>
                        <div class="text-contact-form">We are available 24/7, 7 days a week.</div>
                        <div class="text-contact-form">Phone: +8801611112222</div>
                    </div>
                </div>

                <div class="d-flex flex-column">
                    <div class="icons-phone-parent">
                        <div class="icons-phone">
                            <img alt="" src="/assets/images/icons-phone.svg">
                        </div>
                        <div class="call-to-us">Write To Us</div>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1"></h6>
                        <div class="text-contact-form">Fill out our form and we will contact you within 24 hours.</div>
                        <div class="text-contact-form">Emails: customer@exclusive.com, support@exclusive.com</div>
                    </div>
                </div>
            </div>

            <!-- Contact form (right column) -->
            <div class="col-12 col-md-8 mt-sm-2">
                <form method="post" action="/contact.send" class="row g-3">
                    <div class="form-group col-md-4">
                        <label class="w-100" for="name">
                            <input class="form-control rounded-3" type="text" name="name" maxlength="40" placeholder="Your Name *" required>
                        </label>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="w-100" for="email">
                            <input class="form-control rounded-3" type="email" name="email" placeholder="Your Email *" required>
                        </label>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="w-100" for="phone">
                            <input class="form-control rounded-3" type="tel" name="phone" placeholder="Your Phone *" required>
                        </label>
                    </div>
                    <div class="form-group col-12 col-md-12">
                        <textarea name="message" class="form-control rounded-3" placeholder="Your Message" rows="7" required></textarea>
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn text-white btn-outline-success:hover" style="background-color: #7ed957;">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
