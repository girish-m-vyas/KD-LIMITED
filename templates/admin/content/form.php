<?php $action = $row ? "/admin/{$slug}/" . (int) $row['id'] : "/admin/{$slug}"; ?>
<a class="small" href="<?= e(url("/admin/{$slug}")) ?>"><i class="bi bi-chevron-left"></i> <?= e($type['plural']) ?></a>
<form method="post" action="<?= e(url($action)) ?>" enctype="multipart/form-data" class="admin-card p-3 p-lg-4 mt-2 narrow" novalidate>
    <?= csrf_field() ?>
    <?php foreach ($type['fields'] as $field => $def): ?>
        <div class="mb-3">
            <?php if ($def['type'] === 'checkbox'): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="<?= e($field) ?>" name="<?= e($field) ?>" <?= !empty($row[$field]) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= e($field) ?>"><?= e($def['label']) ?></label>
                </div>
            <?php elseif ($def['type'] === 'image'): ?>
                <label class="form-label" for="<?= e($field) ?>"><?= e($def['label']) ?><?= empty($def['required']) || $row ? ' <span class="text-secondary">(optional)</span>' : '' ?></label>
                <?php if (!empty($row[$field])): ?>
                    <div class="mb-2"><img class="admin-thumb" src="<?= e(media($def['folder'], $row[$field])) ?>" alt=""></div>
                <?php endif; ?>
                <input class="form-control<?= invalid($errors, $field) ?>" type="file" id="<?= e($field) ?>" name="<?= e($field) ?>" accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPEG, PNG or WebP, up to 2 MB.</div>
            <?php elseif ($def['type'] === 'textarea'): ?>
                <label class="form-label" for="<?= e($field) ?>"><?= e($def['label']) ?></label>
                <textarea class="form-control<?= invalid($errors, $field) ?>" id="<?= e($field) ?>" name="<?= e($field) ?>" rows="<?= $slug === 'terms' ? 10 : 3 ?>"><?= old($field, $row[$field] ?? '') ?></textarea>
            <?php else: ?>
                <label class="form-label" for="<?= e($field) ?>"><?= e($def['label']) ?></label>
                <input class="form-control<?= invalid($errors, $field) ?>" type="<?= $def['type'] === 'number' ? 'number' : 'text' ?>" id="<?= e($field) ?>" name="<?= e($field) ?>" value="<?= old($field, $row[$field] ?? ($def['type'] === 'number' ? '0' : '')) ?>">
            <?php endif; ?>
            <?= field_error($errors, $field) ?>
        </div>
    <?php endforeach; ?>
    <button class="btn btn-dark" type="submit"><?= $row ? 'Save' : 'Add ' . e($type['singular']) ?></button>
</form>
