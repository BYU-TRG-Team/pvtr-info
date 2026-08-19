<?php

namespace App\Enums;

enum LicenseStatusAtFiling: string
{
    case Valid = 'valid';
    case NonExistent = 'non_existent';
    case InvalidOrSuspended = 'invalid_or_suspended';
}
