<?php
require_once VIEWS_DIR . '/partials.php';
/** @var array $rows */
/** @var array $user */
?>
<div class="page-head">
    <div>
        <h1>My reservations</h1>
        <p class="muted">Active, upcoming, and past bookings you created.</p>
    </div>
</div>
<?php if ($rows === []): ?>
    <div class="empty">
        <p>You have not reserved a Demo VM yet.</p>
        <?php if (can_reserve($user)): ?>
            <a class="btn primary" href="<?= e(url('/reserve')) ?>">Reserve</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-wrap panel">
        <table>
            <thead>
                <tr>
                    <th>VM</th>
                    <th>Window (ET)</th>
                    <th>Length</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><a href="<?= e(url('/vms/' . $row['vm_id'])) ?>"><?= e($row['vm_name']) ?></a></td>
                        <td><?= window_et($row) ?></td>
                        <td><?= e($row['duration_label']) ?></td>
                        <td><?= status_badge($row['status']) ?></td>
                        <td>
                            <?php if ($row['status'] === 'active' && can_reserve($user)): ?>
                                <a href="<?= e(url('/extend?id=' . $row['id'])) ?>">Extend</a>
                                <form method="post" action="<?= e(url('/release')) ?>" class="inline" onsubmit="return confirm('Release this reservation now? Watchers will be emailed.');">
                                    <?= $csrfField ?>
                                    <input type="hidden" name="reservation_id" value="<?= (int) $row['id'] ?>">
                                    <input type="hidden" name="back" value="/mine">
                                    <button type="submit" class="linkish danger-text">Release</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
