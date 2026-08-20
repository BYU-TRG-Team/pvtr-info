<?php

namespace Tests\Feature;

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

        $validComplaint = $service->file($this->complaintData('100001'));
        $invalidComplaint = $service->file($this->complaintData('100-002'));
        $unknownComplaint = $service->file($this->complaintData('999-999'));

        $this->assertSame(LicenseStatusAtFiling::Valid, $validComplaint->license_status_at_filing);
        $this->assertTrue($validComplaint->licenseRecord->is($valid));
        $this->assertSame(LicenseStatusAtFiling::InvalidOrSuspended, $invalidComplaint->license_status_at_filing);
        $this->assertTrue($invalidComplaint->licenseRecord->is($invalid));
        $this->assertSame(LicenseStatusAtFiling::NonExistent, $unknownComplaint->license_status_at_filing);
        $this->assertNull($unknownComplaint->licenseRecord);
    }

    public function test_it_creates_the_complaint_and_initial_message_in_one_filing_operation(): void
    {
        $complaint = app(ComplaintFilingService::class)->file(
            $this->complaintData('100-001'),
        );

        $this->assertMatchesRegularExpression('/^CMP-\d{4}-\d{4}$/', $complaint->public_reference);
        $this->assertGreaterThanOrEqual(32, strlen($complaint->secret_link_key));
        $this->assertNotNull($complaint->filed_at);
        $this->assertCount(1, $complaint->messages);
        $this->assertSame(
            'The logo attached to this translation appears to be invalid.',
            $complaint->messages->first()->body,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function complaintData(string $licenseNumber): array
    {
        return [
            'complainant_name' => 'Example Reporter',
            'complainant_email' => 'reporter@example.com',
            'license_number' => $licenseNumber,
            'complaint_type' => ComplaintType::InvalidLogo->value,
            'statement' => 'The logo attached to this translation appears to be invalid.',
        ];
    }
}
