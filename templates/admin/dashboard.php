<?php use KD\Services\OrderService; ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="stat-tile"><div class="label">Products</div><div class="value"><?= (int) $stats['products'] ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="stat-tile"><div class="label">Customers</div><div class="value"><?= (int) $stats['customers'] ?></div></div></div>
    <div class="col-6 col-xl-3">
        <div class="stat-tile">
            <div class="label">Payments to verify</div>
            <div class="value"><?= (int) $stats['to_verify'] ?></div>
            <div class="small text-secondary"><?= (int) $stats['awaiting'] ?> orders awaiting payment</div>
        </div>
    </div>
    <div class="col-6 col-xl-3"><div class="stat-tile"><div class="label">Paid revenue</div><div class="value"><?= e(money($stats['revenue'])) ?></div></div></div>
</div>

<div class="row g-4">
    <?php if ($orders): ?>
    <div class="col-xl-7">
        <div class="admin-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Latest orders</h2>
                <a class="small" href="<?= e(url('/admin/orders')) ?>">All orders</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Invoice</th><th>Customer</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><a class="font-monospace" href="<?= e(url('/admin/orders/' . (int) $o['id'])) ?>"><?= e($o['invoice_no']) ?></a></td>
                            <td><?= e($o['customer_name']) ?></td>
                            <td class="text-end"><?= e(money($o['total_paise'])) ?></td>
                            <td><span class="status status-<?= e($o['status']) ?>"><?= e(OrderService::label($o['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($activity): ?>
    <div class="col-xl-5">
        <div class="admin-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Recent activity</h2>
                <a class="small" href="<?= e(url('/admin/audit')) ?>">Audit log</a>
            </div>
            <ul class="list-unstyled small mb-0">
                <?php foreach ($activity as $a): ?>
                    <li class="py-1 border-bottom">
                        <code><?= e($a['action']) ?></code>
                        <span class="text-secondary">by <?= e($a['admin_name'] ?? 'system') ?> · <?= e(date('d M H:i', strtotime($a['created_at']))) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$orders && !$activity): ?>
    <div class="col-12">
        <div class="admin-card p-4">
            <h2 class="h6">Welcome, <?= e($admin['name']) ?></h2>
            <p class="text-secondary mb-0">Use the menu to manage the areas your role gives you access to.</p>
        </div>
    </div>
    <?php endif; ?>
</div>
