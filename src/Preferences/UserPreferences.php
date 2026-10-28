<?php

declare(strict_types=1);

namespace App\Preferences;

/**
 * What we remember about a visitor, read from the preferences cookie.
 * Everything is empty unless the visitor accepted the cookie banner.
 */
final class UserPreferences
{
    /**
     * @param bool|null          $consent   null = the visitor has not answered the banner yet
     * @param list<string>       $stores
     * @param array<string, int> $interests category id => interest score
     */
    public function __construct(
        public readonly ?bool $consent = null,
        public readonly ?string $location = null,
        public readonly array $stores = [],
        public readonly bool $includeLoyalty = true,
        public readonly array $interests = [],
    ) {
    }

    public function isReturning(): bool
    {
        return $this->consent === true && ($this->location !== null || $this->stores !== [] || $this->interests !== []);
    }

    /** @return list<string> category ids, strongest interest first */
    public function topInterests(int $limit = 3): array
    {
        $interests = $this->interests;
        arsort($interests);

        return array_slice(array_keys($interests), 0, $limit);
    }
}
