<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticatedSessionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get(route('login'))
            ->assertSee('Dashboard Realisasi Fisik')
            ->assertSee('Diskominfo Kabupaten Kutai Barat')
            ->assertSee('Ingat saya')
            ->assertSee('Masuk')
            ->assertDontSee('Microsoft');
    }

    public function test_valid_credentials_authenticate_user_and_redirect_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'operator@example.com',
            'password' => Hash::make('secure-password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secure-password',
        ])->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_return_a_generic_error_and_keep_user_as_guest(): void
    {
        User::factory()->create([
            'email' => 'operator@example.com',
            'password' => Hash::make('secure-password'),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'operator@example.com',
                'password' => 'incorrect-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_login_page(): void
    {
        $this->actingAs(User::factory()->make())
            ->get(route('login'))
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $this->actingAs(User::factory()->make())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
