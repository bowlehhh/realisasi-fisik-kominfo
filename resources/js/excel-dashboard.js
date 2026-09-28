export function initializeExcelDashboard(document, window) {
    const iframe = document.getElementById('excel-dashboard');

    if (!iframe) {
        return;
    }

    const sourceUrl = iframe.src;
    const loading = document.getElementById('excel-loading');
    const refreshButton = document.getElementById('excel-refresh');
    const status = document.getElementById('excel-refresh-status');
    const refreshInterval = 30_000;
    let isLoading = false;
    let timeout;
    let lastRefresh = 0;
    let refreshSequence = 0;

    function finishLoading(message) {
        isLoading = false;
        window.clearTimeout(timeout);
        loading.classList.add('hidden');
        refreshButton.disabled = false;
        status.textContent = message;
    }

    function refresh() {
        if (isLoading || document.hidden) {
            return;
        }

        if (window.navigator.onLine === false) {
            finishLoading('Koneksi terputus. Tampilan akan dimuat ulang setelah tersambung kembali.');

            return;
        }

        const url = new URL(sourceUrl);
        lastRefresh = Date.now();
        refreshSequence += 1;
        url.searchParams.set('_dashboard_refresh', `${lastRefresh}-${refreshSequence}`);
        isLoading = true;
        refreshButton.disabled = true;
        status.textContent = 'Meminta tampilan terbaru dari Excel Online…';
        timeout = window.setTimeout(() => {
            finishLoading('Excel belum selesai dimuat. Pembaruan akan dicoba kembali; Anda juga dapat membuka Excel Online.');
        }, 45_000);
        iframe.src = url.href;
    }

    iframe.addEventListener('load', () => {
        finishLoading('Pemuatan tampilan selesai. Pembaruan otomatis setiap 30 detik.');
    });
    iframe.addEventListener('error', () => {
        finishLoading('Tampilan Excel gagal dimuat. Pembaruan akan dicoba kembali.');
    });
    refreshButton.addEventListener('click', refresh);
    document.addEventListener('visibilitychange', refresh);
    window.addEventListener('online', refresh);
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            refresh();
        }
    });
    window.setInterval(() => {
        if (Date.now() - lastRefresh >= refreshInterval) {
            refresh();
        }
    }, refreshInterval);

    refresh();
}
