<?php

namespace Tests\Feature;

use App\Enums\ComplaintMessageAuthorType;
use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminComplaintTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_complaint_routes(): void
    {
        $complaint = Complaint::factory()->create();

        $this->get(route('admin.complaints.index'))->assertRedirect('/login');
        $this->get(route('admin.complaints.show', $complaint))->assertRedirect('/login');
        $this->put(route('admin.complaints.status.update', $complaint), [
            'status' => ComplaintStatus::Closed->value,
        ])->assertRedirect('/login');
        $this->post(route('admin.complaints.replies.store', $complaint), [
            'body' => 'Admin reply.',
        ])->assertRedirect('/login');
        $this->put(route('admin.complaints.update', $complaint), [
            'complainant_name' => 'Updated Reporter',
        ])->assertRedirect('/login');
        $this->delete(route('admin.complaints.destroy', $complaint))
            ->assertRedirect('/login');
        $this->post(route('admin.complaints.restore', $complaint))
            ->assertRedirect('/login');
    }

    public function test_admin_can_list_newest_complaints_and_filter_by_status(): void
    {
        $admin = User::factory()->create();
        $older = Complaint::factory()->underReview()->create([
            'public_reference' => 'CMP-2026-0001',
            'filed_at' => now()->subDay(),
        ]);
        $newer = Complaint::factory()->replySent()->create([
            'public_reference' => 'CMP-2026-0002',
            'filed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.complaints.index'))
            ->assertOk()
            ->assertSee('Complaint management')
            ->assertSee('name="status"', false)
            ->assertSeeInOrder([
                $newer->public_reference,
                $older->public_reference,
            ]);

        $this->actingAs($admin)
            ->get(route('admin.complaints.index', [
                'status' => ComplaintStatus::UnderReview->value,
            ]))
            ->assertOk()
            ->assertSee($older->public_reference)
            ->assertDontSee($newer->public_reference);
    }

    public function test_admin_can_view_all_complaint_details_and_chronological_messages(): void
    {
        $admin = User::factory()->create();
        $complaint = Complaint::factory()->create([
            'public_reference' => 'CMP-2026-0001',
            'complainant_name' => 'Example Reporter',
            'complainant_email' => 'reporter@example.com',
            'complainant_phone' => '555-0100',
            'license_number' => '100-001',
            'license_status_at_filing' => 'valid',
            'complaint_type' => ComplaintType::InvalidLogo,
            'details' => [
                'translation_location' => 'https://example.com/translation',
                'valid_license_explanation' => 'The displayed logo does not belong to the licensed organization.',
            ],
        ]);
        ComplaintMessage::factory()->for($complaint)->complainant()->create([
            'body' => 'Original complaint.',
            'created_at' => now()->subMinute(),
        ]);
        ComplaintMessage::factory()->for($complaint)->admin($admin)->create([
            'body' => 'Administrator response.',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('CMP-2026-0001')
            ->assertSee('Example Reporter')
            ->assertSee('reporter@example.com')
            ->assertSee('555-0100')
            ->assertSee('100-001')
            ->assertSee('Valid')
            ->assertSee('https://example.com/translation')
            ->assertSee('The displayed logo does not belong to the licensed organization.')
            ->assertSee(
                'data-copy-value="'.url('/complaints/'.$complaint->secret_link_key).'"',
                false,
            )
            ->assertSee('View private thread')
            ->assertSee('Copy private link')
            ->assertSee('name="complaint_type"', false)
            ->assertSee('name="harm_type"', false)
            ->assertDontSee('name="complainant_name"', false)
            ->assertDontSee('name="complainant_email"', false)
            ->assertDontSee('name="statement"', false)
            ->assertSeeInOrder([
                'Original complaint.',
                'Administrator response.',
            ]);
    }

    public function test_admin_can_correct_select_classification_fields_only(): void
    {
        $admin = User::factory()->create();
        $complaint = Complaint::factory()->create([
            'complainant_name' => 'Original Reporter',
            'complainant_email' => 'original@example.com',
            'complaint_type' => ComplaintType::PoorQualityTranslation,
            'details' => [
                'translation_location' => 'https://example.com/original',
                'major_error' => 'Original supporting detail.',
                'harm_type' => 'injury',
            ],
        ]);
        $statement = ComplaintMessage::factory()->for($complaint)->complainant()->create([
            'body' => 'Original statement.',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.complaints.update', $complaint), [
                'complaint_type' => ComplaintType::InvalidLogo->value,
                'harm_type' => 'equipment_damage',
                'complainant_name' => 'Tampered Reporter',
                'complainant_email' => 'tampered@example.com',
                'statement' => 'Tampered statement.',
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint))
            ->assertSessionHas('status', 'Complaint classification updated.');

        $complaint->refresh();

        $this->assertSame('Original Reporter', $complaint->complainant_name);
        $this->assertSame('original@example.com', $complaint->complainant_email);
        $this->assertSame(ComplaintType::InvalidLogo, $complaint->complaint_type);
        $this->assertSame([
            'translation_location' => 'https://example.com/original',
            'major_error' => 'Original supporting detail.',
            'harm_type' => 'equipment_damage',
        ], $complaint->details);
        $this->assertSame('Original statement.', $statement->refresh()->body);
    }

    public function test_admin_classification_edit_rejects_invalid_select_values(): void
    {
        $admin = User::factory()->create();
        $complaint = Complaint::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.complaints.show', $complaint))
            ->put(route('admin.complaints.update', $complaint), [
                'complaint_type' => 'invalid',
                'harm_type' => 'invalid',
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint))
            ->assertSessionHasErrors([
                'complaint_type',
                'harm_type',
            ]);
    }

    public function test_admin_can_archive_resolved_complaints_and_restore_them(): void
    {
        $admin = User::factory()->create();
        $active = Complaint::factory()->create([
            'public_reference' => 'CMP-2026-0001',
        ]);
        $resolved = Complaint::factory()->create([
            'public_reference' => 'CMP-2026-0002',
            'status' => ComplaintStatus::Closed,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.complaints.destroy', $resolved))
            ->assertRedirect(route('admin.complaints.index'))
            ->assertSessionHas('status', 'Complaint archived.');

        $this->assertSoftDeleted($resolved);

        $this->actingAs($admin)
            ->get(route('admin.complaints.index'))
            ->assertOk()
            ->assertSee('Active')
            ->assertSee('Archived')
            ->assertSee($active->public_reference)
            ->assertDontSee($resolved->public_reference);

        $this->actingAs($admin)
            ->get(route('admin.complaints.index', ['view' => 'archived']))
            ->assertOk()
            ->assertSee($resolved->public_reference)
            ->assertDontSee($active->public_reference);

        $this->get(route('complaints.show', [
            'secretLinkKey' => $resolved->secret_link_key,
        ]))
            ->assertOk()
            ->assertSee('This complaint has been archived')
            ->assertDontSee('Send reply');

        $this->actingAs($admin)
            ->get(route('admin.complaints.show', $resolved))
            ->assertOk()
            ->assertSee('Archived');

        $this->actingAs($admin)
            ->post(route('admin.complaints.restore', $resolved))
            ->assertRedirect(route('admin.complaints.show', $resolved))
            ->assertSessionHas('status', 'Complaint restored.');

        $this->assertNotSoftDeleted($resolved);
    }

    public function test_admin_can_update_complaint_status_and_invalid_status_is_rejected(): void
    {
        $admin = User::factory()->create();
        $complaint = Complaint::factory()->underReview()->create();

        $this->actingAs($admin)
            ->put(route('admin.complaints.status.update', $complaint), [
                'status' => ComplaintStatus::AwaitingMoreInformation->value,
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint))
            ->assertSessionHas('status', 'Complaint status updated.');

        $this->assertSame(
            ComplaintStatus::AwaitingMoreInformation,
            $complaint->refresh()->status,
        );

        $this->actingAs($admin)
            ->from(route('admin.complaints.show', $complaint))
            ->put(route('admin.complaints.status.update', $complaint), [
                'status' => 'not-a-real-status',
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint))
            ->assertSessionHasErrors('status');

        $this->assertSame(
            ComplaintStatus::AwaitingMoreInformation,
            $complaint->refresh()->status,
        );
    }

    public function test_admin_reply_records_author_and_sets_reply_sent_status(): void
    {
        $admin = User::factory()->create();
        $complaint = Complaint::factory()->underReview()->create();

        $this->actingAs($admin)
            ->post(route('admin.complaints.replies.store', $complaint), [
                'body' => 'We reviewed the additional information.',
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint))
            ->assertSessionHas('status', 'Reply sent.');

        $this->assertDatabaseHas('complaint_messages', [
            'complaint_id' => $complaint->id,
            'user_id' => $admin->id,
            'author_type' => ComplaintMessageAuthorType::Admin->value,
            'body' => 'We reviewed the additional information.',
        ]);
        $this->assertSame(ComplaintStatus::ReplySent, $complaint->refresh()->status);

        $this->get(route('complaints.show', [
            'secretLinkKey' => $complaint->secret_link_key,
        ]))
            ->assertOk()
            ->assertSee('We reviewed the additional information.');
    }

    public function test_admin_reply_requires_a_message_body(): void
    {
        $admin = User::factory()->create();
        $complaint = Complaint::factory()->underReview()->create();

        $this->actingAs($admin)
            ->from(route('admin.complaints.show', $complaint))
            ->post(route('admin.complaints.replies.store', $complaint), [
                'body' => '',
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint))
            ->assertSessionHasErrors('body');

        $this->assertDatabaseMissing('complaint_messages', [
            'complaint_id' => $complaint->id,
            'author_type' => ComplaintMessageAuthorType::Admin->value,
        ]);
        $this->assertSame(ComplaintStatus::UnderReview, $complaint->refresh()->status);
    }

    public function test_complaints_navigation_is_visible_to_admins_only(): void
    {
        $this->get(route('verification.index'))
            ->assertOk()
            ->assertDontSee(route('admin.complaints.index'), false);

        $this->actingAs(User::factory()->create())
            ->get(route('verification.index'))
            ->assertOk()
            ->assertSee('Complaints')
            ->assertSee(route('admin.complaints.index'), false);
    }

    public function test_end_to_end_public_filing_admin_review_and_public_reply_visibility(): void
    {
        $this->post(route('complaints.store'), [
            'complainant_name' => 'Example Reporter',
            'complainant_email' => 'reporter@example.com',
            'license_number' => '100-001',
            'complaint_type' => ComplaintType::InvalidLogo->value,
            'translation_location' => 'https://example.com/public-listing',
            'statement' => 'The logo appears to be invalid.',
        ])->assertRedirect(route('complaints.submitted'));

        $complaint = Complaint::query()->firstOrFail();
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('The logo appears to be invalid.');

        $this->actingAs($admin)
            ->put(route('admin.complaints.status.update', $complaint), [
                'status' => ComplaintStatus::UnderReview->value,
            ])->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.complaints.replies.store', $complaint), [
                'body' => 'The review board has received your complaint.',
            ])->assertRedirect();

        $this->get(route('complaints.show', [
            'secretLinkKey' => $complaint->secret_link_key,
        ]))
            ->assertOk()
            ->assertSee('The review board has received your complaint.');
    }
}
