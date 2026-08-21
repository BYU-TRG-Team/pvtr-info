<?php

namespace Tests\Feature;

use App\Enums\LicenseStatusAtFiling;
use App\Models\LicenseRecord;
use App\Services\LicenseStatusAtFilingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseStatusAtFilingResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_a_valid_current_active_license(): void
    {
        $license = LicenseRecord::factory()->create([
            'license_number' => '100-001',
        ]);

        $resolution = app(LicenseStatusAtFilingResolver::class)->resolve('100001');

        $this->assertSame(LicenseStatusAtFiling::Valid, $resolution->status);
        $this->assertTrue($resolution->license->is($license));
    }

    public function test_it_resolves_an_unknown_license_as_non_existent(): void
    {
        $resolution = app(LicenseStatusAtFilingResolver::class)->resolve('999-999');

        $this->assertSame(LicenseStatusAtFiling::NonExistent, $resolution->status);
        $this->assertNull($resolution->license);
    }

    public function test_it_resolves_expired_stale_and_inactive_licenses_as_invalid_or_suspended(): void
    {
        $expired = LicenseRecord::factory()->expired()->create([
            'license_number' => '100-001',
        ]);
        $stale = LicenseRecord::factory()->notCurrent()->create([
            'license_number' => '100-002',
        ]);
        $inactive = LicenseRecord::factory()->create([
            'license_number' => '100-003',
            'license_status' => 'Suspended',
        ]);
        $resolver = app(LicenseStatusAtFilingResolver::class);

        foreach ([$expired, $stale, $inactive] as $license) {
            $resolution = $resolver->resolve($license->license_number);

            $this->assertSame(LicenseStatusAtFiling::InvalidOrSuspended, $resolution->status);
            $this->assertTrue($resolution->license->is($license));
        }
    }
}
