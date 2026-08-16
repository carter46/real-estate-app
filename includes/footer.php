<?php
/**
 * Public footer — SDC branding.
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
<footer class="w-full pt-section-gap pb-12" style="background-color: #370518;">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
      <div class="col-span-1">
        <img src="<?= e(site_logo_url()) ?>" alt="<?= e(site_name()) ?>" class="h-8 w-auto mb-6 opacity-90 brightness-0 invert"/>
        <p class="font-body-md text-body-md text-white/75"><?= e(site_name()) ?> — luxury property listing with an editorial standard.</p>
      </div>
      <div class="flex flex-col gap-4">
        <h4 class="font-subheading text-subheading text-white mb-2">OFFICES</h4>
        <p class="font-body-md text-body-md text-white/75">
          <?php
          $names = array_map(static fn ($o) => e((string) $o['name']), $offices);
          echo implode('<br/>', $names);
          ?>
        </p>
      </div>
      <div class="flex flex-col gap-4">
        <h4 class="font-subheading text-subheading text-white mb-2">CONTACT</h4>
        <p class="font-body-md text-body-md text-white/75">
          <a class="hover:text-white no-underline text-white/75" href="tel:<?= e(preg_replace('/\D+/', '', $phone) ?: $phone) ?>"><?= e($phone) ?></a><br/>
          <a class="hover:text-white no-underline text-white/75" href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
        </p>
      </div>
      <div class="flex flex-col gap-4">
        <h4 class="font-subheading text-subheading text-white mb-2">EXPLORE</h4>
        <p class="font-body-md text-body-md text-white/75 flex flex-col gap-2">
          <a class="hover:text-white no-underline text-white/75" href="<?= e(base_url('properties.php')) ?>">Properties</a>
          <a class="hover:text-white no-underline text-white/75" href="<?= e(base_url('agents.php')) ?>">Agents</a>
          <a class="hover:text-white no-underline text-white/75" href="<?= e(base_url('about.php')) ?>">About Us</a>
          <a class="hover:text-white no-underline text-white/75" href="<?= e(base_url('contact.php')) ?>">Contact</a>
          <a class="hover:text-white no-underline text-white/75" href="<?= e(base_url('faq.php')) ?>">FAQ</a>
        </p>
      </div>
    </div>
    <div class="pt-8 border-t border-white/20 flex flex-col md:flex-row justify-between items-center gap-4 text-label-sm font-label-sm text-white/55">
      <span>&copy; <?= e(date('Y')) ?> <?= e(site_name()) ?></span>
      <div class="flex gap-6">
        <a class="hover:text-white no-underline text-white/55" href="<?= e(base_url('about.php')) ?>">About Us</a>
        <a class="hover:text-white no-underline text-white/55" href="<?= e(base_url('faq.php')) ?>">FAQ</a>
        <span>Privacy Policy</span>
        <span>Fair Housing</span>
      </div>
    </div>
  </div>
</footer>
</body>
</html>
