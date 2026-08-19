<?php

namespace Tests\Unit;

use App\Enums\ComplaintStatus;
use PHPUnit\Framework\TestCase;

class ComplaintStatusTest extends TestCase
{
    public function test_it_defines_the_complaint_workflow_statuses(): void
    {
        $this->assertSame([
            'under_review',
            'reply_sent',
            'awaiting_more_information',
            'board_decision_suspend_license',
            'board_decision_no_action',
            'closed',
        ], array_column(ComplaintStatus::cases(), 'value'));
    }
}
