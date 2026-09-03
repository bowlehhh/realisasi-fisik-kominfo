<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->make(['name' => 'Operator Dashboard']));
    }

    public function test_dashboard_page_displays_a_placeholder_when_embed_url_is_empty(): void
    {
        config()->set('dashboard.excel_embed_url', '');

        $this->get('/')
            ->assertSee('Dashboard Realisasi Fisik sedang disiapkan.')
            ->assertSee('Detail Realisasi Fisik dan Keuangan')
            ->assertSee('Dashboard IKK')
            ->assertSee('Dashboard IKU')
            ->assertDontSee('<iframe', false)
            ->assertDontSee('DASHBOARD_EXCEL_EMBED_URL')
            ->assertDontSee('Administrator perlu');
    }

    public function test_authenticated_dashboard_responses_are_not_cacheable(): void
    {
        $this->get('/')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache');
    }

    public function test_dashboard_page_displays_a_trusted_excel_iframe(): void
    {
        config()->set('dashboard.excel_embed_url', 'https://1drv.ms/x/c/example?wdAllowInteractivity=True&wdHideGridlines=True&wdHideHeaders=True&wdDownloadButton=False');
        config()->set('dashboard.excel_source_url', 'https://onedrive.live.com/?id=example');

        $response = $this->get('/')
            ->assertOk()
            ->assertSee('https://1drv.ms/x/c/example?wdAllowInteractivity=True&amp;wdHideGridlines=True&amp;wdHideHeaders=True&amp;wdDownloadButton=False', false)
            ->assertSee('Buka Excel Online')
            ->assertSee('Data bersumber dari file Excel Online yang sama.')
            ->assertSee('wdHideGridlines=True', false)
            ->assertSee('id="excel-dashboard-panel"', false)
            ->assertSee('w-[calc(100vw-24px)]', false)
            ->assertSee('max-w-none', false)
            ->assertSee('min-h-[760px]', false)
            ->assertSee('class="relative z-10 block', false)
            ->assertSee('class="excel-dashboard-stage', false)
            ->assertSee('<iframe', false)
            ->assertSee('Operator Dashboard')
            ->assertSee('Keluar')
            ->assertDontSee('Baca Saja')
            ->assertDontSee('Muat Ulang Data')
            ->assertDontSee('Hubungkan Microsoft')
            ->assertDontSee('dashboard/excel-values')
            ->assertDontSee('type="file"', false);

        $contentSecurityPolicy = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://1drv.ms', $contentSecurityPolicy);
        $this->assertStringContainsString('https://onedrive.live.com', $contentSecurityPolicy);
        $this->assertStringContainsString('https://*.officeapps.live.com', $contentSecurityPolicy);
    }

    public function test_dashboard_rejects_an_untrusted_embed_url(): void
    {
        config()->set('dashboard.excel_embed_url', 'https://example.com/workbook');

        $this->get('/')
            ->assertSee('Dashboard Realisasi Fisik sedang disiapkan.')
            ->assertDontSee('https://example.com/workbook');
    }

    public function test_apbd_dashboard_route_is_available(): void
    {
        $this->get('/dashboard-apbd')
            ->assertOk()
            ->assertSee('Dashboard Realisasi Fisik & Keuangan', false)
            ->assertSee('Kembali ke Dashboard Realisasi Fisik')
            ->assertSee('Kembali ke Dashboard Utama')
            ->assertSee('id="dashboard-home-link"', false);
    }

    public function test_iku_dashboard_route_is_available(): void
    {
        $this->get('/dashboard-iku')
            ->assertOk()
            ->assertSee('Executive Dashboard IKU 2025–2029', false)
            ->assertSee('Kembali ke Dashboard Utama')
            ->assertSee('id="dashboard-home-link"', false);
    }

    public function test_ikk_dashboard_route_is_available(): void
    {
        $this->get('/dashboard-ikk')
            ->assertOk()
            ->assertSee('Dashboard IKK Diskominfo Kutai Barat 2025–2029', false)
            ->assertSee('Kembali ke Dashboard Utama')
            ->assertSee('id="dashboard-home-link"', false);
    }

    public function test_excel_source_file_is_not_publicly_accessible(): void
    {
        $this->get('/Dashboard-Realisasi-Fisik-Diskominfo.xlsx')->assertNotFound();
    }
}
