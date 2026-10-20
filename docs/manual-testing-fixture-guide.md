# Synthetic test account and fixture guide

## Start or repair the isolated sandbox

From the repository root in PowerShell:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\Start-ApplicantSandbox.ps1
```

Use `-SetupOnly` to migrate and seed without starting the server. The script reads the existing protected fixture password from the local temporary credential file or `OSDS_FIXTURE_PASSWORD`. It never prints the password. To reset the local test password, replace that protected file or environment value and rerun setup; do not commit it.

## Accounts

All fixture accounts use the protected local fixture password.

| Role | Account identifier | Login | Organization/region | Expected use |
| --- | --- | --- | --- | --- |
| Super User | `super.user@example.invalid` | `/admin` on port 8001 | Synthetic South | Explicitly granted oversight functions only |
| Admin | `admin@example.invalid` | `/admin` on port 8001 | Synthetic South | Reviews, applicant publication, eligibility and reviewer assignments |
| OSDS | `osds@example.invalid` | `/admin` on port 8001 | Synthetic South | Questionnaire authoring and review submission |
| CHEDRO | `chedro@example.invalid` | `/admin` on port 8001 | Synthetic North | Assigned evidence reviews and application intake |
| SCHOOL | `school@example.invalid` | `/applicant/login` on port 8001 | Harborview Learning Institute, Synthetic North | Applicant drafts, evidence, submission, tracking, corrections |

SCHOOL must not use `/admin`. Staff must not use `/applicant/login`.

## Provisioned records

- Modules: Certificate of Registration and Authority to Conduct Sports Activities.
- Organization: Harborview Learning Institute (`SYN-001`), synthetic HEI in Synthetic North.
- Verified representation: SCHOOL → Harborview.
- Eligibility: Harborview → both modules, effective and unrevoked.
- Publications: one active applicant-sandbox manifest per module.
- CHEDRO assignments: Synthetic North plus both modules for application intake and evidence review.

## Safe recovery

1. Stop the port 8001 server.
2. Run the launcher with `-SetupOnly`.
3. Confirm it reports migrations complete and fixture seeding complete.
4. Restart the launcher normally.
5. Verify `/applicant/login`, `/admin`, and `/applicant/start`.

The setup is idempotent and does not delete existing applications. Use a newly created disposable database for destructive reset testing.
