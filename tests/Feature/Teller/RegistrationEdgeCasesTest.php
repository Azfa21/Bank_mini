<?php

namespace Tests\Feature\Teller;

use App\Models\Member;

class RegistrationEdgeCasesTest extends TellerTestCase
{
    protected function register(string $name): void
    {
        $this->actingAsTeller()
            ->post(route('teller.members.register.store'), [
                'full_name' => $name,
                'graduation_year' => '2026',
            ])
            ->assertRedirect(route('teller.members.register'));
    }

    public function test_registration_generates_unique_nis(): void
    {
        $this->register('Budi Santoso');
        $this->register('Citra Lestari');

        $this->assertSame(2, Member::count());

        $this->assertSame(
            Member::pluck('nis')->unique()->count(),
            Member::count()
        );
    }

    public function test_registration_requires_full_name(): void
    {
        $this->actingAsTeller()
            ->post(route('teller.members.register.store'), [])
            ->assertSessionHasErrors(['full_name']);

        $this->assertSame(0, Member::count());
    }

    public function test_registration_json_returns_created_member(): void
    {
        $this->actingAsTeller()
            ->postJson(route('teller.members.register.store'), [
                'full_name' => 'Budi Santoso',
                'graduation_year' => '2026',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.full_name', 'Budi Santoso')
            ->assertJsonPath('data.nis', fn ($value) => is_string($value) && $value !== '');
    }
}
