<div class="container my-4 narrow-md">
    <h1 class="h3">Contact us</h1>
    <p class="text-secondary">Questions about an order, sizing or returns? Send us a message and we'll reply by email.</p>
    <form method="post" action="<?= e(url('/contact')) ?>" class="auth-card" novalidate>
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Name</label>
                <input class="form-control<?= invalid($errors, 'name') ?>" id="name" name="name" maxlength="120" value="<?= old('name', $customer['name'] ?? '') ?>" required>
                <?= field_error($errors, 'name') ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input class="form-control<?= invalid($errors, 'email') ?>" type="email" id="email" name="email" maxlength="190" value="<?= old('email', $customer['email'] ?? '') ?>" required>
                <?= field_error($errors, 'email') ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="subject">Subject</label>
                <input class="form-control<?= invalid($errors, 'subject') ?>" id="subject" name="subject" maxlength="190" value="<?= old('subject') ?>" required>
                <?= field_error($errors, 'subject') ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="message">Message</label>
                <textarea class="form-control<?= invalid($errors, 'message') ?>" id="message" name="message" rows="5" maxlength="4000" required><?= old('message') ?></textarea>
                <?= field_error($errors, 'message') ?>
            </div>
        </div>
        <button class="btn btn-dark mt-3" type="submit"><i class="bi bi-send"></i> Send message</button>
    </form>
</div>
