# Gate B2 — UI personnel continuation

**27 October 2026. BLOCKED: one authorized continuation created the office and two personnel drafts, then stopped on a reviewer-phase browser timeout. Review/publication NOT RUN. A prior-log preservation exception is separately FAIL.** B3/B4 remain NOT AUTHORIZED; Batch G remains CONDITIONAL CLOSE.

## Selector correction and static evidence

Changed only the public verification driver `scripts/verify-uiux-b2-personnel.mjs`. It now decodes mounted Livewire snapshots, selects by exact `memo.name` **`App\Filament\Pages\DemonstrationDocuments`**, requires exactly one match, validates its data shape and retains the original revision/key equality assertion. No DOM-order fallback or revision substitution remains.

Static checks against the retained trace selected the correct directory component with revision 25 and null office key, despite the earlier Topbar and Sidebar components. Separate checks covered zero matches, duplicate matches and malformed JSON; the caller rejects non-unique results. JavaScript syntax and whitespace checks passed. The existing dynamic same-origin Livewire endpoint validation, action filter and external-request blocking remain in place.

Only the driver changed as an existing public tool. To avoid reusing fixed inspection filenames, private snapshots of the existing bootstrap, inspector and router relocated root/evidence paths. Their original public files and guards were not edited. PHP syntax passed for these private copies. Their HTTP identity-log isolation did not work fully, as disclosed below.

## Fresh preflight and identity

Before browser authentication, the fixed read-only preflight passed against the actual database. The new evidence root is:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-ui-personnel-continuation/`

| Preflight | Actual result |
| --- | --- |
| Database | **PASS:** `pgsql / 127.0.0.1:55432 / osds_ui_browser_test` |
| Foundation | **PASS:** 18 exact synthetic profiles, 154 direct pivots, 2 regions, 3 institutions, 3 representations, 6 eligibility grants, 12 regional assignments and 20 reviewer assignments |
| Protected roots / B0 projections | **PASS:** exact original projections and fixed COR/ACSA roots; publication 128 intact |
| Original personnel | **PASS:** original 16 published mappings unchanged |
| Directory | **PASS:** freshly observed latest revision **25**, complete existing map; no fictional primary entry |
| New personnel slots | **PASS:** no primary/probe versions occupied the intended slots |

The application server bound only loopback port **8044**. Authentication used real forms and private credentials, without impersonation or injected sessions. Configured-target checks and actual identity verification ran before Laravel bootstrap and write-capable connections. No forbidden database was accessed.

There are **113 new identity observations**, all PASS: 12 in the continuation log and 101 unexpectedly appended to the earlier setup log. Every observation names `osds_ui_browser_test` at `127.0.0.1:55432`; zero mismatches. The HTTP-associated observations begin at **10:32:30 UTC**; the final post-stop observation is **10:35:32 UTC**. The append-routing exception does not change the observed target, but prevents a clean prior-log preservation PASS.

## One controlled browser continuation

Executed once:

```powershell
node scripts/verify-uiux-b2-personnel.mjs
```

Using the portable Node binary and Chrome/Playwright. Private browser evidence is under `browser-attempt-01/` within the new continuation root. No further browser execution or submitted-mutation retry occurred.

| UI step | Actual outcome |
| --- | --- |
| Real `osds` login | **PASS** |
| Deterministic directory component selection | **PASS:** exactly one matching page; `expectedDirectory=25`, `officeKey=null` |
| One fictional primary office save | **PASS:** new append-only directory revision **26** |
| COR personnel draft | **PASS:** version record **47**, module **1**, primary region **391**, `draft`, lock version **1** |
| ACSA personnel draft | **PASS:** version record **48**, module **2**, primary region **391**, `draft`, lock version **1** |
| Reviewer authentication/navigation | **BLOCKED / not verified complete:** timeout in `page.waitForLoadState` during the reviewer phase |
| Review / publication buttons | **NOT RUN**; no such action recorded or corresponding history exists |

Fictional office values saved through the existing form:

- Label: `uiux_20261027_a01 Fictional primary office`
- Director: `Fictional OSDS Primary Director`
- Designation: `Synthetic demonstration director`

Every prior office entry remains unchanged in revision 26. Exactly one revision was added. Both new personnel drafts reference that revision and its new office key, snapshot the same fictional office, and target the primary region. The probe region remains unmapped. No old mapping was withdrawn or reused.

## Timeout and driver limitations

The corrected page selector is no longer the blocker. The final browser error is:

```text
page.waitForLoadState: Timeout 30000ms exceeded.
```

The driver recorded its successful author steps and both drafts, but did not record a successful reviewer login. The timeout occurred during the reviewer phase's load-state waiting. The available evidence does not establish whether it occurred before or after acceptance of reviewer credentials, so completed reviewer authentication is not claimed.

The retained result also shows the request filter repeatedly blocked framework-internal **`__dispatch`** calls and one **`__lazyLoad`** call. These were outside its narrow action allowlist. The resulting network activity is a verification-infrastructure concern associated with the failure; the exact event payload/target and causal contribution to this timeout require further review. No application authorization defect is established.

The filter accumulated these errors while the authorized office/draft operations completed; the main flow then stopped on the load-state timeout. It did not fail immediately on the first blocked framework call. A follow-up should review legitimate internal calls and controlled fail-fast behavior rather than globally permitting arbitrary dispatch or merely increasing timeouts. No such filter correction was implemented here.

No office or personnel mutation was automatically replayed. Committed revision 26 and drafts 47/48 were retained, not deleted, repaired or recreated.

## Post-stop verification and actual lifecycle evidence

Stopped the task-owned server, then ran the existing read-only draft-state inspection through a private evidence-path copy. It completed **PASS** and recorded `post-stop-drafts.json`. Foundation permissions, relationship rows, protected configurations and application/request/document counts remain unchanged.

| Artifact | Confirmed state / evidence |
| --- | --- |
| Directory revision **26** | Exactly one new revision, all 25 earlier entries preserved plus the one fictional office |
| Directory audit | **8759**, `demonstration_document.directory_updated`, synthetic `osds` actor |
| COR version **47** | **draft**, lock version **1**, history **237** `version_created`, audit **8760** |
| ACSA version **48** | **draft**, lock version **1**, history **238** `version_created`, audit **8761** |
| Review events | **0** for these versions |
| Publication events | **0** for these versions |
| Original directory history / published personnel / six roots | **Unchanged** |
| New applicant workflows, decisions, requests, documents or permission changes | **None observed** |

Actual actor IDs, office key, bindings, full new-record snapshots and audit relationships remain private in the inspection records. No completed setup or publication is inferred from successful draft creation.

## Preservation and prior-log exception

Recorded HEAD **`f7625a9de909a991013db6ce43c68bee65402ce0`**, dirty state and **26,876 existing file hashes** before the driver change.

| Preservation | Actual outcome |
| --- | --- |
| Existing files | **26,874 unchanged; 2 changed; zero missing** |
| Authorized changed file | `scripts/verify-uiux-b2-personnel.mjs` |
| Unexpected changed prior evidence | Earlier `gate-b2-ui-personnel/identities.jsonl` received **101 appended records** — **FAIL** against byte-identical preservation |
| Original PDFs | **73/73 unchanged**; all **81 current PDF-named artifacts** unchanged |
| Historical diagnostic JSON | **10/10 unchanged** |
| Previous reports, other attempt evidence, foundation ledger/credentials, runtime and Batch G artifacts | **Unchanged** |
| Existing Laravel logs / Blade caches | **Unchanged**, not cleared or restored |
| Known missing temporary fake-upload metadata | Prior exception retained; not reconstructed |

The earlier identity log's **original 61-record byte prefix is intact**: its prefix SHA-256 exactly matches the pre-continuation whole-file baseline:

`2B033FCA6B8737F60F8210CE89CE14404275B88F7122AAE7D34FC0D389CA889B`

The file as a whole is not unchanged. The 101 appended records were copied separately into the continuation evidence; the existing file was not truncated, restored, renamed or rewritten to conceal the append. `identity-log-preservation-exception.json` records the expected/prefix hashes and counts. The cause of HTTP logging to the prior shared path, despite private path-relocated snapshots, remains unresolved. Correct and verify that routing before another execution.

Private new evidence includes selector/static results, fresh preflight, directory/draft and post-stop inspections, session files, screenshots, the retained author trace, browser results, private bootstrap/router copies, Git/baseline records and preservation diagnostics. Credentials were not printed or embedded in public artifacts. No prior failure result or report was overwritten.

## Readiness and recommended next authorization

**B2 remains incomplete and not ready for B3:** directory setup succeeded, but both personnel versions remain drafts. Full preservation acceptance also has the explicit prior-log exception above.

Recommend a bounded source/evidence review first: identify the required internal Livewire events and their components, diagnose the reviewer load-state wait, and fix attempt-local identity logging. Do not widen the filter indiscriminately or weaken the pre-save/component checks. Any subsequent authorization should resume **only the existing drafts 47 and 48** through real reviewer login, review and publication, with fresh read-only checks and new non-overwriting evidence. Do not replay office creation or create replacement drafts.

**STOP. No second browser continuation, configuration repair, review/publication, application workflow, issuance request, PDF, B3/B4, runtime change, commit, push or deployment occurred.**
