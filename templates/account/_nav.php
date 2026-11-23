<?php
$items = [
    'orders'   => ['/account/orders', 'bi-receipt', 'My orders'],
    'edit'     => ['/account/edit', 'bi-person', 'Edit account'],
    'password' => ['/account/password', 'bi-key', 'Change password'],
    'delete'   => ['/account/delete', 'bi-trash', 'Delete account'],
];
?>
<div class="account-nav">
    <div class="d-flex align-items-center gap-2 mb-3">
        <img class="avatar" src="<?= e(media('customers', $customer['image'] ?? '')) ?>" alt="">
        <div>
            <div class="fw-semibold"><?= e($customer['name']) ?></div>
            <div class="small text-secondary text-break"><?= e($customer['email']) ?></div>
        </div>
    </div>
    <nav class="list-group">
        <?php foreach ($items as $key => [$href, $icon, $label]): ?>
            <a class="list-group-item list-group-item-action<?= $section === $key ? ' active' : '' ?>" href="<?= e(url($href)) ?>"><i class="bi <?= e($icon) ?> me-2"></i><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
</div>
