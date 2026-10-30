<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Enums;

/**
 * Wayl's link env field. Test and live links use the same API host and the
 * same merchant key — this value is sent only when a link is created, and
 * later reads do not echo it back.
 */
enum LinkEnvironment: string
{
    case Test = 'test';
    case Live = 'live';
}
