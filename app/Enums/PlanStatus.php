<?php

namespace App\Enums;

enum PlanStatus: string
{
    case Active = 'active';
    case Deprecated = 'deprecated';
    case Draft = 'draft';
}
