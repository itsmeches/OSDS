---
name: osds-accessibility-acceptance
description: Verify OSDS navigation, dashboards and applicant flows for accessibility and responsive behavior while separating previews, authenticated checks and human acceptance.
---

Apply [OSDS Verification Gates](../osds-verification-gates/SKILL.md) as the shared safety contract, using the current user authorization.


Read the relevant Phase 1–3 plan/report and [pending accessibility remediation](../../../docs/osds-b4-accessibility-remediation-report.md) before defining acceptance. Preserve unresolved historical findings.

- Use real semantic controls, complete accessible names, heading/landmark structure, visible keyboard focus and non-color status labels. Recheck keyboard order, mobile navigation and focus after updates.
- Follow the approved viewport/theme matrix; include narrow reflow and text scaling where required. Root font scaling is not native zoom or screen-reader acceptance.
- Run default axe plus approved target-size checks without exclusions or relaxed thresholds. Retain violations and incomplete findings; distinguish transient diagnostic captures from standard acceptance evidence.
- Label component previews and permission simulations honestly. They do not establish authenticated Filament/Livewire integration, direct-route authorization or business readiness.
- Scope captures and exported DOM to synthetic authorized accounts; screenshots may contain private data. Browser MCPs do not bypass authentication or workflow authorization gates.

Avoid cosmetic changes that obscure pending failures. Report automated results, sampled manual keyboard checks, human screen-reader/native zoom and supervisor/first-time-user review as separate activities. Do not claim full WCAG or application acceptance from an axe pass.

## Evidence and completion

Required evidence: approved viewport/theme/scenario matrix, raw axe results including incomplete findings, keyboard observations and screenshot scope. Complete with sampled automated/manual outcomes and separate pending screen-reader, native-zoom and business acceptance.
