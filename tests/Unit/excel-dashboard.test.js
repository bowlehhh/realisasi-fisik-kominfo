import assert from 'node:assert/strict';
import { test } from 'node:test';
import { initializeExcelDashboard } from '../../resources/js/excel-dashboard.js';

function dashboard(t, { online = true } = {}) {
    t.mock.timers.enable({ apis: ['Date', 'setTimeout', 'setInterval'], now: 1_000 });
    const elements = Object.fromEntries([
        'excel-dashboard', 'excel-loading', 'excel-refresh', 'excel-refresh-status',
    ].map((id) => [id, Object.assign(new EventTarget(), {
        classList: new Set(),
        textContent: '',
        disabled: false,
    })]));
    const iframe = elements['excel-dashboard'];
    iframe.src = 'https://onedrive.live.com/embed?resid=workbook&authkey=a%2Bb&wdAllowInteractivity=True#Dashboard';
    const document = Object.assign(new EventTarget(), {
        hidden: false,
        getElementById: (id) => elements[id],
    });
    const window = Object.assign(new EventTarget(), {
        navigator: { onLine: online },
        setTimeout, clearTimeout, setInterval,
    });
    initializeExcelDashboard(document, window);

    return {
        document, window, iframe,
        button: elements['excel-refresh'],
        status: elements['excel-refresh-status'],
        loading: elements['excel-loading'],
    };
}

test('requests a fresh workbook on opening and every 30 seconds without losing its identity or permissions', (t) => {
    const { iframe } = dashboard(t);
    const firstRequest = iframe.src;
    const url = new URL(firstRequest);
    assert.equal(url.origin + url.pathname, 'https://onedrive.live.com/embed');
    assert.equal(url.searchParams.get('resid'), 'workbook');
    assert.equal(url.searchParams.get('authkey'), 'a+b');
    assert.equal(url.searchParams.get('wdAllowInteractivity'), 'True');
    assert.equal(url.hash, '#Dashboard');
    assert.ok(url.searchParams.has('_dashboard_refresh'));

    iframe.dispatchEvent(new Event('load'));
    t.mock.timers.tick(29_999);
    assert.equal(iframe.src, firstRequest);
    t.mock.timers.tick(1);
    assert.notEqual(iframe.src, firstRequest);
});

test('does not interrupt a pending load and retries after a timeout', (t) => {
    const { iframe, button, status, loading } = dashboard(t);
    const firstRequest = iframe.src;
    t.mock.timers.tick(30_000);
    assert.equal(iframe.src, firstRequest);
    assert.equal(button.disabled, true);
    t.mock.timers.tick(15_000);
    assert.equal(button.disabled, false);
    assert.ok(loading.classList.has('hidden'));
    assert.match(status.textContent, /belum selesai dimuat/);
    t.mock.timers.tick(15_000);
    assert.notEqual(iframe.src, firstRequest);
});

test('pauses requests while hidden and refreshes on returning to the page', (t) => {
    const { document, iframe } = dashboard(t);
    iframe.dispatchEvent(new Event('load'));
    const firstRequest = iframe.src;
    document.hidden = true;
    t.mock.timers.tick(30_000);
    assert.equal(iframe.src, firstRequest);
    document.hidden = false;
    document.dispatchEvent(new Event('visibilitychange'));
    assert.notEqual(iframe.src, firstRequest);
});

test('recovers when connectivity returns without leaving the loading overlay stuck', (t) => {
    const { window, iframe, loading, status } = dashboard(t, { online: false });
    const firstRequest = iframe.src;
    t.mock.timers.tick(30_000);
    assert.equal(iframe.src, firstRequest);
    assert.ok(loading.classList.has('hidden'));
    assert.match(status.textContent, /Koneksi terputus/);
    window.navigator.onLine = true;
    window.dispatchEvent(new Event('online'));
    assert.notEqual(iframe.src, firstRequest);
});

test('allows manual retry after an error and clears the timeout after loading', (t) => {
    const { document, iframe, button, status } = dashboard(t);
    iframe.dispatchEvent(new Event('error'));
    assert.match(status.textContent, /gagal dimuat/);
    const firstRequest = iframe.src;
    button.dispatchEvent(new Event('click'));
    assert.notEqual(iframe.src, firstRequest);
    iframe.dispatchEvent(new Event('load'));
    assert.equal(button.disabled, false);
    document.hidden = true;
    t.mock.timers.tick(45_000);
    assert.match(status.textContent, /Pemuatan tampilan selesai/);
});

test('refreshes a restored browser history page', (t) => {
    const { window, iframe } = dashboard(t);
    iframe.dispatchEvent(new Event('load'));
    const firstRequest = iframe.src;
    window.dispatchEvent(Object.assign(new Event('pageshow'), { persisted: true }));
    assert.notEqual(iframe.src, firstRequest);
});

test('does nothing on pages without an Excel embed', () => {
    initializeExcelDashboard({ getElementById: () => null }, {});
});
