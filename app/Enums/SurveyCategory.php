<?php

namespace App\Enums;

enum SurveyCategory: string
{
    case Academic = 'academic';
    case Polls = 'polls';
    case MarketResearch = 'market_research';
    case Feasibility = 'feasibility';
    case Social = 'social';
    case Business = 'business';
}
