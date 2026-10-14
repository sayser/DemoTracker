-- DemoTracker migration 001
-- No table changes. Max reservation length is now 6 months in application code.
-- Safe to run on an existing database; it does nothing.

SELECT 'No schema change required for 6-month reservations.' AS info;
