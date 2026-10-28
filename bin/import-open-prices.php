<?php

/*
 * Imports recent discounted prices seen in French supermarkets from Open
 * Prices (Open Food Facts, ODbL licence) into data/sources/open-prices.json.
 *
 * Meant to run once a day from cron:
 *   15 6 * * * php /var/www/paniermalin/bin/import-open-prices.php
 *
 * On any network/API error the previous file is kept untouched.
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/src/bootstrap.php';

use App\Infrastructure\JsonCatalogRepository;
use App\Infrastructure\OpenPrices\OpenPricesMapper;

$config = require APP_ROOT . '/config/app.php';
$settings = $config['open_prices'];
$target = $config['data_dir'] . '/sources/open-prices.json';
$catalog = new JsonCatalogRepository($config['data_dir']);
$mapper = new OpenPricesMapper($settings['validity_days']);

// Only prices still displayable today are worth importing.
$oldestDate = (new DateTimeImmutable('today'))->modify('-' . ($settings['validity_days'] - 1) . ' days')->format('Y-m-d');
$context = stream_context_create(['http' => [
    'header' => 'User-Agent: ' . $settings['user_agent'] . "\r\nAccept: application/json\r\n",
    'timeout' => 30,
    'ignore_errors' => true,
]]);

$records = [];
$skipped = [];
$fetched = 0;
for ($page = 1; $page <= $settings['max_pages']; $page++) {
    $url = $settings['api_url'] . '?' . http_build_query([
        'price_is_discounted' => 'true',
        'currency' => 'EUR',
        'order_by' => '-date',
        'size' => 100,
        'page' => $page,
    ]);
    $body = @file_get_contents($url, false, $context);
    $status = isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m) ? (int) $m[1] : 0;
    $data = $body !== false && $status === 200 ? json_decode($body, true) : null;
    if (!is_array($data) || !isset($data['items'])) {
        fwrite(STDERR, "Erreur API Open Prices (page {$page}, HTTP {$status}) : import annulé, fichier précédent conservé.\n");
        exit(1);
    }

    $reachedOldest = false;
    foreach ($data['items'] as $item) {
        if (($item['date'] ?? '') < $oldestDate) {
            $reachedOldest = true;
            break;
        }
        $fetched++;
        $record = $mapper->map($item, $reason);
        if ($record !== null && $catalog->location($record['zones'][0]) === null) {
            [$record, $reason] = [null, 'département hors catalogue'];
        }
        if ($record === null) {
            $skipped[$reason] = ($skipped[$reason] ?? 0) + 1;
            continue;
        }
        // Items come newest first: keep the latest price per product, store and department.
        $key = $record['product'] . '|' . $record['store'] . '|' . $record['zones'][0];
        $records[$key] ??= $record;
    }
    if ($reachedOldest || count($data['items']) < 100) {
        break;
    }
    usleep(300_000); // be gentle with a free community service
}

$json = json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
if (!is_dir(dirname($target))) {
    mkdir(dirname($target), 0775, true);
}
$tmp = $target . '.tmp';
file_put_contents($tmp, $json . "\n");
rename($tmp, $target); // atomic: visitors never read a half-written file

arsort($skipped);
printf("Open Prices : %d prix remisés lus depuis le %s, %d promotions importées.\n", $fetched, $oldestDate, count($records));
foreach ($skipped as $why => $count) {
    printf("  - %d ignorés : %s\n", $count, $why);
}
