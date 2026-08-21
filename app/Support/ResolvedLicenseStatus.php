<?php

namespace App\Support;

use App\Enums\LicenseStatusAtFiling;
use App\Models\LicenseRecord;

readonly class ResolvedLicenseStatus
{
    public function __construct(
        public LicenseStatusAtFiling $status,
        public ?LicenseRecord $license,
    ) {}
}
