<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Model\Promotion;
use App\Domain\Repository\CatalogRepositoryInterface;
use App\Domain\Repository\PromotionRepositoryInterface;

/**
 * Promotions typed in by hand from store flyers, in data/sources/manual.csv.
 *
 * Semicolon-separated UTF-8 (what Excel/LibreOffice produce in French),
 * decimal comma accepted, dates as 2026-10-28 or 28/10/2026. Lines starting
 * with "#" are ignored. Invalid lines are skipped and reported by errors()
 * (see bin/check-sources.php) so one typo never breaks the site.
 */
final class CsvPromotionRepository implements PromotionRepositoryInterface
{
    public const COLUMNS = [
        'product', 'name', 'brand', 'category', 'store', 'price', 'regular_price', 'quantity', 'unit',
        'pack', 'mechanic', 'min_quantity', 'loyalty_card', 'starts', 'ends', 'zones',
    ];
    private const REQUIRED = ['product', 'name', 'category', 'store', 'price', 'regular_price', 'starts', 'ends'];

    /** @var list<Promotion>|null */
    private ?array $promotions = null;
    /** @var list<string> */
    private array $errors = [];

    public function __construct(
        private readonly string $file,
        private readonly CatalogRepositoryInterface $catalog,
    ) {
    }

    public function findAll(): array
    {
        $this->load();

        return $this->promotions;
    }

    /** @return list<string> human-readable problems, one per skipped line */
    public function errors(): array
    {
        $this->load();

        return $this->errors;
    }

    private function load(): void
    {
        if ($this->promotions !== null) {
            return;
        }
        $this->promotions = [];
        if (!is_file($this->file) || ($handle = fopen($this->file, 'r')) === false) {
            return;
        }

        $header = null;
        $line = 0;
        while (($row = fgetcsv($handle, 0, ';', '"', '')) !== false) {
            $line++;
            if ($line === 1) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]); // UTF-8 BOM added by Excel
            }
            if (trim(implode('', array_map('strval', $row))) === '' || str_starts_with(trim((string) $row[0]), '#')) {
                continue;
            }
            if ($header === null) {
                $header = array_map(static fn ($h): string => strtolower(trim((string) $h)), $row);
                $missing = array_diff(self::REQUIRED, $header);
                if ($missing !== []) {
                    $this->errors[] = "Ligne {$line} : colonnes manquantes : " . implode(', ', $missing);
                    break;
                }
                continue;
            }
            $values = array_pad(array_slice(array_map(static fn ($v): string => trim((string) $v), $row), 0, count($header)), count($header), '');
            try {
                $this->promotions[] = $this->toPromotion(array_combine($header, $values), $line);
            } catch (\InvalidArgumentException $e) {
                $this->errors[] = "Ligne {$line} : " . $e->getMessage();
            }
        }
        fclose($handle);
    }

    /** @param array<string, string> $r */
    private function toPromotion(array $r, int $line): Promotion
    {
        $product = strtolower($r['product']);
        if (!preg_match('/^[a-z0-9-]{1,80}$/', $product)) {
            throw new \InvalidArgumentException("« product » doit être un code-barres ou un identifiant en minuscules sans espace (ex. nutella-750g), reçu « {$r['product']} »");
        }
        if ($r['name'] === '') {
            throw new \InvalidArgumentException('« name » est vide');
        }
        if ($this->catalog->store($r['store']) === null) {
            throw new \InvalidArgumentException("enseigne inconnue « {$r['store']} » (valeurs : " . implode(', ', array_keys($this->catalog->stores())) . ')');
        }
        if ($this->catalog->category($r['category']) === null) {
            throw new \InvalidArgumentException("catégorie inconnue « {$r['category']} » (valeurs : " . implode(', ', array_keys($this->catalog->categories())) . ')');
        }

        $price = $this->number($r['price'], 'price');
        $regular = $this->number($r['regular_price'], 'regular_price');
        if ($price <= 0 || $regular < $price) {
            throw new \InvalidArgumentException("prix incohérents : price {$r['price']} doit être positif et inférieur à regular_price {$r['regular_price']}");
        }

        [$quantity, $unit] = $this->quantity($r['quantity'] ?? '', $r['unit'] ?? '');
        $startsAt = $this->date($r['starts'], 'starts');
        $endsAt = $this->date($r['ends'], 'ends')->setTime(23, 59, 59);
        if ($endsAt < $startsAt) {
            throw new \InvalidArgumentException('« ends » est avant « starts »');
        }

        $zones = preg_split('/[\s,]+/', strtoupper($r['zones'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: ['FR'];
        foreach ($zones as $zone) {
            if ($this->catalog->location($zone) === null) {
                throw new \InvalidArgumentException("zone inconnue « {$zone} » (FR, code région comme IDF, ou département comme 91)");
            }
        }

        return new Promotion(
            id: 'manual-' . $line . '-' . $r['store'] . '-' . $product,
            productKey: $product,
            name: $r['name'],
            brand: $r['brand'] ?? '',
            categoryId: $r['category'],
            storeId: $r['store'],
            price: $price,
            regularPrice: $regular,
            quantity: $quantity,
            unit: $unit,
            packLabel: $r['pack'] ?? '',
            mechanic: ($r['mechanic'] ?? '') !== '' ? $r['mechanic'] : null,
            minQuantity: max(1, (int) ($r['min_quantity'] ?? 1)),
            loyaltyCard: in_array(strtolower($r['loyalty_card'] ?? ''), ['1', 'oui', 'yes', 'true', 'x'], true),
            startsAt: $startsAt,
            endsAt: $endsAt,
            zones: $zones,
            source: Promotion::SOURCE_MANUAL,
        );
    }

    private function number(string $value, string $column): float
    {
        $normalized = str_replace([',', ' ', "\u{00A0}", '€'], ['.', '', '', ''], $value);
        if (!is_numeric($normalized)) {
            throw new \InvalidArgumentException("« {$column} » n'est pas un nombre : « {$value} »");
        }

        return round((float) $normalized, 3);
    }

    /** @return array{float, string} quantity in kg / L / pieces */
    private function quantity(string $quantity, string $unit): array
    {
        if ($quantity === '') {
            return [1.0, Promotion::UNIT_PIECE];
        }
        $q = $this->number($quantity, 'quantity');

        return match (strtolower($unit)) {
            'g' => [$q / 1000, Promotion::UNIT_KG],
            'kg' => [$q, Promotion::UNIT_KG],
            'ml' => [$q / 1000, Promotion::UNIT_LITRE],
            'cl' => [$q / 100, Promotion::UNIT_LITRE],
            'l' => [$q, Promotion::UNIT_LITRE],
            '', 'piece', 'pièce', 'pieces', 'pièces', 'u' => [$q, Promotion::UNIT_PIECE],
            default => throw new \InvalidArgumentException("unité inconnue « {$unit} » (g, kg, ml, cl, l ou piece)"),
        };
    }

    private function date(string $value, string $column): \DateTimeImmutable
    {
        foreach (['!Y-m-d', '!d/m/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false && $date->format(substr($format, 1)) === $value) {
                return $date;
            }
        }
        throw new \InvalidArgumentException("« {$column} » n'est pas une date valide (2026-10-28 ou 28/10/2026) : « {$value} »");
    }
}
