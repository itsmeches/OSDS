# Downloadable forms — Windows upload recovery

## Final ZIP transport correction — 22 October 2026

The subsequent user screenshot explicitly showed **“The archive field is required”** beside a selected filename. A filename in the native chooser did not mean Livewire held a completed temporary file. The earlier runtime and click changes did not resolve that reported missing-file handoff. A direct diagnostic against the real local signed temporary-upload endpoint accepted the identical 43,599-byte source ZIP (HTTP 200/valid paths), without logging into an account or publishing anything. This isolates a missing component-upload state, but does not establish every cause of the user's browser handoff failure.

The ZIP editor now uses a **normal CSRF-protected multipart POST**, submitting the ZIP and version label together to `/admin/downloadable-forms/import-zip`. It no longer binds the ZIP to Livewire or uses the temporary-upload endpoint. One explicit Import action triggers the upload, then redirects with a persistent success banner. Browser validation still requires both fields; the controller independently validates them and reuses the unchanged authorized, single-attempt atomic import service. Its runtime exception handler exposes only fixed guidance/support reference and logs exception class/reference. Validation preserves the version label and asks for file reselection; uncertain outcomes require catalogue inspection before any explicit repeat. No automatic mutation retry.

**24 DownloadableFormsTest tests / 216 assertions passed**, 16,265 ms, including the actual multipart route importing five originals with five audits, missing-field/CHEDRO denial and real audit insertion followed by failure with row/audit/byte rollback and redacted feedback. Existing service and legacy Livewire regression paths remain retained; the ZIP UI now exercises the new route. Browser normal Artisan server / osds_ui_browser_test confirms file-first selection, version entry, the separate single-file editor left open and exactly the normal multipart import request, with no temporary-upload request or page errors and a visible five-form success banner. [Final transport results](screenshots/multipart-zip-import/results.json). The first runner retained an obsolete Livewire-form selector and timed out before clicking; its [result](screenshots/multipart-zip-import/old-locator-attempt.json) and private log are preserved. Updating the selector changed no application assertion. Prior file-first artifacts remain separate.

Pint and whitespace checks passed. The temporary browser server was stopped; the existing local server remains running. No schema, permission, default-database catalogue mutation, new audit writer, blanket transaction, commit/push, full-suite rerun or new accessibility acceptance. The earlier fixes below are historical and are not sufficient explanations of this later screenshot. Refresh the page to obtain the new native multipart form, then select the ZIP again.

## Follow-up: enabled ZIP button with no apparent action

The user reported an enabled import button doing nothing even with a ZIP and version label. The ordinary file-first sequence succeeded in isolation; the exact original user failure was not independently reproduced. The import button now explicitly invokes `importArchive`, and its form defers required-field checks to the existing server validation. This avoids native HTML validation silently swallowing the click when Livewire holds a successful temporary upload but the native chooser has become empty. No upload, permission or service validation was removed.

Browser regression deliberately clears only the native chooser after a successful temporary upload, leaves the single-file editor open with empty required fields, enters the ZIP version label, then clicks Import. The five entries import successfully, no page errors. [Results](screenshots/zip-import-file-first/results.json). This is a controlled UI-state check, not proof that the user's browser entered that exact state. **21 DownloadableFormsTest tests / 183 assertions passed**, 11,954 ms, including new missing-file/version denials with no row/audit delta. Pint and whitespace checks passed. Isolated test server stopped; default database unchanged. A refresh is required to load the updated button markup. No commit/push or broader test claim.

**22 October 2026. Local development upload correction.** No change to permissions, accepted file types, publication/audit boundaries or applicant access.

## Findings and correction

The reported DOCX and ZIP stayed in Livewire's temporary-upload state. Recent local logs recorded attempts to return upload validation HTTP 422 followed by headers-already-sent errors. The running server was the portable PHP `artisan serve` process on port 8000.

Installed Laravel's ServeCommand filters environment variables when starting its reloading Windows child and omitted TEMP/TMP/TMPDIR. With TEMP/TMP removed, this PHP installation selected an unwritable user-profile directory for temporary files. AppServiceProvider now preserves those three variables for the Windows console development server. Vendor files and application persistence services are unchanged. A process regression checks the filtered child retains the same writable temporary directory as its parent.

Separately, the portable PHP configuration had no explicit upload settings and used defaults of **2 MB per upload / 8 MB POST**, despite the UI's 10 MB contract. Its php.ini now sets `upload_max_filesize = 10M` and `post_max_size = 12M`. A prior-config backup is private at `storage/app/private/php-upload-settings-before.ini`. This is an external local runtime setting, not shipped PHP configuration; other development machines must configure compatible limits themselves.

The verified existing OSDS Artisan parent/child on port 8000 was restarted after both changes, validating the Windows intermediate-launcher process chain before stopping it. Local `/admin/login` returned HTTP 200 afterward. No account, catalogue row, migration or default-database fixture was changed. Publication still requires an explicit successful manager action.

Both upload editors now show fixed visible guidance on native Livewire upload failure, retain entered metadata and require explicit file reselection. They do not replay Publish/Import automatically. The ZIP requires its **Imported version label**, e.g. October 2026; the user's ZIP screenshot showed that field empty.

## Executed checks

- **21 tests / 177 assertions passed**, 43,345 ms: WindowsServeUploadEnvironmentTest plus DownloadableFormsTest. PostgreSQL tests use osds_test; the Windows process probe itself does not access a database.
- Actual Chrome, **normal reloading Artisan server** on isolated port 8035 / osds_ui_browser_test: forced temporary-upload HTTP 422 shows safe guidance and retains title; original undertaking DOCX uploads and publishes; original ZIP uploads and imports five forms; a 3 MB sample PDF uploads and publishes beyond the former PHP limit. Three successful temporary-upload HTTP 200 responses, no page errors. [Results](screenshots/form-upload-recovery/results.json). The 3 MB PDF is a transport-size fixture, not a PDF-reader compatibility test.
- Browser fixture settings were explicitly preserved through the server's environment filter; no manual-database fixture or signed-in user session was used. The browser test server was stopped afterward. The user's local server remains running on port 8000.
- Touched PHP Pint and whitespace checks passed, with CRLF advisories. No complete-suite rerun, broad preservation journey, new accessibility pass or production build is claimed; no asset source changed.

The missing writable temporary directory is established by the installed launcher/filter and child-environment probe; the original user's network response body was not captured. The ordinary Artisan-server browser journey passes after correction. A previously stuck tab needs a refresh and explicit file selection. No automatic publication or import was performed in osds_dev, and uploaded files cannot be inferred from merely selecting a filename.

No commit/push, deployment, production change, official issuance, Batch H or Phase 4. Batch G's conditional-close status remains unchanged.
