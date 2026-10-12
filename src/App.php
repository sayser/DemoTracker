<?php

declare(strict_types=1);

final class App
{
    public static function dispatch(): void
    {
        $method = request_method();
        $path = current_path();

        if (!in_array($method, ['GET', 'POST'], true)) {
            http_response_code(405);
            header('Allow: GET, POST');
            echo 'Method not allowed';
            return;
        }

        self::enforceIdle();

        try {
            ReservationService::processDueFromWeb();
        } catch (Throwable $e) {
            error_log($e->getMessage());
        }

        if ($method === 'POST') {
            Csrf::requireValid();
        }

        match (true) {
            $method === 'GET' && $path === '/login' => self::loginForm(),
            $method === 'POST' && $path === '/login' => self::loginSubmit(),
            $method === 'POST' && $path === '/logout' => self::logout(),
            $method === 'GET' && $path === '/' => self::dashboard(),
            $method === 'GET' && $path === '/history' => self::history(),
            $method === 'GET' && preg_match('#^/vms/(\d+)$#', $path, $m) === 1 => self::vmShow((int) $m[1]),
            $method === 'GET' && $path === '/reserve' => self::reserveForm(),
            $method === 'POST' && $path === '/reserve' => self::reserveSubmit(),
            $method === 'POST' && $path === '/release' => self::releaseSubmit(),
            $method === 'GET' && $path === '/extend' => self::extendForm(),
            $method === 'POST' && $path === '/extend' => self::extendSubmit(),
            $method === 'GET' && $path === '/mine' => self::mine(),
            $method === 'GET' && $path === '/watch' => self::watchForm(),
            $method === 'POST' && $path === '/watch' => self::watchSubmit(),
            $method === 'GET' && $path === '/password' => self::passwordForm(),
            $method === 'POST' && $path === '/password' => self::passwordSubmit(),
            $method === 'GET' && $path === '/admin/users' => self::adminUsers(),
            $method === 'POST' && $path === '/admin/users' => self::adminUsersCreate(),
            $method === 'POST' && $path === '/admin/users/update' => self::adminUsersUpdate(),
            $method === 'GET' && $path === '/admin/vms' => self::adminVms(),
            $method === 'POST' && $path === '/admin/vms' => self::adminVmsCreate(),
            $method === 'POST' && $path === '/admin/vms/update' => self::adminVmsUpdate(),
            default => Http::notFound(),
        };
    }

    private static function enforceIdle(): void
    {
        global $config;
        if (empty($_SESSION['user_id'])) {
            return;
        }
        $minutes = max(5, (int) ($config['app']['session_idle_minutes'] ?? 15));
        $last = (int) ($_SESSION['_last_activity'] ?? time());
        if (time() - $last > $minutes * 60) {
            Auth::logout();
            session_start();
            flash('error', 'Your session timed out. Sign in again.');
            redirect('/login');
        }
        $_SESSION['_last_activity'] = time();
    }

    private static function fail(Throwable $e): string
    {
        return Security::publicMessage($e);
    }

    private static function loginForm(): void
    {
        if (Auth::user()) {
            redirect('/');
        }
        render('login', ['title' => 'Sign in']);
    }

    private static function loginSubmit(): void
    {
        $username = post_string('username');
        $password = $_POST['password'] ?? '';
        $password = is_string($password) ? $password : '';
        if ($username === '' || $password === '') {
            flash('error', 'Enter your username and password.');
            redirect('/login');
        }
        $result = Auth::login($username, $password);
        if ($result === 'ok') {
            redirect('/');
        }
        if ($result === 'throttled') {
            flash('error', 'Too many failed sign-in attempts. Try again in a few minutes.');
        } else {
            flash('error', 'Invalid username or password, or the account is disabled.');
        }
        redirect('/login');
    }

    private static function logout(): void
    {
        Auth::logout();
        session_start();
        flash('success', 'You are signed out.');
        redirect('/login');
    }

    private static function dashboard(): void
    {
        $user = require_login();
        $tab = is_string($_GET['tab'] ?? null) ? $_GET['tab'] : 'vms';
        if ($tab !== 'calendar') {
            $tab = 'vms';
        }

        $now = Timezone::nowApp();
        $monthParam = is_string($_GET['month'] ?? null) ? $_GET['month'] : '';
        if (preg_match('/^(\d{4})-(\d{2})$/', $monthParam, $m) === 1) {
            $year = (int) $m[1];
            $month = (int) $m[2];
        } else {
            $year = (int) $now->format('Y');
            $month = (int) $now->format('n');
        }

        render('dashboard', [
            'title' => $tab === 'calendar' ? 'Calendar' : 'Demo VMs',
            'user' => $user,
            'tab' => $tab,
            'vms' => ReservationService::dashboard(),
            'calendar' => ReservationService::monthCalendar($year, $month),
            'nowEt' => $now->format('l, M j, Y g:i A') . ' ET',
        ]);
    }

    private static function history(): void
    {
        $user = require_login();
        $vmId = query_int('vm', 0);
        render('history', [
            'title' => 'Reservation history',
            'user' => $user,
            'rows' => ReservationService::history($vmId > 0 ? $vmId : null),
            'vms' => ReservationService::listVms(false),
            'vmId' => $vmId,
        ]);
    }

    private static function vmShow(int $id): void
    {
        $user = require_login();
        $vm = ReservationService::findVm($id);
        if ($vm === null) {
            Http::notFound();
        }
        $now = Timezone::utcSql(Timezone::nowUtc());
        render('vm', [
            'title' => $vm['name'],
            'user' => $user,
            'vm' => ReservationService::decorateVm($vm, $now),
            'history' => ReservationService::history($id, 50),
        ]);
    }

    private static function reserveForm(): void
    {
        $user = require_can_reserve();
        $vmId = query_int('vm', 0);
        $vms = ReservationService::listVms(true);
        if ($vms === []) {
            flash('error', 'No Demo VMs are available. Ask an admin to add one.');
            redirect('/');
        }
        $selected = $vmId > 0 ? $vmId : (int) $vms[0]['id'];
        $startDefault = Timezone::nowApp()->format('Y-m-d\TH:i');
        render('reserve', [
            'title' => 'Reserve a Demo VM',
            'user' => $user,
            'vms' => $vms,
            'selectedVm' => $selected,
            'startDefault' => $startDefault,
        ]);
    }

    private static function reserveSubmit(): void
    {
        $user = require_can_reserve();
        $vmId = post_int('vm_id');
        $startLocal = post_string('start_local');
        $mode = post_string('mode', 'duration');
        $amount = post_int('amount', 1);
        $unit = post_string('unit', 'days');
        $endLocal = post_string('end_local');
        $notes = post_string('notes');
        if (strlen($notes) > 500) {
            flash('error', 'Notes must be 500 characters or fewer.');
            redirect('/reserve?vm=' . $vmId);
        }
        try {
            $start = Timezone::parseAppLocal($startLocal);
            if ($mode === 'end') {
                $end = Timezone::parseAppLocal($endLocal);
                $label = ReservationService::humanDuration($start, $end);
            } else {
                if ($amount < 1) {
                    throw new InvalidArgumentException('Duration must be at least 1.');
                }
                $end = add_duration($start, $amount, $unit);
                $label = duration_label($amount, $unit);
            }
            ReservationService::create($vmId, (int) $user['id'], $start, $end, $label, $notes);
            flash('success', 'Reservation created. Watchers were notified by email.');
            redirect('/vms/' . $vmId);
        } catch (Throwable $e) {
            flash('error', self::fail($e));
            redirect('/reserve?vm=' . $vmId);
        }
    }

    private static function releaseSubmit(): void
    {
        $user = require_can_reserve();
        $id = post_int('reservation_id');
        try {
            ReservationService::release($id, $user);
            flash('success', 'Reservation released. Watchers were notified by email.');
        } catch (Throwable $e) {
            flash('error', self::fail($e));
        }
        $back = post_string('back', '/');
        if (!str_starts_with($back, '/') || str_starts_with($back, '//')) {
            $back = '/';
        }
        redirect($back);
    }

    private static function extendForm(): void
    {
        $user = require_can_reserve();
        $id = query_int('id', 0);
        $row = ReservationService::findReservation($id);
        if ($row === null || $row['status'] !== 'active') {
            flash('error', 'Reservation not found.');
            redirect('/');
        }
        if (!ReservationService::canMutate($row, $user)) {
            flash('error', 'You cannot extend this reservation.');
            redirect('/');
        }
        $end = Timezone::fromUtcString($row['end_utc']);
        render('extend', [
            'title' => 'Extend reservation',
            'user' => $user,
            'row' => $row,
            'endDefault' => Timezone::toLocalInput($end),
        ]);
    }

    private static function extendSubmit(): void
    {
        $user = require_can_reserve();
        $id = post_int('reservation_id');
        $endLocal = post_string('end_local');
        try {
            $end = Timezone::parseAppLocal($endLocal);
            ReservationService::extend($id, $end, $user);
            flash('success', 'Reservation extended.');
            $row = ReservationService::findReservation($id);
            redirect($row ? '/vms/' . (int) $row['vm_id'] : '/');
        } catch (Throwable $e) {
            flash('error', self::fail($e));
            redirect('/extend?id=' . $id);
        }
    }

    private static function mine(): void
    {
        $user = require_login();
        render('mine', [
            'title' => 'My reservations',
            'user' => $user,
            'rows' => ReservationService::forUser((int) $user['id']),
        ]);
    }

    private static function watchForm(): void
    {
        $user = require_login();
        render('watch', [
            'title' => 'Watch list',
            'user' => $user,
            'global' => WatchService::isGlobal((int) $user['id']),
            'watched' => WatchService::vmIds((int) $user['id']),
            'vms' => ReservationService::listVms(false),
        ]);
    }

    private static function watchSubmit(): void
    {
        $user = require_login();
        $global = isset($_POST['global']) && $_POST['global'] === '1';
        $vmIds = $_POST['vm_ids'] ?? [];
        if (!is_array($vmIds)) {
            $vmIds = [];
        }
        $vmIds = array_map('intval', $vmIds);
        try {
            WatchService::save((int) $user['id'], $global, $vmIds);
            flash('success', 'Watch list saved. You will get email when matching reservations are created, expire, or are released early.');
        } catch (Throwable $e) {
            flash('error', self::fail($e));
        }
        redirect('/watch');
    }

    private static function passwordForm(): void
    {
        $user = require_login();
        render('password', ['title' => 'Change password', 'user' => $user]);
    }

    private static function passwordSubmit(): void
    {
        $user = require_login();
        $current = is_string($_POST['current'] ?? null) ? $_POST['current'] : '';
        $next = is_string($_POST['next'] ?? null) ? $_POST['next'] : '';
        $confirm = is_string($_POST['confirm'] ?? null) ? $_POST['confirm'] : '';
        if ($next !== $confirm) {
            flash('error', 'New password and confirmation do not match.');
            redirect('/password');
        }
        try {
            Auth::changePassword((int) $user['id'], $current, $next);
            flash('success', 'Password updated.');
            redirect('/');
        } catch (Throwable $e) {
            flash('error', self::fail($e));
            redirect('/password');
        }
    }

    private static function adminUsers(): void
    {
        $user = require_admin();
        render('admin_users', [
            'title' => 'Users',
            'user' => $user,
            'users' => Auth::all(),
        ]);
    }

    private static function adminUsersCreate(): void
    {
        require_admin();
        try {
            Auth::createUser(
                post_string('username'),
                post_string('email'),
                is_string($_POST['password'] ?? null) ? $_POST['password'] : '',
                post_string('role', 'standard')
            );
            flash('success', 'User created.');
        } catch (Throwable $e) {
            flash('error', self::fail($e));
        }
        redirect('/admin/users');
    }

    private static function adminUsersUpdate(): void
    {
        require_admin();
        $id = post_int('id');
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        try {
            Auth::updateUser(
                $id,
                post_string('email'),
                post_string('role'),
                post_string('active', '0') === '1',
                $password !== '' ? $password : null
            );
            flash('success', 'User updated.');
        } catch (Throwable $e) {
            flash('error', self::fail($e));
        }
        redirect('/admin/users');
    }

    private static function adminVms(): void
    {
        $user = require_admin();
        render('admin_vms', [
            'title' => 'Demo VMs',
            'user' => $user,
            'vms' => ReservationService::listVms(false),
        ]);
    }

    private static function adminVmsCreate(): void
    {
        require_admin();
        try {
            ReservationService::createVm(
                post_string('name'),
                post_string('description'),
                post_string('ip_hostname')
            );
            flash('success', 'Demo VM added.');
        } catch (Throwable $e) {
            flash('error', self::fail($e));
        }
        redirect('/admin/vms');
    }

    private static function adminVmsUpdate(): void
    {
        require_admin();
        try {
            ReservationService::updateVm(
                post_int('id'),
                post_string('name'),
                post_string('description'),
                post_string('ip_hostname'),
                post_string('active', '0') === '1'
            );
            flash('success', 'Demo VM updated.');
        } catch (Throwable $e) {
            flash('error', self::fail($e));
        }
        redirect('/admin/vms');
    }
}
