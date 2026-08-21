<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case UnderReview = 'under_review';
    case ReplySent = 'reply_sent';
    case AwaitingMoreInformation = 'awaiting_more_information';
    case BoardDecisionSuspendLicense = 'board_decision_suspend_license';
    case BoardDecisionNoAction = 'board_decision_no_action';
    case Closed = 'closed';
}
