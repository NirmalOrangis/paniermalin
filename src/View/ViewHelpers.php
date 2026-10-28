<?php

declare(strict_types=1);

namespace App\View;

use App\Domain\Model\Category;
use App\Domain\Model\Location;
use App\Domain\Model\Promotion;
use App\Domain\Model\Store;
use App\Domain\Repository\CatalogRepositoryInterface;
use App\Support\Clock;

/** Formatting and URL helpers available as $h in every template. */
final class ViewHelpers
{
    private const DAYS = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
    private const MONTHS = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

    private string $location;
    private ?string $persistLocation = null;

    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly array $config,
        private readonly CatalogRepositoryInterface $catalog,
        private readonly Clock $clock,
    ) {
        $this->location = (string) $config['default_location'];
    }

    public function withLocation(string $location, bool $persistInUrls): self
    {
        $clone = clone $this;
        $clone->location = $location;
        $clone->persistLocation = $persistInUrls ? $location : null;

        return $clone;
    }

    public function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function config(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }

    /** @param array<string, string|int|float|null> $params */
    public function url(string $path, array $params = []): string
    {
        if ($this->persistLocation !== null && !isset($params['loc'])) {
            $params['loc'] = $this->persistLocation;
        }
        $params = array_filter($params, static fn ($v): bool => $v !== null);

        return $path . ($params ? '?' . http_build_query($params) : '');
    }

    public function absoluteUrl(string $path): string
    {
        return $this->config['url'] . $path;
    }

    /** Cache-busted asset path. */
    public function asset(string $path): string
    {
        $file = APP_ROOT . '/public/' . ltrim($path, '/');

        return '/' . ltrim($path, '/') . '?v=' . (is_file($file) ? filemtime($file) : '0');
    }

    public function price(float $amount): string
    {
        return number_format($amount, 2, ',', "\u{202F}") . "\u{00A0}€";
    }

    public function unitPrice(Promotion $p): string
    {
        return number_format($p->unitPrice(), 2, ',', "\u{202F}") . "\u{00A0}" . $p->unitLabel();
    }

    public function date(\DateTimeImmutable $date): string
    {
        return self::DAYS[(int) $date->format('w')] . ' ' . $date->format('j') . ' ' . self::MONTHS[(int) $date->format('n')];
    }

    public function daysLeft(Promotion $p): int
    {
        return $p->daysLeft($this->clock->now());
    }

    public function endsLabel(Promotion $p): string
    {
        $days = $this->daysLeft($p);

        return match (true) {
            $days <= 0 => 'Dernier jour !',
            $days === 1 => 'Se termine demain',
            $days <= 3 => "Plus que {$days} jours",
            default => 'Jusqu\'au ' . $this->date($p->endsAt),
        };
    }

    /** "Relevé le …" for prices seen in a shop, offer end date otherwise. */
    public function freshnessLabel(Promotion $p): string
    {
        return $p->isFieldObservation() ? 'Relevé le ' . $this->date($p->startsAt) : $this->endsLabel($p);
    }

    public function plural(int $count, string $singular, string $plural): string
    {
        return $count . "\u{00A0}" . ($count > 1 ? $plural : $singular);
    }

    public function store(string $id): ?Store
    {
        return $this->catalog->store($id);
    }

    /** @return array<string, Store> */
    public function stores(): array
    {
        return $this->catalog->stores();
    }

    public function storeName(string $id): string
    {
        return $this->catalog->store($id)?->name ?? $id;
    }

    public function storeBadge(string $id, string $extraClass = ''): string
    {
        $store = $this->catalog->store($id);
        if ($store === null) {
            return '';
        }

        return sprintf(
            '<span class="store %s" style="--store-bg:%s;--store-fg:%s">%s</span>',
            $this->e($extraClass),
            $this->e($store->color),
            $this->e($store->textColor),
            $this->e($store->name),
        );
    }

    public function category(string $id): ?Category
    {
        return $this->catalog->category($id);
    }

    /** @return array<string, Category> */
    public function categories(): array
    {
        return $this->catalog->categories();
    }

    public function locationCode(): string
    {
        return $this->location;
    }

    public function location(?string $code = null): ?Location
    {
        return $this->catalog->location($code ?? $this->location);
    }

    /** @return list<array{region: Location, departments: list<Location>}> */
    public function locationTree(): array
    {
        return $this->catalog->locationTree();
    }

    /** @param list<string> $zones */
    public function zonesLabel(array $zones): string
    {
        if (in_array('FR', $zones, true)) {
            return 'Toute la France';
        }

        return implode(', ', array_map(fn (string $code): string => $this->catalog->location($code)?->label() ?? $code, $zones));
    }

    /** Turns "nutella-750g" into "Nutella 750g" for products without a current offer. */
    public function humanizeKey(string $key): string
    {
        return ucfirst(str_replace('-', ' ', $key));
    }
}
