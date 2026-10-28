<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Promotion;

/**
 * Source of promotions. The demo uses a JSON file; a real provider (catalog
 * API, partner feed, Open Prices…) only has to implement this interface.
 */
interface PromotionRepositoryInterface
{
    /** @return list<Promotion> */
    public function findAll(): array;
}
