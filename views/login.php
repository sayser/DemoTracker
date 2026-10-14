<?php
/** @var string $appTitle */
?>
<section class="login-card">
    <h1><?= e($appTitle) ?></h1>
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
