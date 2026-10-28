<?php

declare(strict_types=1);

/*
 * Application configuration. Values can be overridden with environment
 * variables on the server (APP_ENV, APP_URL, APP_DEBUG).
 */
return [
    'name' => 'PanierMalin',
    'tagline' => 'Toutes les promos des supermarchés près de chez vous, comparées en un clin d\'œil.',
    'url' => rtrim((string) (getenv('APP_URL') ?: ''), '/'),
    'env' => getenv('APP_ENV') ?: 'prod',
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
    'data_dir' => APP_ROOT . '/data',
    'default_location' => 'IDF',
    'per_page' => 24,
    // Generated sample promotions (data/promotions.json). Set to false once
    // real sources (data/sources/) provide enough offers.
    'demo_data' => false,
    'open_prices' => [
        'api_url' => 'https://prices.openfoodfacts.org/api/v1/prices',
        // A price seen on day D is shown until D + validity_days - 1.
        'validity_days' => 7,
        'max_pages' => 50,
        'user_agent' => 'PanierMalin/1.0 (comparateur de promotions)',
    ],
];
