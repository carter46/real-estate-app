<?php
/**
 * All markets / regions — dedicated destination index (not the property listing).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$markets = [];
$dbOk = true;
try {
    // Live query: every Active region from Admin → Regions (create + update).
    $markets = regions_for_markets();
} catch (Throwable $e) {
    $dbOk = false;
    $markets = [];
    error_log('[SDC] markets: ' . $e->getMessage());
}

$pageTitle = 'Markets — ' . site_name();
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>
<section class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop pt-16 pb-8">
  <span class="font-subheading text-subheading text-primary uppercase tracking-widest mb-4 block">Destinations</span>
  <h1 class="font-display-lg text-display-lg-mobile lg:text-display-lg text-on-surface mb-6">All Markets</h1>
  <p class="font-body-lg text-body-lg text-on-surface-variant max-w-xl font-light">Browse every SDC market. Select a destination to view available properties in that region.</p>
</section>

<section class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop pb-20">
  <?php if (!$dbOk): ?>
    <p class="font-body-md text-on-surface-variant">Market data unavailable. Configure the database to continue.</p>
  <?php elseif ($markets === []): ?>
    <p class="font-body-md text-on-surface-variant">No active markets yet. Add regions in Admin → Regions.</p>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($markets as $collection): ?>
        <?php require __DIR__ . '/includes/partials/market-card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
