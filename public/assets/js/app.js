/* App-wide UI behaviour: toasts, entrance animations, count-up numbers,
   active navigation, shortcuts and DataTables defaults. */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ---------- Toasts (also replace window.alert so old pages get them for free) ----------
    var icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };

    function toast(message, type, timeout) {
        type = type || 'info';
        var box = document.getElementById('appToasts');
        if (!box) { return; }
        var el = document.createElement('div');
        el.className = 'app-toast app-toast--' + type;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        el.innerHTML = '<i class="fa-solid ' + (icons[type] || icons.info) + '"></i><div class="app-toast__msg"></div><button type="button" class="app-toast__close" aria-label="Close">&times;</button>';
        el.querySelector('.app-toast__msg').textContent = message;
        var ms = timeout || (type === 'error' ? 6000 : 3500);
        el.style.setProperty('--t', ms + 'ms');
        box.appendChild(el);
        requestAnimationFrame(function () { el.classList.add('is-in'); });
        var close = function () {
            el.classList.remove('is-in');
            el.classList.add('is-out');
            setTimeout(function () { el.remove(); }, 300);
        };
        el.querySelector('.app-toast__close').onclick = close;
        setTimeout(close, ms);
    }
    window.toast = toast;

    // ---------- Celebration burst (e.g. sale completed) ----------
    window.celebrate = function (x, y) {
        if (reduceMotion) { return; }
        var colors = ['#6366F1', '#8B5CF6', '#EC4899', '#F59E0B', '#10B981', '#0EA5E9'];
        x = x == null ? window.innerWidth / 2 : x;
        y = y == null ? window.innerHeight / 3 : y;
        for (var i = 0; i < 42; i++) {
            var c = document.createElement('div');
            c.className = 'confetti';
            c.style.background = colors[i % colors.length];
            c.style.left = x + 'px';
            c.style.top = y + 'px';
            document.body.appendChild(c);
            var angle = Math.random() * Math.PI * 2, dist = 120 + Math.random() * 220;
            c.animate([
                { transform: 'translate(0,0) rotate(0deg)', opacity: 1 },
                { transform: 'translate(' + Math.cos(angle) * dist + 'px,' + (Math.sin(angle) * dist + 260) + 'px) rotate(' + (Math.random() * 720 - 360) + 'deg)', opacity: 0 },
            ], { duration: 1100 + Math.random() * 600, easing: 'cubic-bezier(.2,.8,.2,1)', fill: 'forwards' }).onfinish = (function (n) { return function () { n.remove(); }; })(c);
        }
    };

    // ---------- Ripple on every button ----------
    document.addEventListener('pointerdown', function (e) {
        var btn = e.target.closest('.themeBtn, .btn');
        if (!btn || reduceMotion) { return; }
        var r = btn.getBoundingClientRect(), size = Math.max(r.width, r.height);
        var s = document.createElement('span');
        s.className = 'ripple';
        s.style.width = s.style.height = size + 'px';
        s.style.left = (e.clientX - r.left - size / 2) + 'px';
        s.style.top = (e.clientY - r.top - size / 2) + 'px';
        btn.appendChild(s);
        setTimeout(function () { s.remove(); }, 650);
    });
    window.alert = function (msg) {
        var text = String(msg);
        var type = /error|cannot|not |please|exceed|invalid|fail/i.test(text) ? 'error' : 'success';
        toast(text.replace(/^Error:\s*/i, ''), type);
    };

    if (window.Swal) {
        window.Swal = Swal.mixin({
            buttonsStyling: false,
            customClass: { popup: 'app-swal', confirmButton: 'themeBtn', cancelButton: 'themeBtn themeBtn--gray ms-2' },
            showClass: { popup: reduceMotion ? '' : 'app-swal-in' },
        });
    }

    // ---------- Active navigation ----------
    function markActiveNav() {
        var path = location.pathname.replace(/\/+$/, '') || '/';
        var best = null, bestLen = -1;
        document.querySelectorAll('.app-nav a[href]').forEach(function (a) {
            var href = new URL(a.href, location.origin).pathname.replace(/\/+$/, '') || '/';
            var match = href === path || (href !== '/' && path.indexOf(href + '/') === 0);
            if (match && href.length > bestLen) { best = a; bestLen = href.length; }
        });
        if (!best) { return; }
        best.classList.add('active');
        var group = best.closest('.collapse');
        if (group) {
            group.classList.add('show');
            var btn = document.querySelector('[data-bs-target="#' + group.id + '"]');
            if (btn) { btn.classList.remove('collapsed'); btn.setAttribute('aria-expanded', 'true'); }
        }
    }

    // ---------- Entrance animations ----------
    var revealSelector = '.metric-card, .chart-card, .transaction-ledger, .sales-form, .payment-summary, .sales-table-section, .add-product-form, .app-card, .dashboard-content__head';

    function reveal() {
        var els = Array.prototype.slice.call(document.querySelectorAll(revealSelector));
        if (reduceMotion || !('IntersectionObserver' in window)) {
            els.forEach(function (el) { el.classList.add('is-in'); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
            });
        }, { threshold: 0.05 });
        els.forEach(function (el, i) {
            el.classList.add('reveal');
            el.style.setProperty('--d', Math.min(i, 10) * 40 + 'ms');
            io.observe(el);
        });
    }

    // ---------- Count-up numbers (keeps prefix/suffix such as currency) ----------
    function countUp() {
        document.querySelectorAll('.metric-card__value, [data-countup]').forEach(function (el) {
            var text = el.textContent.trim();
            var m = text.match(/^([^\d-]*)(-?[\d,]+(?:\.\d+)?)(.*)$/);
            if (!m) { return; }
            var target = parseFloat(m[2].replace(/,/g, ''));
            if (!isFinite(target) || target === 0 || reduceMotion) { return; }
            var decimals = (m[2].split('.')[1] || '').length;
            var start = null, dur = 700;
            var fmt = function (v) { return m[1] + v.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + m[3]; };
            var step = function (ts) {
                if (!start) { start = ts; }
                var p = Math.min((ts - start) / dur, 1);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = fmt(target * eased);
                if (p < 1) { requestAnimationFrame(step); } else { el.textContent = text; }
            };
            requestAnimationFrame(step);
        });
    }

    // ---------- Row action menus ----------
    // Menus inside tables are positioned "fixed" so the scrolling table wrapper can't clip them.
    // Created on pointerdown (capture) so it exists before Bootstrap's own click handler runs.
    document.addEventListener('pointerdown', function (e) {
        var t = e.target.closest('[data-bs-toggle="dropdown"]');
        if (!t || !t.closest('table, .table-responsive') || !window.bootstrap) { return; }
        if (!bootstrap.Dropdown.getInstance(t)) {
            bootstrap.Dropdown.getOrCreateInstance(t, {
                popperConfig: function (cfg) { return Object.assign({}, cfg, { strategy: 'fixed' }); },
            });
        }
    }, true);

    // ---------- Shortcuts ----------
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F1' && window.APP && APP.salesUrl && location.pathname.indexOf('/sales') !== 0) {
            e.preventDefault();
            location.href = APP.salesUrl;
        }
    });

    // ---------- DataTables defaults ----------
    if (window.jQuery && $.fn.dataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            pageLength: 25,
            language: {
                search: '',
                searchPlaceholder: 'Search…',
                processing: '<div class="app-spinner"></div>',
                emptyTable: 'Nothing here yet',
                zeroRecords: 'No matching records',
            },
        });
    }

    // ---------- Sidebar collapse + colour theme (remembered per browser) ----------
    function store(key, val) { try { localStorage.setItem(key, val); } catch (e) {} }

    function initPrefs() {
        var root = document.documentElement;
        var btn = document.getElementById('navCollapseBtn');
        var syncTitle = function () {
            var c = root.classList.contains('nav-collapsed');
            if (btn) { btn.title = c ? 'Expand menu' : 'Collapse menu'; btn.setAttribute('aria-label', btn.title); }
        };
        if (btn) {
            // After collapsing, don't re-open on hover until the pointer leaves the sidebar
            var sidebar = btn.closest('.app-sidebar');
            sidebar.addEventListener('mouseleave', function () { root.classList.remove('no-peek'); });
            btn.addEventListener('click', function () {
                root.classList.add('no-peek');
                root.classList.toggle('nav-collapsed');
                store('pos-nav-collapsed', root.classList.contains('nav-collapsed') ? '1' : '0');
                syncTitle();
                // Charts resize themselves on window resize
                setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 380);
            });
        }
        syncTitle();

        var current = root.getAttribute('data-theme') || 'ocean';
        document.querySelectorAll('.theme-swatch').forEach(function (s) {
            s.classList.toggle('active', s.dataset.t === current);
            s.addEventListener('click', function (e) {
                e.stopPropagation();
                root.setAttribute('data-theme', s.dataset.t);
                store('pos-theme', s.dataset.t);
                document.querySelectorAll('.theme-swatch').forEach(function (o) { o.classList.toggle('active', o === s); });
                var r = s.getBoundingClientRect();
                if (window.celebrate) { celebrate(r.left + r.width / 2, r.top + r.height / 2); }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPrefs();
        markActiveNav();
        reveal();
        countUp();

        // Safety net: if the browser throttles animations (background tab, low-power mode),
        // never leave the page faded — finish entrance effects that haven't progressed.
        setTimeout(function () {
            document.querySelectorAll('.reveal:not(.is-in)').forEach(function (el) { el.classList.add('is-in'); });
            if (document.getAnimations) {
                document.getAnimations().forEach(function (a) {
                    if (a.playState === 'running' && a.effect && a.effect.getComputedTiming && a.effect.getComputedTiming().progress < 0.05) {
                        try { a.finish(); } catch (e) {}
                    }
                });
            }
        }, 1500);
        if (window.APP) {
            if (APP.flash.success) { toast(APP.flash.success, 'success'); }
            if (APP.flash.error) { toast(APP.flash.error, 'error'); }
        }
    });
})();
