<?php

namespace App\Enums;

enum FeatureValueType: string
{
    case Boolean = 'boolean';
    case Integer = 'integer';
    case String = 'string';
    case Unlimited = 'unlimited';
}
