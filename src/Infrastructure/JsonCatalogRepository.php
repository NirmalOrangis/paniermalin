<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Model\Category;
use App\Domain\Model\Location;
use App\Domain\Model\Store;
use App\Domain\Repository\CatalogRepositoryInterface;

final class JsonCatalogRepository implements CatalogRepositoryInterface
{
    /** @var array<string, Store>|null */
    private ?array $stores = null;
    /** @var array<string, Category>|null */
    private ?array $categories = null;
    /** @var array<string, Location>|null */
    private ?array $locations = null;
    /** @var list<array{region: Location, departments: list<Location>}> */
    private array $tree = [];

    public function __construct(private readonly string $dataDir)
    {
    }

    public function stores(): array
    {
        if ($this->stores === null) {
            $this->stores = [];
            foreach (JsonFile::read($this->dataDir . '/stores.json') as $row) {
                $this->stores[$row['id']] = new Store($row['id'], $row['name'], $row['color'], $row['text'], $row['format']);
            }
        }

        return $this->stores;
    }

    public function store(string $id): ?Store
    {
        return $this->stores()[$id] ?? null;
    }

    public function categories(): array
    {
        if ($this->categories === null) {
            $this->categories = [];
            foreach (JsonFile::read($this->dataDir . '/categories.json') as $row) {
                $this->categories[$row['id']] = new Category($row['id'], $row['name'], $row['emoji'], $row['color']);
            }
        }

        return $this->categories;
    }

    public function category(string $id): ?Category
    {
        return $this->categories()[$id] ?? null;
    }

    public function location(string $code): ?Location
    {
        $this->loadLocations();

        return $this->locations[$code] ?? null;
    }

    public function locationTree(): array
    {
        $this->loadLocations();

        return $this->tree;
    }

    private function loadLocations(): void
    {
        if ($this->locations !== null) {
            return;
        }
        $this->locations = ['FR' => new Location('FR', 'Toute la France', Location::COUNTRY, null)];
        foreach (JsonFile::read($this->dataDir . '/regions.json') as $row) {
            $region = new Location($row['code'], $row['name'], Location::REGION, 'FR');
            $this->locations[$region->code] = $region;
            $departments = [];
            foreach ($row['departments'] as $dep) {
                $department = new Location($dep['code'], $dep['name'], Location::DEPARTMENT, $region->code);
                $this->locations[$department->code] = $department;
                $departments[] = $department;
            }
            $this->tree[] = ['region' => $region, 'departments' => $departments];
        }
    }
}
