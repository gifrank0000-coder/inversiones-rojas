// Toggle del sidebar con persistencia y manejo avanzado
document.addEventListener('DOMContentLoaded', function() {
    if (window.sidebarInitialized) return; // Evitar inicialización múltiple
    window.sidebarInitialized = true;

    // Sidebar toggle with persistence and single-responsibility
    try {
        var sidebar = document.querySelector('.admin-sidebar');
        var btn = document.getElementById('sidebarToggle');
        var STORAGE_KEY = 'ir_sidebar_collapsed';

        function setCollapsed(collapsed) {
            if (!sidebar) return;
            sidebar.classList.toggle('collapsed', !!collapsed);
        }

        // Restore previous state
        try { var collapsedState = localStorage.getItem(STORAGE_KEY); if (collapsedState === '1') setCollapsed(true); } catch(e) { /* ignore storage errors */ }

        if (btn && sidebar) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var nowCollapsed = !sidebar.classList.contains('collapsed'); // will be toggled
                sidebar.classList.toggle('collapsed');
                try { localStorage.setItem(STORAGE_KEY, sidebar.classList.contains('collapsed') ? '1' : '0'); } catch(err) {}
                // dispatch a custom event so other code can react
                sidebar.dispatchEvent(new CustomEvent('sidebar:toggled', { detail: { collapsed: sidebar.classList.contains('collapsed') } }));
            });
        }

        // Close open submenus when sidebar is collapsed
        var observer = new MutationObserver(function(mutations) {
            if (!sidebar) return;
            if (sidebar.classList.contains('collapsed')) {
                document.querySelectorAll('.admin-sidebar .menu-item.open').forEach(function(li){ li.classList.remove('open'); });
            }
        });
        if (sidebar) observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });

        // Submenu behavior: toggle on parent link click, allow real links to navigate
        document.querySelectorAll('.admin-sidebar .menu-item').forEach(function(li){
            var submenu = li.querySelector('.submenu');
            var link = li.querySelector('a');
            if (!submenu || !link) return;

            // mark for CSS
            link.setAttribute('data-toggle', 'submenu');
            link.setAttribute('aria-expanded', li.classList.contains('open') ? 'true' : 'false');

            link.addEventListener('click', function(e){
                var href = link.getAttribute('href') || '';
                var isHash = href === '#' || href.trim() === '' || href.indexOf('javascript:') === 0;
                if (!isHash) {
                    // real navigation, let it happen
                    return;
                }
                e.preventDefault();
                var wasOpen = li.classList.contains('open');
                if (wasOpen) {
                    li.classList.remove('open');
                    link.setAttribute('aria-expanded', 'false');
                } else {
                    li.classList.add('open');
                    link.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // Close open submenus on outside click
        document.addEventListener('click', function(ev){
            if (ev.target.closest('.admin-sidebar')) return; // click inside sidebar
            document.querySelectorAll('.admin-sidebar .menu-item.open').forEach(function(li){ li.classList.remove('open'); });
        });

        // Close open submenus with ESC
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { document.querySelectorAll('.admin-sidebar .menu-item.open').forEach(function(li){ li.classList.remove('open'); }); } });

    } catch(err) { console.error('sidebar/init error', err); }
});