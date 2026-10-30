<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LeviLabs\LaravelWayl\Enums\LinkStatus;

/**
 * @property string $merchant
 * @property string $reference_id
 * @property string $wayl_id
 * @property string|null $code
 * @property LinkStatus $status
 * @property int $total
 * @property string $currency
 * @property string|null $type
 * @property string|null $payment_method
 * @property bool|null $reversable
 * @property string|null $custom_parameter
 * @property string|null $url
 * @property string|null $webhook_url
 * @property string|null $redirection_url
 * @property string|null $link_expires_in
 * @property string|null $environment
 * @property string|null $webhook_secret
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $gateway_created_at
 * @property CarbonImmutable|null $gateway_updated_at
 * @property CarbonImmutable|null $checked_at
 * @property array<string, mixed>|null $meta
 */
class WaylLink extends Model
{
    protected $table = 'wayl_links';

    protected $guarded = ['id'];

    protected $hidden = [
        'webhook_secret',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LinkStatus::class,
            'total' => 'integer',
            'reversable' => 'boolean',
            'webhook_secret' => 'encrypted',
            'completed_at' => 'immutable_datetime',
            'gateway_created_at' => 'immutable_datetime',
            'gateway_updated_at' => 'immutable_datetime',
            'checked_at' => 'immutable_datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<WaylRefund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(WaylRefund::class, 'link_id');
    }
}
