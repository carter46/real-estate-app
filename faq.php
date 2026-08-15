<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'FAQ — ' . (string) app_config('app.name');
$navVariant = 'content';
require __DIR__ . '/includes/header.php';
$faqs = [
    ['q' => 'How do I schedule a private showing?', 'a' => 'Contact an SDC advisor through the Contact page or a property detail inquiry CTA. Full inquiry email delivery is enabled in Phase 5.'],
    ['q' => 'Are listings kept current?', 'a' => 'Yes. Public pages read from MySQL. When an admin updates a property, the public site reflects the change automatically.'],
    ['q' => 'Do you handle both mountain and metro properties?', 'a' => 'SDC covers Colorado mountain and metro markets represented in the portfolio database.'],
];
?>
<section class="listings-hero">
    <p class="eyebrow">Support</p>
    <h1 class="display" style="font-size:clamp(2rem,4vw,3rem);">Expertise, Clearly Defined.</h1>
</section>
<section class="section">
    <div class="container">
        <?php foreach ($faqs as $faq): ?>
            <details style="border-bottom:1px solid rgba(215,193,197,.45);padding:1rem 0;">
                <summary class="headline" style="font-size:1.25rem;cursor:pointer;"><?= e($faq['q']) ?></summary>
                <p class="lead"><?= e($faq['a']) ?></p>
            </details>
        <?php endforeach; ?>
        <div class="section-cta"><a class="btn" href="<?= e(base_url('contact.php')) ?>">Still have questions?</a></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
