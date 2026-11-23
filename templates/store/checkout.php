<?php use KD\Services\Cart; ?>
<div class="container my-4 narrow-lg">
    <h1 class="h3 mb-4">Checkout</h1>
    <div class="row g-4">
        <div class="col-md-7">
            <h2 class="h6 text-uppercase text-secondary">Deliver to</h2>
            <div class="summary-box mb-4">
                <strong><?= e($customer['name']) ?></strong><br>
                <?= nl2br(e($customer['address'])) ?><br>
                <?= e($customer['city']) ?><br>
                <span class="text-secondary"><?= e($customer['contact']) ?></span>
                <div class="mt-2"><a class="small" href="<?= e(url('/account/edit')) ?>">Change details</a></div>
            </div>
            <h2 class="h6 text-uppercase text-secondary">Payment</h2>
            <p class="text-secondary">We accept offline payment by UPI, bank transfer, cheque or cash deposit. After you place the order you'll see its invoice number. Pay that amount, then submit your payment reference from the order page. We confirm the order once we've checked the payment.</p>
        </div>
        <div class="col-md-5">
            <div class="summary-box">
                <h2 class="h5">Your order</h2>
                <ul class="list-unstyled small mb-3">
                    <?php foreach ($lines as $l): ?>
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span><?= e($l['product']['title']) ?> <span class="text-secondary">(<?= e(Cart::SIZES[$l['size']] ?? $l['size']) ?> × <?= (int) $l['qty'] ?>)</span></span>
                            <span><?= e(money($l['subtotal'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span><?= e(money($total)) ?></span></div>
                <form method="post" action="<?= e(url('/checkout')) ?>" class="mt-3">
                    <?= csrf_field() ?>
                    <button class="btn btn-dark w-100" type="submit">Place order</button>
                </form>
            </div>
        </div>
    </div>
</div>
