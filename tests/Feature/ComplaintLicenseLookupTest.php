<?php

namespace Tests\Feature;

use App\Enums\LicenseStatusAtFiling;
use App\Models\LicenseRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintLicenseLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_valid_status_and_canonical_license_number(): void
    {
        LicenseRecord::factory()->create([
            'license_number' => '100-001',
        ]);

        $this->getJson(route('complaints.license-status', [
            'license_number' => '100001',
        ]))
            ->assertOk()
            ->assertJson([
                'license_number' => '100-001',
                'license_status_at_filing' => LicenseStatusAtFiling::Valid->value,
            ]);
    }

    public function test_it_returns_invalid_or_suspended_for_nonvalid_license(): void
    {
        LicenseRecord::factory()->expired()->create([
            'license_number' => '100-002',
        ]);

        $this->getJson(route('complaints.license-status', [
            'license_number' => '100-002',
        ]))
            ->assertOk()
            ->assertJson([
                'license_number' => '100-002',
                'license_status_at_filing' => LicenseStatusAtFiling::InvalidOrSuspended->value,
            ]);
    }

    public function test_it_returns_nonexistent_for_unknown_license_number(): void
    {
        $this->getJson(route('complaints.license-status', [
            'license_number' => '999-999',
        ]))
            ->assertOk()
            ->assertJson([
                'license_number' => '999-999',
                'license_status_at_filing' => LicenseStatusAtFiling::NonExistent->value,
            ]);
    }

    public function test_it_rejects_missing_license_number(): void
    {
        $this->getJson(route('complaints.license-status'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('license_number');
    }
}
