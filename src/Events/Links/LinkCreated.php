<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Events\Links;

use Illuminate\Foundation\Events\Dispatchable;
use LeviLabs\LaravelWayl\Data\Links\LinkData;
use LeviLabs\LaravelWayl\Enums\LinkEnvironment;

final class LinkCreated
{
    use Dispatchable;

    public function __construct(
        public readonly LinkData $link,
        public readonly string $merchant,
        public readonly LinkEnvironment $environment,
        public readonly ?string $webhookSecret,
    ) {}
}
