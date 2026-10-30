<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Data\Links;

use InvalidArgumentException;
use LeviLabs\LaravelWayl\Enums\LineItemType;

/**
 * One row of a link's `lineItem` array. If any rows are sent, they must
 * add up to the link total. Wayl does not echo line items back.
 */
final readonly class LineItem
{
    public function __construct(
        public string $label,
        public int $amount,
        public LineItemType $type,
    ) {
        $length = mb_strlen($label);

        if ($length < 3 || $length > 255) {
            throw new InvalidArgumentException("Wayl line item label must be between 3 and 255 characters, got {$length}.");
        }

        if ($amount < 1) {
            throw new InvalidArgumentException("Wayl line item amount must be at least 1 IQD, got {$amount}.");
        }
    }

    /**
     * @return array{label: string, amount: int, type: string}
     */
    public function toApiArray(): array
    {
        return [
            'label' => $this->label,
            'amount' => $this->amount,
            'type' => $this->type->value,
        ];
    }
}
