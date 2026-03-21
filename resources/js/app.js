import './bootstrap';

function initLiveClock() {
    const clockNodes = document.querySelectorAll('[data-vm-live-clock]');
    if (!clockNodes.length) return;

    const firstNode = clockNodes[0];
    const serverIso = firstNode?.getAttribute('data-server-time');
    let clientStartedAtMs = Date.now();
    let serverStartedAtMs = clientStartedAtMs;

    const applyServerIso = (isoValue) => {
        if (!isoValue) return false;

        const parsed = new Date(isoValue);
        if (Number.isNaN(parsed.getTime())) return false;

        clientStartedAtMs = Date.now();
        serverStartedAtMs = parsed.getTime();

        clockNodes.forEach((node) => {
            node.setAttribute('data-server-time', parsed.toISOString());
        });

        return true;
    };

    applyServerIso(serverIso);

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

    const syncFromServer = async () => {
        try {
            const response = await fetch(`/api/server-time?ts=${Date.now()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
            });

            if (!response.ok) return;

            const payload = await response.json();
            if (applyServerIso(payload?.serverTime)) {
                render();
            }
        } catch {
            // Ignore network failures and keep local ticking fallback.
        }
    };

    render();
    window.setInterval(render, 1000);
    window.setInterval(() => {
        void syncFromServer();
    }, 60000);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            void syncFromServer();
            return;
        }

        render();
    });

    window.addEventListener('focus', () => {
        void syncFromServer();
    });

    window.addEventListener('pageshow', () => {
        void syncFromServer();
    });

    void syncFromServer();
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
            const queued = navigator.sendBeacon(endpoint, blob);
            if (queued) {
                return;
            }
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

    const trackFromEvent = (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        const clickable = target.closest('[data-metrics-event]');
        if (!(clickable instanceof Element)) return;

        // Avoid duplicate sends when pointerdown + click fire for the same interaction.
        const nowMs = Date.now();
        const lastSentMs = Number(clickable.getAttribute('data-metrics-last-sent-at') ?? '0');
        if (Number.isFinite(lastSentMs) && nowMs - lastSentMs < 700) {
            return;
        }
        clickable.setAttribute('data-metrics-last-sent-at', String(nowMs));

        const eventType = clickable.getAttribute('data-metrics-event') ?? 'category_click';
        const meetingRowId = clickable.getAttribute('data-metrics-meeting-row-id') ?? null;
        const meetingSignature = clickable.getAttribute('data-metrics-meeting-signature') ?? null;

        if (eventType === 'category_click' && !meetingRowId) {
            return;
        }

        sendEvent({
            event_type: eventType,
            category: clickable.getAttribute('data-source-section') ?? 'unknown',
            route: clickable.getAttribute('data-metrics-route') ?? window.location.pathname,
            meeting_name: clickable.getAttribute('data-meeting-name') ?? null,
            meeting_row_id: meetingRowId,
            meeting_signature: meetingSignature,
            source_section: clickable.getAttribute('data-source-section') ?? null,
        });
    };

    document.addEventListener('pointerdown', trackFromEvent, true);
    document.addEventListener('click', trackFromEvent, true);
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

