/*
 * PanierMalin front-end: progressive enhancement over server-rendered pages.
 * No dependency, no build step.
 *
 * Storage rules:
 *  - nothing is persisted until the visitor accepts the consent banner;
 *  - after consent: preferences in the "pm_prefs" cookie (read by the server to
 *    personalise the home page) and the shopping list in localStorage;
 *  - without consent the list only lives in sessionStorage.
 * Cookie names must match App\Preferences\PreferencesResolver.
 */
(() => {
    'use strict';

    const CONSENT = 'pm_consent';
    const PREFS = 'pm_prefs';
    const LIST = 'pm_list';
    const MAX_AGE = 60 * 60 * 24 * 180; // 6 months, CNIL recommendation
    const MAX_INTERESTS = 8;

    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const body = document.body;
    document.documentElement.classList.add('js');

    // ------------------------------------------------------------------ cookies
    const cookies = {
        get(name) {
            const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
            return match ? decodeURIComponent(match[1]) : null;
        },
        set(name, value, maxAge = MAX_AGE) {
            const secure = location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Lax${secure}`;
        },
        remove(name) {
            document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax`;
        },
    };

    const consent = {
        value: () => cookies.get(CONSENT), // '1' | '0' | null (not answered)
        granted: () => cookies.get(CONSENT) === '1',
    };

    // -------------------------------------------------------------- preferences
    const prefs = {
        read() {
            try {
                return JSON.parse(cookies.get(PREFS) || '{}') || {};
            } catch {
                return {};
            }
        },
        update(mutator) {
            if (!consent.granted()) return;
            const p = this.read();
            p.v = 1;
            mutator(p);
            cookies.set(PREFS, JSON.stringify(p));
        },
        bumpInterest(category, weight = 1) {
            if (!category) return;
            this.update((p) => {
                const interests = p.int || {};
                interests[category] = (interests[category] || 0) + weight;
                // Keep the cookie small: only the strongest interests survive.
                p.int = Object.fromEntries(Object.entries(interests).sort((a, b) => b[1] - a[1]).slice(0, MAX_INTERESTS));
            });
        },
    };

    // ------------------------------------------------------------ shopping list
    const listStorage = () => {
        try {
            return consent.granted() ? localStorage : sessionStorage;
        } catch {
            return null;
        }
    };

    const list = {
        items() {
            try {
                const items = JSON.parse(listStorage()?.getItem(LIST) || '[]');
                return Array.isArray(items) ? items : [];
            } catch {
                return [];
            }
        },
        save(items) {
            try {
                listStorage()?.setItem(LIST, JSON.stringify(items));
            } catch { /* storage full or blocked: the list simply isn't kept */ }
            refreshListUi();
        },
        has(key) {
            return this.items().some((i) => i.k === key);
        },
        toggle(key, name) {
            const items = this.items();
            const exists = items.some((i) => i.k === key);
            this.save(exists ? items.filter((i) => i.k !== key) : [...items, { k: key, n: name || key }]);
            return !exists;
        },
        remove(key) {
            this.save(this.items().filter((i) => i.k !== key));
        },
        clear() {
            this.save([]);
        },
    };

    function refreshListUi() {
        const items = list.items();
        const keys = new Set(items.map((i) => i.k));
        $$('[data-list-count]').forEach((el) => {
            el.textContent = String(items.length);
            el.hidden = items.length === 0;
        });
        $$('[data-add]').forEach((btn) => {
            const on = keys.has(btn.dataset.add);
            btn.classList.toggle('is-active', on);
            btn.setAttribute('aria-pressed', String(on));
            if (btn.classList.contains('btn--icon')) {
                btn.firstElementChild.textContent = on ? '✓' : '＋';
            } else {
                btn.textContent = on ? '✓ Dans ma liste' : '＋ Ajouter à ma liste';
            }
        });
    }

    // -------------------------------------------------------------------- toast
    let toastTimer;
    function toast(message) {
        const el = $('[data-toast]');
        if (!el) return;
        el.textContent = message;
        el.hidden = false;
        requestAnimationFrame(() => el.classList.add('is-visible'));
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            el.classList.remove('is-visible');
            setTimeout(() => { el.hidden = true; }, 250);
        }, 2400);
    }

    // ------------------------------------------------------------ consent banner
    const banner = $('[data-consent]');
    if (banner && consent.value() === null) banner.hidden = false;

    banner?.addEventListener('click', (event) => {
        const choice = event.target.closest('[data-consent-choice]');
        if (!choice) return;
        const accepted = choice.dataset.consentChoice === 'accept';
        const sessionItems = list.items(); // read before switching storage
        cookies.set(CONSENT, accepted ? '1' : '0');
        if (accepted) {
            list.save(sessionItems);
            prefs.update((p) => {
                p.loc = body.dataset.location;
            });
            toast('✓ Préférences enregistrées pour vos prochaines visites');
        } else {
            cookies.remove(PREFS);
            try { localStorage.removeItem(LIST); } catch { /* ignore */ }
        }
        banner.hidden = true;
        $('[data-list-session-note]')?.toggleAttribute('hidden', accepted);
    });

    // --------------------------------------------------------- global listeners
    document.addEventListener('click', (event) => {
        const target = event.target;

        const addButton = target.closest('[data-add]');
        if (addButton) {
            const added = list.toggle(addButton.dataset.add, addButton.dataset.name);
            toast(added ? '✓ Ajouté à votre liste' : 'Retiré de votre liste');
            if (added) prefs.bumpInterest(addButton.dataset.cat, 2);
            return;
        }

        const tracked = target.closest('[data-cat-track]');
        if (tracked) prefs.bumpInterest(tracked.dataset.catTrack, 1);

        if (target.closest('[data-consent-open]')) {
            event.preventDefault();
            if (banner) banner.hidden = false;
            return;
        }

        if (target.closest('[data-forget]')) {
            event.preventDefault();
            cookies.remove(PREFS);
            cookies.remove(CONSENT);
            try {
                localStorage.removeItem(LIST);
                sessionStorage.removeItem(LIST);
            } catch { /* ignore */ }
            toast('Toutes vos données ont été effacées');
            setTimeout(() => location.assign('/'), 900);
        }
    });

    // ------------------------------------------------------------ location picker
    const locationSelect = $('[data-location-select]');
    locationSelect?.addEventListener('change', () => {
        prefs.update((p) => {
            p.loc = locationSelect.value;
        });
        const url = new URL(location.href);
        url.searchParams.set('loc', locationSelect.value);
        url.searchParams.delete('page');
        location.assign(url);
    });

    // Interest signal from a product page view.
    const viewedCategory = $('[data-track-category]')?.dataset.trackCategory;
    if (viewedCategory) prefs.bumpInterest(viewedCategory, 1);

    // ------------------------------------------------------------- autocomplete
    const searchInput = $('#q');
    const suggestBox = $('#suggest');
    if (searchInput && suggestBox) {
        let timer;
        let controller;
        let active = -1;

        const options = () => $$('[role="option"]', suggestBox);
        const close = () => {
            suggestBox.hidden = true;
            searchInput.setAttribute('aria-expanded', 'false');
            active = -1;
        };
        const highlight = (index) => {
            const opts = options();
            if (!opts.length) return;
            active = (index + opts.length) % opts.length;
            opts.forEach((o, i) => o.classList.toggle('is-active', i === active));
            opts[active].scrollIntoView({ block: 'nearest' });
        };

        searchInput.addEventListener('input', () => {
            clearTimeout(timer);
            const q = searchInput.value.trim();
            if (q.length < 2) return close();
            timer = setTimeout(async () => {
                controller?.abort();
                controller = new AbortController();
                try {
                    const params = new URLSearchParams({ q, loc: body.dataset.location || '' });
                    const res = await fetch('/api/suggest?' + params, { signal: controller.signal });
                    if (!res.ok) return;
                    const data = await res.json();
                    suggestBox.innerHTML = data.html;
                    suggestBox.hidden = false;
                    searchInput.setAttribute('aria-expanded', 'true');
                    active = -1;
                } catch { /* aborted or offline: keep the plain search form */ }
            }, 180);
        });

        searchInput.addEventListener('keydown', (event) => {
            if (suggestBox.hidden) return;
            if (event.key === 'ArrowDown') { event.preventDefault(); highlight(active + 1); }
            else if (event.key === 'ArrowUp') { event.preventDefault(); highlight(active - 1); }
            else if (event.key === 'Escape') { close(); }
            else if (event.key === 'Enter' && active >= 0) { event.preventDefault(); options()[active].click(); }
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-search]')) close();
        });
    }

    // ---------------------------------------------------------- explorer filters
    const filters = $('[data-filters]');
    if (filters) {
        const grid = $('[data-results]');
        const totalEl = $('[data-total]');
        const loadMore = $('[data-load-more]');
        const empty = $('[data-empty]');
        const backdrop = $('.filters-backdrop');
        const toggle = $('[data-filters-open]');
        let controller;
        let page = 1;
        let timer;

        const params = (forPage = 1) => {
            const sp = new URLSearchParams();
            for (const [key, value] of new FormData(filters)) {
                if (value !== '') sp.append(key, value);
            }
            if (forPage > 1) sp.set('page', String(forPage));
            return sp;
        };

        const updateFacets = (facets) => {
            const apply = (prefix, counts) => {
                $$(`[data-facet^="${prefix}:"]`, filters).forEach((el) => {
                    const id = el.dataset.facet.slice(prefix.length + 1);
                    const count = counts[id] || 0;
                    el.textContent = String(count);
                    el.closest('.check')?.classList.toggle('is-zero', count === 0);
                });
            };
            apply('store', facets.stores || {});
            apply('cat', facets.categories || {});
        };

        const activeFilterCount = () => {
            const data = new FormData(filters);
            let count = data.getAll('stores[]').length + data.getAll('cat[]').length;
            if (data.get('remise') && data.get('remise') !== '0') count++;
            if (data.get('prixmax')) count++;
            if (!data.get('loyal')) count++;
            return count;
        };

        const rememberFilters = () => {
            const data = new FormData(filters);
            prefs.update((p) => {
                p.stores = data.getAll('stores[]');
                p.loyal = data.get('loyal') ? 1 : 0;
            });
            data.getAll('cat[]').forEach((cat) => prefs.bumpInterest(cat, 1));
        };

        const load = async (append = false) => {
            controller?.abort();
            controller = new AbortController();
            const sp = params(append ? page + 1 : 1);
            grid.setAttribute('aria-busy', 'true');
            try {
                const res = await fetch('/api/promotions?' + sp, { signal: controller.signal, headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error(String(res.status));
                const data = await res.json();
                page = data.page;
                if (append) grid.insertAdjacentHTML('beforeend', data.html);
                else grid.innerHTML = data.html;
                totalEl.textContent = data.totalLabel;
                empty.hidden = data.total > 0;
                loadMore.hidden = !data.hasMore;
                loadMore.href = data.nextUrl;
                updateFacets(data.facets);
                const count = activeFilterCount();
                const badge = $('[data-filters-count]');
                if (badge) { badge.textContent = String(count); badge.hidden = count === 0; }
                sp.delete('page');
                history.replaceState(null, '', '/promos?' + sp);
                refreshListUi();
                if (!append) rememberFilters();
            } catch (error) {
                if (error.name !== 'AbortError') toast('Oups, impossible de charger les promos. Réessayez.');
            } finally {
                grid.removeAttribute('aria-busy');
            }
        };

        const isInstant = (el) => el.type === 'checkbox' || el.type === 'radio' || el.tagName === 'SELECT';
        document.addEventListener('input', (event) => {
            if (event.target.form !== filters) return;
            clearTimeout(timer);
            timer = setTimeout(load, isInstant(event.target) ? 0 : 350);
        });

        const openDrawer = (open) => {
            filters.classList.toggle('is-open', open);
            if (backdrop) backdrop.hidden = !open;
            toggle?.setAttribute('aria-expanded', String(open));
            document.documentElement.style.overflow = open ? 'hidden' : '';
        };
        toggle?.addEventListener('click', () => openDrawer(true));
        $$('[data-filters-close]').forEach((el) => el.addEventListener('click', () => openDrawer(false)));
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') openDrawer(false); });

        filters.addEventListener('submit', (event) => {
            event.preventDefault();
            clearTimeout(timer);
            load();
            openDrawer(false);
        });

        $('[data-filters-reset]')?.addEventListener('click', (event) => {
            event.preventDefault();
            filters.reset();
            $$('input[type="checkbox"]', filters).forEach((c) => { c.checked = c.name === 'loyal'; });
            $$('input[type="search"], input[type="number"]', filters).forEach((i) => { i.value = ''; });
            const allDiscounts = $('input[name="remise"][value="0"]', filters);
            if (allDiscounts) allDiscounts.checked = true;
            load();
        });

        $('[data-check-all]')?.addEventListener('click', (event) => {
            const boxes = $$(`input[name="${event.currentTarget.dataset.checkAll}"]`, filters);
            const allChecked = boxes.every((b) => b.checked);
            boxes.forEach((b) => { b.checked = !allChecked; });
            load();
        });

        loadMore?.addEventListener('click', (event) => {
            event.preventDefault();
            load(true);
        });
    }

    // ------------------------------------------------------------ shopping list
    const listRoot = $('[data-list-root]');
    if (listRoot) {
        const emptyState = $('[data-list-empty]');
        const sessionNote = $('[data-list-session-note]');

        const render = async () => {
            const items = list.items();
            emptyState.hidden = items.length > 0;
            if (sessionNote) sessionNote.hidden = consent.granted() || items.length === 0;
            if (!items.length) {
                listRoot.innerHTML = '';
                return;
            }
            listRoot.setAttribute('aria-busy', 'true');
            try {
                const params = new URLSearchParams({ items: items.map((i) => i.k).join(','), loc: body.dataset.location || '' });
                const res = await fetch('/api/basket?' + params);
                if (!res.ok) throw new Error(String(res.status));
                const data = await res.json();
                listRoot.innerHTML = data.html;
                // Products without a current offer: show the name saved with the list.
                const names = Object.fromEntries(items.map((i) => [i.k, i.n]));
                $$('[data-list-name]', listRoot).forEach((el) => {
                    el.textContent = names[el.dataset.listName] || el.textContent;
                });
            } catch {
                listRoot.innerHTML = '<p class="notice">Impossible de calculer votre panier pour le moment.</p>';
            } finally {
                listRoot.removeAttribute('aria-busy');
            }
        };

        listRoot.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove]');
            if (remove) {
                list.remove(remove.dataset.remove);
                render();
            } else if (event.target.closest('[data-list-clear]')) {
                list.clear();
                render();
            }
        });

        render();
    }

    // Without consent the location only lives in URLs: links rendered by the
    // server already carry it; the shopping list badge still needs a refresh.
    refreshListUi();
})();
