<?php

namespace App\Enums;

enum FollowedAppKind: string
{
    case Mine = 'mine';
    case Competitor = 'competitor';

    public function label(): string
    {
        return match ($this) {
            self::Mine => 'My app',
            self::Competitor => 'Competitor',
        };
    }
}
