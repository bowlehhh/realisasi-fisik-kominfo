<?php

namespace App\Http\Controllers;

use App\Services\MicrosoftGraphExcelService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    public function index(MicrosoftGraphExcelService $microsoft): View
    {
        $embedUrl = config('dashboard.excel_embed_url');
        $sourceUrl = config('dashboard.excel_source_url');
        $microsoftIsConfigured = $microsoft->isConfigured();

        return view('dashboard.index', [
            'agency' => config('dashboard.agency'),
            'apbdUrl' => $this->apbdUrl(),
            'embedUrl' => $this->isTrustedMicrosoftUrl($embedUrl) ? $embedUrl : null,
            'excelSourceUrl' => $this->isTrustedMicrosoftUrl($sourceUrl) ? $sourceUrl : null,
            'showMicrosoftConnectButton' => $microsoftIsConfigured
                && ! $microsoft->hasAuthorization(),
            'title' => config('dashboard.title'),
            'user' => auth()->user(),
        ]);
    }

    public function apbd(): Response
    {
        $dashboardPath = storage_path('app/private/dashboard-apbd/dashboard-apbd.html');

        if (! is_file($dashboardPath)) {
            return response()->view('dashboard.apbd-unavailable', [
                'agency' => config('dashboard.agency'),
                'title' => config('dashboard.title'),
            ], 404);
        }

        return response(file_get_contents($dashboardPath), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
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
