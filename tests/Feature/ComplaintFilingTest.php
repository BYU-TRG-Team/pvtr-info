<?php

namespace Tests\Feature;

use App\Enums\ComplaintType;
use App\Models\Complaint;
use App\Models\LicenseRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintFilingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_open_the_complaint_filing_page(): void
    {
        $this->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('File a complaint')
            ->assertSee('Complaint form')
            ->assertSee('name="complainant_name"', false)
            ->assertSee('name="complainant_email"', false)
            ->assertSee('name="complainant_phone"', false)
            ->assertSee('name="license_number"', false)
            ->assertSee('name="complaint_type"', false)
            ->assertSee('name="statement"', false)
            ->assertSee('name="translation_location"', false)
            ->assertSee('name="major_error"', false)
            ->assertSee('name="harm_type"', false);
    }

    public function test_all_complaint_fields_are_visible_and_enabled_on_initial_load(): void
    {
        $response = $this->get(route('complaints.create'))->assertOk();
        $html = $response->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<fieldset[^>]*data-poor-quality-fields[^>]*hidden/s',
            $html,
        );
        $this->assertStringNotContainsString(' disabled', $html);
    }

    public function test_complaint_form_explains_both_reporting_scenarios(): void
    {
        $this->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('Which type of complaint should I choose?')
            ->assertSee('Invalid logo attached to a translation')
            ->assertSee('Valid logo, poor-quality translation')
            ->assertSee('unverified automatic translation');
    }

    public function test_complaint_form_has_help_for_complex_fields(): void
    {
        $this->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('Dashes are optional')
            ->assertSee('Choose the scenario that best matches')
            ->assertSee('Provide a URL, document name, product')
            ->assertSee('Choose the most likely consequence')
            ->assertSee('tabindex="0"', false)
            ->assertSee('role="tooltip"', false);
    }

    public function test_complaint_link_is_visible_to_guests_and_admins(): void
    {
        $complaintUrl = route('complaints.create');

        $this->get(route('verification.index'))
            ->assertOk()
            ->assertSee('File a complaint')
            ->assertSee($complaintUrl, false);

        $this->actingAs(User::factory()->create())
            ->get(route('verification.index'))
            ->assertOk()
            ->assertSee('File a complaint')
            ->assertSee($complaintUrl, false);
    }

    public function test_public_user_can_submit_a_complaint_and_view_confirmation(): void
    {
        $response = $this->post(route('complaints.store'), [
            ...$this->validComplaintData(),
            'translation_location' => 'https://example.com/logo-usage',
        ]);

        $response
            ->assertRedirect(route('complaints.submitted'))
            ->assertSessionHas('submitted_complaint_id');

        $complaint = Complaint::query()->firstOrFail();

        $this->assertDatabaseHas('complaint_messages', [
            'complaint_id' => $complaint->id,
            'body' => 'The logo attached to this translation appears to be invalid.',
        ]);

        $this->get(route('complaints.submitted'))
            ->assertOk()
            ->assertSee('Complaint submitted')
            ->assertSee($complaint->public_reference)
            ->assertSee(
                'data-copy-value="'.url('/complaints/'.$complaint->secret_link_key).'"',
                false,
            )
            ->assertSee('Open complaint thread')
            ->assertSee('Copy private link')
            ->assertSee('Save this private link');
    }

    public function test_poor_quality_complaint_stores_conditional_details_and_initial_message(): void
    {
        $data = [
            ...$this->validComplaintData(),
            'complaint_type' => ComplaintType::PoorQualityTranslation->value,
            'statement' => 'The translation has a serious quality problem.',
            'translation_location' => 'https://example.com/translation',
            'major_error' => 'A medication dosage uses the wrong unit.',
            'harm_type' => 'injury',
        ];

        $this->post(route('complaints.store'), $data)
            ->assertRedirect(route('complaints.submitted'));

        $complaint = Complaint::query()->firstOrFail();

        $this->assertSame([
            'translation_location' => 'https://example.com/translation',
            'major_error' => 'A medication dosage uses the wrong unit.',
            'harm_type' => 'injury',
        ], $complaint->details);
        $this->assertSame(
            'The translation has a serious quality problem.',
            $complaint->messages()->firstOrFail()->body,
        );
    }

    public function test_invalid_logo_complaint_requires_translation_location(): void
    {
        $this->from(route('complaints.create'))
            ->post(route('complaints.store'), $this->validComplaintData())
            ->assertRedirect(route('complaints.create'))
            ->assertSessionHasErrors('translation_location');
    }

    public function test_invalid_logo_complaint_with_valid_license_requires_explanation(): void
    {
        LicenseRecord::factory()->create([
            'license_number' => '100-001',
        ]);

        $this->from(route('complaints.create'))
            ->post(route('complaints.store'), [
                ...$this->validComplaintData(),
                'translation_location' => 'https://example.com/logo-usage',
            ])
            ->assertRedirect(route('complaints.create'))
            ->assertSessionHasErrors('valid_license_explanation');
    }

    public function test_invalid_logo_complaint_can_store_valid_license_explanation(): void
    {
        LicenseRecord::factory()->create([
            'license_number' => '100-001',
        ]);

        $this->post(route('complaints.store'), [
            ...$this->validComplaintData(),
            'translation_location' => 'https://example.com/logo-usage',
            'valid_license_explanation' => 'The logo graphic shown on the page does not match the licensed entity.',
        ])->assertRedirect(route('complaints.submitted'));

        $complaint = Complaint::query()->firstOrFail();

        $this->assertSame([
            'translation_location' => 'https://example.com/logo-usage',
            'valid_license_explanation' => 'The logo graphic shown on the page does not match the licensed entity.',
        ], $complaint->details);

        $this->get(route('complaints.show', [
            'secretLinkKey' => $complaint->secret_link_key,
        ]))
            ->assertOk()
            ->assertSee('Why this was still filed as an invalid logo complaint')
            ->assertSee('The logo graphic shown on the page does not match the licensed entity.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validComplaintData(): array
    {
        return [
            'complainant_name' => 'Example Reporter',
            'complainant_email' => 'reporter@example.com',
            'complainant_phone' => '555-0100',
            'license_number' => '100-001',
            'complaint_type' => ComplaintType::InvalidLogo->value,
            'statement' => 'The logo attached to this translation appears to be invalid.',
        ];
    }
}
