-- Public applicants have no staff account. Keep attribution explicit and preserve old records.
ALTER TABLE public.member_applications ALTER COLUMN submitted_by_user_id DROP NOT NULL;
ALTER TABLE public.member_applications ADD COLUMN submission_source varchar(30) NOT NULL DEFAULT 'association_account',
    ADD COLUMN reason_for_applying text, ADD COLUMN email varchar(255),
    ADD COLUMN public_location_id uuid REFERENCES public.gis_locations(id) ON DELETE SET NULL,
    ADD COLUMN public_location_title varchar(255);
ALTER TABLE public.members ADD COLUMN email varchar(255);
-- A public submission has no account actor; association submissions still require one.
ALTER TABLE public.member_applications ADD CONSTRAINT member_application_source_check CHECK (
    (submission_source = 'association_account' AND submitted_by_user_id IS NOT NULL AND reason_for_applying IS NULL)
    OR (submission_source = 'public_gis' AND submitted_by_user_id IS NULL AND reason_for_applying IS NOT NULL AND btrim(reason_for_applying) <> '')
);

-- Preserve account ownership checks while admitting the explicitly marked public source.
CREATE OR REPLACE FUNCTION public.validate_member_application_submitter()
RETURNS trigger LANGUAGE plpgsql SECURITY DEFINER SET search_path = public AS $$
BEGIN
    IF NEW.submission_source = 'public_gis' AND NEW.submitted_by_user_id IS NULL THEN
        RETURN NEW;
    END IF;
    IF NEW.submission_source <> 'association_account' OR NOT EXISTS (
        SELECT 1 FROM public.association_accounts aa
        WHERE aa.user_id = NEW.submitted_by_user_id AND aa.association_id = NEW.association_id AND aa.is_active = true
    ) THEN
        RAISE EXCEPTION 'Member application submitter is not the active account for this association.';
    END IF;
    RETURN NEW;
END;
$$;
DROP TRIGGER trg_validate_member_application_submitter ON public.member_applications;
CREATE TRIGGER trg_validate_member_application_submitter BEFORE INSERT OR UPDATE OF association_id, submitted_by_user_id, submission_source
ON public.member_applications FOR EACH ROW EXECUTE FUNCTION public.validate_member_application_submitter();
