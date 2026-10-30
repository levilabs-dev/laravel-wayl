<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Console\Commands;

use Illuminate\Console\Command;
use LeviLabs\LaravelWayl\Contracts\WaylLinkServiceContract;
use LeviLabs\LaravelWayl\Contracts\WaylRefundServiceContract;
use LeviLabs\LaravelWayl\Enums\LinkStatus;
use LeviLabs\LaravelWayl\Enums\RefundStatus;
use LeviLabs\LaravelWayl\Models\WaylLink;
use LeviLabs\LaravelWayl\Models\WaylRefund;
use Throwable;

/**
 * Re-checks links and refunds that have not reached a finished status.
 * Wayl's webhook is best-effort: if the endpoint was down, the secret
 * did not match, or delivery was dropped, a link can sit open forever
 * without this.
 */
class SyncWaylStatuses extends Command
{
    protected $signature = 'wayl:sync-statuses {--limit=100}';

    protected $description = 'Re-check open Wayl links and requested refunds directly from the Wayl API';

    public function handle(WaylLinkServiceContract $links, WaylRefundServiceContract $refunds): int
    {
        $limitOption = $this->option('limit');
        $limit = is_numeric($limitOption) ? (int) $limitOption : 100;

        $openStatuses = array_map(
            fn (LinkStatus $status): string => $status->value,
            array_values(array_filter(
                LinkStatus::cases(),
                fn (LinkStatus $status): bool => ! $status->isTerminal(),
            )),
        );

        $pendingLinks = WaylLink::query()
            ->whereIn('status', $openStatuses)
            ->limit($limit)
            ->get();

        foreach ($pendingLinks as $link) {
            try {
                $links->get($link->reference_id, $link->merchant);
            } catch (Throwable $e) {
                $this->warn("Link {$link->reference_id} could not be re-checked: {$e->getMessage()}");
            }
        }

        $pendingRefunds = WaylRefund::query()
            ->where('status', RefundStatus::Requested->value)
            ->limit($limit)
            ->get();

        foreach ($pendingRefunds as $refund) {
            try {
                $refunds->get($refund->refund_id, $refund->merchant);
            } catch (Throwable $e) {
                $this->warn("Refund {$refund->refund_id} could not be re-checked: {$e->getMessage()}");
            }
        }

        $this->info("Checked {$pendingLinks->count()} open link(s) and {$pendingRefunds->count()} requested refund(s).");

        return self::SUCCESS;
    }
}
