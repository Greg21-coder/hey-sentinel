<?php

namespace App\Enums;

enum AiStatus: string
{
    case Pending = 'pending';
    case Batched = 'batched';
    case Processed = 'processed';
    case Skipped = 'skipped';
    case Error = 'error';
}
