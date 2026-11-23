<?php use KD\Services\OrderService; ?>
<form class="d-flex flex-wrap gap-2 mb-3" method="get" action="<?= e(url('/admin/orders')) ?>">
    <select class="form-select form-select-sm w-auto" name="status" aria-label="Filter by status">
        <option value="">All statuses</option>
        <?php foreach (OrderService::STATUS_LABELS as $code => $label): ?>
            <option value="<?= e($code) ?>" <?= $status === $code ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input class="form-control form-control-sm w-auto" type="search" name="q" value="<?= e($q) ?>" placeholder="Invoice no. or customer email" aria-label="Search orders">
    <button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button>
</form>
<div class="admin-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (!$orders): ?>
                <tr><td colspan="6" class="text-center text-secondary py-4">No orders found.</td></tr>
            <?php endif; ?>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td class="font-monospace"><?= e($o['invoice_no']) ?></td>
                    <td class="small"><?= e(date('d M Y H:i', strtotime($o['created_at']))) ?></td>
                    <td><?= e($o['customer_name']) ?><br><span class="small text-secondary"><?= e($o['customer_email']) ?></span></td>
                    <td class="text-end"><?= e(money($o['total_paise'])) ?></td>
                    <td><span class="status status-<?= e($o['status']) ?>"><?= e(OrderService::label($o['status'])) ?></span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/orders/' . (int) $o['id'])) ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
