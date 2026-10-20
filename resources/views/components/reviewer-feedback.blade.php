@error('staffAction')<div class="intake-error" role="alert">{{ $message }}</div>@enderror
<div data-reviewer-recovery hidden class="intake-error" role="alert" tabindex="-1">
    <div><strong>Result not confirmed</strong><p data-reviewer-message></p><p>Keep this tab open and copy any unsaved instructions or remarks before refreshing. Check the case status and history before repeating an action. If history is unavailable to you, ask an authorized reviewer to check before trying again.</p><button type="button" data-reviewer-reload>Refresh and check saved state</button></div>
</div>
@script
<script>
    const root = $wire.$el;
    let uncertain = false;
    const recover = (message, denied = false) => {
        uncertain = true;
        root.querySelectorAll('[data-finding-confirmed]').forEach(element => element.hidden = true);
        const panel = root.querySelector('[data-reviewer-recovery]');
        panel.hidden = false;
        panel.querySelector('[data-reviewer-message]').textContent = message;
        root.querySelectorAll('.intake-form button, .staff-queue-actions button').forEach(button => button.disabled = true);
        if (denied) root.querySelectorAll('.intake-panel, .intake-page-heading, .intake-history').forEach(element => element.hidden = true);
        panel.focus();
    };
    $wire.$interceptRequest(({onFailure, onError, onRedirect}) => {
        onFailure(() => recover('The connection was interrupted. A write may already have completed. It has not been retried.'));
        onError(({response, preventDefault}) => {
            preventDefault();
            recover([401,403,404].includes(response.status) ? 'Access has changed. Reopen your assigned queue after checking your account.' : response.status === 419 ? 'Your session expired. Sign in again before reopening this case.' : 'The service could not confirm the result. No automatic retry was made.', [401,403,404].includes(response.status));
        });
        onRedirect(({url, preventDefault}) => {
            if (new URL(url, location.origin).pathname.endsWith('/login')) {
                preventDefault(); recover('Your session expired. Sign in in another tab before reopening the case.');
            }
        });
    });
    root.querySelector('[data-reviewer-reload]').addEventListener('click', () => location.reload());
    root.addEventListener('submit', event => { if (uncertain) {event.preventDefault(); event.stopImmediatePropagation();} }, true);
</script>
@endscript
