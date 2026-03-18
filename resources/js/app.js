import './bootstrap';

function initLiveClock() {
    const clockNodes = document.querySelectorAll('[data-vm-live-clock]');
    if (!clockNodes.length) return;

    const firstNode = clockNodes[0];
    const serverIso = firstNode?.getAttribute('data-server-time');
    const clientStartedAtMs = Date.now();
    let serverStartedAtMs = clientStartedAtMs;

    if (serverIso) {
        const parsed = new Date(serverIso);
        if (!Number.isNaN(parsed.getTime())) {
            serverStartedAtMs = parsed.getTime();
        }
    }

    const formatter = new Intl.DateTimeFormat('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'America/Sao_Paulo',
    });

    const render = () => {
        const elapsedMs = Date.now() - clientStartedAtMs;
        const now = new Date(serverStartedAtMs + elapsedMs);
        const value = formatter.format(now);
        clockNodes.forEach((node) => {
            node.textContent = value;
        });
    };

    render();
    window.setInterval(render, 1000);
    document.addEventListener('visibilitychange', render);
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

function initMetricsTracking() {
    const endpoint = '/api/metrics/event';

    const sendEvent = (payload) => {
        const body = JSON.stringify(payload);

        if (navigator.sendBeacon) {
            const blob = new Blob([body], { type: 'application/json' });
            navigator.sendBeacon(endpoint, blob);
            return;
        }

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body,
            keepalive: true,
        }).catch(() => {
            // Ignore metric transport failures by design.
        });
    };

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        const clickable = target.closest('[data-metrics-event]');
        if (!(clickable instanceof Element)) return;

        sendEvent({
            event_type: clickable.getAttribute('data-metrics-event') ?? 'category_click',
            category: clickable.getAttribute('data-source-section') ?? 'unknown',
            route: clickable.getAttribute('data-metrics-route') ?? window.location.pathname,
            meeting_name: clickable.getAttribute('data-meeting-name') ?? null,
            source_section: clickable.getAttribute('data-source-section') ?? null,
        });
    });
}

function initMeetingShare() {
    const shareButtons = document.querySelectorAll('[data-vm-share-button]');
    if (!shareButtons.length) return;

    let toast = document.querySelector('[data-vm-share-toast]');

    const ensureToast = () => {
        if (toast) return toast;

        toast = document.createElement('div');
        toast.className = 'vm-share-toast';
        toast.setAttribute('data-vm-share-toast', '');
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        document.body.appendChild(toast);

        return toast;
    };

    let toastTimeoutId = null;
    const showToast = (message, tone = 'success') => {
        const node = ensureToast();
        node.textContent = message;
        node.dataset.tone = tone;
        node.classList.add('is-visible');

        if (toastTimeoutId) window.clearTimeout(toastTimeoutId);
        toastTimeoutId = window.setTimeout(() => {
            node.classList.remove('is-visible');
        }, 2200);
    };

    const copyText = async (text) => {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const helper = document.createElement('textarea');
        helper.value = text;
        helper.setAttribute('readonly', '');
        helper.style.position = 'fixed';
        helper.style.opacity = '0';
        document.body.appendChild(helper);
        helper.focus();
        helper.select();

        const succeeded = document.execCommand('copy');
        document.body.removeChild(helper);

        if (!succeeded) {
            throw new Error('copy_failed');
        }
    };

    document.addEventListener('click', async (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        const button = target.closest('[data-vm-share-button]');
        if (!(button instanceof HTMLButtonElement)) return;

        const title = button.dataset.shareTitle?.trim() ?? '';
        const text = button.dataset.shareText?.trim() ?? '';
        const url = button.dataset.shareUrl?.trim() ?? '';

        const sharePayload = {
            title,
            text,
            ...(url ? { url } : {}),
        };

        try {
            if (navigator.share && (!navigator.canShare || navigator.canShare(sharePayload))) {
                await navigator.share(sharePayload);
                showToast('Compartilhado');
                return;
            }

            await copyText(text);
            showToast('Link copiado');
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            showToast('Nao foi possivel compartilhar', 'error');
        }
    });
}

initLiveClock();
initMobileMenu();
initMeetingShare();
initMetricsTracking();

