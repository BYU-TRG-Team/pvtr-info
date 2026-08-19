<?php

namespace Tests\Feature;

use App\Enums\ComplaintMessageAuthorType;
use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ComplaintModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_complaint_can_be_stored_with_casted_domain_values(): void
    {
        $filedAt = now();

        $complaint = Complaint::create([
            'public_reference' => 'CMP-2026-0001',
            'secret_link_key' => str_repeat('a', 64),
            'complainant_name' => 'Example Reporter',
            'complainant_email' => 'reporter@example.com',
            'complainant_phone' => '555-0100',
            'license_number' => '100-001',
            'license_status_at_filing' => 'valid',
            'complaint_type' => ComplaintType::InvalidLogo,
            'status' => ComplaintStatus::UnderReview,
            'details' => ['translation_location' => 'https://example.com/translation'],
            'filed_at' => $filedAt,
        ]);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'public_reference' => 'CMP-2026-0001',
            'complainant_email' => 'reporter@example.com',
            'status' => ComplaintStatus::UnderReview->value,
        ]);
        $this->assertSame(ComplaintType::InvalidLogo, $complaint->complaint_type);
        $this->assertSame(ComplaintStatus::UnderReview, $complaint->status);
        $this->assertSame(
            ['translation_location' => 'https://example.com/translation'],
            $complaint->details,
        );
        $this->assertInstanceOf(Carbon::class, $complaint->filed_at);
    }

    public function test_a_complaint_has_messages_with_complainant_and_admin_authors(): void
    {
        $complaint = Complaint::factory()->create();
        $admin = User::factory()->create();

        $complainantMessage = ComplaintMessage::factory()
            ->for($complaint)
            ->complainant()
            ->create();
        $adminMessage = ComplaintMessage::factory()
            ->for($complaint)
            ->admin($admin)
            ->create();

        $this->assertCount(2, $complaint->messages);
        $this->assertTrue($complainantMessage->complaint->is($complaint));
        $this->assertSame(ComplaintMessageAuthorType::Complainant, $complainantMessage->author_type);
        $this->assertNull($complainantMessage->user);
        $this->assertTrue($adminMessage->user->is($admin));
        $this->assertSame(ComplaintMessageAuthorType::Admin, $adminMessage->author_type);
    }

    public function test_complaint_factory_provides_workflow_states(): void
    {
        $underReview = Complaint::factory()->underReview()->create();
        $replySent = Complaint::factory()->replySent()->create();

        $this->assertSame(ComplaintStatus::UnderReview, $underReview->status);
        $this->assertSame(ComplaintStatus::ReplySent, $replySent->status);
    }
}
