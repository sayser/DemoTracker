<?php
/** @var string $appTitle */
?>
<section class="login-card">
    <p class="eyebrow">Internal lab</p>
    <h1><?= e($appTitle) ?></h1>
    <p class="lede">Reserve a Demo VM, see who has it, and get email when a window opens.</p>
    <form method="post" action="<?= e(url('/login')) ?>" class="stack">
        <?= $csrfField ?>
        <label>
            Username
            <input type="text" name="username" autocomplete="username" required autofocus>
        </label>
        <label>
            Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button type="submit" class="btn primary">Sign in</button>
    </form>
</section>
