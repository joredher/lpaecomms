<?php

use Lpaecomms\Database;

$conn = Database::getConnection();
$backUrl = $_SESSION['previous_page'];
$cart = $_SESSION['cart'] ?? [];
$total = $_SESSION['total'] ?? 0;

?>

<div class="pd-cart-section container py-5">
    <div class="mb-5">
        <a href="<?= $backUrl ?>" class="pd-back-button d-flex align-items-center text-decoration-none">
            <img src="../../assets/images/icons/back.svg" alt="Back" class="me-2">
            <span>Back</span>
        </a>
    </div>
    <div class="mt-5">
        <?php if (!$cart) : ?>
            <div class="container text-center align-content-center mb-4">
                <h3 class="text-white">Opps!</h3>
                <h5 class="text-white">Your cart is empty.</h5>
            </div>
        <?php else : ?>
            <form action="?route=cart.update" method="POST">
                <div class="table-responsive">
                    <table class="table align-middle table-striped">
                        <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Subtotal</th>
                        </tr>
                        </thead>
                        <tbody class="table-group-divider">
                        <?php foreach ($cart as $id => $item):
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                            ?>
                            <tr>
                                <td class="text-start d-flex align-items-center">
                                    <img src="<?= ($item['image']) ?>"
                                         alt="<?= ($item['name']) ?>"
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                         class="me-3"
                                         onerror="this.src='assets/images/placeholder.png';">
                                    <?= $item['name'] ?>
                                </td>
                                <td>$<?= number_format($item['price'], 2) ?></td>
                                <td>
                                    <input type="hidden" name="id[]" value="<?= $id ?>">
                                    <input type="number"
                                           name="quantity[]"
                                           class="form-control text-center"
                                           min="1"
                                           max="<?= $item['stock'] ?? 10 ?>"
                                           value="<?= $item['quantity'] ?>"
                                           style="width: 70px;">
                                </td>
                                <td>$<?= number_format($subtotal, 2) ?></td>
                                <td>
                                    <a href="?route=cart.remove&id=<?= $id ?>" class="btn btn-sm btn-outline-danger">✖</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mt-1 mb-5">
                    <a href="?route=products" class="btn btn-return-shop">Return To Shop</a>
                    <button type="submit" class="btn btn-update-cart">Update Cart</button>
                </div>
            </form>

            <!-- Cart Actions (Coupon + Summary) -->
            <div class="row mt-4">
                <!-- Coupon Section -->
                <div class="col-md-6 mb-3">
                    <?php if (isset($_SESSION['user'])): ?>
                        <form method="POST" action="?route=apply_coupon" class="d-flex flex-column flex-md-row align-items-md-center">
                            <label>
                                <input type="text" name="coupon_code" class="form-control me-md-2 mb-2 mb-md-0" placeholder="Coupon Code">
                            </label>
                            <button type="submit" class="btn btn-success">Apply Coupon</button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Cart Total Summary -->
                <div class="col-md-6">
                    <div class="card border shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Cart Total</h5>
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <strong>$<?= number_format($total, 2) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Shipping:</span>
                                <strong>Free</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Total:</span>
                                <strong>$<?= number_format($total, 2) ?></strong>
                            </div>
                            <a href="/checkout" class="btn btn-success w-100">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
