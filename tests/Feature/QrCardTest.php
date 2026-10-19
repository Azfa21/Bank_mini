<?php

namespace Tests\Feature;

use Tests\Feature\Teller\TellerTestCase;

class QrCardTest extends TellerTestCase
{
    public function test_qr_show_returns_svg_with_no_store_headers(): void
    {
        $response = $this->actingAsTeller()
            ->get(route('qr.show', ['data' => $this->account->account_number, 'size' => 220]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_qr_show_requires_data_parameter(): void
    {
        $this->actingAsTeller()
            ->get(route('qr.show'))
            ->assertStatus(422);
    }

    public function test_qr_download_returns_attachment_with_sanitized_filename(): void
    {
        $response = $this->actingAsTeller()
            ->get(route('qr.download', ['data' => '2026/2027-001']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8');

        $this->assertStringContainsString(
            'attachment; filename="qr-2026-2027-001.svg"',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_qr_includes_white_background_and_quiet_zone(): void
    {
        $content = $this->actingAsTeller()
            ->get(route('qr.download', ['data' => '2026/2027-001']))
            ->getContent();

        $this->assertStringContainsString('<rect width="100%" height="100%" fill="#ffffff"/>', $content);

        $this->assertMatchesRegularExpression(
            '/translate\(4,4\)/',
            $content,
            'QR must be rendered with a 4-module quiet zone.'
        );
    }

    public function test_all_roles_can_access_qr_endpoints(): void
    {
        foreach ([
            [$this->admin, 'web'],
            [$this->teller, 'web'],
            [$this->admin2, 'web'],
            [$this->nasabah, 'nasabah'],
        ] as [$user, $guard]) {
            $this->actingAs($user, $guard)
                ->get(route('qr.show', ['data' => 'access-check']))
                ->assertOk();

            $this->actingAs($user, $guard)
                ->get(route('qr.download', ['data' => 'access-check']))
                ->assertOk();
        }
    }

    public function test_guest_cannot_access_qr_endpoints(): void
    {
        $this->get(route('qr.show', ['data' => 'x']))->assertRedirect(route('login'));
        $this->get(route('qr.download', ['data' => 'x']))->assertRedirect(route('login'));
    }
}
