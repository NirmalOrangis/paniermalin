<?php

declare(strict_types=1);

namespace App\Domain\Model;

/** France, a region ("IDF") or a department ("91"). */
final class Location
{
    public const COUNTRY = 'country';
    public const REGION = 'region';
    public const DEPARTMENT = 'department';

    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $parent,
    ) {
    }

    public function label(): string
    {
        return $this->type === self::DEPARTMENT ? $this->code . ' · ' . $this->name : $this->name;
    }
}
