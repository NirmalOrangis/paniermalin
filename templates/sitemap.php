<?php
/**
 * @var App\View\ViewHelpers $h
 * @var list<string>         $products
 */
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach (['/', '/promos', '/comparer', '/confidentialite'] as $path): ?>
    <url><loc><?= $h->e($h->absoluteUrl($path)) ?></loc><changefreq>daily</changefreq></url>
<?php endforeach; ?>
<?php foreach ($h->categories() as $cat): ?>
    <url><loc><?= $h->e($h->absoluteUrl('/promos?cat=' . $cat->id)) ?></loc><changefreq>daily</changefreq></url>
<?php endforeach; ?>
<?php foreach ($products as $key): ?>
    <url><loc><?= $h->e($h->absoluteUrl('/comparer/' . $key)) ?></loc><changefreq>daily</changefreq></url>
<?php endforeach; ?>
</urlset>
