# Schema migrations

Apply numbered files in order against the existing `demo_reservation` database. Do not re-run `sql/schema.sql` on a live database.

```bash
sudo mysql demo_reservation < /opt/websites/demotracker/sql/migrations/002_add_vm_url.sql
```

If `url` already exists, MySQL will error; that means the column is already there.
