<?php
/**
 * Property gallery partial — ordered images from property_images.
 * Layout matches Glass House reference (2-up + wide).
 *
 * @var list<array<string, mixed>> $images
 */

declare(strict_types=1);

$images = $images ?? [];
if ($images === []) {
    echo '<p class="font-body-md text-on-surface-variant">No images yet.</p>';
    return;
}

$tiles = [];
foreach ($images as $image) {
    $src = media_url((string) ($image['path'] ?? ''));
    if ($src === '') {
        continue;
    }
    $tiles[] = [
        'src' => $src,
        'alt' => (string) ($image['alt_text'] ?? ''),
        'caption' => (string) ($image['caption'] ?? ''),
    ];
}

if ($tiles === []) {
    echo '<p class="font-body-md text-on-surface-variant">No images yet.</p>';
    return;
}
?>
<div class="flex flex-col gap-8">
  <?php if (count($tiles) >= 2): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <?php foreach (array_slice($tiles, 0, 2) as $tile): ?>
        <div class="relative group overflow-hidden h-[400px]">
          <img class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" src="<?= e($tile['src']) ?>" alt="<?= e($tile['alt']) ?>"/>
          <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors duration-500"></div>
          <?php if ($tile['caption'] !== ''): ?>
            <div class="absolute bottom-6 left-6 right-6">
              <p class="font-subheading text-subheading text-on-primary tracking-widest drop-shadow-md uppercase"><?= e($tile['caption']) ?></p>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php foreach (array_slice($tiles, 2) as $tile): ?>
      <div class="relative group overflow-hidden h-[500px]">
        <img class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" src="<?= e($tile['src']) ?>" alt="<?= e($tile['alt']) ?>"/>
        <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors duration-500"></div>
        <?php if ($tile['caption'] !== ''): ?>
          <div class="absolute bottom-8 left-8 right-8">
            <p class="font-subheading text-subheading text-on-primary tracking-widest drop-shadow-md uppercase"><?= e($tile['caption']) ?></p>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="relative group overflow-hidden h-[500px]">
      <img class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" src="<?= e($tiles[0]['src']) ?>" alt="<?= e($tiles[0]['alt']) ?>"/>
      <?php if ($tiles[0]['caption'] !== ''): ?>
        <div class="absolute bottom-8 left-8 right-8">
          <p class="font-subheading text-subheading text-on-primary tracking-widest drop-shadow-md uppercase"><?= e($tiles[0]['caption']) ?></p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
