<?php

declare(strict_types=1);

namespace App\Support;

/** Single source of "now", fixable in tests. */
final class Clock
{
    private readonly \DateTimeImmutable $fixed;

    public function __construct(?string $fixedTime = null)
    {
        if ($fixedTime !== null) {
            $this->fixed = new \DateTimeImmutable($fixedTime);
        }
    }

    public function now(): \DateTimeImmutable
    {
        return $this->fixed ?? new \DateTimeImmutable();
    }

    public function today(): \DateTimeImmutable
    {
        return $this->now()->setTime(0, 0);
    }
}
