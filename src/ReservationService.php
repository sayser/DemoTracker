<?php

declare(strict_types=1);

final class ReservationService
{
    public static function listVms(bool $activeOnly = true): array
    {
        $sql = 'SELECT id, name, description, ip_hostname, url, active, created_at FROM demo_vms';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY name';
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function findVm(int $id, bool $activeOnly = false): ?array
    {
        $sql = 'SELECT id, name, description, ip_hostname, url, active, created_at FROM demo_vms WHERE id = ?';
        if ($activeOnly) {
            $sql .= ' AND active = 1';
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createVm(string $name, string $description, string $ipHostname, string $url = ''): int
    {
        $name = self::cleanText($name, 64);
        if ($name === '') {
            throw new InvalidArgumentException('VM name is required (max 64 characters).');
        }
        $description = self::cleanText($description, 500);
        $ipHostname = self::cleanText($ipHostname, 255);
        $url = self::cleanUrl($url);
        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO demo_vms (name, description, ip_hostname, url, active) VALUES (?, ?, ?, ?, 1)'
            );
            $stmt->execute([
                $name,
                $description !== '' ? $description : null,
                $ipHostname !== '' ? $ipHostname : null,
                $url !== '' ? $url : null,
            ]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException('A Demo VM with that name already exists.');
            }
            throw $e;
        }
        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateVm(int $id, string $name, string $description, string $ipHostname, bool $active, string $url = ''): void
    {
        $name = self::cleanText($name, 64);
        if ($name === '') {
            throw new InvalidArgumentException('VM name is required (max 64 characters).');
        }
        $description = self::cleanText($description, 500);
        $ipHostname = self::cleanText($ipHostname, 255);
        $url = self::cleanUrl($url);
        try {
            $stmt = Database::pdo()->prepare(
                'UPDATE demo_vms SET name = ?, description = ?, ip_hostname = ?, url = ?, active = ? WHERE id = ?'
            );
            $stmt->execute([
                $name,
                $description !== '' ? $description : null,
                $ipHostname !== '' ? $ipHostname : null,
                $url !== '' ? $url : null,
                $active ? 1 : 0,
                $id,
            ]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException('A Demo VM with that name already exists.');
            }
            throw $e;
        }
    }

    public static function dashboard(): array
    {
        $now = Timezone::utcSql(Timezone::nowUtc());
        $vms = self::listVms(true);
        $out = [];
        foreach ($vms as $vm) {
            $out[] = self::decorateVm($vm, $now);
        }
        return $out;
    }

    public static function decorateVm(array $vm, ?string $nowSql = null): array
    {
        $nowSql = $nowSql ?? Timezone::utcSql(Timezone::nowUtc());
        $current = self::currentForVm((int) $vm['id'], $nowSql);
        $upcoming = self::upcomingForVm((int) $vm['id'], $nowSql, 8);
        $vm['current'] = $current;
        $vm['upcoming'] = $upcoming;
        $vm['busy_now'] = $current !== null;
        return $vm;
    }

    public static function currentForVm(int $vmId, string $nowSql): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT r.*, u.username, u.email
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             WHERE r.vm_id = ? AND r.status = "active"
               AND r.start_utc <= ? AND r.end_utc > ?
             ORDER BY r.start_utc
             LIMIT 1'
        );
        $stmt->execute([$vmId, $nowSql, $nowSql]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function upcomingForVm(int $vmId, string $nowSql, int $limit = 20): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT r.*, u.username, u.email
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             WHERE r.vm_id = ? AND r.status = "active" AND r.end_utc > ?
             ORDER BY r.start_utc
             LIMIT ' . (int) $limit
        );
        $stmt->execute([$vmId, $nowSql]);
        return $stmt->fetchAll();
    }

    public static function activeOverlapping(DateTimeImmutable $rangeStartUtc, DateTimeImmutable $rangeEndUtc): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT r.*, u.username, u.email, v.name AS vm_name, v.ip_hostname
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN demo_vms v ON v.id = r.vm_id
             WHERE r.status = "active"
               AND r.start_utc < ? AND r.end_utc > ?
             ORDER BY r.start_utc, v.name'
        );
        $stmt->execute([Timezone::utcSql($rangeEndUtc), Timezone::utcSql($rangeStartUtc)]);
        return $stmt->fetchAll();
    }

    /**
     * Month calendar in Eastern Time. Each day lists active reservations that overlap that day.
     *
     * @return array{year:int,month:int,label:string,prev:string,next:string,weeks:array,legend:array}
     */
    public static function monthCalendar(int $year, int $month): array
    {
        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            $now = Timezone::nowApp();
            $year = (int) $now->format('Y');
            $month = (int) $now->format('n');
        }

        $tz = Timezone::appTz();
        $first = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month), $tz);
        $pad = (int) $first->format('w');
        $gridStart = $first->modify('-' . $pad . ' days');
        $cells = [];
        $cursor = $gridStart;
        $today = Timezone::nowApp()->format('Y-m-d');
        for ($i = 0; $i < 42; $i++) {
            $next = $cursor->modify('+1 day');
            $cells[] = [
                'date' => $cursor->format('Y-m-d'),
                'day' => (int) $cursor->format('j'),
                'in_month' => (int) $cursor->format('n') === $month,
                'is_today' => $cursor->format('Y-m-d') === $today,
                'start_utc' => Timezone::utcSql($cursor->setTimezone(Timezone::utcTz())),
                'end_utc' => Timezone::utcSql($next->setTimezone(Timezone::utcTz())),
                'events' => [],
            ];
            $cursor = $next;
        }

        $rangeStart = $gridStart->setTimezone(Timezone::utcTz());
        $rangeEnd = $cursor->setTimezone(Timezone::utcTz());
        $rows = self::activeOverlapping($rangeStart, $rangeEnd);
        $legend = [];
        foreach ($rows as $row) {
            $legend[(int) $row['vm_id']] = [
                'id' => (int) $row['vm_id'],
                'name' => $row['vm_name'],
                'color' => vm_color_class((int) $row['vm_id']),
            ];
            foreach ($cells as $i => $cell) {
                if ($row['start_utc'] < $cell['end_utc'] && $row['end_utc'] > $cell['start_utc']) {
                    $cells[$i]['events'][] = $row;
                }
            }
        }
        usort($legend, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        $weeks = array_chunk($cells, 7);
        $prev = $first->modify('-1 month');
        $next = $first->modify('+1 month');

        return [
            'year' => $year,
            'month' => $month,
            'label' => $first->format('F Y'),
            'prev' => $prev->format('Y-m'),
            'next' => $next->format('Y-m'),
            'weeks' => $weeks,
            'legend' => array_values($legend),
        ];
    }

    public static function history(?int $vmId = null, int $limit = 200): array
    {
        $sql = 'SELECT r.*, u.username, u.email, v.name AS vm_name, v.ip_hostname,
                       rb.username AS released_by_username
                FROM reservations r
                JOIN users u ON u.id = r.user_id
                JOIN demo_vms v ON v.id = r.vm_id
                LEFT JOIN users rb ON rb.id = r.released_by_user_id';
        $params = [];
        if ($vmId !== null) {
            $sql .= ' WHERE r.vm_id = ?';
            $params[] = $vmId;
        }
        $sql .= ' ORDER BY r.start_utc DESC LIMIT ' . (int) $limit;
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT r.*, v.name AS vm_name, v.ip_hostname
             FROM reservations r
             JOIN demo_vms v ON v.id = r.vm_id
             WHERE r.user_id = ?
             ORDER BY r.start_utc DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function findReservation(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT r.*, u.username, u.email, v.name AS vm_name, v.ip_hostname, v.id AS vm_table_id
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN demo_vms v ON v.id = r.vm_id
             WHERE r.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(
        int $vmId,
        int $userId,
        DateTimeImmutable $startUtc,
        DateTimeImmutable $endUtc,
        string $durationLabel,
        string $notes
    ): int {
        $notes = self::cleanText($notes, 500);
        $durationLabel = self::cleanText($durationLabel, 64);
        self::assertWindow($startUtc, $endUtc);
        $now = Timezone::nowUtc();
        if ($startUtc < $now->sub(new DateInterval('PT2M'))) {
            throw new InvalidArgumentException('Start time must be now or in the future.');
        }

        $id = 0;
        $vm = null;
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT id, name, description, ip_hostname, url, active FROM demo_vms WHERE id = ? FOR UPDATE');
            $lock->execute([$vmId]);
            $vm = $lock->fetch();
            if (!$vm || (int) $vm['active'] !== 1) {
                throw new RuntimeException('That Demo VM is not available.');
            }
            self::assertNoOverlap($pdo, $vmId, $startUtc, $endUtc, null);

            $ins = $pdo->prepare(
                'INSERT INTO reservations (vm_id, user_id, start_utc, end_utc, duration_label, notes, status)
                 VALUES (?, ?, ?, ?, ?, ?, "active")'
            );
            $ins->execute([
                $vmId,
                $userId,
                Timezone::utcSql($startUtc),
                Timezone::utcSql($endUtc),
                $durationLabel,
                $notes !== '' ? $notes : null,
            ]);
            $id = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $reservation = self::findReservation($id);
        $owner = Auth::find($userId);
        if ($reservation && $owner) {
            Mailer::reservationCreated($reservation, $vm, $owner);
        }
        return $id;
    }

    public static function extend(int $reservationId, DateTimeImmutable $newEndUtc, array $actor): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'SELECT r.*, v.name, v.description, v.ip_hostname, v.active AS vm_active
                 FROM reservations r
                 JOIN demo_vms v ON v.id = r.vm_id
                 WHERE r.id = ? FOR UPDATE'
            );
            $stmt->execute([$reservationId]);
            $row = $stmt->fetch();
            if (!$row || $row['status'] !== 'active') {
                throw new RuntimeException('Reservation not found or no longer active.');
            }
            if (!self::canMutate($row, $actor)) {
                throw new RuntimeException('You cannot extend this reservation.');
            }

            $start = Timezone::fromUtcString($row['start_utc']);
            $oldEnd = Timezone::fromUtcString($row['end_utc']);
            if ($newEndUtc <= $oldEnd) {
                throw new InvalidArgumentException('New end time must be after the current end time.');
            }
            self::assertWindow($start, $newEndUtc);
            $lock = $pdo->prepare('SELECT id FROM demo_vms WHERE id = ? FOR UPDATE');
            $lock->execute([(int) $row['vm_id']]);
            self::assertNoOverlap($pdo, (int) $row['vm_id'], $start, $newEndUtc, $reservationId);

            $label = self::humanDuration($start, $newEndUtc);
            $upd = $pdo->prepare('UPDATE reservations SET end_utc = ?, duration_label = ? WHERE id = ?');
            $upd->execute([Timezone::utcSql($newEndUtc), $label, $reservationId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function release(int $reservationId, array $actor): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'SELECT r.* FROM reservations r WHERE r.id = ? FOR UPDATE'
            );
            $stmt->execute([$reservationId]);
            $row = $stmt->fetch();
            if (!$row || $row['status'] !== 'active') {
                throw new RuntimeException('Reservation not found or no longer active.');
            }
            if (!self::canMutate($row, $actor)) {
                throw new RuntimeException('You cannot release this reservation.');
            }

            $now = Timezone::nowUtc();
            $end = Timezone::fromUtcString($row['end_utc']);
            if ($end <= $now) {
                throw new RuntimeException('This reservation has already ended. Open any page to expire it and notify watchers.');
            }

            $upd = $pdo->prepare(
                'UPDATE reservations SET status = "released", released_by_user_id = ?, ended_at = ? WHERE id = ?'
            );
            $upd->execute([(int) $actor['id'], Timezone::utcSql($now), $reservationId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $full = self::findReservation($reservationId);
        $vm = self::findVm((int) $full['vm_id']);
        $owner = Auth::find((int) $full['user_id']);
        if ($full && $vm && $owner) {
            Mailer::reservationReleased($full, $vm, $owner, $actor);
        }
    }

    public static function processDueFromWeb(): void
    {
        $pdo = Database::pdo();
        $lock = $pdo->query("SELECT GET_LOCK('demotracker_expire', 0)")->fetchColumn();
        if ((int) $lock !== 1) {
            return;
        }
        try {
            self::expireDue();
            Auth::pruneLoginAttempts();
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('demotracker_expire')");
        }
    }

    public static function expireDue(): int
    {
        $now = Timezone::utcSql(Timezone::nowUtc());
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT r.id FROM reservations r WHERE r.status = "active" AND r.end_utc <= ?'
        );
        $stmt->execute([$now]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $count = 0;
        foreach ($ids as $id) {
            if (self::expireOne($id)) {
                $count++;
            }
        }
        return $count;
    }

    private static function expireOne(int $id): bool
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM reservations WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            $now = Timezone::nowUtc();
            if (!$row || $row['status'] !== 'active') {
                $pdo->commit();
                return false;
            }
            if (Timezone::fromUtcString($row['end_utc']) > $now) {
                $pdo->commit();
                return false;
            }
            $upd = $pdo->prepare(
                'UPDATE reservations SET status = "expired", ended_at = ? WHERE id = ?'
            );
            $upd->execute([Timezone::utcSql($now), $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $full = self::findReservation($id);
        $vm = $full ? self::findVm((int) $full['vm_id']) : null;
        $owner = $full ? Auth::find((int) $full['user_id']) : null;
        if ($full && $vm && $owner) {
            Mailer::reservationExpired($full, $vm, $owner);
        }
        return true;
    }

    public static function canMutate(array $reservation, array $actor): bool
    {
        if ($actor['role'] === 'admin') {
            return true;
        }
        if ($actor['role'] === 'standard' && (int) $reservation['user_id'] === (int) $actor['id']) {
            return true;
        }
        return false;
    }

    public static function humanDuration(DateTimeImmutable $start, DateTimeImmutable $end): string
    {
        $startApp = $start->setTimezone(Timezone::appTz());
        $endApp = $end->setTimezone(Timezone::appTz());
        $diff = $startApp->diff($endApp);
        if ($diff->invert === 1) {
            throw new InvalidArgumentException('End time must be after start time.');
        }
        if ($diff->d === 0 && $diff->h === 0 && $diff->i === 0 && $diff->y === 0 && $diff->m >= 1) {
            return duration_label($diff->m, 'month');
        }
        $seconds = $end->getTimestamp() - $start->getTimestamp();
        if ($seconds % 604800 === 0) {
            return duration_label(intdiv($seconds, 604800), 'week');
        }
        if ($seconds % 86400 === 0) {
            return duration_label(intdiv($seconds, 86400), 'day');
        }
        return duration_label(max(1, (int) round($seconds / 86400)), 'day');
    }

    public static function assertWindow(DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        global $config;
        if ($end <= $start) {
            throw new InvalidArgumentException('End time must be after start time.');
        }
        $months = (int) ($config['app']['max_duration_months'] ?? 6);
        if ($months < 1) {
            $months = 6;
        }
        $startApp = $start->setTimezone(Timezone::appTz());
        $endApp = $end->setTimezone(Timezone::appTz());
        $maxEnd = $startApp->add(new DateInterval('P' . $months . 'M'));
        if ($endApp > $maxEnd) {
            throw new InvalidArgumentException('Reservations cannot be longer than 6 months.');
        }
    }

    private static function assertNoOverlap(
        PDO $pdo,
        int $vmId,
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        ?int $exceptId
    ): void {
        $sql = 'SELECT id FROM reservations
                WHERE vm_id = ? AND status = "active"
                  AND start_utc < ? AND end_utc > ?';
        $params = [$vmId, Timezone::utcSql($end), Timezone::utcSql($start)];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1 FOR UPDATE';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            throw new RuntimeException('That Demo VM is already reserved for part of this time window.');
        }
    }

    private static function cleanText(string $value, int $max): string
    {
        $value = trim(Security::oneLine($value));
        if (strlen($value) > $max) {
            throw new InvalidArgumentException('That field is too long.');
        }
        return $value;
    }

    private static function cleanUrl(string $value): string
    {
        $value = self::cleanText($value, 500);
        if ($value === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('URL must start with http:// or https://');
        }
        return $value;
    }
}
