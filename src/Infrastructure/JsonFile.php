<?php

declare(strict_types=1);

namespace App\Infrastructure;

final class JsonFile
{
    /** @return array<mixed> */
    public static function read(string $path): array
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Unable to read data file "%s".', $path));
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
