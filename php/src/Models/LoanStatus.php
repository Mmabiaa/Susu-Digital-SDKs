<?php

declare(strict_types=1);

namespace SusuDigital\Models;

enum LoanStatus: string
{
    case Pending     = 'pending';
    case UnderReview = 'under_review';
    case Approved    = 'approved';
    case Disbursed   = 'disbursed';
    case Active      = 'active';
    case Closed      = 'closed';
    case Defaulted   = 'defaulted';
    case Rejected    = 'rejected';
}
