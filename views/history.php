<?php
require_once VIEWS_DIR . '/partials.php';
/** @var array $rows */
/** @var array $vms */
/** @var int $vmId */
?>
<div class="page-head">
    <div>
        <h1>Reservation history</h1>
        <p class="muted">Active, expired, and early-released bookings. Times are Eastern Time.</p>
    </div>
    <form method="get" action="<?= e(url('/history')) ?>" class="filter">
        <label>
            Demo VM
            <select name="vm" onchange="this.form.submit()">
                <option value="0">All</option>
                <?php foreach ($vms as $vm): ?>
                    <option value="<?= (int) $vm['id'] ?>" <?= $vmId === (int) $vm['id'] ? 'selected' : '' ?>><?= e($vm['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>
</div>
<?php if ($rows === []): ?>
    <div class="empty"><p>No reservations recorded yet.</p></div>
<?php else: ?>
    <div class="table-wrap panel">
        <table>
            <thead>
                <tr>
                    <th>VM</th>
                    <th>Who</th>
                    <th>Window (ET)</th>
                    <th>Length</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a href="<?= e(url('/vms/' . $row['vm_id'])) ?>"><?= e($row['vm_name']) ?></a></td>
                        <td><?= e($row['username']) ?></td>
                        <td><?= window_et($row) ?></td>
                        <td><?= e($row['duration_label']) ?></td>
                        <td>
                            <?= status_badge($row['status']) ?>
                            <?php if ($row['status'] === 'released' && !empty($row['released_by_username'])): ?>
                                <span class="tiny muted">by <?= e($row['released_by_username']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
