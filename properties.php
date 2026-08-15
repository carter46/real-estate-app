<?php
/**
 * Property listings + search/filter results (same visual card system).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'location' => trim((string) ($_GET['location'] ?? '')),
    'region' => trim((string) ($_GET['region'] ?? '')),
    'type' => trim((string) ($_GET['type'] ?? '')),
    'price' => trim((string) ($_GET['price'] ?? '')),
];
$sort = (string) ($_GET['sort'] ?? 'newest');
if (!in_array($sort, ['newest', 'price_asc', 'price_desc'], true)) {
    $sort = 'newest';
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$total = 0;
$rows = [];
$dbOk = true;
try {
    $total = property_count_public($filters);
    $rows = property_list_public($filters, $sort, $perPage, $offset);
} catch (Throwable $e) {
    $dbOk = false;
    error_log('[SDC] listings: ' . $e->getMessage());
}

$types = [];
try {
    $types = property_types_all();
} catch (Throwable $e) {
    $types = [];
}

// Signature / featured card: first featured in result set, else none
$featuredRow = null;
$standardRows = [];
foreach ($rows as $row) {
    if ($featuredRow === null && (!empty($row['is_featured']) || ($row['badge'] ?? '') === 'Signature Property')) {
        $featuredRow = $row;
    } else {
        $standardRows[] = $row;
    }
}

$pageTitle = 'Colorado\'s Finest Estates — ' . (string) app_config('app.name');
$navVariant = 'home';
require __DIR__ . '/includes/header.php';
?>
<section class="listings-hero">
    <p class="eyebrow">Curated Portfolio</p>
    <h1 class="display" style="font-size:clamp(2rem,4vw,3rem);">Colorado's Finest Estates</h1>
    <p class="lead">Search and filter live inventory from the SDC database.</p>
</section>

<form class="filters" method="get" action="<?= e(base_url('properties.php')) ?>">
    <div>
        <label for="region">Destination</label>
        <select id="region" name="region">
            <option value="">All Destinations</option>
            <?php foreach (['Aspen', 'Vail', 'Telluride', 'Steamboat', 'Beaver Creek', 'Snowmass', 'Denver Metro'] as $dest): ?>
                <option value="<?= e($dest) ?>" <?= $filters['region'] === $dest ? 'selected' : '' ?>><?= e($dest) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="price">Price</label>
        <select id="price" name="price">
            <option value="">Price Range</option>
            <option value="2-5" <?= $filters['price'] === '2-5' ? 'selected' : '' ?>>$2M–$5M</option>
            <option value="5-10" <?= $filters['price'] === '5-10' ? 'selected' : '' ?>>$5M–$10M</option>
            <option value="10+" <?= $filters['price'] === '10+' ? 'selected' : '' ?>>$10M+</option>
        </select>
    </div>
    <div>
        <label for="type">Property Type</label>
        <select id="type" name="type">
            <option value="">Property Type</option>
            <?php foreach ($types as $t): ?>
                <option value="<?= e((string) $t['slug']) ?>" <?= $filters['type'] === $t['slug'] ? 'selected' : '' ?>><?= e((string) $t['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($filters['q'] !== ''): ?>
        <input type="hidden" name="q" value="<?= e($filters['q']) ?>">
    <?php endif; ?>
    <?php if ($filters['location'] !== ''): ?>
        <input type="hidden" name="location" value="<?= e($filters['location']) ?>">
    <?php endif; ?>
    <button class="btn" type="submit">Refine Search</button>
</form>

<div class="results-meta">
    <p>Showing <strong><?= e((string) $total) ?></strong> Exclusive Properties</p>
    <form method="get" action="">
        <?php foreach ($filters as $k => $v): ?>
            <?php if ($v !== ''): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif; ?>
        <?php endforeach; ?>
        <label for="sort">Sort</label>
        <select id="sort" name="sort" onchange="this.form.submit()">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price High→Low</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price Low→High</option>
        </select>
    </form>
</div>

<div class="container" style="padding-bottom:4rem;">
    <?php if (!$dbOk): ?>
        <p class="empty">Listings unavailable. Check database configuration.</p>
    <?php elseif ($rows === []): ?>
        <p class="empty">No properties match these filters.</p>
    <?php else: ?>
        <div class="cards-grid">
            <?php if ($featuredRow): ?>
                <?php $property = $featuredRow; require __DIR__ . '/includes/property-card-featured.php'; ?>
            <?php endif; ?>
            <?php foreach ($standardRows as $property): ?>
                <?php require __DIR__ . '/includes/property-card-list.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php if ($offset + count($rows) < $total): ?>
            <div class="section-cta" style="text-align:center;">
                <a class="btn btn--ghost" href="<?= e(base_url('properties.php?' . http_build_query(array_filter(array_merge($filters, ['sort' => $sort, 'page' => $page + 1]))))) ?>">Load More Estates</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php
require __DIR__ . '/includes/footer.php';
