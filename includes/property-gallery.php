<?php
/**
 * Property gallery partial — ordered images from property_images.
 *
 * @var list<array<string, mixed>> $images
 */

declare(strict_types=1);

$images = $images ?? [];
if ($images === []) {
    echo '<p class="empty">No images yet.</p>';
    return;
}
?>
<div class="gallery-grid property-gallery">
    <?php foreach ($images as $image): ?>
        <?php
        $src = media_url((string) ($image['path'] ?? ''));
        $alt = (string) ($image['alt_text'] ?? '');
        $caption = (string) ($image['caption'] ?? '');
        $isCover = !empty($image['is_cover']);
        ?>
        <figure class="<?= $isCover ? 'is-cover' : '' ?>">
            <?php if ($src !== ''): ?>
                <img src="<?= e($src) ?>" alt="<?= e($alt) ?>">
            <?php endif; ?>
            <?php if ($caption !== ''): ?>
                <figcaption><?= e($caption) ?></figcaption>
            <?php endif; ?>
        </figure>
    <?php endforeach; ?>
</div>
