<?php

declare(strict_types=1);

namespace App\Preferences;

use App\Domain\Repository\CatalogRepositoryInterface;
use App\Http\Request;

/**
 * Reads the consent and preferences cookies written by public/assets/js/app.js.
 * Cookie names must stay in sync with that file.
 */
final class PreferencesResolver
{
    public const CONSENT_COOKIE = 'pm_consent';
    public const PREFS_COOKIE = 'pm_prefs';

    public function __construct(private readonly CatalogRepositoryInterface $catalog)
    {
    }

    public function fromRequest(Request $request): UserPreferences
    {
        $consent = match ($request->cookie(self::CONSENT_COOKIE)) {
            '1' => true,
            '0' => false,
            default => null,
        };
        if ($consent !== true) {
            return new UserPreferences($consent);
        }

        $raw = json_decode((string) $request->cookie(self::PREFS_COOKIE), true);
        $raw = is_array($raw) ? $raw : [];

        $location = is_string($raw['loc'] ?? null) && $this->catalog->location($raw['loc']) !== null ? $raw['loc'] : null;

        $stores = array_values(array_filter(
            is_array($raw['stores'] ?? null) ? $raw['stores'] : [],
            fn ($id): bool => is_string($id) && $this->catalog->store($id) !== null,
        ));

        $interests = [];
        foreach (is_array($raw['int'] ?? null) ? $raw['int'] : [] as $category => $score) {
            if (is_string($category) && is_numeric($score) && $this->catalog->category($category) !== null) {
                $interests[$category] = max(0, min(1000, (int) $score));
            }
        }

        return new UserPreferences(true, $location, $stores, ($raw['loyal'] ?? 1) != 0, $interests);
    }
}
