<?php
/**
 * Market / region collection card.
 * Expects $collection as a regions row array.
 */
declare(strict_types=1);

$label = (string) ($collection['name'] ?? '');
$imgPath = trim((string) ($collection['image_path'] ?? ''));
$imgUrl = $imgPath !== '' ? media_url($imgPath) : base_url('assets/img/home-hero.jpg');
?>
<a href="<?= e(base_url('properties.php?region=' . rawurlencode($label))) ?>" class="group relative min-h-[22rem] flex items-end p-6 text-on-primary overflow-hidden collection-photo no-underline" style="background-image: linear-gradient(180deg, transparent 30%, rgba(0,0,0,0.7)), url('<?= e($imgUrl) ?>');">
  <div class="relative z-10">
    <p class="font-label-sm text-label-sm uppercase tracking-widest text-primary-fixed-dim mb-2">Discover</p>
    <p class="font-headline-md text-[28px]"><?= e($label) ?></p>
  </div>
</a>
