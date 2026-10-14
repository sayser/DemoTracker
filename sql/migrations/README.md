# Schema migrations

Apply numbered files in order against the existing `demo_reservation` database. Do not re-run `sql/schema.sql` on a live database.

```bash
sudo mysql demo_reservation < /opt/websites/demotracker/sql/migrations/001_noop_duration_is_app_config.sql
```

This release does not change tables. Duration (days / weeks / months, max 6 months) is enforced in PHP. Existing reservation rows stay valid.
