<?php
use KD\Services\Cart;
use KD\Services\OrderService;
use KD\Support\Money;
use KD\Support\View;
?>
<div class="container my-4">
    <div class="row g-4">
        <div class="col-lg-3"><?= View::partial('account/_nav', ['section' => $section, 'customer' => $customer]) ?></div>
        <div class="col-lg-9">
            <a class="small" href="<?= e(url('/account/orders')) ?>"><i class="bi bi-chevron-left"></i> All orders</a>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-3 gap-2">
                <h1 class="h3 mb-0">Order <span class="font-monospace"><?= e($order['invoice_no']) ?></span></h1>
                <span class="status status-<?= e($order['status']) ?>"><?= e(OrderService::label($order['status'])) ?></span>
            </div>
            <p class="text-secondary small">Placed on <?= e(date('d M Y, H:i', strtotime($order['created_at']))) ?></p>

            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Product</th><th>Size</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $i): ?>
                        <tr>
                            <td><?= e($i['product_title']) ?></td>
                            <td><?= e(Cart::SIZES[$i['size']] ?? $i['size']) ?></td>
                            <td class="text-end"><?= (int) $i['qty'] ?></td>
                            <td class="text-end"><?= e(money($i['unit_price_paise'])) ?></td>
                            <td class="text-end"><?= e(money((int) $i['unit_price_paise'] * (int) $i['qty'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><th colspan="4" class="text-end">Total</th><th class="text-end"><?= e(money($order['total_paise'])) ?></th></tr></tfoot>
                </table>
            </div>

            <?php if ($order['status'] === OrderService::STATUS_PENDING): ?>
                <div class="summary-box mt-4">
                    <h2 class="h5">Submit your payment</h2>
                    <p class="text-secondary small">Pay <strong><?= e(money($order['total_paise'])) ?></strong> and quote invoice <strong class="font-monospace"><?= e($order['invoice_no']) ?></strong> as the reference. Then enter the details below.</p>
                    <form method="post" action="<?= e(url('/account/orders/' . (int) $order['id'] . '/pay')) ?>" novalidate>
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="amount">Amount paid (₹)</label>
                                <input class="form-control<?= invalid($errors, 'amount') ?>" id="amount" name="amount" inputmode="decimal" value="<?= old('amount', Money::toInput((int) $order['total_paise'])) ?>" required>
                                <?= field_error($errors, 'amount') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mode">Payment mode</label>
                                <select class="form-select<?= invalid($errors, 'mode') ?>" id="mode" name="mode" required>
                                    <option value="">Choose…</option>
                                    <?php foreach ($modes as $m): ?>
                                        <option value="<?= e($m) ?>" <?= ($_SESSION['_old']['mode'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= field_error($errors, 'mode') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="ref_no">Transaction or UTR number</label>
                                <input class="form-control<?= invalid($errors, 'ref_no') ?>" id="ref_no" name="ref_no" maxlength="60" value="<?= old('ref_no') ?>" required>
                                <?= field_error($errors, 'ref_no') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="code">Cheque or bank code <span class="text-secondary">(optional)</span></label>
                                <input class="form-control<?= invalid($errors, 'code') ?>" id="code" name="code" maxlength="60" value="<?= old('code') ?>">
                                <?= field_error($errors, 'code') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="payment_date">Payment date</label>
                                <input class="form-control<?= invalid($errors, 'payment_date') ?>" type="date" id="payment_date" name="payment_date" max="<?= e(date('Y-m-d')) ?>" value="<?= old('payment_date', date('Y-m-d')) ?>" required>
                                <?= field_error($errors, 'payment_date') ?>
                            </div>
                        </div>
                        <button class="btn btn-dark mt-3" type="submit">Submit payment details</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($payments): ?>
                <h2 class="h5 mt-4">Payments submitted</h2>
                <ul class="list-group">
                    <?php foreach ($payments as $p): ?>
                        <li class="list-group-item d-flex justify-content-between flex-wrap gap-2">
                            <span><?= e($p['mode']) ?> · ref <span class="font-monospace"><?= e($p['ref_no']) ?></span> · <?= e($p['payment_date']) ?></span>
                            <strong><?= e(money($p['amount_paise'])) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
