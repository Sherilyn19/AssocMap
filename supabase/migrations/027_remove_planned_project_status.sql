-- Convert project statuses without changing historical monitoring or activity statuses.
BEGIN;
SET LOCAL lock_timeout = '10s';
UPDATE public.projects SET status = 'ongoing' WHERE status = 'planned';
ALTER TABLE public.projects ALTER COLUMN status SET DEFAULT 'ongoing';
ALTER TABLE public.projects DROP CONSTRAINT projects_status_check;
ALTER TABLE public.projects ADD CONSTRAINT projects_status_check
    CHECK (status IN ('ongoing', 'completed', 'suspended', 'cancelled', 'terminated'));
COMMIT;
