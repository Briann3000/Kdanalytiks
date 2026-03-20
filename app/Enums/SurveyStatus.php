<?php

namespace App\Enums;

enum SurveyStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Closed = 'closed';
    case Archived = 'archived';
}
