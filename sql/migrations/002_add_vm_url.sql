-- DemoTracker migration 002
-- Adds URL on Demo VMs. Safe on an existing database. Run once:
--   sudo mysql demo_reservation < /opt/websites/demotracker/sql/migrations/002_add_vm_url.sql

ALTER TABLE demo_vms
  ADD COLUMN url VARCHAR(500) NULL AFTER ip_hostname;
