<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    public function test_guests_are_redirected_from_internal_dashboard_pages_and_microsoft_routes(): void
    {
        foreach ([
            route('dashboard.index'),
            route('dashboard.apbd'),
            route('microsoft.redirect'),
            route('microsoft.callback'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_guests_cannot_disconnect_microsoft_or_read_excel_values(): void
    {
        $this->post(route('microsoft.disconnect'))
            ->assertRedirect(route('login'));

        $this->getJson(route('dashboard.excel-values'))
            ->assertUnauthorized();
    }

    public function test_static_apbd_dashboard_file_is_not_publicly_accessible(): void
    {
        $this->get('/dashboard-apbd.html')->assertNotFound();
    }

    public function test_non_administrators_cannot_manage_the_microsoft_connection(): void
    {
        config()->set('admin.email', 'admin@example.com');

        $this->actingAs(User::factory()->make(['email' => 'operator@example.com']))
            ->get(route('microsoft.redirect'))
            ->assertForbidden();

        $this->post(route('microsoft.disconnect'))
            ->assertForbidden();
    }
}
