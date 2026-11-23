<?php use KD\Services\Cart; ?>
<div class="container my-4">
    <h1 class="h3 mb-4">Shopping cart</h1>

    <?php if (!$lines): ?>
        <div class="empty-state">
            <i class="bi bi-bag"></i>
            <p class="mb-2">Your cart is empty.</p>
            <a href="<?= e(url('/shop')) ?>" class="btn btn-dark">Start shopping</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <form method="post" action="<?= e(url('/cart/update')) ?>">
                    <?= csrf_field() ?>
                    <div class="table-responsive">
                        <table class="table align-middle cart-table">
                            <thead><tr>
                                <th colspan="2">Product</th><th>Size</th><th class="w-qty">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th><th class="text-center">Remove</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($lines as $l): ?>
                                <tr>
                                    <td class="w-thumb"><img class="cart-thumb" src="<?= e(media('products', $l['product']['image1'])) ?>" alt=""></td>
                                    <td><a href="<?= e(url('/product/' . (int) $l['product']['id'])) ?>"><?= e($l['product']['title']) ?></a></td>
                                    <td><?= e(Cart::SIZES[$l['size']] ?? $l['size']) ?></td>
                                    <td>
                                        <label class="visually-hidden" for="qty-<?= e($l['key']) ?>">Quantity</label>
                                        <input class="form-control form-control-sm" type="number" min="1" max="<?= Cart::MAX_QTY ?>" id="qty-<?= e($l['key']) ?>" name="qty[<?= e($l['key']) ?>]" value="<?= (int) $l['qty'] ?>">
                                    </td>
                                    <td class="text-end"><?= e(money($l['unit'])) ?></td>
                                    <td class="text-end"><?= e(money($l['subtotal'])) ?></td>
                                    <td class="text-center"><input class="form-check-input" type="checkbox" name="remove[]" value="<?= e($l['key']) ?>" aria-label="Remove <?= e($l['product']['title']) ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?= e(url('/shop')) ?>" class="btn btn-outline-secondary"><i class="bi bi-chevron-left"></i> Continue shopping</a>
                        <button class="btn btn-outline-dark" type="submit"><i class="bi bi-arrow-repeat"></i> Update cart</button>
                    </div>
                </form>
            </div>
            <div class="col-lg-4">
                <div class="summary-box">
                    <h2 class="h5">Order summary</h2>
                    <dl class="row mb-0">
                        <dt class="col-7 fw-normal">Items</dt><dd class="col-5 text-end"><?= e(money($total)) ?></dd>
                        <dt class="col-7 fw-normal">Shipping</dt><dd class="col-5 text-end">Free</dd>
                        <dt class="col-7 border-top pt-2">Total</dt><dd class="col-5 text-end border-top pt-2 fw-bold"><?= e(money($total)) ?></dd>
                    </dl>
                    <a href="<?= e(url('/checkout')) ?>" class="btn btn-dark w-100 mt-3">Proceed to checkout <i class="bi bi-chevron-right"></i></a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
