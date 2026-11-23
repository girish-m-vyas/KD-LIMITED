<form class="d-flex flex-wrap gap-2 mb-3 align-items-center" method="get" action="<?= e(url('/admin/audit')) ?>">
    <select class="form-select form-select-sm w-auto" name="action" aria-label="Filter by action">
        <option value="">All actions</option>
        <?php foreach ($actions as $a): ?>
            <option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e($a) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button>
    <span class="small text-secondary ms-auto"><?= (int) $total ?> entries. The log is append-only; nothing in the admin panel can edit or delete it.</span>
</form>
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Target</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
            <?php if (!$entries): ?>
                <tr><td colspan="6" class="text-center text-secondary py-4">No entries.</td></tr>
            <?php endif; ?>
            <?php foreach ($entries as $en): ?>
                <tr>
                    <td class="small text-nowrap"><?= e(date('d M Y H:i:s', strtotime($en['created_at']))) ?></td>
                    <td class="small"><?= $en['admin_name'] ? e($en['admin_name']) . '<br><span class="text-secondary">' . e($en['admin_email']) . '</span>' : '<span class="text-secondary">—</span>' ?></td>
                    <td><code><?= e($en['action']) ?></code></td>
                    <td class="small"><?= e($en['entity']) ?><?= $en['entity_id'] !== null ? ' #' . (int) $en['entity_id'] : '' ?></td>
                    <td><div class="audit-details font-monospace"><?= e($en['details'] === '[]' || $en['details'] === '{}' ? '' : $en['details']) ?></div></td>
                    <td class="small font-monospace"><?= e($en['ip']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if ($pages > 1): ?>
    <nav class="mt-3" aria-label="Pages"><ul class="pagination pagination-sm">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item<?= $i === $page ? ' active' : '' ?>"><a class="page-link" href="<?= e(url('/admin/audit', ['action' => $action, 'page' => $i])) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>
