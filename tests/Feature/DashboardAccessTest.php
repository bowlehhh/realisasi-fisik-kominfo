<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    public function test_guests_are_redirected_from_internal_dashboard_pages(): void
    {
        foreach ([
            route('dashboard.index'),
            route('dashboard.apbd'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_microsoft_api_routes_are_not_registered(): void
    {
        $this->get('/auth/microsoft/redirect')->assertNotFound();
        $this->getJson('/api/dashboard/excel-values')->assertNotFound();
    }

    public function test_static_apbd_dashboard_file_is_not_publicly_accessible(): void
    {
        $this->get('/dashboard-apbd.html')->assertNotFound();
    }
}
