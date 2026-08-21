<?php

namespace App\Enums;

enum ComplaintType: string
{
    case InvalidLogo = 'invalid_logo';
    case PoorQualityTranslation = 'poor_quality_translation';
}
