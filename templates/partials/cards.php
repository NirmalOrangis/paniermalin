<?php
/**
 * @var App\View\ViewHelpers               $h
 * @var list<App\Domain\Model\Promotion>   $items
 * @var array<string, int>                 $offerCounts
 */
foreach ($items as $p) {
    include __DIR__ . '/promo-card.php';
}
