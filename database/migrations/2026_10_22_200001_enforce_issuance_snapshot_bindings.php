<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_issuance_snapshot_binding() RETURNS trigger AS $$
            DECLARE s application_submissions%ROWTYPE; d issuance_definitions%ROWTYPE; director application_decision_stages%ROWTYPE; organization jsonb; address jsonb; evidence jsonb;
            BEGIN
                SELECT * INTO s FROM application_submissions WHERE id=NEW.submission_id;
                SELECT * INTO d FROM issuance_definitions WHERE id=NEW.definition_id;
                SELECT * INTO director FROM application_decision_stages WHERE id=NEW.decision_id;
                SELECT value INTO organization FROM application_submission_answers WHERE submission_id=s.id AND form_question_id=(d.definition->>'name_question_id')::bigint AND is_applicable;
                SELECT value INTO address FROM application_submission_answers WHERE submission_id=s.id AND form_question_id=(d.definition->>'address_question_id')::bigint AND is_applicable;
                SELECT COALESCE(jsonb_agg(i.evidence_reference_revision_id ORDER BY i.id),'[]'::jsonb) INTO evidence FROM application_submission_evidence_items i JOIN application_submission_requirements r ON r.id=i.submission_requirement_id WHERE r.submission_id=s.id;
                IF (NEW.identity_snapshot->>'application_id')::bigint IS DISTINCT FROM NEW.application_id
                    OR (NEW.identity_snapshot->>'submission_id')::bigint IS DISTINCT FROM s.id
                    OR (NEW.identity_snapshot->>'decision_id')::bigint IS DISTINCT FROM NEW.decision_id
                    OR (NEW.identity_snapshot->>'institution_id')::bigint IS DISTINCT FROM s.institution_id
                    OR (NEW.identity_snapshot->>'region_id')::bigint IS DISTINCT FROM s.routing_region_id
                    OR (NEW.identity_snapshot->>'director_actor_id')::bigint IS DISTINCT FROM director.actor_id
                    OR NEW.identity_snapshot->>'certificate_type' IS DISTINCT FROM NEW.certificate_type
                    OR NEW.identity_snapshot->>'submission_hash' IS DISTINCT FROM s.combined_hash
                    OR NEW.identity_snapshot->>'approved_institution_name' IS DISTINCT FROM s.organization_name_snapshot
                    OR jsonb_typeof(organization) IS DISTINCT FROM 'string' OR NULLIF(BTRIM(organization #>> '{}'),'') IS NULL
                    OR jsonb_typeof(address) IS DISTINCT FROM 'string' OR NULLIF(BTRIM(address #>> '{}'),'') IS NULL
                    OR NEW.identity_snapshot->'organization' IS DISTINCT FROM organization
                    OR NEW.identity_snapshot->'address' IS DISTINCT FROM address
                    OR NEW.identity_snapshot->'evidence_revision_ids' IS DISTINCT FROM evidence
                THEN RAISE EXCEPTION 'Approved issuance snapshot bindings must match immutable source records.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER issuance_snapshot_binding BEFORE INSERT ON issuance_requests FOR EACH ROW EXECUTE FUNCTION enforce_issuance_snapshot_binding();
        SQL);
    }

    public function down(): void
    {
        if (DB::table('issuance_requests')->exists()) {
            throw new RuntimeException('Preserve issuance snapshot safeguards; downgrade requires separate review.');
        }
        DB::statement('DROP TRIGGER issuance_snapshot_binding ON issuance_requests');
        DB::statement('DROP FUNCTION enforce_issuance_snapshot_binding()');
    }
};
