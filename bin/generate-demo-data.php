<?php

/*
 * Generates realistic demo promotions into data/promotions.json.
 *
 * Dates are written relative to "today" (startOffsetDays / endOffsetDays) so
 * the demo always shows current offers. Prices follow each store's price
 * index so comparisons look plausible. Output is deterministic for a seed.
 *
 * Usage: php bin/generate-demo-data.php [seed]
 */

declare(strict_types=1);

$root = dirname(__DIR__);
mt_srand((int) ($argv[1] ?? 2026));

$stores = json_decode((string) file_get_contents("$root/data/stores.json"), true, 512, JSON_THROW_ON_ERROR);
$regions = json_decode((string) file_get_contents("$root/data/regions.json"), true, 512, JSON_THROW_ON_ERROR);

// [key, name, brand, category, quantity, unit, pack label, average regular price]
$products = [
    ['pommes-golden-1kg', 'Pommes Golden', 'Origine France', 'fruits-legumes', 1, 'kg', '1 kg', 2.49],
    ['bananes-1kg', 'Bananes', 'Origine Antilles', 'fruits-legumes', 1, 'kg', '1 kg', 1.99],
    ['tomates-grappe-1kg', 'Tomates grappe', 'Origine France', 'fruits-legumes', 1, 'kg', '1 kg', 3.49],
    ['carottes-1kg', 'Carottes', 'Origine France', 'fruits-legumes', 1, 'kg', '1 kg', 1.39],
    ['avocats-x2', 'Avocats mûrs à point', 'Origine Pérou', 'fruits-legumes', 2, 'piece', 'x2', 2.49],
    ['clementines-2kg', 'Clémentines', 'Origine Espagne', 'fruits-legumes', 2, 'kg', '2 kg', 4.99],
    ['pommes-de-terre-2-5kg', 'Pommes de terre', 'Origine France', 'fruits-legumes', 2.5, 'kg', '2,5 kg', 3.29],
    ['salade-batavia', 'Salade Batavia', 'Origine France', 'fruits-legumes', 1, 'piece', 'la pièce', 0.99],
    ['courgettes-1kg', 'Courgettes', 'Origine Espagne', 'fruits-legumes', 1, 'kg', '1 kg', 2.79],

    ['filets-poulet-1kg', 'Filets de poulet', 'Le Gaulois', 'viandes-poissons', 1, 'kg', '1 kg', 12.49],
    ['steaks-haches-x10', 'Steaks hachés 5 % MG x10', 'Charal', 'viandes-poissons', 1, 'kg', '10 x 100 g', 13.49],
    ['paves-saumon-x2', 'Pavés de saumon x2', 'Odyssée', 'viandes-poissons', 0.25, 'kg', '250 g', 6.99],
    ['jambon-blanc-x4', 'Jambon Le Bon Paris x4', 'Herta', 'viandes-poissons', 0.16, 'kg', '160 g', 3.39],
    ['lardons-fumes', 'Lardons fumés', 'Herta', 'viandes-poissons', 0.2, 'kg', '2 x 100 g', 2.49],
    ['crevettes-cuites', 'Crevettes cuites décortiquées', 'Odyssée', 'viandes-poissons', 0.3, 'kg', '300 g', 6.49],
    ['cotes-porc-x4', 'Côtes de porc échine x4', 'Origine France', 'viandes-poissons', 0.8, 'kg', '800 g', 7.99],

    ['lait-demi-ecreme-6x1l', 'Lait demi-écrémé UHT', 'Lactel', 'cremerie', 6, 'l', '6 x 1 L', 7.69],
    ['beurre-doux-250g', 'Beurre doux', 'Président', 'cremerie', 0.25, 'kg', '250 g', 3.25],
    ['oeufs-plein-air-x12', 'Œufs frais plein air x12', 'Loué', 'cremerie', 12, 'piece', 'x12', 4.39],
    ['yaourts-nature-x16', 'Yaourts nature x16', 'Danone', 'cremerie', 2, 'kg', '16 x 125 g', 4.19],
    ['emmental-rape-200g', 'Emmental râpé', 'Entremont', 'cremerie', 0.2, 'kg', '200 g', 2.69],
    ['camembert', 'Camembert', 'Président', 'cremerie', 0.25, 'kg', '250 g', 2.39],
    ['creme-fraiche-epaisse', 'Crème fraîche épaisse 30 %', 'Elle & Vire', 'cremerie', 0.2, 'l', '20 cl', 1.95],
    ['comte-aop', 'Comté AOP 12 mois', 'Juraflore', 'cremerie', 0.25, 'kg', '250 g', 5.49],

    ['spaghetti-500g', 'Spaghetti n°5', 'Barilla', 'epicerie-salee', 0.5, 'kg', '500 g', 1.49],
    ['riz-basmati-1kg', 'Riz basmati', 'Taureau Ailé', 'epicerie-salee', 1, 'kg', '1 kg', 3.69],
    ['huile-olive-1l', 'Huile d\'olive vierge extra', 'Puget', 'epicerie-salee', 1, 'l', '1 L', 10.99],
    ['thon-albacore-3x93g', 'Thon albacore au naturel', 'Petit Navire', 'epicerie-salee', 0.279, 'kg', '3 x 93 g', 6.49],
    ['sauce-tomate-basilic', 'Sauce tomate basilic', 'Panzani', 'epicerie-salee', 0.4, 'kg', '400 g', 1.99],
    ['chips-nature-250g', 'Chips nature', 'Lay\'s', 'epicerie-salee', 0.25, 'kg', '250 g', 2.99],
    ['mayonnaise-dijon', 'Mayonnaise de Dijon', 'Amora', 'epicerie-salee', 0.47, 'kg', '470 g', 2.95],
    ['cassoulet-1-2kg', 'Cassoulet au confit de canard', 'William Saurin', 'epicerie-salee', 1.2, 'kg', '1,2 kg', 7.49],

    ['nutella-750g', 'Pâte à tartiner noisettes cacao', 'Nutella', 'epicerie-sucree', 0.75, 'kg', '750 g', 5.69],
    ['cafe-moulu-2x250g', 'Café moulu Classique', 'Carte Noire', 'epicerie-sucree', 0.5, 'kg', '2 x 250 g', 8.49],
    ['capsules-espresso-x20', 'Capsules espresso Ristretto x20', 'L\'Or', 'epicerie-sucree', 20, 'piece', '20 capsules', 6.29],
    ['cereales-chocapic', 'Céréales Chocapic', 'Nestlé', 'epicerie-sucree', 0.43, 'kg', '430 g', 3.59],
    ['biscuits-prince', 'Biscuits Prince chocolat', 'LU', 'epicerie-sucree', 0.3, 'kg', '300 g', 2.29],
    ['chocolat-lait-5x100g', 'Chocolat au lait du Pays Alpin', 'Milka', 'epicerie-sucree', 0.5, 'kg', '5 x 100 g', 5.49],
    ['confiture-fraises', 'Confiture extra fraises', 'Bonne Maman', 'epicerie-sucree', 0.37, 'kg', '370 g', 3.09],
    ['sucre-poudre-1kg', 'Sucre en poudre', 'Béghin Say', 'epicerie-sucree', 1, 'kg', '1 kg', 1.65],

    ['coca-cola-6x1-5l', 'Coca-Cola Original', 'Coca-Cola', 'boissons', 9, 'l', '6 x 1,5 L', 11.49],
    ['eau-source-6x1-5l', 'Eau de source', 'Cristaline', 'boissons', 9, 'l', '6 x 1,5 L', 2.09],
    ['evian-6x1-5l', 'Eau minérale naturelle', 'Evian', 'boissons', 9, 'l', '6 x 1,5 L', 4.49],
    ['jus-orange-1l', 'Jus d\'orange sans pulpe', 'Tropicana', 'boissons', 1, 'l', '1 L', 3.39],
    ['biere-20x25cl', 'Bière blonde 5 %', 'Heineken', 'boissons', 5, 'l', '20 x 25 cl', 14.49],
    ['bordeaux-rouge', 'Bordeaux rouge AOC', 'Mouton Cadet', 'boissons', 0.75, 'l', '75 cl', 9.49],
    ['champagne-brut', 'Champagne brut', 'Nicolas Feuillatte', 'boissons', 0.75, 'l', '75 cl', 29.99],

    ['pizza-4-fromages', 'Pizza 4 fromages Four à Pierre', 'Buitoni', 'surgeles', 0.35, 'kg', '350 g', 3.89],
    ['frites-1kg', 'Frites Golden Long', 'McCain', 'surgeles', 1, 'kg', '1 kg', 3.59],
    ['magnum-x4', 'Glaces Magnum Classic x4', 'Magnum', 'surgeles', 0.44, 'l', '4 x 110 ml', 5.29],
    ['batonnets-poisson', 'Bâtonnets de colin d\'Alaska x18', 'Findus', 'surgeles', 0.45, 'kg', '450 g', 4.89],
    ['poelee-legumes', 'Poêlée de légumes grillés', 'Bonduelle', 'surgeles', 0.75, 'kg', '750 g', 3.69],

    ['dentifrice-total', 'Dentifrice Total', 'Colgate', 'hygiene-beaute', 0.075, 'l', '75 ml', 3.59],
    ['gel-douche', 'Gel douche Nourishing', 'Dove', 'hygiene-beaute', 0.25, 'l', '250 ml', 3.19],
    ['shampooing-antipelliculaire', 'Shampooing antipelliculaire', 'Head & Shoulders', 'hygiene-beaute', 0.27, 'l', '270 ml', 4.99],
    ['papier-toilette-x12', 'Papier toilette confort x12', 'Lotus', 'hygiene-beaute', 12, 'piece', '12 rouleaux', 6.79],
    ['lames-fusion5-x4', 'Lames de rasoir Fusion5 x4', 'Gillette', 'hygiene-beaute', 4, 'piece', 'x4', 18.49],
    ['deodorant-150ml', 'Déodorant Invisible Black & White', 'Nivea', 'hygiene-beaute', 0.15, 'l', '150 ml', 3.29],

    ['lessive-liquide-40', 'Lessive liquide 40 lavages', 'Ariel', 'entretien', 40, 'piece', '40 lavages', 16.49],
    ['liquide-vaisselle', 'Liquide vaisselle citron', 'Paic', 'entretien', 0.75, 'l', '750 ml', 2.39],
    ['tablettes-lave-vaisselle-x40', 'Tablettes lave-vaisselle Quantum x40', 'Finish', 'entretien', 40, 'piece', 'x40', 13.49],
    ['essuie-tout-x6', 'Essuie-tout x6', 'Okay', 'entretien', 6, 'piece', '6 rouleaux', 5.69],
    ['nettoyant-multi-usages', 'Nettoyant multi-usages', 'Mr Propre', 'entretien', 1, 'l', '1 L', 3.29],

    ['couches-t4-x46', 'Couches Baby-Dry taille 4', 'Pampers', 'bebe', 46, 'piece', 'x46', 16.49],
    ['lingettes-4x52', 'Lingettes Sensitive', 'Pampers', 'bebe', 208, 'piece', '4 x 52', 8.29],
    ['lait-2e-age-800g', 'Lait 2e âge', 'Guigoz', 'bebe', 0.8, 'kg', '800 g', 20.49],
    ['petits-pots-legumes', 'Petits pots légumes dès 6 mois', 'Blédina', 'bebe', 0.4, 'kg', '2 x 200 g', 2.89],

    ['croquettes-chat-1-5kg', 'Croquettes chat stérilisé', 'Purina One', 'animaux', 1.5, 'kg', '1,5 kg', 16.49],
    ['patee-chien-4x400g', 'Pâtée chien au bœuf', 'Pedigree', 'animaux', 1.6, 'kg', '4 x 400 g', 5.79],
    ['litiere-agglomerante-10l', 'Litière agglomérante', 'Catsan', 'animaux', 10, 'l', '10 L', 9.99],

    ['ecouteurs-bluetooth', 'Écouteurs sans fil Tune Beam', 'JBL', 'electronique', 1, 'piece', 'la paire', 79.99],
    ['tv-55-4k', 'Téléviseur 55" 4K UHD', 'Samsung', 'electronique', 1, 'piece', '138 cm', 549.00],
    ['smartphone-128go', 'Smartphone Redmi Note 128 Go', 'Xiaomi', 'electronique', 1, 'piece', '128 Go', 229.00],
    ['tablette-11', 'Tablette Galaxy Tab 64 Go', 'Samsung', 'electronique', 1, 'piece', '11"', 229.00],
    ['enceinte-bluetooth', 'Enceinte Bluetooth Flip', 'JBL', 'electronique', 1, 'piece', 'la pièce', 119.00],
    ['batterie-externe', 'Batterie externe 10 000 mAh', 'Anker', 'electronique', 1, 'piece', 'la pièce', 29.99],

    ['friteuse-sans-huile', 'Friteuse sans huile Easy Fry 4,2 L', 'Moulinex', 'electromenager', 1, 'piece', 'la pièce', 99.99],
    ['aspirateur-balai', 'Aspirateur balai sans fil', 'Rowenta', 'electromenager', 1, 'piece', 'la pièce', 219.00],
    ['machine-capsules', 'Machine à café Dolce Gusto', 'Krups', 'electromenager', 1, 'piece', 'la pièce', 79.99],
    ['micro-ondes-20l', 'Micro-ondes 20 L', 'Whirlpool', 'electromenager', 1, 'piece', 'la pièce', 89.99],
    ['bouilloire', 'Bouilloire 1,7 L', 'Philips', 'electromenager', 1, 'piece', 'la pièce', 34.99],

    ['ampoules-led-x3', 'Ampoules LED E27 x3', 'Philips', 'maison-jardin', 3, 'piece', 'x3', 9.99],
    ['piles-aa-x12', 'Piles alcalines AA x12', 'Duracell', 'maison-jardin', 12, 'piece', 'x12', 13.49],
    ['poele-28cm', 'Poêle Ingenio 28 cm', 'Tefal', 'maison-jardin', 1, 'piece', '28 cm', 34.99],
    ['couette-240', 'Couette tempérée 240 x 260', 'Dodo', 'maison-jardin', 1, 'piece', '240 x 260 cm', 59.99],
];

$bigTicket = ['electronique', 'electromenager', 'maison-jardin'];
$idfDepartments = array_column($regions[0]['departments'], 'code');
$otherRegions = array_column(array_slice($regions, 1), 'code');
$allDepartments = array_merge(...array_map(static fn (array $r): array => array_column($r['departments'], 'code'), $regions));

function pick(array $values): mixed
{
    return $values[mt_rand(0, count($values) - 1)];
}

/** Shelf-style prices: 2,49 / 12,99 / 529,99. */
function niceRound(float $price): float
{
    return round(match (true) {
        $price < 10 => floor($price * 10) / 10 + 0.09,
        $price < 100 => floor($price) + 0.99,
        default => floor($price / 10) * 10 + 9.99,
    }, 2);
}

/** @return array{?string, float, int, bool} [label, price factor, min quantity, loyalty card] */
function pickMechanic(array $store, bool $bigTicket): array
{
    $roll = mt_rand(1, 100);
    if ($store['loyalty'] !== null && $roll > ($bigTicket ? 75 : 78)) {
        $pct = pick($bigTicket ? [10, 15, 20, 25] : [20, 25, 30, 34, 40, 50]);

        return ["-{$pct} % {$store['loyalty']}", 1 - $pct / 100, 1, true];
    }
    if (!$bigTicket) {
        if ($roll <= 12) {
            return ['2e à -50 %', 0.75, 2, false];
        }
        if ($roll <= 18) {
            return ['2e à -70 %', 0.65, 2, false];
        }
        if ($roll <= 25) {
            return ['3 pour le prix de 2', 2 / 3, 3, false];
        }
        if ($roll <= 30) {
            return ['1 acheté = 1 offert', 0.5, 2, false];
        }
    }
    $pct = pick($bigTicket ? [10, 15, 20, 25, 30] : [10, 15, 20, 25, 30, 34, 40, 50]);

    return [null, 1 - $pct / 100, 1, false];
}

/** @return list<string> */
function pickZones(array $idfDepartments, array $otherRegions, array $allDepartments): array
{
    $roll = mt_rand(1, 100);
    if ($roll <= 55) {
        return ['FR'];
    }
    if ($roll <= 75) {
        return ['IDF'];
    }
    if ($roll <= 85) {
        return [pick($otherRegions)];
    }
    $pool = mt_rand(1, 100) <= 70 ? $idfDepartments : $allDepartments;
    shuffle($pool);

    return array_slice($pool, 0, mt_rand(1, 3));
}

$promotions = [];
foreach ($products as [$key, $name, $brand, $category, $quantity, $unit, $pack, $price]) {
    $eligible = array_values(array_filter(
        $stores,
        static fn (array $s): bool => $s['categories'] === '*' || in_array($category, $s['categories'], true),
    ));
    shuffle($eligible);
    $isBigTicket = in_array($category, $bigTicket, true);
    $count = min(count($eligible), $isBigTicket ? mt_rand(1, 3) : mt_rand(2, 6));

    foreach (array_slice($eligible, 0, $count) as $store) {
        $regular = niceRound($price * $store['priceIndex'] * mt_rand(95, 105) / 100);
        [$mechanic, $factor, $minQuantity, $loyalty] = pickMechanic($store, $isBigTicket);
        $start = -mt_rand(0, 6);

        $promotions[] = [
            'id' => $store['id'] . '-' . $key,
            'product' => $key,
            'name' => $name,
            'brand' => $brand,
            'category' => $category,
            'store' => $store['id'],
            'price' => round($regular * $factor, 2),
            'regularPrice' => $regular,
            'quantity' => $quantity,
            'unit' => $unit,
            'pack' => $pack,
            'mechanic' => $mechanic,
            'minQuantity' => $minQuantity,
            'loyaltyCard' => $loyalty,
            'startOffsetDays' => $start,
            'endOffsetDays' => $start + mt_rand(6, 20),
            'zones' => pickZones($idfDepartments, $otherRegions, $allDepartments),
        ];
    }
}

file_put_contents(
    "$root/data/promotions.json",
    json_encode($promotions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
);
printf("%d promotions written for %d products.\n", count($promotions), count($products));
