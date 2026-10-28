<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Repository\CatalogRepositoryInterface;

/** Decides whether an offer's zones cover the location the user picked. */
final class LocationMatcher
{
    public function __construct(private readonly CatalogRepositoryInterface $catalog)
    {
    }

    /** @param list<string> $zones */
    public function matches(array $zones, string $location): bool
    {
        if ($location === 'FR') {
            return true;
        }
        $parent = $this->catalog->location($location)?->parent;
        foreach ($zones as $zone) {
            // National offer, exact match, or regional offer covering the chosen department.
            if ($zone === 'FR' || $zone === $location || $zone === $parent) {
                return true;
            }
            // Department-only offer while the user browses its whole region.
            if ($this->catalog->location($zone)?->parent === $location) {
                return true;
            }
        }

        return false;
    }
}
