<?php
/**
 * Inicializa el tema antes de pintar la página para evitar destellos.
 * El valor se guarda en localStorage; si no existe, se usa la preferencia del sistema.
 */
?>
<script>
    (function () {
        try {
            var key = 'dentahistoria-theme';
            var stored = localStorage.getItem(key);
            var theme = (stored === 'light' || stored === 'dark')
                ? stored
                : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.style.colorScheme = theme;
            var meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.setAttribute('content', theme === 'dark' ? '#101b2d' : '#1a5ab0');
        } catch (error) {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
</script>
