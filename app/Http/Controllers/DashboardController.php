<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    public function index(): View
    {
        $embedUrl = config('dashboard.excel_embed_url');
        $sourceUrl = config('dashboard.excel_source_url');

        return view('dashboard.index', [
            'agency' => config('dashboard.agency'),
            'apbdUrl' => $this->apbdUrl(),
            'embedUrl' => $this->isTrustedMicrosoftUrl($embedUrl) ? $embedUrl : null,
            'excelSourceUrl' => $this->isTrustedMicrosoftUrl($sourceUrl) ? $sourceUrl : null,
            'title' => config('dashboard.title'),
            'user' => auth()->user(),
        ]);
    }

    public function apbd(): Response
    {
        return $this->privateDashboardResponse('dashboard-apbd', 'Dashboard APBD');
    }

    public function iku(): Response
    {
        return $this->privateDashboardResponse('dashboard-iku', 'Dashboard IKU');
    }

    public function ikk(): Response
    {
        return $this->privateDashboardResponse('dashboard-ikk', 'Dashboard IKK');
    }

    private function privateDashboardResponse(string $directory, string $dashboardName): Response
    {
        $dashboardPath = storage_path("app/private/{$directory}/index.html");

        if (! is_file($dashboardPath) || ! is_readable($dashboardPath)) {
            return response()->view('dashboard.apbd-unavailable', [
                'agency' => config('dashboard.agency'),
                'dashboardName' => $dashboardName,
                'title' => config('dashboard.title'),
            ], 404);
        }

        $dashboardHtml = file_get_contents($dashboardPath);

        if (! is_string($dashboardHtml)) {
            return response()->view('dashboard.apbd-unavailable', [
                'agency' => config('dashboard.agency'),
                'dashboardName' => $dashboardName,
                'title' => config('dashboard.title'),
            ], 404);
        }

        return response($this->withDashboardHomeLink($dashboardHtml), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    private function withDashboardHomeLink(string $dashboardHtml): string
    {
        $homeUrl = htmlspecialchars(route('dashboard.index'), ENT_QUOTES, 'UTF-8');
        $homeLinkStyles = <<<'HTML'
<style id="dashboard-home-link-style">
#dashboard-home-link { position: fixed; right: 18px; bottom: 18px; z-index: 2147483647; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #bae6fd; border-radius: 9999px; padding: 10px 14px; background: #ffffff; color: #075985; font: 700 14px/1.2 Inter, ui-sans-serif, system-ui, sans-serif; text-decoration: none; box-shadow: 0 10px 26px rgba(2, 132, 199, .25); transition: background .2s ease, transform .2s ease; }
#dashboard-home-link:hover { background: #f0f9ff; transform: translateY(-1px); }
#dashboard-home-link:focus { outline: 3px solid #7dd3fc; outline-offset: 3px; }
@media (max-width: 640px) { #dashboard-home-link { right: 12px; bottom: 12px; padding: 9px 12px; font-size: 12px; } }
</style>
HTML;
        $homeLink = "<a id=\"dashboard-home-link\" href=\"{$homeUrl}\">← Kembali ke Dashboard Utama</a>";

        $htmlWithStyles = str_ireplace('</head>', "{$homeLinkStyles}</head>", $dashboardHtml);
        $homeLinkMarkup = $htmlWithStyles === $dashboardHtml ? $homeLinkStyles.$homeLink : $homeLink;
        $htmlWithHomeLink = preg_replace('/<body\\b[^>]*>/i', '$0'.$homeLinkMarkup, $htmlWithStyles, 1);

        if (is_string($htmlWithHomeLink) && str_contains($htmlWithHomeLink, 'id="dashboard-home-link"')) {
            return $htmlWithHomeLink;
        }

        return $htmlWithStyles.$homeLinkStyles.$homeLink;
    }

    private function apbdUrl(): string
    {
        $url = config('dashboard.apbd_url');

        if (! is_string($url) || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return route('dashboard.apbd');
        }

        return $url;
    }

    private function isTrustedMicrosoftUrl(mixed $url): bool
    {
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $port = $parts['port'] ?? 443;

        if (($parts['scheme'] ?? null) !== 'https' || $port !== 443) {
            return false;
        }

        return in_array($host, ['1drv.ms', 'onedrive.live.com'], true)
            || str_ends_with($host, '.officeapps.live.com');
    }
}
