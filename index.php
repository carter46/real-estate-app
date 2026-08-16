<?php
/**
 * Homepage — seven Stitch sections + MySQL featured listings.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = site_name() . ' — Luxury Mountain Living';
$activeNav = 'home';

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
    error_log('[SDC] home: ' . $e->getMessage());
}

$collections = [
    ['label' => 'Aspen', 'region' => 'Aspen', 'image' => 'assets/img/collection-aspen.jpg'],
    ['label' => 'Vail', 'region' => 'Vail', 'image' => 'assets/img/collection-vail.jpg'],
    ['label' => 'Beaver Creek', 'region' => 'Beaver Creek', 'image' => 'assets/img/collection-beaver-creek.jpg'],
];

require __DIR__ . '/includes/header.php';
?>
<!-- 2. Hero -->
<section class="relative min-h-[78vh] flex items-center justify-center hero-photo" style="background-image: linear-gradient(180deg, rgba(28,27,27,0.2), rgba(28,27,27,0.72)), url('<?= e(base_url('assets/img/home-hero.jpg')) ?>');">
  <div class="w-full max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop py-24 text-center flex flex-col items-center">
    <div class="w-12 h-0.5 bg-primary-fixed-dim mb-6"></div>
    <h1 class="font-display-lg text-display-lg-mobile lg:text-display-lg text-on-primary max-w-3xl mb-4">
      Luxury Mountain Living, <em class="italic">Redefined.</em>
    </h1>
    <p class="font-body-lg text-body-lg text-on-primary/85 font-light max-w-xl mb-8">
      Discover exclusive Colorado estates curated by <?= e(site_name()) ?>.
    </p>
    <div class="flex flex-wrap gap-3 justify-center">
      <a href="<?= e(base_url('properties.php')) ?>" class="px-8 py-3 bg-surface text-primary font-label-sm text-label-sm uppercase tracking-widest hover:bg-primary-fixed transition-colors">Explore Properties</a>
      <a href="<?= e(base_url('agents.php')) ?>" class="px-8 py-3 border border-on-primary/50 text-on-primary font-label-sm text-label-sm uppercase tracking-widest hover:bg-on-primary/10 transition-colors">Meet Our Agents</a>
    </div>
  </div>
</section>

<!-- 3. Overlapping quick-search -->
<div class="relative z-10 max-w-[1100px] mx-auto px-margin-mobile -mt-16">
  <div class="bg-surface-container-lowest border border-outline-variant/40 shadow-[0_12px_40px_rgba(55,5,24,0.08)] p-6 md:p-8">
    <?php $searchAction = base_url('properties.php'); require __DIR__ . '/includes/search-form.php'; ?>
  </div>
</div>

<!-- 4. Exclusive Collections -->
<section class="py-section-gap">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop">
    <div class="max-w-2xl mb-12">
      <p class="font-subheading text-subheading uppercase tracking-widest text-primary mb-3">Destinations</p>
      <h2 class="font-headline-md text-headline-md text-on-surface mb-3">Exclusive Collections</h2>
      <p class="font-body-lg text-body-lg text-on-surface-variant font-light">Explore signature markets across Colorado’s most sought-after alpine communities.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php foreach ($collections as $collection): ?>
        <a href="<?= e(base_url('properties.php?region=' . rawurlencode($collection['region']))) ?>" class="group relative min-h-[22rem] flex items-end p-6 text-on-primary overflow-hidden collection-photo no-underline" style="background-image: linear-gradient(180deg, transparent 30%, rgba(0,0,0,0.7)), url('<?= e(base_url($collection['image'])) ?>');">
          <div class="relative z-10">
            <p class="font-label-sm text-label-sm uppercase tracking-widest text-primary-fixed-dim mb-2">Discover</p>
            <p class="font-headline-md text-[28px]"><?= e($collection['label']) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="mt-10">
      <a href="<?= e(base_url('properties.php')) ?>" class="inline-flex font-label-sm text-label-sm uppercase tracking-widest text-primary border-b border-primary pb-1 hover:opacity-70">View All Markets</a>
    </div>
  </div>
</section>

<!-- 5. Curated For You -->
<section class="py-section-gap bg-surface-container-lowest">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop">
    <div class="max-w-2xl mb-12">
      <p class="font-subheading text-subheading uppercase tracking-widest text-primary mb-3">Portfolio</p>
      <h2 class="font-headline-md text-headline-md text-on-surface mb-3">Curated For You</h2>
      <p class="font-body-lg text-body-lg text-on-surface-variant font-light">Featured listings from the SDC portfolio — one database record per property wherever it appears.</p>
    </div>
    <?php if (!$dbOk): ?>
      <p class="font-body-md text-on-surface-variant">Property data unavailable. Configure the database to continue.</p>
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
      <h2 class="font-headline-md text-headline-md text-on-surface mb-4">Unmatched Market Expertise</h2>
      <p class="font-body-lg text-body-lg text-on-surface-variant font-light mb-4">From alpine estates to urban residences, SDC combines local market intelligence with a refined, editorial approach to property presentation.</p>
      <p class="font-body-lg text-body-lg text-on-surface-variant font-light mb-8">Our advisors guide acquisitions and sales with discretion, clarity, and an eye for architectural significance.</p>
      <a href="<?= e(base_url('about.php')) ?>" class="inline-flex font-label-sm text-label-sm uppercase tracking-widest text-primary border border-primary px-8 py-3 hover:bg-primary hover:text-on-primary transition-colors">Read Our Latest Market Report</a>
    </div>
    <div class="relative min-h-[28rem] bg-cover bg-center" style="background-image: url('<?= e(base_url('assets/img/editorial-expertise.jpg')) ?>');">
      <div class="absolute left-4 bottom-4 bg-surface-container-lowest border border-outline-variant/40 p-5 shadow-md">
        <p class="font-display-lg text-[32px] text-on-surface">$2B+</p>
        <p class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">In Luxury Sales Volume</p>
      </div>
    </div>
  </div>
</section>
<?php
require __DIR__ . '/includes/footer.php';
