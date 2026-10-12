<?php
/** @var array $vms */
/** @var int $selectedVm */
/** @var string $startDefault */
?>
<div class="page-head">
    <div>
        <h1>Reserve a Demo VM</h1>
        <p class="muted">Pick a start in Eastern Time. Maximum length is 3 weeks. Overlapping bookings on the same VM are rejected.</p>
    </div>
</div>

<form method="post" action="<?= e(url('/reserve')) ?>" class="panel form-grid" id="reserve-form">
    <?= $csrfField ?>
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
    <fieldset class="segment">
        <legend>How long?</legend>
        <label class="radio">
            <input type="radio" name="mode" value="duration" checked>
            Duration (hours, days, or weeks)
        </label>
        <div class="row">
            <label>
                Amount
                <input type="number" name="amount" id="amount" min="1" value="3">
            </label>
            <label>
                Unit
                <select name="unit" id="unit">
                    <option value="hours">Hours</option>
                    <option value="days" selected>Days</option>
                    <option value="weeks">Weeks</option>
                </select>
            </label>
        </div>
        <label class="radio">
            <input type="radio" name="mode" value="end">
            Specific end time (Eastern Time)
        </label>
        <label>
            End (Eastern Time)
            <input type="datetime-local" name="end_local" id="end_local">
        </label>
    </fieldset>
    <p class="preview" id="preview">End will show here.</p>
    <label>
        Notes (optional)
        <textarea name="notes" rows="2" maxlength="500" placeholder="Why you need it"></textarea>
    </label>
    <button type="submit" class="btn primary">Create reservation</button>
</form>
