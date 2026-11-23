<div class="admin-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th></th><th>Name</th><th>Email</th><th>Phone</th><th>City</th><th class="text-end">Orders</th><th>Joined</th><th></th></tr></thead>
            <tbody>
            <?php if (!$customers): ?>
                <tr><td colspan="8" class="text-center text-secondary py-4">No customers yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($customers as $c): ?>
                <?php $anon = str_starts_with((string) $c['email'], 'deleted-'); ?>
                <tr<?= $anon ? ' class="text-secondary"' : '' ?>>
                    <td><img class="avatar" src="<?= e(media('customers', $c['image'])) ?>" alt=""></td>
                    <td><?= e($c['name']) ?></td>
                    <td><?= $anon ? '—' : e($c['email']) ?></td>
                    <td><?= e($c['contact']) ?></td>
                    <td><?= e($c['city']) ?></td>
                    <td class="text-end"><?= (int) $c['order_count'] ?></td>
                    <td class="small"><?= e(date('d M Y', strtotime($c['created_at']))) ?></td>
                    <td class="text-end">
                        <?php if (!$anon): ?>
                            <form method="post" action="<?= e(url('/admin/customers/' . (int) $c['id'] . '/delete')) ?>" data-confirm="Remove <?= e($c['name']) ?>? Customers with orders are anonymised.">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
