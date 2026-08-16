<?php
/**
 * Public footer — Stitch structure (surface-container-low), SDC branding.
 */

declare(strict_types=1);

$offices = [];
$phone = site_phone();
$email = site_email();
try {
    $offices = offices_list_public();
} catch (Throwable $e) {
    app_log('footer', $e->getMessage());
}
if ($offices === []) {
    $offices = [
        ['name' => 'Vail Village'],
        ['name' => 'Beaver Creek'],
        ['name' => 'Aspen Core'],
        ['name' => 'Denver Cherry Creek'],
    ];
}
?>
</main>
<footer class="w-full bg-surface-container-low pt-section-gap pb-12 border-t border-outline-variant/20">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
      <div class="col-span-1">
        <img src="<?= e(site_logo_url()) ?>" alt="<?= e(site_name()) ?>" class="h-8 w-auto mb-6 opacity-80"/>
        <p class="font-body-md text-body-md text-on-surface-variant"><?= e(site_name()) ?> — luxury property listing with an editorial standard.</p>
      </div>
      <div class="flex flex-col gap-4">
        <h4 class="font-subheading text-subheading text-primary mb-2">OFFICES</h4>
        <p class="font-body-md text-body-md text-on-surface-variant">
          <?php
          $names = array_map(static fn ($o) => e((string) $o['name']), $offices);
          echo implode('<br/>', $names);
          ?>
        </p>
      </div>
      <div class="flex flex-col gap-4">
        <h4 class="font-subheading text-subheading text-primary mb-2">CONTACT</h4>
        <p class="font-body-md text-body-md text-on-surface-variant">
          <a class="hover:text-primary no-underline text-on-surface-variant" href="tel:<?= e(preg_replace('/\D+/', '', $phone) ?: $phone) ?>"><?= e($phone) ?></a><br/>
          <a class="hover:text-primary no-underline text-on-surface-variant" href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
        </p>
      </div>
      <div class="flex flex-col gap-4">
        <h4 class="font-subheading text-subheading text-primary mb-2">FOLLOW</h4>
        <div class="flex gap-4 text-on-surface-variant">
          <span class="material-symbols-outlined hover:text-primary cursor-pointer">share</span>
          <span class="material-symbols-outlined hover:text-primary cursor-pointer">public</span>
          <span class="material-symbols-outlined hover:text-primary cursor-pointer">play_circle</span>
        </div>
      </div>
    </div>
    <div class="pt-8 border-t border-outline-variant/30 flex flex-col md:flex-row justify-between items-center gap-4 text-label-sm font-label-sm text-on-surface-variant opacity-60">
      <span>&copy; <?= e(date('Y')) ?> <?= e(site_name()) ?></span>
      <div class="flex gap-6">
        <a class="hover:text-primary no-underline" href="<?= e(base_url('about.php')) ?>">About Us</a>
        <a class="hover:text-primary no-underline" href="<?= e(base_url('faq.php')) ?>">FAQ</a>
        <span>Privacy Policy</span>
        <span>Fair Housing</span>
      </div>
    </div>
  </div>
</footer>
</body>
</html>
