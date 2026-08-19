<?php

namespace App\Services;

use App\Models\Complaint;

class ComplaintFilingService
{
    public function __construct(
        private readonly LicenseStatusAtFilingResolver $licenseStatusResolver,
    ) {}

    /**
     * Create a complaint with license data derived from the current snapshot.
     *
     * The initial complaint message will be added to this transaction in
     * PVTR-C17 when the public filing workflow is implemented.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function file(array $attributes): Complaint
    {
        $resolution = $this->licenseStatusResolver->resolve(
            (string) $attributes['license_number'],
        );

        return Complaint::create([
            ...$attributes,
            'license_record_id' => $resolution->license?->id,
            'license_number' => $resolution->license?->license_number
                ?? trim((string) $attributes['license_number']),
            'license_status_at_filing' => $resolution->status,
        ]);
    }
}
