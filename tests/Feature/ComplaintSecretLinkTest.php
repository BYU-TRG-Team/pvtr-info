<?php

namespace Tests\Feature;

use App\Enums\ComplaintMessageAuthorType;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintSecretLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_view_a_complaint_thread_by_secret_link(): void
    {
        $complaint = Complaint::factory()->create([
            'public_reference' => 'CMP-2026-0001',
            'secret_link_key' => str_repeat('a', 64),
            'complainant_name' => 'Example Reporter',
            'license_number' => '100-001',
        ]);
        $admin = User::factory()->create();

        ComplaintMessage::factory()
            ->for($complaint)
            ->complainant()
            ->create([
                'body' => 'The original complaint statement.',
                'created_at' => now()->subMinute(),
            ]);
        ComplaintMessage::factory()
            ->for($complaint)
            ->admin($admin)
            ->create([
                'body' => 'The administrator response.',
                'created_at' => now(),
            ]);

        $this->get(route('complaints.show', [
            'secretLinkKey' => $complaint->secret_link_key,
        ]))
            ->assertOk()
            ->assertSee('Complaint thread')
            ->assertSee('CMP-2026-0001')
            ->assertSee('100-001')
            ->assertSeeInOrder([
                'The original complaint statement.',
                'The administrator response.',
            ])
            ->assertDontSee('Update complaint status')
            ->assertDontSee($complaint->complainant_email);
    }

    public function test_invalid_or_unknown_secret_links_return_not_found_without_leaking_complaint_data(): void
    {
        Complaint::factory()
            ->has(ComplaintMessage::factory()->complainant()->state([
                'body' => 'Private complaint details must not leak.',
            ]), 'messages')
            ->create([
                'public_reference' => 'CMP-2026-PRIVATE',
                'secret_link_key' => str_repeat('a', 64),
            ]);

        $this->get('/complaints/too-short')
            ->assertNotFound()
            ->assertDontSee('CMP-2026-PRIVATE')
            ->assertDontSee('Private complaint details must not leak.');

        $this->get('/complaints/'.str_repeat('z', 64))
            ->assertNotFound()
            ->assertDontSee('CMP-2026-PRIVATE')
            ->assertDontSee('Private complaint details must not leak.');
    }

    public function test_complainant_can_post_a_follow_up_through_the_secret_link(): void
    {
        $complaint = Complaint::factory()
            ->has(ComplaintMessage::factory()->complainant(), 'messages')
            ->create([
                'secret_link_key' => str_repeat('a', 64),
            ]);

        $this->post(route('complaints.replies.store', [
            'secretLinkKey' => $complaint->secret_link_key,
        ]), [
            'body' => 'Here is the additional information you requested.',
        ])->assertRedirect(route('complaints.show', [
            'secretLinkKey' => $complaint->secret_link_key,
        ]));

        $this->assertDatabaseHas('complaint_messages', [
            'complaint_id' => $complaint->id,
            'user_id' => null,
            'author_type' => ComplaintMessageAuthorType::Complainant->value,
            'body' => 'Here is the additional information you requested.',
        ]);

        $this->get(route('complaints.show', [
            'secretLinkKey' => $complaint->secret_link_key,
        ]))
            ->assertOk()
            ->assertSee('Here is the additional information you requested.');
    }

    public function test_secret_links_isolate_complaint_threads_and_replies(): void
    {
        $complaintA = Complaint::factory()
            ->has(ComplaintMessage::factory()->complainant()->state([
                'body' => 'Private details for complaint A.',
            ]), 'messages')
            ->create([
                'public_reference' => 'CMP-2026-0001',
                'secret_link_key' => str_repeat('a', 64),
            ]);
        $complaintB = Complaint::factory()
            ->has(ComplaintMessage::factory()->complainant()->state([
                'body' => 'Private details for complaint B.',
            ]), 'messages')
            ->create([
                'public_reference' => 'CMP-2026-0002',
                'secret_link_key' => str_repeat('b', 64),
            ]);

        $this->get(route('complaints.show', [
            'secretLinkKey' => $complaintA->secret_link_key,
        ]))
            ->assertOk()
            ->assertSee('CMP-2026-0001')
            ->assertSee('Private details for complaint A.')
            ->assertDontSee('CMP-2026-0002')
            ->assertDontSee('Private details for complaint B.');

        $this->post(route('complaints.replies.store', [
            'secretLinkKey' => $complaintA->secret_link_key,
        ]), [
            'body' => 'Follow-up sent through complaint A.',
        ])->assertRedirect();

        $this->assertDatabaseHas('complaint_messages', [
            'complaint_id' => $complaintA->id,
            'body' => 'Follow-up sent through complaint A.',
        ]);
        $this->assertDatabaseMissing('complaint_messages', [
            'complaint_id' => $complaintB->id,
            'body' => 'Follow-up sent through complaint A.',
        ]);
    }
}
