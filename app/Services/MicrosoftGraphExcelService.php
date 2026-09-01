<?php

namespace App\Services;

use App\Contracts\MicrosoftConnectionRepository;
use App\Exceptions\MicrosoftAuthorizationRequiredException;
use App\Exceptions\MicrosoftReauthorizationRequiredException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MicrosoftGraphExcelService
{
    private const CACHE_TTL_SECONDS = 60;

    public function __construct(private MicrosoftConnectionRepository $connections) {}

    public function isConfigured(): bool
    {
        return collect([
            config('services.microsoft.client_id'),
            config('services.microsoft.client_secret'),
            config('services.microsoft.redirect_uri'),
            config('services.microsoft.tenant'),
            config('services.microsoft.workbook_item_id'),
            config('services.microsoft.worksheet_name'),
        ])->every(fn (mixed $value): bool => is_string($value) && filled($value));
    }

    public function hasAuthorization(): bool
    {
        return $this->connections->exists();
    }

    public function authorizationUrl(string $state): string
    {
        $tenant = rawurlencode((string) config('services.microsoft.tenant'));

        return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize?".http_build_query([
            'client_id' => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('services.microsoft.redirect_uri'),
            'response_mode' => 'query',
            'scope' => implode(' ', config('services.microsoft.scopes')),
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @throws ConnectionException
     */
    public function storeAuthorizationCode(string $code): void
    {
        $tokens = $this->requestToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.microsoft.redirect_uri'),
        ]);

        $accessToken = $tokens['access_token'] ?? null;
        $refreshToken = $tokens['refresh_token'] ?? null;

        if (! is_string($accessToken) || blank($accessToken) || ! is_string($refreshToken) || blank($refreshToken)) {
            throw new RuntimeException('Microsoft did not return the required delegated tokens.');
        }

        $this->connections->saveTokens(
            $accessToken,
            $refreshToken,
            now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
        );

        Cache::forget($this->cacheKey());
    }

    public function disconnect(): void
    {
        $this->connections->delete();
        Cache::forget($this->cacheKey());
    }

    /**
     * @return array{realisasi_fisik: string, realisasi_anggaran: string, updated_at: string}
     *
     * @throws ConnectionException
     */
    public function values(): array
    {
        if (! $this->hasAuthorization()) {
            throw new MicrosoftAuthorizationRequiredException;
        }

        return Cache::remember($this->cacheKey(), self::CACHE_TTL_SECONDS, function (): array {
            $values = $this->worksheetValues();

            return [
                'realisasi_fisik' => self::normalizePercentage(data_get($values, '0.0')),
                'realisasi_anggaran' => self::normalizePercentage(data_get($values, '0.6')),
                'updated_at' => now()->toISOString(),
            ];
        });
    }

    public static function normalizePercentage(mixed $value): string
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim(str_replace('%', '', $value)));
        }

        if (! is_numeric($value)) {
            return '—';
        }

        $percentage = (float) $value;

        if (abs($percentage) <= 1) {
            $percentage *= 100;
        }

        return number_format($percentage, 2, '.', '').'%';
    }

    /**
     * @return array<int, array<int, mixed>>
     *
     * @throws ConnectionException
     */
    private function worksheetValues(): array
    {
        $worksheet = rawurlencode((string) config('services.microsoft.worksheet_name'));
        $itemId = rawurlencode((string) config('services.microsoft.workbook_item_id'));

        $response = Http::baseUrl('https://graph.microsoft.com/v1.0')
            ->withToken($this->delegatedAccessToken())
            ->connectTimeout(3)
            ->timeout(8)
            ->get("/me/drive/items/{$itemId}/workbook/worksheets/{$worksheet}/range(address='AC3:AI3')");

        if ($response->status() === 401) {
            throw new MicrosoftReauthorizationRequiredException;
        }

        $values = $response->throw()->json('values');

        if (! is_array($values)) {
            throw new RuntimeException('Microsoft Graph did not return worksheet values.');
        }

        return $values;
    }

    /**
     * @throws ConnectionException
     */
    private function delegatedAccessToken(): string
    {
        $connection = $this->connections->find();

        if ($connection === null) {
            throw new MicrosoftAuthorizationRequiredException;
        }

        if ($connection->expires_at?->isAfter(now()->addMinute())) {
            return $connection->access_token;
        }

        $tokens = $this->requestRefreshToken($connection->refresh_token);
        $accessToken = $tokens['access_token'] ?? null;

        if (! is_string($accessToken) || blank($accessToken)) {
            throw new MicrosoftReauthorizationRequiredException;
        }

        $this->connections->saveTokens(
            $accessToken,
            is_string($tokens['refresh_token'] ?? null) && filled($tokens['refresh_token'])
                ? $tokens['refresh_token']
                : $connection->refresh_token,
            now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
        );

        return $accessToken;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    private function requestToken(array $payload): array
    {
        $response = Http::asForm()
            ->connectTimeout(3)
            ->timeout(8)
            ->post($this->tokenEndpoint(), [
                'client_id' => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                ...$payload,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Microsoft token exchange failed.');
        }

        $tokens = $response->json();

        if (! is_array($tokens)) {
            throw new RuntimeException('Microsoft token exchange returned an invalid response.');
        }

        return $tokens;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    private function requestRefreshToken(string $refreshToken): array
    {
        try {
            return $this->requestToken([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'redirect_uri' => config('services.microsoft.redirect_uri'),
            ]);
        } catch (RuntimeException) {
            throw new MicrosoftReauthorizationRequiredException;
        }
    }

    private function tokenEndpoint(): string
    {
        $tenant = rawurlencode((string) config('services.microsoft.tenant'));

        return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";
    }

    private function cacheKey(): string
    {
        return 'dashboard.excel-values.'.hash('sha256', implode('|', [
            config('services.microsoft.workbook_item_id'),
            config('services.microsoft.worksheet_name'),
        ]));
    }
}
