<?php
require_once VIEWS_DIR . '/partials.php';
/** @var array $calendar */
/** @var array $user */
$prevHref = url('/?tab=calendar&month=' . rawurlencode($calendar['prev']));
$nextHref = url('/?tab=calendar&month=' . rawurlencode($calendar['next']));
?>
<div class="cal-toolbar">
    <a class="btn small" href="<?= e($prevHref) ?>">Previous</a>
    <h2><?= e($calendar['label']) ?></h2>
    <a class="btn small" href="<?= e($nextHref) ?>">Next</a>
</div>
<p class="muted cal-hint">Active reservations only, in Eastern Time. Each Demo VM has its own color. Click a block for details.</p>

<?php if ($calendar['legend']): ?>
    <ul class="cal-legend">
        <?php foreach ($calendar['legend'] as $item): ?>
            <li>
                <span class="swatch <?= e($item['color']) ?>" aria-hidden="true"></span>
                <a href="<?= e(url('/vms/' . $item['id'])) ?>"><?= e($item['name']) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="muted">No active reservations in this month.</p>
<?php endif; ?>

<div class="cal-grid" role="grid" aria-label="Reservation calendar">
    <div class="cal-dow" role="row">
        <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
    </div>
    <?php foreach ($calendar['weeks'] as $week): ?>
        <div class="cal-week" role="row">
            <?php foreach ($week as $cell): ?>
                <section class="cal-day <?= $cell['in_month'] ? '' : 'is-out' ?> <?= $cell['is_today'] ? 'is-today' : '' ?>" role="gridcell">
                    <header><?= (int) $cell['day'] ?></header>
                    <?php foreach ($cell['events'] as $row): ?>
                        <?php
                        $startEt = Timezone::toApp(Timezone::fromUtcString($row['start_utc']));
                        $endEt = Timezone::toApp(Timezone::fromUtcString($row['end_utc']));
                        $sameDay = $startEt->format('Y-m-d') === $cell['date'] && $endEt->format('Y-m-d') === $cell['date'];
                        $timeLabel = $sameDay
                            ? $startEt->format('g:ia') . '–' . $endEt->format('g:ia')
                            : 'Through ' . $endEt->format('M j g:ia');
                        $title = $row['vm_name'] . ' · ' . $row['username'] . ' · '
                            . Timezone::formatEt(Timezone::fromUtcString($row['start_utc']))
                            . ' to '
                            . Timezone::formatEt(Timezone::fromUtcString($row['end_utc']));
                        ?>
                        <a class="cal-event <?= e(vm_color_class((int) $row['vm_id'])) ?>"
                           href="<?= e(url('/vms/' . $row['vm_id'])) ?>"
                           title="<?= e($title) ?>">
                            <strong><?= e($row['vm_name']) ?></strong>
                            <span><?= e($row['username']) ?></span>
                            <span class="tiny"><?= e($timeLabel) ?> ET</span>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
