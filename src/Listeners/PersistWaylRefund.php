<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Listeners;

use LeviLabs\LaravelWayl\Data\Refunds\RefundData;
use LeviLabs\LaravelWayl\Events\Refunds\RefundCancelled;
use LeviLabs\LaravelWayl\Events\Refunds\RefundCreated;
use LeviLabs\LaravelWayl\Events\Refunds\RefundStatusChecked;
use LeviLabs\LaravelWayl\Models\WaylLink;
use LeviLabs\LaravelWayl\Models\WaylRefund;

final class PersistWaylRefund
{
    public function onCreated(RefundCreated $event): void
    {
        $this->persist($event->refund, $event->merchant);
    }

    public function onStatusChecked(RefundStatusChecked $event): void
    {
        $this->persist($event->refund, $event->merchant);
    }

    public function onCancelled(RefundCancelled $event): void
    {
        $this->persist($event->refund, $event->merchant);
    }

    private function persist(RefundData $refund, string $merchant): void
    {
        $link = null;

        if ($refund->linkId !== null) {
            $link = WaylLink::query()->where('wayl_id', $refund->linkId)->first();
        }

        if ($link === null && $refund->referenceId !== null) {
            $link = WaylLink::query()
                ->where('merchant', $merchant)
                ->where('reference_id', $refund->referenceId)
                ->first();
        }

        WaylRefund::query()->updateOrCreate(
            ['refund_id' => $refund->id],
            array_filter([
                'link_id' => $link?->id,
                'merchant' => $merchant,
                'reference_id' => $refund->referenceId,
                'wayl_link_id' => $refund->linkId,
                'reason' => $refund->reason !== '' ? $refund->reason : null,
                'amount' => $refund->amount,
                'initiated_by' => $refund->initiatedBy,
                'status' => $refund->status,
                'refund_method' => $refund->refundMethod,
                'account_number' => $refund->accountNumber,
            ], fn (mixed $value): bool => $value !== null),
        );
    }
}
