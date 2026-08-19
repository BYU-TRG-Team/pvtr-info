<?php

namespace Tests\Unit;

use App\Enums\ComplaintType;
use PHPUnit\Framework\TestCase;

class ComplaintTypeTest extends TestCase
{
    public function test_it_defines_complaint_types(): void
    {
        $this->assertSame([
            'invalid_logo',
            'poor_quality_translation',
        ], array_column(ComplaintType::cases(), 'value'));
    }
}
