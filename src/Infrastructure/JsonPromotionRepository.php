<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Model\Promotion;
use App\Domain\Repository\PromotionRepositoryInterface;
use App\Support\Clock;

/**
 * Reads promotions from a JSON file.
 *
 * Each record has either absolute dates ("startsAt"/"endsAt", e.g. from an
 * importer) or dates relative to today ("startOffsetDays"/"endOffsetDays"),
 * which keeps the bundled demo data current forever.
 */
final class JsonPromotionRepository implements PromotionRepositoryInterface
{
    /** @var list<Promotion>|null */
    private ?array $promotions = null;

    /** @param bool $optional a missing file means "no promotions" (e.g. importer not run yet) */
    public function __construct(
        private readonly string $file,
        private readonly Clock $clock,
        private readonly bool $optional = false,
    ) {
    }

    public function findAll(): array
    {
        if ($this->promotions !== null) {
            return $this->promotions;
        }

        $today = $this->clock->today();
        $this->promotions = [];
        $rows = $this->optional && !is_file($this->file) ? [] : JsonFile::read($this->file);
        foreach ($rows as $row) {
            $this->promotions[] = new Promotion(
                id: (string) $row['id'],
                productKey: (string) $row['product'],
                name: (string) $row['name'],
                brand: (string) ($row['brand'] ?? ''),
                categoryId: (string) $row['category'],
                storeId: (string) $row['store'],
                price: (float) $row['price'],
                regularPrice: (float) $row['regularPrice'],
                quantity: (float) ($row['quantity'] ?? 1),
                unit: (string) ($row['unit'] ?? Promotion::UNIT_PIECE),
                packLabel: (string) ($row['pack'] ?? ''),
                mechanic: isset($row['mechanic']) ? (string) $row['mechanic'] : null,
                minQuantity: (int) ($row['minQuantity'] ?? 1),
                loyaltyCard: (bool) ($row['loyaltyCard'] ?? false),
                startsAt: $this->date($row, 'startsAt', 'startOffsetDays', $today, 0)->setTime(0, 0),
                endsAt: $this->date($row, 'endsAt', 'endOffsetDays', $today, 7)->setTime(23, 59, 59),
                zones: array_values(array_map('strval', $row['zones'] ?? ['FR'])),
                source: (string) ($row['source'] ?? Promotion::SOURCE_DEMO),
                place: isset($row['place']) ? (string) $row['place'] : null,
            );
        }

        return $this->promotions;
    }

    /** @param array<string, mixed> $row */
    private function date(array $row, string $absoluteKey, string $offsetKey, \DateTimeImmutable $today, int $defaultOffset): \DateTimeImmutable
    {
        if (isset($row[$absoluteKey])) {
            return new \DateTimeImmutable((string) $row[$absoluteKey]);
        }

        return $today->modify(sprintf('%+d days', (int) ($row[$offsetKey] ?? $defaultOffset)));
    }
}
