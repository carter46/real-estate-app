<?php
/**
 * Public footer — Stitch structure, SDC branding.
 */

declare(strict_types=1);

$offices = [];
$phone = '800.555.0123';
$email = 'info@example.com';
try {
    $offices = offices_list_public();
    $phone = setting_get('site_phone', $phone) ?? $phone;
    $email = setting_get('site_email', $email) ?? $email;
} catch (Throwable $e) {
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
<footer class="bg-primary text-on-primary mt-section-gap">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop py-16 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-10">
    <div>
      <div class="sdc-logo-mark mb-4">SDC</div>
      <p class="font-body-md text-body-md text-on-primary/80 font-light">Sunview Development and Consultancy — luxury property listing with an editorial standard.</p>
    </div>
    <div>
      <h4 class="font-subheading text-label-sm uppercase tracking-widest text-primary-fixed-dim mb-4">Offices</h4>
      <ul class="space-y-2 font-body-md text-body-md text-on-primary/80">
        <?php foreach ($offices as $office): ?>
          <li><?= e((string) $office['name']) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h4 class="font-subheading text-label-sm uppercase tracking-widest text-primary-fixed-dim mb-4">Contact</h4>
      <ul class="space-y-2 font-body-md text-body-md">
        <li><a class="text-on-primary/80 hover:text-on-primary" href="tel:<?= e(preg_replace('/\D+/', '', $phone) ?: $phone) ?>"><?= e($phone) ?></a></li>
        <li><a class="text-on-primary/80 hover:text-on-primary" href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
      </ul>
    </div>
    <div>
      <h4 class="font-subheading text-label-sm uppercase tracking-widest text-primary-fixed-dim mb-4">Follow</h4>
      <div class="flex gap-3 text-on-primary/80">
        <span class="material-symbols-outlined">public</span>
        <span class="material-symbols-outlined">share</span>
        <span class="material-symbols-outlined">alternate_email</span>
      </div>
    </div>
  </div>
  <div class="border-t border-on-primary/15">
    <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop py-6 flex flex-col md:flex-row gap-3 justify-between font-body-md text-sm text-on-primary/70">
      <span>&copy; <?= e(date('Y')) ?> Sunview Development and Consultancy (SDC)</span>
      <span class="flex flex-wrap gap-x-4 gap-y-2">
        <a class="hover:text-on-primary" href="<?= e(base_url('about.php')) ?>">About Us</a>
        <a class="hover:text-on-primary" href="<?= e(base_url('faq.php')) ?>">FAQ</a>
        <span>Privacy Policy</span>
        <span>Fair Housing</span>
      </span>
    </div>
  </div>
</footer>
</body>
</html>
