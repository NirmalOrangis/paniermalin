<?php

declare(strict_types=1);

// Let PHP's built-in dev server serve static files directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/src/bootstrap.php';

$request = App\Http\Request::fromGlobals();
$kernel = new App\Kernel(require APP_ROOT . '/config/app.php');
$kernel->handle($request)->send($request->method === 'HEAD');
