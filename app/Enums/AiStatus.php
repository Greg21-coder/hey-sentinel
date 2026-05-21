<?php

namespace App\Enums;

enum AiStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Skipped = 'skipped';
    case Error = 'error';
}
