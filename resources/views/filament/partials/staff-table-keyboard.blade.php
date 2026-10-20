@script
<script>
    // Native Filament tables can have no focusable row actions for a read-only
    // reviewer. Keep their horizontal scroll region keyboard reachable too.
    const component = $wire.__instance;
    const enableKeyboardScroll = area => {
        area.setAttribute('tabindex', '0');
        area.setAttribute('role', 'region');
        area.setAttribute('aria-label', 'Results table. Scroll horizontally for more columns.');
    };
    component.el.querySelectorAll('.fi-ta-content-ctn').forEach(enableKeyboardScroll);
    const offMorph = Livewire.hook('morph.updated', ({ el, component: updated }) => {
        if (updated.id !== component.id) return;
        if (el.matches('.fi-ta-content-ctn')) enableKeyboardScroll(el);
        el.querySelectorAll('.fi-ta-content-ctn').forEach(enableKeyboardScroll);
    });
    component.addCleanup(offMorph);
</script>
@endscript
