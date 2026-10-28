<?php

/*
 * Shows what each promotion source currently provides and lists the
 * problems in data/sources/manual.csv. Run it after editing the CSV:
 *   php bin/check-sources.php
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/src/bootstrap.php';

use App\Domain\Model\Promotion;
use App\Infrastructure\CompositePromotionRepository;
use App\Infrastructure\CsvPromotionRepository;
use App\Infrastructure\JsonCatalogRepository;
use App\Support\Clock;

$config = require APP_ROOT . '/config/app.php';
$clock = new Clock();
$now = $clock->now();
$repository = CompositePromotionRepository::fromConfig($config, new JsonCatalogRepository($config['data_dir']), $clock);

$exitCode = 0;
foreach ($repository->sources() as $name => $source) {
    $all = $source->findAll();
    $active = array_filter($all, static fn (Promotion $p): bool => $p->isActiveAt($now));
    printf("%-12s %4d promotions, dont %d en cours\n", $name, count($all), count($active));

    if ($source instanceof CsvPromotionRepository) {
        foreach ($source->errors() as $error) {
            echo "             ⚠ {$error}\n";
            $exitCode = 1;
        }
    }
}
if (!$config['demo_data']) {
    echo "(données de démonstration désactivées)\n";
}
exit($exitCode);
