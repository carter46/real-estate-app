<?php
/**
 * Agents / Our Experts — from agents table.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$region = trim((string) ($_GET['region'] ?? ''));
$agents = [];
$dbOk = true;
try {
    $agents = agents_list_public($region !== '' ? $region : null);
} catch (Throwable $e) {
    $dbOk = false;
    error_log('[SDC] agents: ' . $e->getMessage());
}

$regions = ['Aspen Core', 'Vail', 'Denver Metro', 'Telluride'];
$pageTitle = 'Our Experts — ' . (string) app_config('app.name');
$navVariant = 'home';
require __DIR__ . '/includes/header.php';
?>
<section class="listings-hero">
    <p class="eyebrow">Advisors</p>
    <h1 class="display" style="font-size:clamp(2rem,4vw,3rem);">Our Experts</h1>
    <p class="lead">Meet the SDC specialists guiding Colorado’s most discerning buyers and sellers.</p>
</section>

<form class="filters" method="get" action="">
    <div>
        <label for="region">Region</label>
        <select id="region" name="region" onchange="this.form.submit()">
            <option value="">All Regions</option>
            <?php foreach ($regions as $r): ?>
                <option value="<?= e($r) ?>" <?= $region === $r ? 'selected' : '' ?>><?= e($r) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="container" style="padding-bottom:4rem;">
    <?php if (!$dbOk): ?>
        <p class="empty">Agents unavailable.</p>
    <?php elseif ($agents === []): ?>
        <p class="empty">No agents found for this filter.</p>
    <?php else: ?>
        <div class="agents-grid">
            <?php foreach ($agents as $agent): ?>
                <article class="agent-tile">
                    <?php if (!empty($agent['photo_path'])): ?>
                        <img class="agent-tile__photo" src="<?= e(media_url((string) $agent['photo_path'])) ?>" alt="">
                    <?php else: ?>
                        <div class="agent-tile__photo" aria-hidden="true"></div>
                    <?php endif; ?>
                    <?php if (!empty($agent['badge'])): ?>
                        <p class="eyebrow"><?= e((string) $agent['badge']) ?></p>
                    <?php endif; ?>
                    <h3><?= e((string) $agent['name']) ?></h3>
                    <p><?= e(trim(($agent['title'] ?? '') . (($agent['region'] ?? '') !== '' ? ' · ' . $agent['region'] : ''))) ?></p>
                    <p><?= e((string) ($agent['bio'] ?? '')) ?></p>
                    <div class="hero__actions" style="margin-top:1rem;">
                        <a class="btn btn--ghost" href="<?= e(base_url('contact.php')) ?>">Contact</a>
                        <a class="btn" href="<?= e(base_url('properties.php?q=' . rawurlencode((string) ($agent['region'] ?? '')))) ?>">View Listings</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
require __DIR__ . '/includes/footer.php';
