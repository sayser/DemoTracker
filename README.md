# DemoTracker

Internal **Demo Reservation** site: reserve Demo VMs, see who has them, and email a watch list when a reservation is created, expires, or is released early.

Stack: PHP 8.1+, nginx, php-fpm, MySQL on localhost, sendmail. Times are Eastern (`America/New_York`).

`config/config.php` is in this repository with `ENTER_SECURE_PASSWORD` placeholders. Replace those values on the server before the site will start.

Deploy on RHEL 8.10 to `/opt/websites/demotracker` using [deploy/README.md](deploy/README.md). MySQL schema is [sql/schema.sql](sql/schema.sql) (tables only). Create the MySQL application user by hand as documented there.
