<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * One promotional offer of one store for one product.
 *
 * $price is the effective price of one item once the mechanic is applied
 * (e.g. "2e à -50 %" on a 4 € item gives 3 € per item when buying 2).
 */
final class Promotion
{
    public const UNIT_KG = 'kg';
    public const UNIT_LITRE = 'l';
    public const UNIT_PIECE = 'piece';

    public const SOURCE_DEMO = 'demo';
    public const SOURCE_MANUAL = 'manual';
    /** Price seen in a shop by an Open Prices contributor: no official end date. */
    public const SOURCE_OPEN_PRICES = 'open-prices';

    /**
     * @param list<string> $zones location codes: "FR", region codes ("IDF") or department codes ("91")
     * @param string|null  $place shop where the price was observed, e.g. "Super U · Louviers"
     */
    public function __construct(
        public readonly string $id,
        public readonly string $productKey,
        public readonly string $name,
        public readonly string $brand,
        public readonly string $categoryId,
        public readonly string $storeId,
        public readonly float $price,
        public readonly float $regularPrice,
        public readonly float $quantity,
        public readonly string $unit,
        public readonly string $packLabel,
        public readonly ?string $mechanic,
        public readonly int $minQuantity,
        public readonly bool $loyaltyCard,
        public readonly \DateTimeImmutable $startsAt,
        public readonly \DateTimeImmutable $endsAt,
        public readonly array $zones,
        public readonly string $source = self::SOURCE_DEMO,
        public readonly ?string $place = null,
    ) {
    }

    /** True when the date is the day the price was seen, not an announced offer period. */
    public function isFieldObservation(): bool
    {
        return $this->source === self::SOURCE_OPEN_PRICES;
    }

    public function discountPercent(): int
    {
        return $this->regularPrice > 0 ? (int) round((1 - $this->price / $this->regularPrice) * 100) : 0;
    }

    public function saving(): float
    {
        return round($this->regularPrice - $this->price, 2);
    }

    /** Price per kg, per litre or per piece. */
    public function unitPrice(): float
    {
        return $this->quantity > 0 ? $this->price / $this->quantity : $this->price;
    }

    public function unitLabel(): string
    {
        return match ($this->unit) {
            self::UNIT_KG => '€/kg',
            self::UNIT_LITRE => '€/L',
            default => '€/pièce',
        };
    }

    public function isActiveAt(\DateTimeImmutable $at): bool
    {
        return $this->startsAt <= $at && $at <= $this->endsAt;
    }

    /** Whole days until the last day of the offer (0 = ends today). */
    public function daysLeft(\DateTimeImmutable $at): int
    {
        $diff = $at->setTime(0, 0)->diff($this->endsAt->setTime(0, 0));

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product' => $this->productKey,
            'name' => $this->name,
            'brand' => $this->brand,
            'category' => $this->categoryId,
            'store' => $this->storeId,
            'price' => $this->price,
            'regularPrice' => $this->regularPrice,
            'discount' => $this->discountPercent(),
            'unitPrice' => round($this->unitPrice(), 2),
            'unit' => $this->unit,
            'pack' => $this->packLabel,
            'mechanic' => $this->mechanic,
            'minQuantity' => $this->minQuantity,
            'loyaltyCard' => $this->loyaltyCard,
            'startsAt' => $this->startsAt->format('Y-m-d'),
            'endsAt' => $this->endsAt->format('Y-m-d'),
            'zones' => $this->zones,
            'source' => $this->source,
            'place' => $this->place,
        ];
    }
}
