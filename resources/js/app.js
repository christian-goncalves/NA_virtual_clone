import './bootstrap';

function initLiveClock() {
    const clockNodes = document.querySelectorAll('[data-vm-live-clock]');
    if (!clockNodes.length) return;

    let now = null;
    const firstNode = clockNodes[0];
    const serverIso = firstNode?.getAttribute('data-server-time');

    if (serverIso) {
        const parsed = new Date(serverIso);
        if (!Number.isNaN(parsed.getTime())) now = parsed;
    }

    if (!now) now = new Date();

    const formatter = new Intl.DateTimeFormat('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'America/Sao_Paulo',
    });

    const render = () => {
        const value = formatter.format(now);
        clockNodes.forEach((node) => {
            node.textContent = value;
        });
    };

    render();
    window.setInterval(() => {
        now = new Date(now.getTime() + 1000);
        render();
    }, 1000);
}

function initMobileMenu() {
    const toggle = document.querySelector('[data-vm-menu-toggle]');
    const menu = document.querySelector('[data-vm-menu]');
    if (!toggle || !menu) return;

    const closeMenu = () => {
        menu.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
    };

    const openMenu = () => {
        menu.classList.remove('hidden');
        toggle.setAttribute('aria-expanded', 'true');
    };

    toggle.addEventListener('click', () => {
        const isHidden = menu.classList.contains('hidden');
        if (isHidden) {
            openMenu();
            return;
        }
        closeMenu();
    });

    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeMenu();
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Node)) return;
        if (menu.contains(target) || toggle.contains(target)) return;
        closeMenu();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) closeMenu();
    });
}

initLiveClock();
initMobileMenu();
