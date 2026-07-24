<?php

namespace Tests\Feature;

use App\Models\LicenseRecord;
use App\Services\LicenseImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_import_upserts_rows_and_retires_missing_records(): void
    {
        $service = app(LicenseImportService::class);

        $service->import($this->writeTxt([
            ['100-001', '01126', 'Example Person', 'Individual', 'Active', 'one@example.com', '5/31/2029'],
            ['100-002', '01226', 'Example Org', 'Organization', 'Active', 'two@example.com', '7/31/2029'],
        ]), 'initial.txt');

        $batch = $service->import($this->writeTxt([
            ['100-002', '01226', 'Updated Org', 'Organization', 'Active', 'updated@example.com', '8/31/2029'],
        ]), 'updated.txt');

        $this->assertSame(1, $batch->imported_rows);
        $this->assertFalse(LicenseRecord::where('license_number', '100-001')->firstOrFail()->is_current);

        $updated = LicenseRecord::where('license_number', '100-002')->firstOrFail();
        $this->assertTrue($updated->is_current);
        $this->assertSame('Updated Org', $updated->entity_name);
        $this->assertSame('updated@example.com', $updated->email);
    }

    public function test_malformed_rows_are_skipped_while_valid_rows_are_imported(): void
    {
        $batch = app(LicenseImportService::class)->import($this->writeTxt([
            ['100-001', '01126', 'Valid Person', 'Individual', 'Active', 'valid@example.com', '5/31/2029'],
            ['', '01126', 'Missing License Number', 'Individual', 'Active', 'missing@example.com', '5/31/2029'],
            ['100-003', '01126', 'Invalid Date', 'Individual', 'Active', 'date@example.com', 'not-a-date'],
            ['100-004', '01126', '', 'Individual', 'Active', 'name@example.com', '5/31/2029'],
            ['100-005', '01126', 'Missing Status', 'Individual', '', 'status@example.com', '5/31/2029'],
        ]), 'malformed-rows.txt');

        $this->assertSame(5, $batch->total_rows);
        $this->assertSame(1, $batch->imported_rows);
        $this->assertSame(4, $batch->skipped_rows);
        $this->assertDatabaseCount('license_records', 1);
        $this->assertDatabaseHas('license_records', [
            'license_number' => '100-001',
            'entity_name' => 'Valid Person',
        ]);
        $this->assertDatabaseMissing('license_records', ['license_number' => '100-003']);
        $this->assertDatabaseMissing('license_records', ['license_number' => '100-004']);
        $this->assertDatabaseMissing('license_records', ['license_number' => '100-005']);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function writeTxt(array $rows): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('license_import_', true).'.txt';
        $lines = [
            implode("\t", ['License #', 'License prefix', 'Entity name', 'Entity type', 'License status', 'Email', 'Expiration date']),
        ];

        foreach ($rows as $row) {
            $lines[] = implode("\t", $row);
        }

        file_put_contents($path, implode("\n", $lines));

        return $path;
    }
}
