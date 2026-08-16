<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'FAQ — ' . site_name();
$activeNav = 'faq';
require __DIR__ . '/includes/header.php';

$faqGroups = [
    [
        'title' => 'Buying &amp; Showings',
        'items' => [
            ['q' => 'How do I schedule a private showing?', 'a' => 'Contact an SDC advisor through the Contact page or a property detail inquiry form. Your message is stored securely and emailed to our team.'],
            ['q' => 'Are listings kept current?', 'a' => 'Yes. Public pages read from MySQL. When an admin updates a property, the public site reflects the change automatically.'],
            ['q' => 'Do you handle both mountain and metro properties?', 'a' => 'SDC covers Colorado mountain and metro markets represented in the portfolio database.'],
        ],
    ],
    [
        'title' => 'Working With SDC',
        'items' => [
            ['q' => 'How do I list my property?', 'a' => 'Use List With Us or Contact to reach an advisor. Portfolio management is handled in the secure admin console.'],
            ['q' => 'Who sees my inquiry?', 'a' => 'Inquiries are reviewed by authorized SDC administrators. You also receive an acknowledgment email when mail delivery is configured.'],
        ],
    ],
];
?>
<section class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop pt-16 pb-8">
  <p class="font-subheading text-subheading uppercase tracking-widest text-primary mb-3">Support</p>
  <h1 class="font-display-lg text-display-lg-mobile lg:text-[48px] text-on-surface mb-4">Expertise, Clearly Defined.</h1>
  <p class="font-body-lg text-body-lg text-on-surface-variant font-light max-w-2xl">Answers to common questions about SDC listings, showings, and inquiries.</p>
</section>

<section class="w-full bg-background py-section-gap px-margin-mobile lg:px-margin-desktop">
  <div class="max-w-[900px] mx-auto flex flex-col gap-16">
    <?php foreach ($faqGroups as $group): ?>
      <div>
        <h2 class="font-headline-md text-headline-md text-on-surface mb-6"><?= $group['title'] ?></h2>
        <div class="flex flex-col gap-0 faq-accordion-group">
          <?php foreach ($group['items'] as $faq): ?>
            <details class="group border-b border-outline-variant/30 py-6">
              <summary class="flex justify-between items-center cursor-pointer list-none">
                <span class="font-body-lg text-body-lg text-on-surface pr-6"><?= e($faq['q']) ?></span>
                <span class="w-8 h-8 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface-variant shrink-0 group-open:rotate-45 transition-transform">
                  <span class="material-symbols-outlined text-[20px]">add</span>
                </span>
              </summary>
              <div class="pt-4 font-body-md text-body-md text-on-surface-variant max-w-prose">
                <?= e($faq['a']) ?>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="text-center pt-8">
      <p class="font-body-md text-on-surface-variant mb-4">Still have questions?</p>
      <a href="<?= e(base_url('contact.php')) ?>" class="inline-flex px-8 py-3 bg-primary text-on-primary font-label-sm text-label-sm uppercase tracking-widest hover:bg-primary-container transition-colors no-underline">Contact Us</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
