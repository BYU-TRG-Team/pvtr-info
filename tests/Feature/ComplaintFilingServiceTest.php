<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Enums\LicenseStatusAtFiling;
use App\Models\LicenseRecord;
use App\Services\ComplaintFilingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintFilingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_derives_and_stores_license_status_when_creating_a_complaint(): void
    {
        $valid = LicenseRecord::factory()->create([
            'license_number' => '100-001',
        ]);
        $invalid = LicenseRecord::factory()->expired()->create([
            'license_number' => '100-002',
        ]);
        $service = app(ComplaintFilingService::class);

        $validComplaint = $service->file($this->complaintData('CMP-2026-0001', '100001'));
        $invalidComplaint = $service->file($this->complaintData('CMP-2026-0002', '100-002'));
        $unknownComplaint = $service->file($this->complaintData('CMP-2026-0003', '999-999'));

        $this->assertSame(LicenseStatusAtFiling::Valid, $validComplaint->license_status_at_filing);
        $this->assertTrue($validComplaint->licenseRecord->is($valid));
        $this->assertSame(LicenseStatusAtFiling::InvalidOrSuspended, $invalidComplaint->license_status_at_filing);
        $this->assertTrue($invalidComplaint->licenseRecord->is($invalid));
        $this->assertSame(LicenseStatusAtFiling::NonExistent, $unknownComplaint->license_status_at_filing);
        $this->assertNull($unknownComplaint->licenseRecord);
    }

    /**
     * @return array<string, mixed>
     */
    private function complaintData(string $reference, string $licenseNumber): array
    {
        return [
            'public_reference' => $reference,
            'secret_link_key' => hash('sha256', $reference),
            'complainant_name' => 'Example Reporter',
            'complainant_email' => 'reporter@example.com',
            'license_number' => $licenseNumber,
            'complaint_type' => ComplaintType::InvalidLogo,
            'status' => ComplaintStatus::UnderReview,
            'details' => [],
            'filed_at' => now(),
        ];
    }
}
