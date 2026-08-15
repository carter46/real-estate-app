<?php
/**
 * Featured / signature listings card — Stitch featured span.
 *
 * @var array<string, mixed> $property
 */

declare(strict_types=1);

if (!isset($property) || !is_array($property)) {
    return;
}

$cover = (string) ($property['cover_path'] ?? $property['image_path'] ?? '');
$badge = (string) ($property['badge'] ?? 'Signature Property');
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
$description = (string) ($property['description'] ?? '');
if (strlen($description) > 220) {
    $description = substr($description, 0, 217) . '...';
}
$beds = $property['bedrooms'] ?? null;
$baths = $property['bathrooms'] ?? null;
$sqft = $property['sqft'] ?? null;
$acres = $property['lot_acres'] ?? null;
$slug = (string) ($property['slug'] ?? '');
$href = $slug !== '' ? base_url('property.php?slug=' . rawurlencode($slug)) : '#';
?>
<article class="group col-span-full grid grid-cols-1 lg:grid-cols-3 border border-outline-variant/40 bg-surface-container-lowest overflow-hidden hover:shadow-lg transition-shadow duration-500">
  <div class="lg:col-span-2 relative overflow-hidden min-h-[280px]">
    <?php if ($cover !== ''): ?>
      <img class="w-full h-full object-cover min-h-[280px] transition-transform duration-700 group-hover:scale-105" src="<?= e(media_url($cover)) ?>" alt="<?= e($title) ?>"/>
    <?php else: ?>
      <div class="w-full h-full min-h-[280px] img-placeholder" aria-hidden="true"></div>
    <?php endif; ?>
  </div>
  <div class="p-8 lg:p-10 flex flex-col gap-4">
    <?php if ($badge !== ''): ?>
      <span class="self-start bg-primary text-on-primary font-label-sm text-label-sm uppercase tracking-widest px-3 py-1"><?= e($badge) ?></span>
    <?php endif; ?>
    <p class="font-headline-md text-headline-md text-on-surface"><?= e($priceLabel) ?></p>
    <h3 class="font-headline-md text-[28px] text-on-surface"><?= e($title) ?></h3>
    <p class="font-body-md text-body-md uppercase tracking-wider text-on-surface-variant text-[12px]"><?= e($locality) ?></p>
    <?php if ($description !== ''): ?>
      <p class="font-body-lg text-body-lg text-on-surface-variant font-light line-clamp-3"><?= e($description) ?></p>
    <?php endif; ?>
    <div class="grid grid-cols-2 gap-3 text-on-surface-variant font-body-md text-body-md mt-auto">
      <span class="inline-flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">bed</span><?= e($beds !== null ? (string) $beds : '—') ?> Beds</span>
      <span class="inline-flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">bathtub</span><?= e($baths !== null ? (string) $baths : '—') ?> Baths</span>
      <span class="inline-flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">square_foot</span><?= e($sqft !== null ? number_format((int) $sqft) : '—') ?> Sq Ft</span>
      <span class="inline-flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">landscape</span><?= e($acres !== null ? (string) $acres : '—') ?> Acres</span>
    </div>
    <a href="<?= e($href) ?>" class="mt-2 inline-flex items-center justify-center px-8 py-3 bg-primary text-on-primary font-label-sm text-label-sm uppercase tracking-widest hover:bg-primary-container transition-colors">View Details</a>
  </div>
</article>
