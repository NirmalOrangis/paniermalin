<?php
/**
 * A titled row of promotion cards.
 *
 * @var App\View\ViewHelpers               $h
 * @var string                             $sectionTitle
 * @var string|null                        $sectionSubtitle
 * @var string|null                        $sectionLink
 * @var list<App\Domain\Model\Promotion>   $items
 * @var array<string, int>                 $offerCounts
 */
if ($items === []) {
    return;
}
?>
<section class="section">
    <div class="section__head">
        <div>
            <h2 class="section__title"><?= $h->e($sectionTitle) ?></h2>
            <?php if (!empty($sectionSubtitle)): ?><p class="section__subtitle"><?= $h->e($sectionSubtitle) ?></p><?php endif; ?>
        </div>
        <?php if (!empty($sectionLink)): ?><a class="section__more" href="<?= $h->e($sectionLink) ?>">Tout voir →</a><?php endif; ?>
    </div>
    <div class="grid grid--scroll">
        <?php include __DIR__ . '/cards.php'; ?>
    </div>
</section>
