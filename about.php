<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'About Us — ' . site_name();
$navVariant = 'content';
$activeNav = 'about';
require __DIR__ . '/includes/header.php';
?>
<section class="relative w-full min-h-[70vh] flex items-center justify-center overflow-hidden hero-photo -mt-20 pt-20">
  <div class="relative z-10 text-center px-margin-mobile py-24">
    <p class="font-subheading text-subheading uppercase tracking-widest text-primary-fixed-dim mb-6">Since Our Founding</p>
    <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-primary max-w-4xl mx-auto drop-shadow-lg">
      The Colorado Standard
    </h1>
  </div>
</section>

<section class="w-full py-section-gap bg-background">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
    <div>
      <div class="w-12 h-0.5 bg-primary mb-6"></div>
      <h2 class="font-display-lg text-display-lg-mobile text-on-surface mb-8">Rooted in the Rockies</h2>
      <p class="font-body-lg text-body-lg text-on-surface-variant font-light mb-6"><?= e(site_name()) ?> presents properties with editorial clarity—heritage, discretion, and market expertise define our approach to luxury real estate advisory.</p>
      <p class="font-body-lg text-body-lg text-on-surface-variant font-light">From alpine estates to metro residences, we guide acquisitions and sales with an eye for architectural significance and lasting value.</p>
    </div>
    <div class="grid grid-cols-2 gap-8">
      <div>
        <div class="font-headline-md text-headline-md text-primary mb-2">40+</div>
        <p class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">Years of Combined Expertise</p>
      </div>
      <div>
        <div class="font-headline-md text-headline-md text-primary mb-2">$2B+</div>
        <p class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">Luxury Sales Volume</p>
      </div>
      <div>
        <div class="font-headline-md text-headline-md text-primary mb-2">8</div>
        <p class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">Signature Markets</p>
      </div>
      <div>
        <div class="font-headline-md text-headline-md text-primary mb-2">1</div>
        <p class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant">Source of Truth — MySQL</p>
      </div>
    </div>
  </div>
</section>

<section class="w-full py-section-gap bg-surface-container-low">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop">
    <h2 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface mb-12 max-w-2xl">What Guides Our Work</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
      <div>
        <h3 class="font-headline-md text-headline-md text-on-surface mb-4">Integrity First</h3>
        <p class="font-body-md text-body-md text-on-surface-variant">Transparent counsel and careful presentation of every listing in the SDC portfolio.</p>
      </div>
      <div>
        <h3 class="font-headline-md text-headline-md text-on-surface mb-4">Local Expertise</h3>
        <p class="font-body-md text-body-md text-on-surface-variant">Deep knowledge of Colorado mountain and metro markets, reflected in live inventory.</p>
      </div>
      <div>
        <h3 class="font-headline-md text-headline-md text-on-surface mb-4">Community Driven</h3>
        <p class="font-body-md text-body-md text-on-surface-variant">Relationships that endure beyond a single transaction—buyers, sellers, and advisors alike.</p>
      </div>
    </div>
  </div>
</section>

<section class="w-full bg-primary text-on-primary py-20">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop text-center">
    <h2 class="font-display-lg text-display-lg-mobile mb-6">Ready to Begin?</h2>
    <p class="font-body-lg text-body-lg text-on-primary/85 font-light max-w-xl mx-auto mb-8">Connect with an SDC advisor for a private conversation about your next mountain or metro move.</p>
    <a href="<?= e(base_url('contact.php')) ?>" class="inline-flex px-10 py-4 bg-surface text-primary font-label-sm text-label-sm uppercase tracking-widest hover:bg-primary-fixed transition-colors no-underline">Connect With Our Experts</a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
