<?php
/** @var string $message */
?>
<div class="empty">
    <h1><?= e($title ?? 'Error') ?></h1>
    <p><?= e($message ?? 'Something went wrong.') ?></p>
    <a class="btn" href="<?= e(url('/')) ?>">Back to dashboard</a>
</div>
