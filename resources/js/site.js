// Interactions and motion for the public website. Dependency-free so pages stay light.
// Pages are swapped by Livewire's navigation (no full reload), so page features are set
// up again after every swap by initPage().

const root = document.documentElement;
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
let observer = null;
let counterObserver = null;
let leafletPromise = null;

const setMenu = (open) => {
    root.classList.toggle('menu-open', open);
    document.querySelector('[data-menu-toggle]')?.setAttribute('aria-expanded', String(open));
};

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setMenu(false);
    }
});

document.addEventListener('click', (event) => {
    // Light / dark switch, remembered on this device
    if (event.target.closest('[data-theme-toggle]')) {
        const current = root.dataset.theme
            || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        const next = current === 'dark' ? 'light' : 'dark';

        root.dataset.theme = next;

        try {
            localStorage.setItem('ng-theme', next);
        } catch (error) {
            // Storage can be blocked; the switch still works for this visit.
        }

        return;
    }

    if (event.target.closest('[data-menu-toggle]')) {
        setMenu(! root.classList.contains('menu-open'));

        return;
    }

    if (event.target.closest('.mobile-menu a')) {
        setMenu(false);
    }

    // Track / Quick quote tabs in the hero
    const tab = event.target.closest('[data-tab]');

    if (tab) {
        const box = tab.closest('[data-tabs]');

        box.querySelectorAll('[data-tab]').forEach((button) => {
            const selected = button === tab;

            button.setAttribute('aria-selected', String(selected));
            button.tabIndex = selected ? 0 : -1;
            document.getElementById(button.dataset.tab).hidden = ! selected;
        });

        document.getElementById(tab.dataset.tab).querySelector('input, select')?.focus();

        return;
    }

    // Copy the tracking number
    const copy = event.target.closest('[data-copy-button]');

    if (copy) {
        navigator.clipboard?.writeText(copy.dataset.copyButton).then(() => {
            copy.textContent = 'Copied!';
            setTimeout(() => { copy.textContent = 'Copy'; }, 2000);
        });
    }
});

const reveal = () => {
    observer?.disconnect();

    const items = document.querySelectorAll('[data-reveal]');

    if (reduceMotion || ! ('IntersectionObserver' in window)) {
        items.forEach((item) => item.classList.add('is-visible'));

        return;
    }

    observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px' });

    items.forEach((item) => observer.observe(item));
};

// Numbers count up from zero when they scroll into view
const counters = () => {
    counterObserver?.disconnect();

    const items = document.querySelectorAll('[data-count]');

    if (reduceMotion || ! ('IntersectionObserver' in window)) {
        return;
    }

    const format = new Intl.NumberFormat('en-US');

    counterObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            counterObserver.unobserve(entry.target);

            const target = Number(entry.target.dataset.count) || 0;
            const started = performance.now();
            const duration = 1600;

            const tick = (now) => {
                const progress = Math.min(1, (now - started) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);

                entry.target.textContent = format.format(Math.round(target * eased));

                if (progress < 1) {
                    requestAnimationFrame(tick);
                }
            };

            requestAnimationFrame(tick);
        });
    }, { threshold: .4 });

    items.forEach((item) => {
        item.textContent = '0';
        counterObserver.observe(item);
    });
};

// Leaflet (map library) loads from a CDN only on pages that show a map.
const loadLeaflet = () => {
    if (window.L) {
        return Promise.resolve(window.L);
    }

    leafletPromise ??= new Promise((resolve, reject) => {
        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
        css.integrity = 'sha384-c6Rcwz4e4CITMbu/NBmnNS8yN2sC3cUElMEMfP3vqqKFp7GOYaaBBCqmaWBjmkjb';
        css.crossOrigin = 'anonymous';
        document.head.append(css);

        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
        script.integrity = 'sha384-NElt3Op+9NBMCYaef5HxeJmU4Xeard/Lku8ek6hoPTvYkQPh3zLIrJP7KiRocsxO';
        script.crossOrigin = 'anonymous';
        script.onload = () => resolve(window.L);
        script.onerror = reject;
        document.head.append(script);
    });

    return leafletPromise;
};

const escapeHtml = (text) => String(text ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
})[character]);

const maps = () => {
    document.querySelectorAll('[data-map]').forEach((element) => {
        if (element.dataset.ready) {
            return;
        }

        element.dataset.ready = '1';

        const pins = JSON.parse(element.dataset.map || '[]');

        loadLeaflet().then((L) => {
            const map = L.map(element, { scrollWheelZoom: false, worldCopyJump: true }).setView([20, 10], 2);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            }).addTo(map);

            const bounds = [];

            pins.forEach((pin) => {
                const icon = L.divIcon({
                    className: '',
                    html: `<span class="map-pin${pin.hub ? ' map-pin--hub' : ''}"></span>`,
                    iconSize: pin.hub ? [22, 22] : [16, 16],
                    iconAnchor: pin.hub ? [11, 11] : [8, 8],
                });

                L.marker([pin.lat, pin.lng], { icon, title: pin.name })
                    .addTo(map)
                    .bindPopup(`<strong>${escapeHtml(pin.name)}</strong>${escapeHtml(pin.kind)}<br>${escapeHtml(pin.address)}, ${escapeHtml(pin.city)}${pin.phone ? `<br>${escapeHtml(pin.phone)}` : ''}`);

                bounds.push([pin.lat, pin.lng]);
            });

            if (bounds.length) {
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: 5 });
            }

            new ResizeObserver(() => map.invalidateSize()).observe(element);
        }).catch(() => {
            element.innerHTML = '<p style="padding:24px">The map could not load. See the list of locations instead.</p>';
        });
    });
};

const initPage = () => {
    setMenu(false);
    reveal();
    counters();
    maps();
};

document.addEventListener('DOMContentLoaded', initPage);
document.addEventListener('livewire:navigated', initPage);
