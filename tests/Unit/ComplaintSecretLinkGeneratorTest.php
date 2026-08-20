<?php

namespace Tests\Unit;

use App\Services\ComplaintSecretLinkGenerator;
use PHPUnit\Framework\TestCase;

class ComplaintSecretLinkGeneratorTest extends TestCase
{
    public function test_it_generates_long_non_sequential_secret_keys(): void
    {
        $generator = new ComplaintSecretLinkGenerator;
        $first = $generator->generate();
        $second = $generator->generate();

        $this->assertGreaterThanOrEqual(32, strlen($first));
        $this->assertNotSame($first, $second);
        $this->assertDoesNotMatchRegularExpression('/^\d+$/', $first);
    }
}
