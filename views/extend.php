<?php
require_once VIEWS_DIR . '/partials.php';
/** @var array $row */
/** @var string $endDefault */
?>
<div class="page-head">
    <div>
        <h1>Extend <?= e($row['vm_name']) ?></h1>
        <p class="muted">Currently <?= window_et($row) ?>. New end must be later, stay within 6 months from the original start, and not overlap the next booking.</p>
    </div>
</div>
<form method="post" action="<?= e(url('/extend')) ?>" class="panel stack">
    <?= $csrfField ?>
    <input type="hidden" name="reservation_id" value="<?= (int) $row['id'] ?>">
    <p>Reserved by <?= e($row['username']) ?></p>
    <label>
        New end (Eastern Time)
        <input type="datetime-local" name="end_local" required value="<?= e($endDefault) ?>">
    </label>
    <button type="submit" class="btn primary">Save extension</button>
</form>
