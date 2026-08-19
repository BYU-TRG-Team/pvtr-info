<?php

namespace Tests\Feature;

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
            ->assertSee('Complaint form');
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
}
