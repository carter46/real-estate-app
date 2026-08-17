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
<a href="<?= e(base_url('properties.php?region=' . rawurlencode($label))) ?>" class="market-card group no-underline">
  <span class="market-card__media" aria-hidden="true">
    <img class="market-card__img" src="<?= e($imgUrl) ?>" alt="">
  </span>
  <span class="market-card__scrim" aria-hidden="true"></span>
  <span class="market-card__content">
    <span class="font-label-sm text-label-sm uppercase tracking-widest text-primary-fixed-dim mb-2 block">Discover</span>
    <span class="font-headline-md text-[28px] text-on-primary block"><?= e($label) ?></span>
  </span>
</a>
