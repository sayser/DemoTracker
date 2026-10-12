# DemoTracker

Internal **Demo Reservation** site: reserve Demo VMs, see who has them, and email a watch list when a reservation is created, expires, or is released early.

Stack: PHP 8.1+, nginx, php-fpm, MySQL on localhost, sendmail. Times are Eastern (`America/New_York`).

Deploy on RHEL 8.10 to `/opt/websites/demotracker` using [deploy/README.md](deploy/README.md) and [deploy/nginx.conf](deploy/nginx.conf).
