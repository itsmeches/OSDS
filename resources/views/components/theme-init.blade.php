<script>
    try {
        const preference = localStorage.getItem('osds-theme') || 'system';
        const dark = preference === 'dark' || (preference === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.dataset.theme = preference;
    } catch (_) {}
</script>
