<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Services\ComplaintReferenceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ComplaintReferenceGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_the_next_unique_reference_for_the_current_year(): void
    {
        $this->travelTo(Carbon::parse('2026-08-19 12:00:00'));
        Complaint::factory()->create([
            'public_reference' => 'CMP-2026-0001',
        ]);

        $reference = app(ComplaintReferenceGenerator::class)->generate();

        $this->assertSame('CMP-2026-0002', $reference);
    }
}
