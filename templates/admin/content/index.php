<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-dark btn-sm" href="<?= e(url("/admin/{$slug}/new")) ?>"><i class="bi bi-plus-lg"></i> Add <?= e($type['singular']) ?></a>
</div>
<div class="admin-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr>
                <?php foreach ($type['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
                <th class="text-end">Actions</th>
            </tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="<?= count($type['columns']) + 1 ?>" class="text-center text-secondary py-4">Nothing here yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($type['columns'] as $col => $_label): ?>
                        <td>
                            <?php $def = $type['fields'][$col] ?? ['type' => 'text']; ?>
                            <?php if ($def['type'] === 'image'): ?>
                                <img class="admin-thumb" src="<?= e(media($def['folder'], $row[$col])) ?>" alt="">
                            <?php elseif ($def['type'] === 'checkbox'): ?>
                                <?= $row[$col] ? '<i class="bi bi-check-lg"></i>' : '' ?>
                            <?php else: ?>
                                <?= e(mb_strimwidth((string) $row[$col], 0, 90, '…')) ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url("/admin/{$slug}/" . (int) $row['id'] . '/edit')) ?>">Edit</a>
                        <form class="d-inline" method="post" action="<?= e(url("/admin/{$slug}/" . (int) $row['id'] . '/delete')) ?>" data-confirm="Delete this <?= e($type['singular']) ?>?">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
