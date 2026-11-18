-- Activity photos use the existing private media lifecycle with their own R2 prefix.
SET LOCAL lock_timeout = '5s';
ALTER TABLE public.media_photos ADD COLUMN program_activity_id UUID REFERENCES public.program_activity_records(id) ON DELETE RESTRICT;
ALTER TABLE public.media_photos DROP CONSTRAINT media_exactly_one_parent;
ALTER TABLE public.media_photos ADD CONSTRAINT media_exactly_one_parent CHECK (
    (CASE WHEN project_id IS NULL THEN 0 ELSE 1 END + CASE WHEN gis_location_id IS NULL THEN 0 ELSE 1 END
     + CASE WHEN monitoring_visit_report_id IS NULL THEN 0 ELSE 1 END + CASE WHEN monitoring_material_id IS NULL THEN 0 ELSE 1 END
     + CASE WHEN program_activity_id IS NULL THEN 0 ELSE 1 END) = 1);
ALTER TABLE public.media_photos DROP CONSTRAINT media_photos_object_prefix_check;
ALTER TABLE public.media_photos ALTER COLUMN object_prefix TYPE VARCHAR(60);
ALTER TABLE public.media_photos ADD CONSTRAINT media_photos_object_prefix_check CHECK (
    (program_activity_id IS NULL AND length(object_prefix) = 42 AND object_prefix LIKE 'media/%') OR
    (program_activity_id IS NOT NULL AND length(object_prefix) = 55 AND object_prefix LIKE 'program-activities/%'));
CREATE INDEX media_program_activity_parent ON public.media_photos(program_activity_id, state, display_order, id);
ALTER TABLE public.media_photos DROP CONSTRAINT media_photos_display_order_check;
ALTER TABLE public.media_photos ADD CONSTRAINT media_photos_display_order_check CHECK (display_order BETWEEN 0 AND 19);
