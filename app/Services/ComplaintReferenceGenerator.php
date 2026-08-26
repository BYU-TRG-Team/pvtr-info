<?php

namespace App\Services;

use App\Models\Complaint;

class ComplaintReferenceGenerator
{
    public function generate(): string
    {
        $prefix = 'CMP-'.now()->format('Y').'-';

        $latestReference = Complaint::withTrashed()
            ->where('public_reference', 'like', $prefix.'%')
            ->orderByDesc('public_reference')
            ->value('public_reference');

        $nextNumber = $latestReference === null
            ? 1
            : ((int) substr($latestReference, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
