<?php use KD\App; use KD\Services\OrderService; $canOpen = App::adminAuth()->can('orders.manage'); ?>
<div class="admin-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Invoice</th><th>Mode</th><th>Reference</th><th>Paid on</th><th class="text-end">Amount</th><th class="text-end">Order total</th><th>Order status</th></tr></thead>
            <tbody>
            <?php if (!$payments): ?>
                <tr><td colspan="7" class="text-center text-secondary py-4">No payments yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td class="font-monospace">
                        <?php if ($canOpen): ?><a href="<?= e(url('/admin/orders/' . (int) $p['order_id'])) ?>"><?= e($p['invoice_no']) ?></a><?php else: ?><?= e($p['invoice_no']) ?><?php endif; ?>
                    </td>
                    <td><?= e($p['mode']) ?></td>
                    <td class="font-monospace"><?= e($p['ref_no']) ?></td>
                    <td><?= e($p['payment_date']) ?></td>
                    <td class="text-end"><?= e(money($p['amount_paise'])) ?></td>
                    <td class="text-end"><?= e(money($p['total_paise'])) ?></td>
                    <td><span class="status status-<?= e($p['order_status']) ?>"><?= e(OrderService::label($p['order_status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
