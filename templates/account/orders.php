<?php use KD\Services\OrderService; use KD\Support\View; ?>
<div class="container my-4">
    <div class="row g-4">
        <div class="col-lg-3"><?= View::partial('account/_nav', ['section' => $section, 'customer' => $customer]) ?></div>
        <div class="col-lg-9">
            <h1 class="h3 mb-3">My orders</h1>
            <?php if (!$orders): ?>
                <div class="empty-state">
                    <i class="bi bi-receipt"></i>
                    <p class="mb-2">You haven't placed any orders yet.</p>
                    <a class="btn btn-dark" href="<?= e(url('/shop')) ?>">Browse the shop</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Invoice</th><th>Date</th><th>Items</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="font-monospace"><?= e($o['invoice_no']) ?></td>
                                <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
                                <td><?= (int) $o['item_count'] ?></td>
                                <td class="text-end"><?= e(money($o['total_paise'])) ?></td>
                                <td><span class="status status-<?= e($o['status']) ?>"><?= e(OrderService::label($o['status'])) ?></span></td>
                                <td class="text-end">
                                    <a class="btn btn-sm <?= $o['status'] === OrderService::STATUS_PENDING ? 'btn-dark' : 'btn-outline-secondary' ?>" href="<?= e(url('/account/orders/' . (int) $o['id'])) ?>">
                                        <?= $o['status'] === OrderService::STATUS_PENDING ? 'Submit payment' : 'View' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
