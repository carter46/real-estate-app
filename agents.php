<?php
/**
 * Agents / Our Experts — from agents table.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$region = trim((string) ($_GET['region'] ?? ''));
$agents = [];
$dbOk = true;
try {
    $agents = agents_list_public($region !== '' ? $region : null);
} catch (Throwable $e) {
    $dbOk = false;
    error_log('[SDC] agents: ' . $e->getMessage());
}

$regions = [];
try {
    $regions = regions_all(true);
} catch (Throwable $e) {
    $regions = [];
}
$pageTitle = 'Our Experts — ' . site_name();
$activeNav = 'agents';
require __DIR__ . '/includes/header.php';
?>
<section class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop pt-16 pb-8">
  <span class="font-subheading text-subheading text-primary uppercase tracking-widest mb-4 block">Meet the Network</span>
  <h1 class="font-display-lg text-display-lg-mobile lg:text-display-lg text-on-surface mb-6">Our Experts</h1>
  <p class="font-body-lg text-body-lg text-on-surface-variant max-w-xl font-light">Meet the SDC specialists guiding Colorado’s most discerning buyers and sellers.</p>
</section>

<form method="get" action="" class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop pb-12">
  <div class="max-w-sm">
  <label class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-2 block" for="region">Region Filter</label>
  <select id="region" name="region" onchange="this.form.submit()" class="w-full appearance-none bg-surface-container border-b border-on-background/20 font-body-md text-body-md text-on-surface py-3 pl-4 pr-10 focus:outline-none focus:border-primary transition-colors cursor-pointer">
    <option value="">All Regions</option>
    <?php foreach ($regions as $r): ?>
      <?php $name = (string) ($r['name'] ?? ''); ?>
      <option value="<?= e($name) ?>" <?= $region === $name ? 'selected' : '' ?>><?= e($name) ?></option>
    <?php endforeach; ?>
  </select>
  </div>
</form>

<div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop pb-20">
  <?php if (!$dbOk): ?>
    <p class="font-body-md text-on-surface-variant">Agents unavailable.</p>
  <?php elseif ($agents === []): ?>
    <p class="font-body-md text-on-surface-variant">No agents found for this filter.</p>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-x-gutter gap-y-16">
      <?php foreach ($agents as $agent): ?>
        <?php
          $photo = trim((string) ($agent['photo_path'] ?? ''));
          if ($photo === '') {
              $slug = preg_replace('/[^a-z0-9\-]+/', '', strtolower((string) ($agent['slug'] ?? ''))) ?: '';
              if ($slug !== '') {
                  $candidate = 'assets/img/agent-' . $slug . '.jpg';
                  if (is_readable(APP_ROOT . '/' . $candidate)) {
                      $photo = $candidate;
                  }
              }
          }
          $photoUrl = $photo !== '' ? media_url($photo) : '';
        ?>
        <article class="group">
          <div class="relative aspect-[3/4] overflow-hidden mb-6 img-placeholder">
            <?php if ($photoUrl !== ''): ?>
              <img class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] group-hover:scale-105" src="<?= e($photoUrl) ?>" alt="<?= e((string) ($agent['name'] ?? '')) ?>">
            <?php endif; ?>
            <?php if (!empty($agent['badge'])): ?>
              <div class="absolute bottom-4 right-4 bg-primary px-3 py-1 text-on-primary font-label-sm text-[10px] uppercase tracking-widest opacity-0 translate-y-2 transition-all duration-300 ease-out group-hover:opacity-100 group-hover:translate-y-0"><?= e((string) $agent['badge']) ?></div>
            <?php endif; ?>
          </div>
          <h3 class="font-headline-md text-[24px] text-on-surface mb-1"><?= e((string) $agent['name']) ?></h3>
          <p class="font-subheading text-[12px] text-on-surface-variant uppercase tracking-wider mb-4 border-b border-on-background/10 pb-4">
            <?= e(trim(($agent['title'] ?? '') . (($agent['region'] ?? '') !== '' ? ', ' . $agent['region'] : ''))) ?>
          </p>
          <p class="font-body-md text-[14px] text-on-surface-variant line-clamp-3 mb-6"><?= e((string) ($agent['bio'] ?? '')) ?></p>
          <div class="flex gap-6">
            <a class="group/link inline-flex items-center gap-1 no-underline" href="<?= e(base_url('contact.php')) ?>">
              <span class="font-label-sm text-label-sm text-on-surface transition-colors group-hover/link:text-primary">Contact</span>
              <span class="material-symbols-outlined text-[16px] text-primary">arrow_forward</span>
            </a>
            <a class="group/link inline-flex items-center gap-1 no-underline" href="<?= e(base_url('properties.php?q=' . rawurlencode((string) ($agent['region'] ?? '')))) ?>">
              <span class="font-label-sm text-label-sm text-on-surface transition-colors group-hover/link:text-primary">View Listings</span>
              <span class="material-symbols-outlined text-[16px] text-primary">arrow_forward</span>
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php
require __DIR__ . '/includes/footer.php';
