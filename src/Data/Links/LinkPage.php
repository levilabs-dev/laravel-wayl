<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Data\Links;

final readonly class LinkPage
{
    /**
     * @param  array<int, LinkData>  $links
     */
    public function __construct(
        public array $links,
        public string $message,
    ) {}
}
