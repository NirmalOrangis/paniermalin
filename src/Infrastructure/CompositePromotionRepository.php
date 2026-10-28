<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Repository\CatalogRepositoryInterface;
use App\Domain\Repository\PromotionRepositoryInterface;
use App\Support\Clock;

/** Merges several promotion sources (demo, manual entry, Open Prices…) into one. */
final class CompositePromotionRepository implements PromotionRepositoryInterface
{
    /** @param array<string, PromotionRepositoryInterface> $sources source name => repository */
    public function __construct(private readonly array $sources)
    {
    }

    /**
     * Builds the sources enabled in config/app.php.
     *
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config, CatalogRepositoryInterface $catalog, Clock $clock): self
    {
        $sources = [];
        if ($config['demo_data']) {
            $sources['demo'] = new JsonPromotionRepository($config['data_dir'] . '/promotions.json', $clock);
        }
        $sources['manual'] = new CsvPromotionRepository($config['data_dir'] . '/sources/manual.csv', $catalog);
        $sources['open-prices'] = new JsonPromotionRepository($config['data_dir'] . '/sources/open-prices.json', $clock, optional: true);

        return new self($sources);
    }

    public function findAll(): array
    {
        return array_merge(...array_values(array_map(
            static fn (PromotionRepositoryInterface $source): array => $source->findAll(),
            $this->sources,
        )));
    }

    /** @return array<string, PromotionRepositoryInterface> */
    public function sources(): array
    {
        return $this->sources;
    }
}
