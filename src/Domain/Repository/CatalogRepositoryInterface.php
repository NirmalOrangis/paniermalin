<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Category;
use App\Domain\Model\Location;
use App\Domain\Model\Store;

/** Reference data: stores, categories and locations. */
interface CatalogRepositoryInterface
{
    /** @return array<string, Store> */
    public function stores(): array;

    public function store(string $id): ?Store;

    /** @return array<string, Category> */
    public function categories(): array;

    public function category(string $id): ?Category;

    public function location(string $code): ?Location;

    /** @return list<array{region: Location, departments: list<Location>}> */
    public function locationTree(): array;
}
