<?php
/**
 * Homepage — seven Stitch sections, SDC brand, MySQL featured listings.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = (string) app_config('app.name') . ' — Luxury Mountain Living';
$navVariant = 'home';
$activeNav = 'buy';

$featured = [];
$dbOk = true;
try {
    $featured = property_list_public(['featured_only' => true], 'newest', 3, 0);
    if (count($featured) < 3) {
        $more = property_list_public([], 'newest', 3, 0);
        $ids = array_column($featured, 'id');
        foreach ($more as $row) {
            if (!in_array($row['id'], $ids, true)) {
                $featured[] = $row;
            }
            if (count($featured) >= 3) {
                break;
            }
        }
    }
} catch (Throwable $e) {
    $dbOk = false;
    error_log('[SDC] home featured: ' . $e->getMessage());
}

$collections = [
    ['label' => 'Aspen', 'region' => 'Aspen'],
    ['label' => 'Vail', 'region' => 'Vail'],
    ['label' => 'Beaver Creek', 'region' => 'Beaver Creek'],
];

require __DIR__ . '/includes/header.php';
?>
<!-- 2. Hero -->
<section class="relative min-h-[78vh] flex items-end hero-photo text-on-primary">
  <div class="w-full max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop pb-28 pt-32">
    <div class="w-12 h-0.5 bg-primary-fixed-dim mb-6"></div>
    <h1 class="font-display-lg text-[40px] lg:text-display-lg max-w-3xl mb-6">Luxury Mountain Living, <em class="italic">Redefined.</em></h1>
    <p class="font-body-lg text-body-lg font-light text-on-primary/85 max-w-xl mb-8">Discover exclusive Colorado estates curated by Sunview Development and Consultancy (SDC).</p>
    <div class="flex flex-wrap gap-4">
      <a href="<?= e(base_url('properties.php')) ?>" class="inline-flex px-8 py-3 bg-surface text-primary font-label-sm text-label-sm uppercase tracking-widest hover:bg-surface-container transition-colors">Explore Properties</a>
      <a href="<?= e(base_url('agents.php')) ?>" class="inline-flex px-8 py-3 border border-on-primary/50 text-on-primary font-label-sm text-label-sm uppercase tracking-widest hover:bg-on-primary/10 transition-colors">Meet Our Agents</a>
    </div>
  </div>
</section>

<!-- 3. Overlapping quick-search -->
<div class="relative z-10 max-w-[1100px] mx-auto px-margin-mobile -mt-16">
  <div class="bg-surface border border-outline-variant/40 shadow-[0_12px_40px_rgba(55,5,24,0.08)] p-6 lg:p-8">
    <?php $searchAction = base_url('properties.php'); require __DIR__ . '/includes/search-form.php'; ?>
  </div>
</div>

<!-- 4. Exclusive Collections -->
<section class="py-section-gap">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop">
    <div class="max-w-2xl mb-12">
      <p class="font-subheading text-subheading uppercase tracking-widest text-primary mb-3">Destinations</p>
      <h2 class="font-headline-md text-headline-md mb-4">Exclusive Collections</h2>
      <p class="font-body-lg text-body-lg font-light text-on-surface-variant">Explore signature markets across Colorado’s most sought-after alpine communities.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php foreach ($collections as $collection): ?>
        <a href="<?= e(base_url('properties.php?region=' . rawurlencode($collection['region']))) ?>" class="relative min-h-[22rem] flex items-end p-6 text-on-primary collection-photo overflow-hidden group">
          <div class="relative z-10">
            <p class="font-label-sm text-label-sm uppercase tracking-widest text-primary-fixed-dim mb-2">Discover</p>
            <h3 class="font-headline-md text-[28px]"><?= e($collection['label']) ?></h3>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="mt-10">
      <a href="<?= e(base_url('properties.php')) ?>" class="font-label-sm text-label-sm uppercase tracking-widest text-primary border-b border-primary pb-1">View All Markets</a>
    </div>
  </div>
</section>

<!-- 5. Curated For You -->
<section class="py-section-gap bg-surface-container-lowest">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop">
    <div class="max-w-2xl mb-12">
      <p class="font-subheading text-subheading uppercase tracking-widest text-primary mb-3">Portfolio</p>
      <h2 class="font-headline-md text-headline-md mb-4">Curated For You</h2>
      <p class="font-body-lg text-body-lg font-light text-on-surface-variant">Featured listings from the SDC portfolio — one database record per property.</p>
    </div>
    <?php if (!$dbOk): ?>
      <p class="font-body-md text-on-surface-variant">Property data is unavailable. Configure the database to continue.</p>
    <?php elseif ($featured === []): ?>
      <p class="font-body-md text-on-surface-variant">No public properties yet. Add listings in the admin console.</p>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($featured as $property): ?>
          <?php require __DIR__ . '/includes/property-card-home.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="mt-10">
      <a href="<?= e(base_url('properties.php')) ?>" class="inline-flex px-8 py-3 bg-primary text-on-primary font-label-sm text-label-sm uppercase tracking-widest hover:bg-primary-container transition-colors">View All Listings</a>
    </div>
  </div>
</section>

<!-- 6. Unmatched Market Expertise -->
<section class="py-section-gap">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
    <div>
      <div class="w-12 h-0.5 bg-primary mb-6"></div>
      <h2 class="font-headline-md text-headline-md mb-6">Unmatched Market Expertise</h2>
      <p class="font-body-lg text-body-lg font-light text-on-surface-variant mb-4">From alpine estates to urban residences, SDC combines local market intelligence with a refined, editorial approach to property presentation.</p>
      <p class="font-body-lg text-body-lg font-light text-on-surface-variant mb-8">Our advisors guide acquisitions and sales with discretion, clarity, and an eye for architectural significance.</p>
      <a href="<?= e(base_url('about.php')) ?>" class="inline-flex px-8 py-3 border border-primary text-primary font-label-sm text-label-sm uppercase tracking-widest hover:bg-primary hover:text-on-primary transition-colors">Read Our Latest Market Report</a>
    </div>
    <div class="relative min-h-[24rem] img-placeholder">
      <div class="absolute left-4 bottom-4 bg-surface border border-outline-variant/40 p-5 shadow-md">
        <p class="font-headline-md text-[32px] text-on-surface">$2B+</p>
        <p class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">In Luxury Sales Volume</p>
      </div>
    </div>
  </div>
</section>
<?php
require __DIR__ . '/includes/footer.php';
