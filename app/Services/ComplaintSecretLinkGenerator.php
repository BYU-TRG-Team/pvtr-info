<?php

namespace App\Services;

use Illuminate\Support\Str;

class ComplaintSecretLinkGenerator
{
    public function generate(): string
    {
        return Str::random(64);
    }
}
