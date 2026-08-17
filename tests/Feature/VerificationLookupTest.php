<?php

namespace Tests\Feature;

use App\Models\LicenseRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_verify_active_current_unexpired_license(): void
    {
        LicenseRecord::create([
            'license_number' => '100-001',
            'license_prefix' => '01126',
            'entity_name' => 'Example Person',
            'entity_type' => 'Individual',
            'license_status' => 'Active',
            'email' => 'person@example.com',
            'expiration_date' => now()->addYear()->toDateString(),
            'is_current' => true,
        ]);

        $this->post(route('verification.verify'), [
            'license_number' => '100-001',
        ])
            ->assertOk()
            ->assertSee('License is valid.')
            ->assertSee('Example Person')
            ->assertSee('person@example.com')
            ->assertSee('There are two types of entity: an individual and a corporation/organization.')
            ->assertSee('The email address associated with this license record.')
            ->assertSee('The current license status. Only an Active status is considered valid when checked.')
            ->assertDontSee('Expiration');
    }

    public function test_public_user_can_verify_license_number_without_dash(): void
    {
        LicenseRecord::create([
            'license_number' => '100-001',
            'license_prefix' => '01126',
            'entity_name' => 'Example Person',
            'entity_type' => 'Individual',
            'license_status' => 'Active',
            'email' => 'person@example.com',
            'expiration_date' => now()->addYear()->toDateString(),
            'is_current' => true,
        ]);

        $this->post(route('verification.verify'), [
            'license_number' => '100001',
        ])
            ->assertOk()
            ->assertSee('License is valid.')
            ->assertSee('Example Person');
    }

    public function test_public_lookup_rejects_unknown_license_number(): void
    {
        LicenseRecord::create([
            'license_number' => '100-001',
            'license_prefix' => '01126',
            'entity_name' => 'Example Person',
            'entity_type' => 'Individual',
            'license_status' => 'Active',
            'email' => 'person@example.com',
            'expiration_date' => now()->addYear()->toDateString(),
            'is_current' => true,
        ]);

        $this->post(route('verification.verify'), [
            'license_number' => '999-999',
        ])
            ->assertOk()
            ->assertSee('No valid matching license was found.')
            ->assertDontSee('Example Person');
    }

    public function test_public_lookup_rejects_stale_or_expired_license(): void
    {
        LicenseRecord::create([
            'license_number' => '100-001',
            'license_prefix' => '01126',
            'entity_name' => 'Stale Person',
            'entity_type' => 'Individual',
            'license_status' => 'Active',
            'email' => 'stale@example.com',
            'expiration_date' => now()->addYear()->toDateString(),
            'is_current' => false,
        ]);

        LicenseRecord::create([
            'license_number' => '100-002',
            'license_prefix' => '01126',
            'entity_name' => 'Expired Person',
            'entity_type' => 'Individual',
            'license_status' => 'Active',
            'email' => 'expired@example.com',
            'expiration_date' => now()->subDay()->toDateString(),
            'is_current' => true,
        ]);

        $this->post(route('verification.verify'), [
            'license_number' => '100-001',
        ])->assertSee('No valid matching license was found.');

        $this->post(route('verification.verify'), [
            'license_number' => '100-002',
        ])->assertSee('No valid matching license was found.');
    }

    public function test_get_verify_route_displays_the_form_for_safe_refreshes(): void
    {
        $this->get(route('verification.show'))
            ->assertOk()
            ->assertSee('Check whether a logo license is valid');
    }
}
