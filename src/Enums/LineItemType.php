<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Enums;

/**
 * Whether a line item adds to or subtracts from the link total.
 * The request field is `lineItem` (singular) and each row's `type`.
 */
enum LineItemType: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';
}
