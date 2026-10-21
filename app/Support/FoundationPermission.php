<?php

namespace App\Support;

enum FoundationPermission: string
{
    case ManageDownloadableForms = 'forms.downloads.manage';
    case ViewDownloadableForms = 'forms.downloads.view';
    case AccessAdminPanel = 'platform.admin.access';
    case ViewUsers = 'users.view';
    case ManageUsers = 'users.manage';
    case CreateAdminAccounts = 'accounts.create.admin';
    case CreateOsdsAccounts = 'accounts.create.osds';
    case CreateChedroAccounts = 'accounts.create.chedro';
    case CreateSchoolAccounts = 'accounts.create.school';
    case VerifySchoolAccounts = 'accounts.verify.school';
    case ActivateAccounts = 'accounts.activate';
    case SuspendAccounts = 'accounts.suspend';
    case DeactivateAccounts = 'accounts.deactivate';
    case ReactivateAccounts = 'accounts.reactivate';
    case AssignRoles = 'roles.assign';
    case ViewRegions = 'regions.view';
    case ManageRegions = 'regions.manage';
    case AssignRegionScope = 'region_scopes.assign';
    case RevokeRegionScope = 'region_scopes.revoke';
    case AssignNationwideScope = 'nationwide_scopes.assign';
    case RevokeNationwideScope = 'nationwide_scopes.revoke';
    case ViewInstitutions = 'institutions.view';
    case ManageInstitutions = 'institutions.manage';
    case AssignInstitutionRepresentatives = 'institution_representatives.assign';
    case RevokeInstitutionRepresentatives = 'institution_representatives.revoke';
    case ViewAudit = 'audit.view';
    case ViewNationalAudit = 'audit.view.national';
    case ViewQuestionnaires = 'questionnaires.view';
    case ManageQuestionnaireDrafts = 'questionnaires.draft.manage';
    case SubmitQuestionnaireReview = 'questionnaires.review.submit';
    case ReviewQuestionnaires = 'questionnaires.review';
    case PublishQuestionnaireSandbox = 'questionnaires.publish.sandbox';
    case RetireQuestionnaires = 'questionnaires.retire';
    case PreviewQuestionnaireSandbox = 'questionnaires.preview.sandbox';
    case ViewQuestionnairePublications = 'questionnaires.publication.view';
    case PublishApplicantSandbox = 'applicant.sandbox.publish';
    case AccessApplicantPortal = 'applicant.portal.access';
    case CreateApplicationDrafts = 'applications.draft.create';
    case ViewApplicationDrafts = 'applications.draft.view';
    case UpdateApplicationDrafts = 'applications.draft.update';
    case ManageEvidenceReferences = 'evidence.references.manage';
    case OpenOwnEvidenceReferences = 'evidence.references.open.own';
    case CreateEvidenceReviewCopy = 'evidence.review.copy.create';
    case ManageEvidenceReviewerAssignments = 'evidence.reviewer.assignments.manage';
    case ViewEvidenceReviews = 'evidence.reviews.view';
    case OpenEvidenceReviews = 'evidence.reviews.open';
    case CreateEvidenceReviewFindings = 'evidence.review.findings.create';
    case ViewEvidenceReviewHistory = 'evidence.review.history.view';
    case SubmitApplications = 'applications.submit';
    case TrackOwnApplications = 'applications.track.own';
    case ViewOwnCorrections = 'applications.corrections.view.own';
    case ResubmitApplications = 'applications.resubmit';
    case ViewApplicationIntake = 'applications.intake.view';
    case ViewApplicationIntakeAnswers = 'applications.intake.answers.view';
    case OpenApplicationIntakeEvidence = 'applications.intake.evidence.open';
    case StartApplicationScreening = 'applications.screening.start';
    case RequestApplicationCorrections = 'applications.corrections.request';
    case ViewApplicationScreeningHistory = 'applications.screening.history.view';
    case ManageApplicationReviewerAssignments = 'applications.reviewer.assignments.manage';
}
