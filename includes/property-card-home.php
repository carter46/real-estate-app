<?php
/**
 * Homepage property card — Stitch field order + utility classes.
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
$addressLine = trim((string) ($property['address_line'] ?? ''));
if ($addressLine === '') {
    $addressLine = trim((string) ($property['title'] ?? ''));
}
$city = trim((string) ($property['city'] ?? ''));
$locationLine = $city !== '' ? ($addressLine . ', ' . $city) : $addressLine;
$beds = $property['bedrooms'] ?? null;
$baths = $property['bathrooms'] ?? null;
$sqft = $property['sqft'] ?? null;
$slug = (string) ($property['slug'] ?? '');
$href = $slug !== '' ? base_url('property.php?slug=' . rawurlencode($slug)) : '#';
?>
<article class="group bg-surface-container-lowest border border-outline-variant/40 overflow-hidden hover:shadow-lg transition-shadow duration-500">
  <div class="relative overflow-hidden">
    <a href="<?= e($href) ?>" class="block">
      <?php if ($cover !== ''): ?>
        <img class="w-full h-72 object-cover transition-transform duration-500 group-hover:scale-105" src="<?= e(media_url($cover)) ?>" alt="<?= e((string) ($property['title'] ?? '')) ?>"/>
      <?php else: ?>
        <div class="w-full h-72 img-placeholder" aria-hidden="true"></div>
      <?php endif; ?>
    </a>
    <?php if ($badge !== ''): ?>
      <span class="absolute top-4 left-4 bg-primary text-on-primary font-label-sm text-label-sm uppercase tracking-widest px-3 py-1"><?= e($badge) ?></span>
    <?php endif; ?>
    <button type="button" disabled class="absolute bottom-4 right-4 w-10 h-10 rounded-full bg-surface/90 flex items-center justify-center text-primary" aria-label="Save property">
      <span class="material-symbols-outlined text-[20px]">favorite</span>
    </button>
  </div>
  <div class="p-6">
    <p class="font-headline-md text-[24px] text-on-surface mb-2"><?= e($priceLabel) ?></p>
    <p class="font-subheading text-label-sm uppercase tracking-widest text-on-surface-variant mb-4"><?= e($locationLine) ?></p>
    <div class="flex flex-wrap gap-4 text-on-surface-variant font-body-md text-body-md">
      <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">bed</span><?= e($beds !== null ? (string) $beds : '—') ?> Beds</span>
      <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">bathtub</span><?= e($baths !== null ? (string) $baths : '—') ?> Baths</span>
      <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">square_foot</span><?= e($sqft !== null ? number_format((int) $sqft) : '—') ?> SqFt</span>
    </div>
  </div>
</article>
