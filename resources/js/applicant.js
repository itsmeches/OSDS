// Applicant-only presentation and recovery. No answers or locators enter browser storage.
document.addEventListener('click', event => {
    const toggle = event.target.closest('[data-applicant-menu], [data-steps-toggle]');
    if (!toggle) return;
    const target = document.getElementById(toggle.getAttribute('aria-controls'));
    const expanded = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(expanded));
    target?.classList.toggle('is-expanded', expanded);
});
document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('[data-applicant-menu][aria-expanded="true"], [data-steps-toggle][aria-expanded="true"]').forEach(button => {
        button.click(); button.focus();
    });
});

const initializeStart = () => {
    const organization = document.querySelector('[data-organization-select]');
    const questionnaire = document.querySelector('[data-questionnaire-select]');
    if (!organization || !questionnaire) return;
    const choices = [...questionnaire.options].slice(1).map(option => option.cloneNode(true));
    const filter = () => {
        const prior = questionnaire.value;
        const modules = (organization.selectedOptions[0]?.dataset.modules || '').split(',');
        const available = choices.filter(option => modules.includes(option.dataset.module));
        questionnaire.replaceChildren(new Option(organization.value ? 'Select questionnaire' : 'Choose organization first', ''), ...available.map(option => option.cloneNode(true)));
        questionnaire.value = available.some(option => option.value === prior) ? prior : '';
        questionnaire.disabled = !organization.value || !available.length;
        document.querySelector('[data-questionnaire-availability]').textContent = !organization.value ? 'Choose an organization to see its available questionnaires.' : available.length ? `${available.length} questionnaire(s) available to this organization.` : 'No questionnaire is available for this organization. Contact the sandbox administrator; changing organizations does not grant eligibility.';
    };
    organization.addEventListener('change', filter);
    filter();
};
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeStart);
else initializeStart();

const initializeRecovery = () => {
    window.Livewire.hook('component.init', ({ component, cleanup }) => {
        const root = component.el;
        if (!root.matches('[data-applicant-editor]')) return;
        let dirty = false, edits = 0, uncertain = false, pending = false, retry = null;
        const status = text => {
            const label = root.querySelector('[data-client-save-state]');
            if (label) { label.hidden = !text; label.textContent = text; }
            root.dataset.localDirty = String(Boolean(text));
        };
        const recovery = (message, canRetry = true, denied = false) => {
            uncertain = true;
            const panel = root.querySelector('[data-recovery]');
            panel.hidden = false;
            panel.querySelector('[data-recovery-message]').textContent = message;
            panel.querySelector('[data-retry]').hidden = !canRetry;
            panel.querySelector('[data-copy-local]').hidden = denied;
            root.querySelector('[data-editor-workspace]').inert = true;
            if (denied) root.querySelector('[data-editor-workspace]').hidden = true;
            status('Save not confirmed');
        };
        const input = event => {
            if (!event.target.matches('input, textarea, select')) return;
            edits++;
            dirty = true;
            status(event.target.closest('.evidence-entry') ? 'Link not added yet' : 'Unsaved changes');
        };
        const beforeLeave = event => {
            if (!dirty && !uncertain && !pending) return;
            event.preventDefault(); event.returnValue = '';
        };
        root.addEventListener('input', input);
        window.addEventListener('beforeunload', beforeLeave);
        const mutations = ['save', 'addEvidence', 'replaceEvidence', 'removeEvidence', 'submitTestApplication', 'sendDocumentsForTestReview', 'supersedeTestReview'];
        let activeMessage = null;
        const orderModels = component.$wire.$interceptAction(({ action }) => {
            if (action.metadata.type !== 'model.live' || !activeMessage || action.isDeferred()) return;
            // Keep one reactive request in flight. Later inputs remain local and are
            // included when the queued action is dispatched, without a timing delay.
            action.defer();
            activeMessage.addInterceptor(({ onFinish }) => onFinish(() => action.fire()));
        });
        const afterRender = component.$wire.$interceptMessage(({ message, onSend, onSuccess, onFinish }) => {
            onSend(() => {
                // Livewire may consolidate changed answer keys into an object update.
                // Its reconciliation baseline must contain the dispatched JSON values,
                // not a reference that later input can mutate while the request runs.
                message.updates = JSON.parse(JSON.stringify(message.updates));
                activeMessage = message;
            });
            onFinish(() => { if (activeMessage === message) activeMessage = null; });
            const action = [...message.actions].find(action => mutations.includes(action.name));
            const sentEdits = edits;
            onSuccess(({ onMorphed }) => onMorphed(() => {
                if (uncertain) return;
                const saved = component.$wire.saveState === 'Saved';
                const hasLinkInput = Object.values(component.$wire.evidenceInputs || {}).some(value => String(value).trim());
                if (action && sentEdits === edits && saved && !hasLinkInput) dirty = false;
                if (component.$wire.saveState === 'Updated in another tab') {
                    dirty = true;
                    status('Updated in another tab — your input is still here');
                } else if (component.$wire.saveState === 'Save failed') status('Save failed — your input is still here');
                else status(dirty ? (saved && hasLinkInput ? 'Link not added yet' : 'Unsaved changes') : '');
            }));
        });
        const off = component.$wire.$interceptRequest(({ request, onSend, onSuccess, onFailure, onError, onRedirect }) => {
            const actions = [...request.messages].filter(message => message.component === component).flatMap(message => [...message.actions]);
            const action = actions.find(action => mutations.includes(action.name));
            onSend(() => { if (action) { pending = true; retry = { name: action.name, params: action.params || [] }; status(action.name === 'save' ? 'Saving…' : 'Working…'); } });
            onFailure(() => recovery('The connection was interrupted. We could not confirm the result. Keep this tab open and retry the same action before editing further.'));
            onRedirect(({ url, preventDefault }) => {
                const destination = new URL(url, location.origin);
                if (destination.origin === location.origin && destination.pathname.endsWith('/login')) {
                    preventDefault();
                    recovery('Your session has ended. Keep this tab open. Copy your unsaved answers before signing in and reopening the application.', false);
                }
            });
            onError(({ response, preventDefault }) => {
                preventDefault();
                if (response.status === 419 || response.redirected) recovery('Your session has ended. Keep this tab open. Copy your unsaved answers before signing in and reopening the application.', false);
                else if ([401, 403, 404].includes(response.status)) recovery('Your access to this application has changed. Editing is blocked. Check your account or contact CHEDRO.', false, true);
                else recovery('The service could not confirm the result. Your input remains in this tab. Retry the same action; do not start another application.');
            });
            onSuccess(() => {
                uncertain = false;
                pending = false;
                const panel = root.querySelector('[data-recovery]');
                if (panel) panel.hidden = true;
                root.querySelector('[data-editor-workspace]').inert = false;
            });
        });
        const click = event => {
            if (event.target.closest('[data-retry]')) {
                if (retry) component.$wire.$call(retry.name, ...retry.params);
                else component.$wire.$refresh();
            }
            if (event.target.closest('[data-copy-local]')) {
                const area = root.querySelector('[data-local-copy]');
                root.querySelector('[data-copy-label]').hidden = false;
                area.hidden = false;
                const labels = JSON.parse(root.dataset.questionLabels || '{}');
                area.value = Object.entries(component.$wire.answers || {}).map(([id, value]) => `${labels[id] || 'Question'}: ${Array.isArray(value) ? value.join(', ') : value ?? ''}`).join('\n\n');
                area.focus(); area.select();
            }
        };
        root.addEventListener('click', click);
        cleanup(() => { root.removeEventListener('input', input); root.removeEventListener('click', click); window.removeEventListener('beforeunload', beforeLeave); off(); afterRender(); orderModels(); });
    });
};
if (window.Livewire) initializeRecovery();
else document.addEventListener('livewire:init', initializeRecovery, { once: true });
