<?php
/** @var array $vms */
/** @var int $selectedVm */
/** @var string $startDefault */
?>
<div class="page-head">
    <div>
        <h1>Reserve a Demo VM</h1>
        <p class="muted">Start time is Eastern Time. Length is days, weeks, or months, up to 6 months. Overlapping bookings on the same VM are rejected.</p>
    </div>
</div>

<form method="post" action="<?= e(url('/reserve')) ?>" class="panel reserve-form" id="reserve-form">
    <?= $csrfField ?>
    <div class="reserve-grid">
        <label>
            Demo VM
            <select name="vm_id" required>
                <?php foreach ($vms as $vm): ?>
                    <option value="<?= (int) $vm['id'] ?>" <?= (int) $vm['id'] === $selectedVm ? 'selected' : '' ?>>
                        <?= e($vm['name']) ?><?= !empty($vm['ip_hostname']) ? ' · ' . e($vm['ip_hostname']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Start (Eastern Time)
            <input type="datetime-local" name="start_local" id="start_local" required value="<?= e($startDefault) ?>">
        </label>
        <label>
            Amount
            <input type="number" name="amount" id="amount" min="1" max="186" value="1" required>
        </label>
        <label>
            Unit
            <select name="unit" id="unit">
                <option value="days">Days</option>
                <option value="weeks" selected>Weeks</option>
                <option value="months">Months</option>
            </select>
        </label>
    </div>
    <input type="hidden" name="mode" value="duration">
    <p class="preview" id="preview">End will show here.</p>
    <label>
        Notes (optional)
        <textarea name="notes" rows="3" maxlength="500" placeholder="Why you need it"></textarea>
    </label>
    <button type="submit" class="btn primary">Create reservation</button>
</form>
