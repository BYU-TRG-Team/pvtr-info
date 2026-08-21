<?php

namespace Tests\Unit;

use App\Enums\ComplaintMessageAuthorType;
use PHPUnit\Framework\TestCase;

class ComplaintMessageAuthorTypeTest extends TestCase
{
    public function test_it_defines_message_author_types(): void
    {
        $this->assertSame([
            'complainant',
            'admin',
        ], array_column(ComplaintMessageAuthorType::cases(), 'value'));
    }
}
