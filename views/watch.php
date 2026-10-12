<?php
/** @var bool $global */
/** @var array $watched */
/** @var array $vms */
$watched = array_map('intval', $watched);
?>
<div class="page-head">
    <div>
        <h1>Watch list</h1>
        <p class="muted">Email via sendmail when a matching reservation is created, expires, or is released early. Site-wide and per-VM can both be on; you still get one email per event.</p>
    </div>
</div>
<form method="post" action="<?= e(url('/watch')) ?>" class="panel stack">
    <?= $csrfField ?>
    <label class="check">
        <input type="checkbox" name="global" value="1" <?= $global ? 'checked' : '' ?>>
        Notify me about every Demo VM
    </label>
    <fieldset>
        <legend>Specific Demo VMs</legend>
        <?php if ($vms === []): ?>
            <p class="muted">No Demo VMs to watch yet.</p>
        <?php else: ?>
            <?php foreach ($vms as $vm): ?>
                <label class="check">
                    <input type="checkbox" name="vm_ids[]" value="<?= (int) $vm['id'] ?>" <?= in_array((int) $vm['id'], $watched, true) ? 'checked' : '' ?>>
                    <?= e($vm['name']) ?><?= !empty($vm['ip_hostname']) ? ' · ' . e($vm['ip_hostname']) : '' ?><?= (int) $vm['active'] === 1 ? '' : ' (disabled)' ?>
                </label>
            <?php endforeach; ?>
        <?php endif; ?>
    </fieldset>
    <button type="submit" class="btn primary">Save watch list</button>
</form>
