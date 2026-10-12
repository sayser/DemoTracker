<?php
require_once VIEWS_DIR . '/partials.php';
/** @var array $vm */
/** @var array $history */
/** @var array $user */
?>
<div class="page-head">
    <div>
        <p class="crumb"><a href="<?= e(url('/')) ?>">Dashboard</a></p>
        <h1><?= e($vm['name']) ?></h1>
        <?php if (!empty($vm['ip_hostname'])): ?>
            <p class="mono"><?= e($vm['ip_hostname']) ?></p>
        <?php endif; ?>
        <?php if (!empty($vm['description'])): ?>
            <p class="lede"><?= e($vm['description']) ?></p>
        <?php endif; ?>
        <?php if ((int) $vm['active'] !== 1): ?>
            <p class="flash flash-error">This Demo VM is disabled. Existing reservations remain, but no new bookings.</p>
        <?php endif; ?>
    </div>
    <?php if (can_reserve($user) && (int) $vm['active'] === 1): ?>
        <a class="btn primary" href="<?= e(url('/reserve?vm=' . $vm['id'])) ?>">Reserve</a>
    <?php endif; ?>
</div>

<section class="panel">
    <h2>Upcoming</h2>
    <?php if (empty($vm['upcoming'])): ?>
        <p class="muted">No upcoming reservations. The calendar is open.</p>
    <?php else: ?>
        <ol class="timeline">
            <?php foreach ($vm['upcoming'] as $row): ?>
                <?php
                $now = Timezone::nowUtc();
                $start = Timezone::fromUtcString($row['start_utc']);
                $end = Timezone::fromUtcString($row['end_utc']);
                $live = $start <= $now && $end > $now;
                ?>
                <li class="<?= $live ? 'live' : '' ?>">
                    <div>
                        <strong><?= e($row['username']) ?></strong>
                        <?= status_badge($live ? 'active' : 'active') ?>
                        <?php if ($live): ?><span class="tiny">now</span><?php endif; ?>
                    </div>
                    <p><?= window_et($row) ?> · <?= e($row['duration_label']) ?></p>
                    <?php if (!empty($row['notes'])): ?>
                        <p class="muted"><?= e($row['notes']) ?></p>
                    <?php endif; ?>
                    <?php if (can_reserve($user) && ReservationService::canMutate($row, $user)): ?>
                        <div class="actions">
                            <a class="btn small" href="<?= e(url('/extend?id=' . $row['id'])) ?>">Extend</a>
                            <form method="post" action="<?= e(url('/release')) ?>" class="inline" onsubmit="return confirm('Release this reservation now? Watchers will be emailed.');">
                                <?= $csrfField ?>
                                <input type="hidden" name="reservation_id" value="<?= (int) $row['id'] ?>">
                                <input type="hidden" name="back" value="<?= e('/vms/' . $vm['id']) ?>">
                                <button type="submit" class="btn small danger">Release</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>History</h2>
    <?php if ($history === []): ?>
        <p class="muted">No reservations yet for this VM.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Who</th>
                        <th>Window (ET)</th>
                        <th>Length</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
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
</section>
