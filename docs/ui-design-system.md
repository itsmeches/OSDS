# OSDS UI design system

**Status:** Provisional foundation, pending approved CHED brand standards
**Applies to:** Filament staff workspace and future Blade/Livewire interfaces

## Principles

1. Use plain language and organize screens around tasks.
2. Give each page one purpose and one emphasized primary action.
3. Preserve security in policies and domain services; navigation visibility is presentation only.
4. Prefer Filament, Blade, Livewire, and Tailwind components before custom controls.
5. Keep technical IDs and hashes available in secondary support details.

## Tokens

Shared CSS custom properties live in `resources/css/theme/tokens.css`. The Filament theme and public stylesheet both consume them. They define provisional navy/blue accents, neutral surfaces, text, borders, focus color, radii, and reduced-motion behavior.

Both light and dark token sets are supported. The default follows the user's operating-system preference, and the Filament user menu provides Light, Dark, and System choices. The navy sidebar remains the stable OSDS identity surface in both modes; canvas, content surfaces, text, borders, and controls change together.

Replace these tokens centrally when CHED supplies approved brand colors or typography. Do not duplicate brand colors in individual views.

## Page and navigation conventions

- Use a plain-language title and a one-sentence description.
- Put the main next action in the page header.
- Group navigation by user task. Hide low-level resources when a task workspace covers them.
- Show only implemented capabilities allowed by explicit permissions.
- Provide predictable Back or parent-workspace navigation.

## Actions

| Type | Examples | Convention |
| --- | --- | --- |
| Primary | Save draft, Submit for review, Approve, Publish | One prominent action for the current step |
| Secondary | Preview, Back, Create new draft | Lower visual emphasis |
| Destructive | Request changes, Retire publication | Red/danger color, consequence text, confirmation, and reason where required |

State-changing actions must call the existing domain service. Consequential actions explain what will happen and what remains unchanged.

## Status and time

Use `App\Support\QuestionnairePresentation` for questionnaire lifecycle labels, colors, and icons. Pair status color with visible text and an icon. Use human labels such as **In review** and **Published to review sandbox**.

Display operational timestamps in Asia/Manila using `M j, Y g:i A`. Keep exact references available in technical details.

## Forms

- Label required information clearly and retain semantic required attributes.
- Use helper text for unfamiliar or consequential fields.
- Use **Save draft** for editable questionnaire configuration.
- Group long content into collapsible, clearly named items.
- Show validation near the affected input and use plain-language action-boundary errors.
- Warn users before navigating away with unsaved changes.
- Stable keys remain visible because conditional logic depends on them, but labels describe their internal purpose.

## Applicant portal pattern (Phase 3B design contract)

The future SCHOOL portal should use an application-focused shell rather than the staff administration navigation. The approved direction is a calm, light workspace with a compact organization sidebar, a visible application-progress rail, and one large form surface.

- Keep **My applications**, **Start application**, **Drafts**, and account/organization access task based.
- Show numbered steps with a short description and a visible current step.
- Keep **Back**, **Save as draft**, and **Next step** in predictable positions.
- Put guidance beside the workflow rather than mixing it into questionnaire fields.
- Preserve the same tokens, focus behavior, validation language, and light/dark support used by staff screens.
- On narrow screens, place progress above the form and keep the next action visible without horizontal scrolling.

This is a reusable layout specification only. Phase 3A contains no applicant route, saved response, evidence link, or submission behavior.

## Staff workspace pattern

The staff workspace uses a persistent navy identity sidebar, a restrained top bar, clear page headings, compact toolbars, and bordered data surfaces. Operational lists should resemble a professional register: search and filters above the table, readable column density, subdued row separators, visible status badges, and an obvious next action.

Questionnaire authoring uses a left workflow rail for **Questions**, **Documents**, **Preview**, and **Review**, with the current program content on the right. The rail becomes a two-column then single-column section navigator at smaller widths. Tables and workflow cards use the same surface, border, radius, and shadow tokens in both themes.

## Tables and empty states

- Prioritize program/name, readable status, relevant date, and next action.
- Put hashes and low-level identifiers in optional technical columns or details.
- Default operational queues to the state that needs action.
- Empty states explain why nothing appears and where permitted work originates.
- Do not require horizontal scrolling to discover a primary action.

## Accessibility baseline

- Preserve native and Filament semantic controls.
- Maintain a visible focus indicator.
- Give icon actions accessible text or labels.
- Keep logical heading order and concise link/button names.
- Do not communicate state by color alone.
- Support 200% zoom, narrow reflow, keyboard use, and `prefers-reduced-motion`.
- Do not add unnecessary time limits or motion.

This foundation targets WCAG 2.2 AA practices. It is not a formal compliance claim.
