<div x-data="{ message: '', savedPath: location.pathname.replace(/\/create$/, '') }" x-cloak x-show="message"
     @staff-action-uncertain.window="if ($event.detail.component === $wire.$id) { message = $event.detail.message; savedPath = $event.detail.savedPath; }"
     class="notice" role="alert">
    <strong>Action not confirmed</strong>
    <p class="mt-2" x-text="message"></p>
    <a class="mt-2 inline-block underline" :href="location.origin + savedPath" target="_blank" rel="noopener noreferrer">Open saved record in a new tab</a>
</div>
