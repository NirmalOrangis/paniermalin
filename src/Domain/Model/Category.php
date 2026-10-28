<?php

declare(strict_types=1);

namespace App\Domain\Model;

final class Category
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $emoji,
        public readonly string $color,
    ) {
    }
}
