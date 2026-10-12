<?php
/** @var array $vms */
?>
<div class="page-head">
    <div>
        <h1>Demo VMs</h1>
        <p class="muted">Name, description, and IP or hostname only. Disabling a VM hides it from new bookings and keeps history.</p>
    </div>
</div>

<form method="post" action="<?= e(url('/admin/vms')) ?>" class="panel form-grid">
    <h2>Add Demo VM</h2>
    <?= $csrfField ?>
    <label>
        Name
        <input type="text" name="name" required maxlength="64" placeholder="Demo01">
    </label>
    <label>
        IP / hostname
        <input type="text" name="ip_hostname" maxlength="255" placeholder="10.0.0.21">
    </label>
    <label class="wide">
        Description
        <input type="text" name="description" maxlength="500">
    </label>
    <button type="submit" class="btn primary">Add Demo VM</button>
</form>

<div class="stack">
    <?php foreach ($vms as $vm): ?>
        <form method="post" action="<?= e(url('/admin/vms/update')) ?>" class="panel form-grid">
            <?= $csrfField ?>
            <input type="hidden" name="id" value="<?= (int) $vm['id'] ?>">
            <label>
                Name
                <input type="text" name="name" required maxlength="64" value="<?= e($vm['name']) ?>">
            </label>
            <label>
                IP / hostname
                <input type="text" name="ip_hostname" maxlength="255" value="<?= e((string) $vm['ip_hostname']) ?>">
            </label>
            <label class="wide">
                Description
                <input type="text" name="description" maxlength="500" value="<?= e((string) $vm['description']) ?>">
            </label>
            <label class="check">
                <input type="checkbox" name="active" value="1" <?= (int) $vm['active'] === 1 ? 'checked' : '' ?>>
                Available for new reservations
            </label>
            <button type="submit" class="btn small">Save</button>
        </form>
    <?php endforeach; ?>
    <?php if ($vms === []): ?>
        <div class="empty"><p>No Demo VMs yet. Add Demo01, Demo02, and so on.</p></div>
    <?php endif; ?>
</div>
