# Deploy DemoTracker on RHEL 8.10

Target layout:

```text
/opt/websites/demotracker/
  public/                 nginx document root
  src/
  views/
  config/config.php       copied from config.example.php (not in git)
  sql/schema.sql
  bin/
  deploy/
```

## 1. Packages

PHP 8.1+ is required (`php-fpm`, `php-mysqlnd`, `php-mbstring`). RHEL 8 AppStream PHP is often older — use a 8.1+ module or Remi.

```bash
sudo dnf install -y nginx mysql-server postfix git
sudo dnf install -y php-fpm php-cli php-mysqlnd php-mbstring
php -v
sudo systemctl enable --now nginx php-fpm mysqld postfix
```

If `mysqld` is not the service name, try `mariadb` or `mysql`.

Confirm the php-fpm listen socket in `/etc/php-fpm.d/www.conf` matches `deploy/nginx.conf` (usually `unix:/run/php-fpm/www.sock`).

## 2. Application files

```bash
sudo mkdir -p /opt/websites
sudo git clone https://github.com/sayser/DemoTracker.git /opt/websites/demotracker
sudo cp /opt/websites/demotracker/config/config.example.php /opt/websites/demotracker/config/config.php
sudo chown -R nginx:nginx /opt/websites/demotracker
sudo chmod 640 /opt/websites/demotracker/config/config.php
```

If `nginx` is not the php-fpm user, use that user instead (`apache` on some images).

## 3. MySQL schema and app user

MySQL is on the same host. Run as root:

```bash
sudo mysql < /opt/websites/demotracker/sql/schema.sql
```

That creates database `demo_reservation` and all tables.

```bash
sudo mysql -e "
CREATE USER IF NOT EXISTS 'demo_reserv'@'127.0.0.1' IDENTIFIED BY 'choose_a_strong_password';
CREATE USER IF NOT EXISTS 'demo_reserv'@'localhost' IDENTIFIED BY 'choose_a_strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON demo_reservation.* TO 'demo_reserv'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON demo_reservation.* TO 'demo_reserv'@'localhost';
FLUSH PRIVILEGES;
"
```

Edit `/opt/websites/demotracker/config/config.php`:

- `db.pass` — the password you set above
- `db.host` — `127.0.0.1`
- `app.base_url` — `http://YOUR_VM_IP` or your hostname
- `app.from_email` — a local address sendmail will accept
- `app.https` — `false` until TLS is on nginx, then `true`

## 4. First admin

```bash
sudo -u nginx php /opt/websites/demotracker/bin/create-admin.php admin you@example.com 'a-strong-password'
```

## 5. nginx

```bash
sudo cp /opt/websites/demotracker/deploy/nginx.conf /etc/nginx/conf.d/demotracker.conf
sudo nginx -t && sudo systemctl reload nginx
```

The sample vhost listens on port 80 and sets `root` to `/opt/websites/demotracker/public`. Change `server_name` from `_` to your hostname when you have one.

SELinux (if enforcing):

```bash
sudo semanage fcontext -a -t httpd_sys_content_t '/opt/websites/demotracker(/.*)?'
sudo restorecon -Rv /opt/websites/demotracker
sudo setsebool -P httpd_can_network_connect_db 1
sudo setsebool -P httpd_can_sendmail 1
```

If nginx cannot talk to the php-fpm socket, check `/var/log/nginx/error.log` and the pool `listen.owner`.

## 6. sendmail / postfix

The app uses PHP `mail()`. Test:

```bash
echo 'test' | mail -s 'DemoTracker mail test' you@example.com
```

## 7. Expiry cron

Every five minutes, due reservations become `expired` and watchers are emailed.

```bash
echo '*/5 * * * * nginx /usr/bin/php /opt/websites/demotracker/bin/expire-reservations.php >> /var/log/demotracker-expire.log 2>&1' | sudo tee /etc/cron.d/demotracker
sudo chmod 644 /etc/cron.d/demotracker
```

## 8. Firewall

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --reload
```

Open the site at `http://YOUR_VM_IP/`, sign in as the admin you created, add Demo VMs, then create a standard user.

Later updates:

```bash
cd /opt/websites/demotracker
sudo -u nginx git pull
```

`config/config.php` is gitignored, so pull will not overwrite credentials.
