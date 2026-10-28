<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenPrices;

use App\Domain\Model\Promotion;
use App\Support\Text;

/**
 * Turns one Open Prices price record (https://prices.openfoodfacts.org) into
 * a promotion record in the data/promotions.json format, or explains why it
 * cannot be used. Pure function of its input: no network, easy to test.
 *
 * Contributor usernames are deliberately not copied.
 */
final class OpenPricesMapper
{
    /** Regex on the normalized OSM brand + shop name => store id in data/stores.json. */
    private const STORES = [
        '/leclerc/' => 'leclerc',
        '/carrefour/' => 'carrefour',
        '/auchan/' => 'auchan',
        '/intermarche/' => 'intermarche',
        '/\blidl\b/' => 'lidl',
        '/\baldi\b/' => 'aldi',
        '/\b(super u|hyper u|u express|marche u|systeme u)\b/' => 'super-u',
        '/\bmonop/' => 'monoprix',
        '/franprix/' => 'franprix',
        '/\bnetto\b/' => 'netto',
        '/grand frais/' => 'grand-frais',
        '/\bpicard\b/' => 'picard',
    ];

    /** Shop-specific markdowns, not promotions people can plan around. */
    private const SKIPPED_DISCOUNTS = ['EXPIRES_SOON', 'SECOND_HAND'];

    private const MECHANICS = [
        'QUANTITY' => 'Remise par quantité',
        'LOYALTY_PROGRAM' => 'Avec la carte de fidélité',
        'SEASONAL' => 'Promo saisonnière',
    ];

    /** Open Food Facts category tags => our category, first match wins (order matters). */
    private const CATEGORIES = [
        'bebe' => ['en:baby-foods', 'en:baby-milks', 'en:infant-formulas'],
        'animaux' => ['en:pet-food', 'en:cat-food', 'en:dog-food'],
        'surgeles' => ['en:frozen-foods'],
        'cremerie' => ['en:dairies', 'en:cheeses', 'en:eggs', 'en:milks', 'en:butters', 'en:yogurts'],
        'viandes-poissons' => ['en:meats', 'en:fishes', 'en:seafood', 'en:poultries', 'en:prepared-meats', 'en:hams'],
        'fruits-legumes' => ['en:fresh-fruits', 'en:fresh-vegetables', 'en:fruits', 'en:vegetables'],
        'epicerie-sucree' => [
            'en:sweet-snacks', 'en:chocolates', 'en:biscuits', 'en:breakfast-cereals', 'en:sweet-spreads', 'en:jams',
            'en:sugars', 'en:confectioneries', 'en:coffees', 'en:teas', 'en:desserts', 'en:cakes',
        ],
        'boissons' => ['en:beverages', 'en:waters', 'en:alcoholic-beverages'],
    ];

    /** Non-food Open Food Facts sister databases. */
    private const PRODUCT_DATABASES = ['obf' => 'hygiene-beaute', 'opff' => 'animaux', 'opf' => 'entretien'];

    /** Loose fruit & vegetables recorded without a barcode (category_tag). */
    private const PRODUCE = [
        'en:apples' => 'Pommes', 'en:bananas' => 'Bananes', 'en:tomatoes' => 'Tomates', 'en:carrots' => 'Carottes',
        'en:potatoes' => 'Pommes de terre', 'en:onions' => 'Oignons', 'en:lemons' => 'Citrons', 'en:oranges' => 'Oranges',
        'en:clementines' => 'Clémentines', 'en:mandarins' => 'Mandarines', 'en:avocados' => 'Avocats',
        'en:zucchini' => 'Courgettes', 'en:courgettes' => 'Courgettes', 'en:cucumbers' => 'Concombres',
        'en:lettuces' => 'Salade', 'en:mushrooms' => 'Champignons', 'en:pears' => 'Poires', 'en:grapes' => 'Raisin',
        'en:kiwis' => 'Kiwis', 'en:strawberries' => 'Fraises', 'en:leeks' => 'Poireaux', 'en:bell-peppers' => 'Poivrons',
        'en:eggplants' => 'Aubergines', 'en:aubergines' => 'Aubergines', 'en:garlic' => 'Ail', 'en:peaches' => 'Pêches',
        'en:nectarines' => 'Nectarines', 'en:melons' => 'Melons', 'en:cauliflowers' => 'Chou-fleur', 'en:broccoli' => 'Brocolis',
    ];

    /** @param int $validityDays how long a price seen on day D is shown (no end date in Open Prices) */
    public function __construct(private readonly int $validityDays = 7)
    {
    }

    /**
     * @param array<string, mixed> $item   one element of GET /api/v1/prices "items"
     * @param string|null          $reason set to why the item was skipped
     *
     * @return array<string, mixed>|null
     */
    public function map(array $item, ?string &$reason = null): ?array
    {
        $reason = null;
        $location = is_array($item['location'] ?? null) ? $item['location'] : [];

        if (($location['osm_address_country_code'] ?? '') !== 'FR' || ($item['currency'] ?? '') !== 'EUR') {
            $reason = 'hors France';
        } elseif (empty($item['price_is_discounted']) || in_array($item['discount_type'] ?? null, self::SKIPPED_DISCOUNTS, true)) {
            $reason = 'pas une promotion';
        } elseif ((float) ($item['price'] ?? 0) <= 0 || (float) ($item['price_without_discount'] ?? 0) <= (float) ($item['price'] ?? 0)) {
            $reason = 'prix barré absent';
        } elseif (($store = $this->store($location)) === null) {
            $reason = 'enseigne non suivie';
        } elseif (($department = self::department((string) ($location['osm_address_postcode'] ?? ''))) === null) {
            $reason = 'code postal inconnu';
        } elseif (($product = $this->product($item)) === null) {
            $reason = 'produit non identifié';
        } elseif (($date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($item['date'] ?? ''))) === false) {
            $reason = 'date invalide';
        }
        if ($reason !== null) {
            return null;
        }

        return $product + [
            'id' => 'op-' . $item['id'],
            'store' => $store,
            'price' => round((float) $item['price'], 2),
            'regularPrice' => round((float) $item['price_without_discount'], 2),
            'mechanic' => self::MECHANICS[$item['discount_type'] ?? ''] ?? null,
            'minQuantity' => 1,
            'loyaltyCard' => ($item['discount_type'] ?? null) === 'LOYALTY_PROGRAM',
            'startsAt' => $date->format('Y-m-d'),
            'endsAt' => $date->modify('+' . max(0, $this->validityDays - 1) . ' days')->format('Y-m-d'),
            'zones' => [$department],
            'source' => Promotion::SOURCE_OPEN_PRICES,
            'place' => implode(' · ', array_filter([$location['osm_name'] ?? null, $location['osm_address_city'] ?? null])),
        ];
    }

    /** French postcode => department code ("91", "2A", "974"). */
    public static function department(string $postcode): ?string
    {
        if (!preg_match('/^\d{5}$/', $postcode)) {
            return null;
        }
        if (str_starts_with($postcode, '20')) {
            return (int) $postcode < 20200 ? '2A' : '2B';
        }

        return str_starts_with($postcode, '97') ? substr($postcode, 0, 3) : substr($postcode, 0, 2);
    }

    /** @param array<string, mixed> $location */
    private function store(array $location): ?string
    {
        $haystack = Text::normalize(($location['osm_brand'] ?? '') . ' ' . ($location['osm_name'] ?? ''));
        foreach (self::STORES as $pattern => $storeId) {
            if (preg_match($pattern, $haystack)) {
                return $storeId;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>|null product fields of the record
     */
    private function product(array $item): ?array
    {
        $product = is_array($item['product'] ?? null) ? $item['product'] : null;
        if ($product !== null) {
            $name = trim((string) ($product['product_name'] ?? ''));
            $code = (string) ($product['code'] ?? $item['product_code'] ?? '');
            if ($name === '' || !preg_match('/^\d{8,14}$/', $code)) {
                return null;
            }
            [$quantity, $unit] = self::quantity($product['product_quantity'] ?? null, $product['product_quantity_unit'] ?? null);

            return [
                'product' => $code,
                'name' => mb_substr($name, 0, 80),
                'brand' => self::brand((string) ($product['brands'] ?? '')),
                'category' => $this->category($product),
                'quantity' => $quantity,
                'unit' => $unit,
                'pack' => trim((string) ($product['quantity'] ?? '')),
            ];
        }

        $tag = (string) ($item['category_tag'] ?? '');
        if (!isset(self::PRODUCE[$tag])) {
            return null;
        }
        $perKilo = ($item['price_per'] ?? '') === 'KILOGRAM';

        return [
            'product' => 'vrac-' . substr($tag, 3),
            'name' => self::PRODUCE[$tag],
            'brand' => '',
            'category' => 'fruits-legumes',
            'quantity' => 1,
            'unit' => $perKilo ? Promotion::UNIT_KG : Promotion::UNIT_PIECE,
            'pack' => $perKilo ? 'le kg' : 'la pièce',
        ];
    }

    /** @param array<string, mixed> $product */
    private function category(array $product): string
    {
        $database = (string) ($product['source'] ?? 'off');
        if (isset(self::PRODUCT_DATABASES[$database])) {
            return self::PRODUCT_DATABASES[$database];
        }
        $tags = is_array($product['categories_tags'] ?? null) ? $product['categories_tags'] : [];
        foreach (self::CATEGORIES as $category => $candidates) {
            if (array_intersect($candidates, $tags) !== []) {
                return $category;
            }
        }

        return 'epicerie-salee';
    }

    /** First listed brand, "PANZANI" shown as "Panzani". */
    private static function brand(string $brands): string
    {
        $brand = trim(explode(',', $brands)[0]);

        return mb_strlen($brand) > 3 && $brand === mb_strtoupper($brand) ? mb_convert_case($brand, MB_CASE_TITLE) : $brand;
    }

    /** @return array{float, string} */
    private static function quantity(mixed $quantity, mixed $unit): array
    {
        $q = is_numeric($quantity) ? (float) $quantity : 0.0;
        if ($q <= 0) {
            return [1.0, Promotion::UNIT_PIECE];
        }

        return match (strtolower((string) $unit)) {
            'g' => [$q / 1000, Promotion::UNIT_KG],
            'kg' => [$q, Promotion::UNIT_KG],
            'ml' => [$q / 1000, Promotion::UNIT_LITRE],
            'cl' => [$q / 100, Promotion::UNIT_LITRE],
            'l' => [$q, Promotion::UNIT_LITRE],
            default => [1.0, Promotion::UNIT_PIECE],
        };
    }
}
