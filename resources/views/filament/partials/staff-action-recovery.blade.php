@include('filament.partials.staff-action-recovery-notice')
@script
<script>
    const component = $wire.__instance;
    const explain = message => {
        let savedPath = location.pathname.replace(/\/create$/, '');
        if (location.pathname === '/admin/users/create' && $wire.data?.account_role === 'SCHOOL' && @js(auth()->user()?->can('institution_representatives.assign') ?? false)) {
            savedPath = '/admin/institution-representatives';
        }
        window.dispatchEvent(new CustomEvent('staff-action-uncertain', { detail: { component: component.id, message, savedPath } }));
    };
    const offRequest = $wire.$interceptRequest(({ onFailure, onRedirect, onError }) => {
        onFailure(() => explain('The connection was interrupted. Keep this tab open and inspect the saved record before repeating this action. No action is replayed automatically.'));
        onRedirect(({ url, preventDefault }) => {
            if (new URL(url, location.origin).pathname.endsWith('/login')) {
                preventDefault();
                explain('Your session has ended. Keep local work in this tab while you sign in and reopen the saved record in another tab.');
            }
        });
        onError(({ response, preventDefault }) => {
            preventDefault();
            if (response.status === 419) explain('Your session has ended. Sign in in another tab and inspect the saved record before continuing.');
            else if ([401, 403, 404].includes(response.status)) explain('This action is no longer available. Your access or the record status may have changed. Open the saved record before continuing.');
            else explain('The service could not confirm this action. Keep local work in this tab and inspect the saved record before trying again.');
        });
    });
    component.addCleanup(offRequest);
</script>
@endscript
