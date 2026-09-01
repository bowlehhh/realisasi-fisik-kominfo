<?php

namespace Tests\Feature;

use App\Contracts\MicrosoftConnectionRepository;
use App\Models\MicrosoftConnection;
use App\Models\User;
use App\Services\MicrosoftGraphExcelService;
use DateTimeInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardExcelValuesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $administrator = User::factory()->make(['email' => 'admin@example.com']);

        config()->set('admin.email', $administrator->email);

        $this->actingAs($administrator);
    }

    #[DataProvider('percentageValues')]
    public function test_it_normalizes_excel_percentage_values(mixed $value, string $expected): void
    {
        $this->assertSame($expected, MicrosoftGraphExcelService::normalizePercentage($value));
    }

    public static function percentageValues(): array
    {
        return [
            'decimal' => [0.2773, '27.73%'],
            'number' => [27.73, '27.73%'],
            'percentage string' => ['27.73%', '27.73%'],
        ];
    }

    public function test_microsoft_redirect_contains_state_and_delegated_scopes(): void
    {
        $this->configureMicrosoft();

        $response = $this->get('/auth/microsoft/redirect');

        $response->assertRedirect();

        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame('client-id', $query['client_id']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('offline_access Files.ReadWrite openid profile email', $query['scope']);
        $this->assertNotEmpty($query['state']);
        $response->assertSessionHas('microsoft_oauth_state', $query['state']);
    }

    public function test_microsoft_callback_rejects_an_invalid_oauth_state(): void
    {
        $this->configureMicrosoft();
        $repository = $this->bindRepository();
        Http::preventStrayRequests();

        $this->withSession(['microsoft_oauth_state' => 'expected-state'])
            ->get('/auth/microsoft/callback?code=authorization-code&state=invalid-state')
            ->assertRedirect(route('dashboard.index'));

        $this->assertFalse($repository->exists());
        Http::assertNothingSent();
    }

    public function test_microsoft_callback_stores_encrypted_delegated_tokens(): void
    {
        $this->configureMicrosoft();
        $repository = $this->bindRepository();
        Http::preventStrayRequests();
        Http::fake([
            'https://login.microsoftonline.com/consumers/oauth2/v2.0/token' => Http::response([
                'access_token' => 'delegated-access-token',
                'refresh_token' => 'delegated-refresh-token',
                'expires_in' => 3600,
            ]),
        ]);

        $this->withSession(['microsoft_oauth_state' => 'valid-state'])
            ->get('/auth/microsoft/callback?code=authorization-code&state=valid-state')
            ->assertRedirect(route('dashboard.index'));

        $connection = $repository->find();

        $this->assertInstanceOf(MicrosoftConnection::class, $connection);
        $this->assertSame('delegated-access-token', $connection->access_token);
        $this->assertSame('delegated-refresh-token', $connection->refresh_token);
        $this->assertNotSame('delegated-access-token', $connection->getAttributes()['access_token']);
        $this->assertNotSame('delegated-refresh-token', $connection->getAttributes()['refresh_token']);
    }

    public function test_active_delegated_access_token_reads_excel_values(): void
    {
        $this->configureMicrosoft();
        $this->bindRepository($this->connection(expiresAt: now()->addHour()));
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.microsoft.com/v1.0/me/drive/items/workbook-id/*' => Http::response([
                'values' => [[0.2773, null, null, null, null, null, 27.73]],
            ]),
        ]);

        $this->getJson('/api/dashboard/excel-values')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('realisasi_fisik', '27.73%')
            ->assertJsonPath('realisasi_anggaran', '27.73%')
            ->assertJsonStructure(['status', 'realisasi_fisik', 'realisasi_anggaran', 'updated_at']);

        Http::assertSent(fn (Request $request): bool => str_starts_with(
            $request->url(),
            'https://graph.microsoft.com/v1.0/me/drive/items/workbook-id/workbook/worksheets/Pivot/range(address=',
        ) && $request->hasHeader('Authorization', 'Bearer delegated-access-token'));
    }

    public function test_expired_delegated_access_token_is_refreshed_before_reading_excel_values(): void
    {
        $this->configureMicrosoft();
        $repository = $this->bindRepository($this->connection(expiresAt: now()->subMinute()));
        Http::preventStrayRequests();
        Http::fake([
            'https://login.microsoftonline.com/consumers/oauth2/v2.0/token' => Http::response([
                'access_token' => 'refreshed-access-token',
                'refresh_token' => 'rotated-refresh-token',
                'expires_in' => 3600,
            ]),
            'https://graph.microsoft.com/v1.0/me/drive/items/workbook-id/*' => Http::response([
                'values' => [[0.2773, null, null, null, null, null, 0.2773]],
            ]),
        ]);

        $this->getJson('/api/dashboard/excel-values')
            ->assertOk()
            ->assertJsonPath('realisasi_fisik', '27.73%')
            ->assertJsonPath('realisasi_anggaran', '27.73%');

        $connection = $repository->find();

        $this->assertSame('refreshed-access-token', $connection->access_token);
        $this->assertSame('rotated-refresh-token', $connection->refresh_token);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/oauth2/v2.0/token')
            && str_contains($request->body(), 'grant_type=refresh_token'));
    }

    public function test_excel_values_endpoint_requires_authorization_when_no_connection_exists(): void
    {
        $this->configureMicrosoft();
        $this->bindRepository();

        $this->getJson('/api/dashboard/excel-values')
            ->assertStatus(401)
            ->assertExactJson([
                'status' => 'authorization_required',
            ]);
    }

    public function test_tokens_do_not_appear_in_dashboard_html_or_api_responses(): void
    {
        $this->configureMicrosoft();
        $this->bindRepository($this->connection(expiresAt: now()->addHour(), accessToken: 'sensitive-access-token'));
        config()->set('dashboard.excel_embed_url', 'https://1drv.ms/x/c/example');
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.microsoft.com/v1.0/me/drive/items/workbook-id/*' => Http::response([
                'values' => [[0.2773, null, null, null, null, null, 0.2773]],
            ]),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('sensitive-access-token');

        $this->getJson('/api/dashboard/excel-values')
            ->assertOk()
            ->assertDontSee('sensitive-access-token');
    }

    private function configureMicrosoft(): void
    {
        config()->set('services.microsoft', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'redirect_uri' => 'http://127.0.0.1:8000/auth/microsoft/callback',
            'tenant' => 'consumers',
            'workbook_item_id' => 'workbook-id',
            'worksheet_name' => 'Pivot',
            'scopes' => ['offline_access', 'Files.ReadWrite', 'openid', 'profile', 'email'],
        ]);
    }

    private function bindRepository(?MicrosoftConnection $connection = null): InMemoryMicrosoftConnectionRepository
    {
        Cache::flush();

        $repository = new InMemoryMicrosoftConnectionRepository($connection);
        $this->app->instance(MicrosoftConnectionRepository::class, $repository);

        return $repository;
    }

    private function connection(DateTimeInterface $expiresAt, string $accessToken = 'delegated-access-token'): MicrosoftConnection
    {
        return new MicrosoftConnection([
            'id' => 1,
            'access_token' => $accessToken,
            'refresh_token' => 'delegated-refresh-token',
            'expires_at' => $expiresAt,
        ]);
    }
}

class InMemoryMicrosoftConnectionRepository implements MicrosoftConnectionRepository
{
    public function __construct(private ?MicrosoftConnection $connection = null) {}

    public function find(): ?MicrosoftConnection
    {
        return $this->connection;
    }

    public function exists(): bool
    {
        return $this->connection !== null;
    }

    public function saveTokens(string $accessToken, string $refreshToken, DateTimeInterface $expiresAt): MicrosoftConnection
    {
        $this->connection ??= new MicrosoftConnection(['id' => 1]);

        $this->connection->forceFill([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
        ]);

        return $this->connection;
    }

    public function delete(): void
    {
        $this->connection = null;
    }
}
