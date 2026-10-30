<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Data\Products;

use Carbon\CarbonImmutable;

/**
 * A Wayl catalog product (Digital, Physical, or Service). Subscription
 * products are excluded by the list endpoint. The single-product response
 * is a subset of the list response, so most fields are nullable.
 */
final readonly class ProductData
{
    /**
     * @param  array<int, array<string, mixed>>  $inventories
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $price,
        public ?string $url,
        public ?string $status,
        public ?string $image,
        public ?string $tags,
        public ?string $description,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
        public ?string $productType,
        public ?string $downloadable,
        public ?string $discountType,
        public ?string $discountExpiry,
        public ?string $discountValue,
        public array $inventories,
        public ?int $quantity,
        public ?bool $unlimited,
        public ?int $notifyOn,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            name: (string) ($data['name'] ?? ''),
            price: (string) ($data['price'] ?? ''),
            url: isset($data['url']) ? (string) $data['url'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            image: isset($data['image']) ? (string) $data['image'] : null,
            tags: isset($data['tags']) ? (string) $data['tags'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            createdAt: isset($data['createdAt']) ? CarbonImmutable::parse((string) $data['createdAt']) : null,
            updatedAt: isset($data['updatedAt']) ? CarbonImmutable::parse((string) $data['updatedAt']) : null,
            productType: isset($data['productType']) ? (string) $data['productType'] : null,
            downloadable: isset($data['downloadable']) ? (string) $data['downloadable'] : null,
            discountType: isset($data['discountType']) ? (string) $data['discountType'] : null,
            discountExpiry: isset($data['discountExpiry']) ? (string) $data['discountExpiry'] : null,
            discountValue: isset($data['discountValue']) ? (string) $data['discountValue'] : null,
            inventories: self::inventories($data['inventories'] ?? []),
            quantity: isset($data['qt']) && is_numeric($data['qt']) ? (int) $data['qt'] : null,
            unlimited: array_key_exists('unlimited', $data) && $data['unlimited'] !== null ? (bool) $data['unlimited'] : null,
            notifyOn: isset($data['notifyOn']) && is_numeric($data['notifyOn']) ? (int) $data['notifyOn'] : null,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, self>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item): self => self::fromArray($item), $items);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'url' => $this->url,
            'status' => $this->status,
            'image' => $this->image,
            'tags' => $this->tags,
            'description' => $this->description,
            'createdAt' => $this->createdAt?->toIso8601String(),
            'updatedAt' => $this->updatedAt?->toIso8601String(),
            'productType' => $this->productType,
            'downloadable' => $this->downloadable,
            'discountType' => $this->discountType,
            'discountExpiry' => $this->discountExpiry,
            'discountValue' => $this->discountValue,
            'inventories' => $this->inventories,
            'qt' => $this->quantity,
            'unlimited' => $this->unlimited,
            'notifyOn' => $this->notifyOn,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function inventories(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                /** @var array<string, mixed> $item */
                $items[] = $item;
            }
        }

        return $items;
    }
}
