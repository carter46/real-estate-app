<?php
/**
 * Listings property card — Stitch list variant.
 *
 * @var array<string, mixed> $property
 */

declare(strict_types=1);

if (!isset($property) || !is_array($property)) {
    return;
}

$cover = (string) ($property['cover_path'] ?? $property['image_path'] ?? '');
$badge = (string) ($property['badge'] ?? '');
$priceLabel = format_price(
    isset($property['price']) ? (float) $property['price'] : null,
    !empty($property['price_on_request']),
    (string) ($property['currency'] ?? 'USD')
);
$title = (string) ($property['title'] ?? '');
$city = (string) ($property['city'] ?? '');
$state = (string) ($property['state'] ?? '');
$zip = (string) ($property['postal_code'] ?? '');
$locality = trim($city . ($state !== '' ? ', ' . $state : '') . ($zip !== '' ? ' ' . $zip : ''));
$beds = $property['bedrooms'] ?? null;
$baths = $property['bathrooms'] ?? null;
$sqft = $property['sqft'] ?? null;
$slug = (string) ($property['slug'] ?? '');
$href = $slug !== '' ? base_url('property.php?slug=' . rawurlencode($slug)) : '#';
$badgeClass = (stripos($badge, 'just') !== false)
    ? 'bg-secondary text-on-secondary'
    : 'bg-primary text-on-primary';
?>
<article class="group bg-surface-container-lowest border border-outline-variant/40 overflow-hidden hover:shadow-md transition-shadow duration-300 relative">
  <a href="<?= e($href) ?>" class="block relative overflow-hidden aspect-[4/3]">
    <?php if ($cover !== ''): ?>
      <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" src="<?= e(media_url($cover)) ?>" alt="<?= e($title) ?>"/>
    <?php else: ?>
      <div class="w-full h-full img-placeholder" aria-hidden="true"></div>
    <?php endif; ?>
  </a>
  <?php if ($badge !== ''): ?>
    <span class="absolute top-4 left-4 <?= e($badgeClass) ?> font-label-sm text-label-sm uppercase tracking-widest px-3 py-1"><?= e($badge) ?></span>
  <?php endif; ?>
  <button type="button" disabled class="absolute top-4 right-4 w-9 h-9 bg-surface/90 flex items-center justify-center text-primary border border-outline-variant/40" aria-label="Save property">
    <span class="material-symbols-outlined text-[18px]">favorite</span>
  </button>
  <div class="p-5">
    <p class="font-display-lg text-[22px] text-on-surface mb-1"><?= e($priceLabel) ?></p>
    <h3 class="font-headline-md text-[22px] text-on-surface mb-1"><a class="no-underline hover:text-primary" href="<?= e($href) ?>"><?= e($title) ?></a></h3>
    <p class="font-body-md text-body-md uppercase tracking-widest text-on-surface-variant text-[12px] mb-4"><?= e($locality) ?></p>
    <div class="grid grid-cols-3 gap-2 border-t border-outline-variant/30 pt-4">
      <div><p class="font-display-lg text-[20px]"><?= e($beds !== null ? (string) $beds : '—') ?></p><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">Beds</p></div>
      <div><p class="font-display-lg text-[20px]"><?= e($baths !== null ? (string) $baths : '—') ?></p><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">Baths</p></div>
      <div><p class="font-display-lg text-[20px]"><?= e($sqft !== null ? number_format((int) $sqft) : '—') ?></p><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">Sq Ft</p></div>
    </div>
  </div>
</article>
