<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'About Us — ' . (string) app_config('app.name');
$navVariant = 'content';
require __DIR__ . '/includes/header.php';
?>
<section class="listings-hero">
    <p class="eyebrow">Since Our Founding</p>
    <h1 class="display" style="font-size:clamp(2rem,4vw,3rem);">The Colorado Standard</h1>
    <p class="lead">Sunview Development and Consultancy (SDC) presents properties with the same editorial clarity as the Stitch reference — content and brand are SDC; layout fidelity continues in Phase 6.</p>
</section>
<section class="section">
    <div class="container">
        <h2 class="headline">Rooted in the Rockies</h2>
        <p class="lead">Heritage, discretion, and market expertise define the SDC approach to luxury real estate advisory and property presentation.</p>
        <div class="section-cta"><a class="btn" href="<?= e(base_url('contact.php')) ?>">Connect With Our Experts</a></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
