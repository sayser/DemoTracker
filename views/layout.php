<?php
/** @var string $content */
/** @var string $appTitle */
/** @var string $title */
/** @var ?array $user */
/** @var array $flashes */
$isLogin = empty($user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') !== '' ? $title . ' · ' . $appTitle : $appTitle) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/app.css')) ?>">
</head>
<body>
    <div class="shell">
        <?php if (!$isLogin): ?>
            <header class="top">
                <a class="brand" href="<?= e(url('/')) ?>">
                    <span class="brand-mark" aria-hidden="true"></span>
                    <span>
                        <strong><?= e($appTitle) ?></strong>
                        <small>Eastern Time · America/New_York</small>
                    </span>
                </a>
                <nav class="nav">
                    <a href="<?= e(url('/')) ?>">Dashboard</a>
                    <a href="<?= e(url('/history')) ?>">History</a>
                    <?php if (can_reserve($user)): ?>
                        <a href="<?= e(url('/reserve')) ?>">Reserve</a>
                        <a href="<?= e(url('/mine')) ?>">My reservations</a>
                    <?php endif; ?>
                    <a href="<?= e(url('/watch')) ?>">Watch list</a>
                    <?php if (is_admin($user)): ?>
                        <a href="<?= e(url('/admin/users')) ?>">Users</a>
                        <a href="<?= e(url('/admin/vms')) ?>">Demo VMs</a>
                    <?php endif; ?>
                </nav>
                <div class="who">
                    <span><?= e($user['username']) ?> · <?= e(role_label($user['role'])) ?></span>
                    <a href="<?= e(url('/password')) ?>">Password</a>
                    <form class="inline" method="post" action="<?= e(url('/logout')) ?>">
                        <?= $csrfField ?? Csrf::field() ?>
                        <button type="submit" class="linkish">Sign out</button>
                    </form>
                </div>
            </header>
        <?php endif; ?>

        <main class="<?= $isLogin ? 'main-login' : 'main' ?>">
            <?php foreach ($flashes as $f): ?>
                <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
            <?= $content ?>
        </main>
    </div>
    <script src="<?= e(url('/assets/app.js')) ?>"></script>
</body>
</html>
