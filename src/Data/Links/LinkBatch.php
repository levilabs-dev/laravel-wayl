<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Data\Links;

/**
 * The response to POST /api/v1/links/batch. Links that do not belong to
 * the authenticated merchant are omitted; totalFound can be lower than
 * totalRequested.
 */
final readonly class LinkBatch
{
    /**
     * @param  array<int, LinkData>  $links
     */
    public function __construct(
        public array $links,
        public string $message,
        public int $totalRequested,
        public int $totalFound,
    ) {}
}
