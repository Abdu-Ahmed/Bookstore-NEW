<?php
declare(strict_types=1);

/** @var array<string,string> $errors */
/** @var array $data */
$base = $data['base_url'] ?? '';
?>
<form class="bg-light" action="<?= $escape($base) . '/register' ?>" method="POST" novalidate>
    <div class="form-group py-3">
        <label for="register-username">Username *</label>
        <input type="text" name="username" id="register-username" value="<?= $escape($data['username'] ?? '') ?>" minlength="2" placeholder="Your Username" class="w-100" required>
        <?php if (isset($errors['username'])): ?>
            <div class="invalid-feedback"><?= $escape($errors['username']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group py-3">
        <label for="register-email">Email Address *</label>
        <input type="email" name="email" id="register-email" value="<?= $escape($data['email'] ?? '') ?>" minlength="2" placeholder="Your Email Address" class="w-100" required>
        <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= $escape($errors['email']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group py-3">
        <label for="register-password">Password *</label>
        <input type="password" name="password" id="register-password" minlength="6" placeholder="Your Password" class="w-100" required>
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback"><?= $escape($errors['password']) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group py-3">
        <label for="register-confirm-password">Confirm Password *</label>
        <input type="password" name="confirm_password" id="register-confirm-password" minlength="6" placeholder="Confirm Your Password" class="w-100" required>
        <?php if (isset($errors['confirm_password'])): ?>
            <div class="invalid-feedback"><?= $escape($errors['confirm_password']) ?></div>
        <?php endif; ?>
    </div>

    <label class="py-3">
        <input type="checkbox" required class="d-inline">
        <span class="label-body">I agree to the <a href="#" class="fw-bold">Privacy Policy</a></span>
    </label>

    <button type="submit" name="submit" class="btn btn-dark w-100 my-3">Register</button>

    <?php if (isset($errors['general'])): ?>
        <div class="text-danger mt-2"><?= $escape($errors['general']) ?></div>
    <?php endif; ?>
</form>
