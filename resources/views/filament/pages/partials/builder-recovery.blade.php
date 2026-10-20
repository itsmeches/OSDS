@script
<script>
    const component = $wire.__instance;
    const root = component.el.querySelector('.form-builder');
    const ui = () => Alpine.$data(root);
    let edits = 0;
    const input = event => {
        const model = event.target.getAttributeNames().find(name => name.startsWith('wire:model'));
        const path = model && event.target.getAttribute(model);
        if (!path || path.startsWith('previewAnswers') || path === 'previewTesting') return;
        edits++;
        ui().localDirty = true;
        ui().message = 'Unsaved changes';
    };
    root.addEventListener('input', input, true);
    const recovery = (message, denied = false) => {
        ui().pending = false;
        ui().uncertain = true;
        ui().denied = denied;
        ui().message = 'Action not confirmed';
        ui().recoveryText = message;
    };
    const offMessage = $wire.$interceptMessage(({ message, onSend, onSuccess }) => {
        const action = [...message.actions].find(action => ['save', 'submitForReview'].includes(action.name));
        let sentEdits;
        onSend(() => {
            sentEdits = edits;
            if (action) { ui().pending = true; ui().message = action.name === 'save' ? 'Saving…' : 'Sending…'; }
        });
        onSuccess(({ onMorphed }) => onMorphed(() => {
            ui().readOnly = root.dataset.builderReadonly === 'true';
            if (action) ui().pending = false;
            if ($wire.saveState === 'Not confirmed') {
                recovery('The service could not confirm the save. Keep this tab open and inspect the saved version before trying again.');
                return;
            }
            if (action?.name === 'save' && $wire.saveState === 'Saved' && sentEdits === edits) ui().localDirty = false;
            if ($wire.saveState === 'Conflict') ui().message = 'Updated elsewhere — your local work is still here';
            else if ($wire.saveState === 'Save failed') ui().message = 'Not saved — correct the highlighted fields';
            else if (ui().localDirty) ui().message = 'Unsaved changes';
        }));
    });
    const offRequest = $wire.$interceptRequest(({ onFailure, onRedirect, onError }) => {
        onFailure(() => recovery('The connection was interrupted. The result is uncertain. Keep this tab open, copy local work if needed, and inspect the saved version.'));
        onRedirect(({ url, preventDefault }) => {
            if (new URL(url, location.origin).pathname.endsWith('/login')) {
                preventDefault();
                recovery('Your session has ended. Keep this tab open. Copy local work before signing in and reopening the saved version.');
            }
        });
        onError(({ response, preventDefault }) => {
            preventDefault();
            if (response.status === 419 || response.redirected) recovery('Your session has ended. Copy local work before signing in and reopening the saved version.');
            else if ([401,403,404].includes(response.status)) recovery('Your access has changed. This workspace is blocked. Contact an authorized administrator.', true);
            else recovery('The service could not confirm this action. Your local work remains in this tab. Inspect the saved version before repeating the action.');
        });
    });
    component.addCleanup(() => { root.removeEventListener('input', input, true); offMessage(); offRequest(); });
</script>
@endscript
