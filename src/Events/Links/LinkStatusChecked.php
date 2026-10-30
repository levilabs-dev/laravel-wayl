<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Events\Links;

use Illuminate\Foundation\Events\Dispatchable;
use LeviLabs\LaravelWayl\Data\Links\LinkData;

final class LinkStatusChecked
{
    use Dispatchable;

    public function __construct(
        public readonly LinkData $link,
        public readonly string $merchant,
    ) {}
}
