<?php
require_once VIEWS_DIR . '/partials.php';
/** @var array $vms */
/** @var array $user */
/** @var string $nowEt */
/** @var string $tab */
/** @var array $calendar */
$tab = $tab ?? 'vms';
?>
<div class="page-head">
    <div>
        <h1><?= $tab === 'calendar' ? 'Reservation calendar' : 'Demo VMs' ?></h1>
    </div>
    <?php if (can_reserve($user)): ?>
        <a class="btn primary" href="<?= e(url('/reserve')) ?>">Reserve a VM</a>
    <?php endif; ?>
</div>

<nav class="tabs" aria-label="Dashboard views">
    <a class="tab <?= $tab === 'vms' ? 'is-on' : '' ?>" href="<?= e(url('/')) ?>">Demo VMs</a>
    <a class="tab <?= $tab === 'calendar' ? 'is-on' : '' ?>" href="<?= e(url('/?tab=calendar')) ?>">Calendar</a>
</nav>

<?php if ($tab === 'calendar'): ?>
    <?php require VIEWS_DIR . '/calendar.php'; ?>
<?php elseif ($vms === []): ?>
    <div class="empty">
        <h2>No Demo VMs yet</h2>
        <p><?= is_admin($user) ? 'Add one under Demo VMs in the header.' : 'Ask an admin to add a Demo VM, then you can reserve it here.' ?></p>
    </div>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($vms as $vm): ?>
            <?php $busy = !empty($vm['busy_now']); ?>
            <article class="vm-card <?= $busy ? 'is-busy' : 'is-free' ?> <?= e(vm_color_class((int) $vm['id'])) ?>">
                <header>
                    <h2><a href="<?= e(url('/vms/' . $vm['id'])) ?>"><?= e($vm['name']) ?></a></h2>
                    <span class="pill"><?= $busy ? 'In use' : 'Free now' ?></span>
                </header>
                <?php if (!empty($vm['ip_hostname'])): ?>
                    <p class="mono"><?= e($vm['ip_hostname']) ?></p>
                <?php endif; ?>
                <?php if (!empty($vm['description'])): ?>
                    <p><?= e($vm['description']) ?></p>
                <?php endif; ?>

                <?php if ($busy && $vm['current']): ?>
                    <dl class="kv">
                        <dt>Held by</dt>
                        <dd><?= e($vm['current']['username']) ?></dd>
                        <dt>Until</dt>
                        <dd><?= e(Timezone::formatEt(Timezone::fromUtcString($vm['current']['end_utc']))) ?></dd>
                    </dl>
                    <?php if (can_reserve($user) && ReservationService::canMutate($vm['current'], $user)): ?>
                        <div class="actions">
                            <a class="btn small" href="<?= e(url('/extend?id=' . $vm['current']['id'])) ?>">Extend</a>
                            <form method="post" action="<?= e(url('/release')) ?>" class="inline" onsubmit="return confirm('Release this reservation now? Watchers will be emailed.');">
                                <?= $csrfField ?>
                                <input type="hidden" name="reservation_id" value="<?= (int) $vm['current']['id'] ?>">
                                <input type="hidden" name="back" value="/">
                                <button type="submit" class="btn small danger">Release</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php
                $future = array_values(array_filter($vm['upcoming'] ?? [], function ($r) use ($vm) {
                    if (!empty($vm['current']) && (int) $r['id'] === (int) $vm['current']['id']) {
                        return false;
                    }
                    return Timezone::fromUtcString($r['start_utc']) > Timezone::nowUtc();
                }));
                ?>
                <?php if ($future): ?>
                    <p class="tiny muted">Next: <?= e($future[0]['username']) ?> · <?= e(Timezone::formatEtShort(Timezone::fromUtcString($future[0]['start_utc']))) ?></p>
                <?php endif; ?>

                <footer>
                    <?php if (can_reserve($user)): ?>
                        <a href="<?= e(url('/reserve?vm=' . $vm['id'])) ?>">Reserve</a>
                    <?php endif; ?>
                    <a href="<?= e(url('/vms/' . $vm['id'])) ?>">Schedule</a>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
