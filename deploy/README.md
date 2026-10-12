# Deploy DemoTracker on RHEL 8.10

Target layout:

```text
/opt/websites/demotracker/
  public/                 nginx document root (only this directory is web-visible)
  src/
  views/
  config/config.php       in git; replace ENTER_SECURE_PASSWORD before use
  sql/schema.sql          tables only — no MySQL users
  bin/
  deploy/
```

The application will **refuse to start** until `config/config.php` uses a real MySQL password (not `ENTER_SECURE_PASSWORD`).

## 1. Packages

PHP 8.1+ is required (`php-fpm`, `php-mysqlnd`, `php-mbstring`).

```bash
sudo dnf install -y nginx mysql-server postfix git
sudo dnf install -y php-fpm php-cli php-mysqlnd php-mbstring
php -v
sudo systemctl enable --now nginx php-fpm mysqld postfix
```

If `mysqld` is not the service name, try `mariadb` or `mysql`. Confirm php-fpm listens on `unix:/run/php-fpm/www.sock` in `/etc/php-fpm.d/www.conf`.

Recommended php-fpm settings (`/etc/php.ini` or the pool):

```ini
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
allow_url_include = Off
cgi.fix_pathinfo = 0
session.cookie_httponly = 1
session.use_strict_mode = 1
```

## 2. Application files

```bash
sudo mkdir -p /opt/websites
sudo git clone https://github.com/sayser/DemoTracker.git /opt/websites/demotracker
sudo chown -R nginx:nginx /opt/websites/demotracker
sudo chmod 640 /opt/websites/demotracker/config/config.php
```

If php-fpm does not run as `nginx`, use that user instead.

## 3. MySQL: schema, then the application user

MySQL stays on this host (`127.0.0.1`). Load **schema only** first. This file creates the `demo_reservation` database and tables. It does **not** create MySQL logins.

```bash
sudo mysql < /opt/websites/demotracker/sql/schema.sql
```

Create the application database user yourself. Use a long unique password (this is the value for `ENTER_SECURE_PASSWORD`). Limit the account to localhost and grant table data rights only (no GRANT OPTION, no SUPER, no FILE):

```bash
sudo mysql
```

```sql
CREATE USER 'demo_reserv'@'127.0.0.1' IDENTIFIED BY 'ENTER_SECURE_PASSWORD';
CREATE USER 'demo_reserv'@'localhost' IDENTIFIED BY 'ENTER_SECURE_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE ON demo_reservation.* TO 'demo_reserv'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON demo_reservation.* TO 'demo_reserv'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Replace `ENTER_SECURE_PASSWORD` in both SQL and in `/opt/websites/demotracker/config/config.php`:

- `db.pass` — same password as the MySQL user
- `db.host` — must stay `127.0.0.1` or `localhost`
- `app.base_url` — `https://your-hostname` (or `http://YOUR_VM_IP` until TLS is on)
- `app.from_email` — a local address sendmail will accept
- `app.https` — `true` when nginx terminates TLS; `false` only for initial HTTP testing
- `app.trust_proxy` — leave `false` unless a trusted reverse proxy sets `X-Forwarded-Proto`

Do not grant `CREATE`, `DROP`, `ALTER`, or `PROCESS` to `demo_reserv`. Bind MySQL to localhost in `/etc/my.cnf` (`bind-address = 127.0.0.1`).

## 4. First site admin (application user, not MySQL)

Passwords must be 12–128 characters with uppercase, lowercase, and a number.

```bash
sudo -u nginx php /opt/websites/demotracker/bin/create-admin.php admin you@example.com 'YourAdminPass1'
```

## 5. nginx

```bash
sudo cp /opt/websites/demotracker/deploy/nginx.conf /etc/nginx/conf.d/demotracker.conf
sudo nginx -t && sudo systemctl reload nginx
```

Document root is `/opt/websites/demotracker/public`. PHP sources under `src/`, `config/`, and `sql/` are outside the docroot.

SELinux (if enforcing):

```bash
sudo semanage fcontext -a -t httpd_sys_content_t '/opt/websites/demotracker(/.*)?'
sudo restorecon -Rv /opt/websites/demotracker
sudo setsebool -P httpd_can_network_connect_db 1
sudo setsebool -P httpd_can_sendmail 1
```

## 6. sendmail / postfix

```bash
echo 'test' | mail -s 'DemoTracker mail test' you@example.com
```

## 7. Expiry cron

```bash
echo '*/5 * * * * nginx /usr/bin/php /opt/websites/demotracker/bin/expire-reservations.php >> /var/log/demotracker-expire.log 2>&1' | sudo tee /etc/cron.d/demotracker
sudo chmod 644 /etc/cron.d/demotracker
```

## 8. Firewall

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --reload
```

Open `http://YOUR_VM_IP/`, sign in as the admin from step 4, add Demo VMs, then create standard users.

Updates:

```bash
cd /opt/websites/demotracker
sudo -u nginx git pull
```

After `git pull`, confirm `config/config.php` still has your real DB password if the tracked file changed.
