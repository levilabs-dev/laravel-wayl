<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LeviLabs\LaravelWayl\Enums\RefundMethod;
use LeviLabs\LaravelWayl\Enums\RefundStatus;

/**
 * @property int|null $link_id
 * @property string $merchant
 * @property string $refund_id
 * @property string|null $reference_id
 * @property string|null $wayl_link_id
 * @property string $reason
 * @property int $amount
 * @property string|null $initiated_by
 * @property RefundStatus $status
 * @property RefundMethod|null $refund_method
 * @property string|null $account_number
 */
class WaylRefund extends Model
{
    protected $table = 'wayl_refunds';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => RefundStatus::class,
            'refund_method' => RefundMethod::class,
        ];
    }

    /**
     * @return BelongsTo<WaylLink, $this>
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(WaylLink::class, 'link_id');
    }
}
