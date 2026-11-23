<?php if (!$messages): ?>
    <div class="admin-card p-4 text-center text-secondary">No messages yet.</div>
<?php endif; ?>
<?php foreach ($messages as $m): ?>
    <article class="admin-card p-3 mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <h2 class="h6 mb-1"><?= e($m['subject']) ?></h2>
            <span class="small text-secondary"><?= e(date('d M Y H:i', strtotime($m['created_at']))) ?></span>
        </div>
        <p class="small text-secondary mb-2"><?= e($m['name']) ?> · <?= e($m['email']) ?></p>
        <p class="mb-0"><?= nl2br(e($m['message'])) ?></p>
    </article>
<?php endforeach; ?>
