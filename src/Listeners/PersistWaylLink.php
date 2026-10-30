<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Listeners;

use LeviLabs\LaravelWayl\Events\Links\LinkCreated;
use LeviLabs\LaravelWayl\Events\Links\LinkStatusChecked;
use LeviLabs\LaravelWayl\Models\WaylLink;

final class PersistWaylLink
{
    public function onCreated(LinkCreated $event): void
    {
        $link = $event->link;

        WaylLink::query()->updateOrCreate(
            [
                'merchant' => $event->merchant,
                'reference_id' => $link->referenceId,
            ],
            array_filter([
                'wayl_id' => $link->id,
                'code' => $link->code,
                'status' => $link->status,
                'total' => $link->total,
                'currency' => $link->currency,
                'type' => $link->type,
                'payment_method' => $link->paymentMethod,
                'reversable' => $link->reversable,
                'custom_parameter' => $link->customParameter,
                'url' => $link->url,
                'webhook_url' => $link->webhookUrl,
                'redirection_url' => $link->redirectionUrl,
                'link_expires_in' => $link->linkExpiresIn,
                'environment' => $event->environment->value,
                'webhook_secret' => $event->webhookSecret,
                'completed_at' => $link->completedAt,
                'gateway_created_at' => $link->createdAt,
                'gateway_updated_at' => $link->updatedAt,
            ], fn (mixed $value): bool => $value !== null),
        );
    }

    public function onStatusChecked(LinkStatusChecked $event): void
    {
        $link = $event->link;

        WaylLink::query()->updateOrCreate(
            [
                'merchant' => $event->merchant,
                'reference_id' => $link->referenceId,
            ],
            array_filter([
                'wayl_id' => $link->id,
                'code' => $link->code,
                'status' => $link->status,
                'total' => $link->total,
                'currency' => $link->currency,
                'type' => $link->type,
                'payment_method' => $link->paymentMethod,
                'reversable' => $link->reversable,
                'custom_parameter' => $link->customParameter,
                'url' => $link->url,
                'webhook_url' => $link->webhookUrl,
                'redirection_url' => $link->redirectionUrl,
                'link_expires_in' => $link->linkExpiresIn,
                'completed_at' => $link->completedAt,
                'gateway_created_at' => $link->createdAt,
                'gateway_updated_at' => $link->updatedAt,
                'checked_at' => now(),
            ], fn (mixed $value): bool => $value !== null),
        );
    }
}
