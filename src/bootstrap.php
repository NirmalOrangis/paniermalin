<?php

declare(strict_types=1);

defined('APP_ROOT') || define('APP_ROOT', dirname(__DIR__));

date_default_timezone_set('Europe/Paris');
mb_internal_encoding('UTF-8');

// Composer is optional: the project has no third-party dependency, so a
// minimal PSR-4 autoloader is used when vendor/ has not been installed.
if (is_file(APP_ROOT . '/vendor/autoload.php')) {
    require APP_ROOT . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class): void {
        if (!str_starts_with($class, 'App\\')) {
            return;
        }
        $file = APP_ROOT . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}
