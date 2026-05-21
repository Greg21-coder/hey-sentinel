<?php

namespace App\Enums;

enum ScrapingStatus: string
{
    case Pending = 'pending';
    case Scraped = 'scraped';
    case Error = 'error';
    case Blocked = 'blocked';
}
