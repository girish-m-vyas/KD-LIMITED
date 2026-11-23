<?php use KD\Support\View; ?>
<div class="container my-4">
    <div class="row g-4">
        <div class="col-lg-3"><?= View::partial('account/_nav', ['section' => $section, 'customer' => $customer]) ?></div>
        <div class="col-lg-9">
            <h1 class="h3 mb-3">Edit account</h1>
            <form method="post" action="<?= e(url('/account/edit')) ?>" enctype="multipart/form-data" class="auth-card" novalidate>
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Full name</label>
                        <input class="form-control<?= invalid($errors, 'name') ?>" id="name" name="name" maxlength="120" value="<?= old('name', $me['name']) ?>" required>
                        <?= field_error($errors, 'name') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control<?= invalid($errors, 'email') ?>" type="email" id="email" name="email" maxlength="190" value="<?= old('email', $me['email']) ?>" required>
                        <?= field_error($errors, 'email') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact">Phone</label>
                        <input class="form-control<?= invalid($errors, 'contact') ?>" type="tel" id="contact" name="contact" maxlength="20" value="<?= old('contact', $me['contact']) ?>" required>
                        <?= field_error($errors, 'contact') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="city">City</label>
                        <input class="form-control<?= invalid($errors, 'city') ?>" id="city" name="city" maxlength="120" value="<?= old('city', $me['city']) ?>" required>
                        <?= field_error($errors, 'city') ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address">Delivery address</label>
                        <textarea class="form-control<?= invalid($errors, 'address') ?>" id="address" name="address" rows="2" maxlength="500" required><?= old('address', $me['address']) ?></textarea>
                        <?= field_error($errors, 'address') ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="image">New profile photo <span class="text-secondary">(optional)</span></label>
                        <input class="form-control<?= invalid($errors, 'image') ?>" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                        <?= field_error($errors, 'image') ?>
                    </div>
                </div>
                <button class="btn btn-dark mt-3" type="submit">Save changes</button>
            </form>
        </div>
    </div>
</div>
