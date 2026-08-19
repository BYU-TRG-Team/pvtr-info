<?php

namespace App\Services;

use App\Enums\LicenseStatusAtFiling;
use App\Models\LicenseRecord;
use App\Support\ResolvedLicenseStatus;

class LicenseStatusAtFilingResolver
{
    public function resolve(string $licenseNumber): ResolvedLicenseStatus
    {
        $normalizedLicenseNumber = LicenseRecord::normalizeLicenseNumber($licenseNumber);

        $license = LicenseRecord::query()
            ->whereRaw(
                "REPLACE(REPLACE(license_number, '-', ''), ' ', '') = ?",
                [$normalizedLicenseNumber],
            )
            ->first();

        if ($license === null) {
            return new ResolvedLicenseStatus(
                status: LicenseStatusAtFiling::NonExistent,
                license: null,
            );
        }

        return new ResolvedLicenseStatus(
            status: $license->isValidForVerification()
                ? LicenseStatusAtFiling::Valid
                : LicenseStatusAtFiling::InvalidOrSuspended,
            license: $license,
        );
    }
}
