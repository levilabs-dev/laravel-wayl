<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Enums;

/**
 * Optional alternative destination for a refund. Omit both this and the
 * account number to refund the original payment method. The two fields
 * must be sent together.
 */
enum RefundMethod: string
{
    case QiCard = 'QiCard';
    case Fib = 'FIB';
    case ZainCash = 'ZainCash';
}
