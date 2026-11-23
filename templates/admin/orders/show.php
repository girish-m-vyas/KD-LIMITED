<?php use KD\Services\Cart; use KD\Services\OrderService; ?>
<a class="small" href="<?= e(url('/admin/orders')) ?>"><i class="bi bi-chevron-left"></i> Orders</a>
<div class="row g-4 mt-0">
    <div class="col-xl-8">
        <div class="admin-card p-3 p-lg-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0 font-monospace"><?= e($order['invoice_no']) ?></h2>
                <span class="status status-<?= e($order['status']) ?>"><?= e(OrderService::label($order['status'])) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Product</th><th>Size</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $i): ?>
                        <tr>
                            <td><?= e($i['product_title']) ?><?= $i['product_id'] === null ? ' <span class="badge text-bg-light">deleted</span>' : '' ?></td>
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

            <h3 class="h6 mt-3">Payments submitted by the customer</h3>
            <?php if (!$payments): ?>
                <p class="text-secondary small mb-0">None yet.</p>
            <?php else: ?>
                <table class="table table-sm">
                    <thead><tr><th>Mode</th><th>Reference</th><th>Code</th><th>Paid on</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= e($p['mode']) ?></td>
                            <td class="font-monospace"><?= e($p['ref_no']) ?></td>
                            <td><?= e($p['code']) ?></td>
                            <td><?= e($p['payment_date']) ?></td>
                            <td class="text-end"><?= e(money($p['amount_paise'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($order['status'] === OrderService::STATUS_PAYMENT_SUBMITTED): ?>
                    <div class="alert alert-info small mb-0">Check this reference against your bank or UPI statement before marking the order as paid.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="admin-card p-3 mb-3">
            <h3 class="h6">Customer</h3>
            <p class="mb-0 small">
                <strong><?= e($order['customer_name']) ?></strong><br>
                <?= e($order['customer_email']) ?><br>
                <?= e($order['customer_contact']) ?><br>
                <?= nl2br(e($order['customer_address'])) ?><br><?= e($order['customer_city']) ?>
            </p>
        </div>
        <div class="admin-card p-3">
            <h3 class="h6">Update status</h3>
            <?php if (!$next): ?>
                <p class="text-secondary small mb-0">This order is <?= e(strtolower(OrderService::label($order['status']))) ?>. No further changes are possible.</p>
            <?php else: ?>
                <div class="d-grid gap-2">
                    <?php foreach ($next as $s): ?>
                        <form method="post" action="<?= e(url('/admin/orders/' . (int) $order['id'] . '/status')) ?>" data-confirm="Mark this order as <?= e(strtolower(OrderService::label($s))) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="<?= e($s) ?>">
                            <button class="btn btn-sm w-100 <?= $s === OrderService::STATUS_CANCELLED ? 'btn-outline-danger' : ($s === OrderService::STATUS_PENDING ? 'btn-outline-secondary' : 'btn-dark') ?>" type="submit">
                                <?= $s === OrderService::STATUS_PENDING ? 'Reject payment (back to awaiting payment)' : 'Mark as ' . e(strtolower(OrderService::label($s))) ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p class="small text-secondary mt-2 mb-0">Placed <?= e(date('d M Y H:i', strtotime($order['created_at']))) ?> · updated <?= e(date('d M Y H:i', strtotime($order['updated_at']))) ?></p>
        </div>
    </div>
</div>
