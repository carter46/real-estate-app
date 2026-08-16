<?php
/**
 * Property listings — Stitch filter/grid structure + DB data.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'location' => trim((string) ($_GET['location'] ?? '')),
    'region' => trim((string) ($_GET['region'] ?? '')),
    'type' => trim((string) ($_GET['type'] ?? '')),
    'price' => trim((string) ($_GET['price'] ?? '')),
    'agent_id' => max(0, (int) ($_GET['agent_id'] ?? 0)),
];
$sort = (string) ($_GET['sort'] ?? 'newest');
if (!in_array($sort, ['newest', 'price_asc', 'price_desc'], true)) {
    $sort = 'newest';
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$agentFilter = null;
if ($filters['agent_id'] > 0) {
    try {
        $stmt = db()->prepare('SELECT id, name FROM agents WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$filters['agent_id']]);
        $row = $stmt->fetch();
        $agentFilter = is_array($row) ? $row : null;
        if ($agentFilter === null) {
            $filters['agent_id'] = 0;
        }
    } catch (Throwable $e) {
        $filters['agent_id'] = 0;
        $agentFilter = null;
    }
}

$total = 0;
$rows = [];
$types = [];
$dbOk = true;
try {
    $total = property_count_public($filters);
    $rows = property_list_public($filters, $sort, $perPage, $offset);
    $types = property_types_all(true);
} catch (Throwable $e) {
    $dbOk = false;
    error_log('[SDC] listings: ' . $e->getMessage());
}

$featuredRow = null;
$standardRows = [];
foreach ($rows as $row) {
    if ($featuredRow === null && (!empty($row['is_featured']) || ($row['badge'] ?? '') === 'Signature Property')) {
        $featuredRow = $row;
    } else {
        $standardRows[] = $row;
    }
}

$pageTitle = "Colorado's Finest Estates — " . site_name();
$activeNav = 'properties';
$regionOptions = regions_all(true);
require __DIR__ . '/includes/header.php';
?>
<section class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop pt-16 pb-8">
  <p class="font-subheading text-subheading uppercase tracking-widest text-primary mb-3">Curated Portfolio</p>
  <h1 class="font-display-lg text-display-lg-mobile lg:text-[48px] text-on-surface mb-3">Colorado's Finest Estates</h1>
  <?php if ($agentFilter): ?>
    <p class="font-body-lg text-body-lg text-on-surface-variant font-light max-w-2xl">
      Listings with <strong class="text-on-surface font-normal"><?= e((string) $agentFilter['name']) ?></strong>.
      <a class="text-primary hover:underline ml-2" href="<?= e(base_url('properties.php')) ?>">Clear agent filter</a>
    </p>
  <?php else: ?>
    <p class="font-body-lg text-body-lg text-on-surface-variant font-light max-w-2xl">Search and filter live inventory from the SDC database.</p>
  <?php endif; ?>
</section>

<form method="get" action="<?= e(base_url('properties.php')) ?>" class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop pb-8 flex flex-wrap gap-4 items-end border-b border-outline-variant/40">
  <div>
    <label class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant block mb-2" for="region">Destination</label>
    <select id="region" name="region" class="border border-outline-variant/60 bg-surface px-3 py-2 font-body-md">
      <option value="">All Destinations</option>
      <?php foreach ($regionOptions as $destRow): ?>
        <?php $dest = (string) ($destRow['name'] ?? ''); ?>
        <option value="<?= e($dest) ?>" <?= $filters['region'] === $dest ? 'selected' : '' ?>><?= e($dest) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant block mb-2" for="price">Price</label>
    <select id="price" name="price" class="border border-outline-variant/60 bg-surface px-3 py-2 font-body-md">
      <option value="">Price Range</option>
      <option value="2-5" <?= $filters['price'] === '2-5' ? 'selected' : '' ?>>$2M–$5M</option>
      <option value="5-10" <?= $filters['price'] === '5-10' ? 'selected' : '' ?>>$5M–$10M</option>
      <option value="10+" <?= $filters['price'] === '10+' ? 'selected' : '' ?>>$10M+</option>
    </select>
  </div>
  <div>
    <label class="font-label-sm text-label-sm uppercase tracking-widest text-on-surface-variant block mb-2" for="type">Property Type</label>
    <select id="type" name="type" class="border border-outline-variant/60 bg-surface px-3 py-2 font-body-md">
      <option value="">Property Type</option>
      <?php foreach ($types as $t): ?>
        <option value="<?= e((string) $t['slug']) ?>" <?= $filters['type'] === $t['slug'] ? 'selected' : '' ?>><?= e((string) $t['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php if ($filters['q'] !== ''): ?><input type="hidden" name="q" value="<?= e($filters['q']) ?>"><?php endif; ?>
  <?php if ($filters['location'] !== ''): ?><input type="hidden" name="location" value="<?= e($filters['location']) ?>"><?php endif; ?>
  <?php if ($filters['agent_id'] > 0): ?><input type="hidden" name="agent_id" value="<?= e((string) $filters['agent_id']) ?>"><?php endif; ?>
  <button type="submit" class="px-6 py-2.5 bg-primary text-on-primary font-label-sm text-label-sm uppercase tracking-widest inline-flex items-center gap-2">
    Refine Search <span class="material-symbols-outlined text-[18px]">tune</span>
  </button>
</form>

<div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop py-6 flex flex-wrap justify-between gap-4 items-center">
  <p class="font-body-md text-body-md text-on-surface">Showing <strong><?= e((string) $total) ?></strong> Exclusive Properties</p>
  <form method="get" class="flex items-center gap-2">
    <?php foreach ($filters as $k => $v): ?>
      <?php if ($v === '' || $v === 0 || $v === null) {
          continue;
      } ?>
      <input type="hidden" name="<?= e($k) ?>" value="<?= e((string) $v) ?>">
    <?php endforeach; ?>
    <span class="material-symbols-outlined text-on-surface-variant">sort</span>
    <select name="sort" onchange="this.form.submit()" class="border-0 border-b border-on-surface bg-transparent font-body-md py-1">
      <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price High→Low</option>
      <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price Low→High</option>
    </select>
  </form>
</div>

<div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop pb-20">
  <?php if (!$dbOk): ?>
    <p class="text-on-surface-variant">Listings unavailable.</p>
  <?php elseif ($rows === []): ?>
    <p class="text-on-surface-variant py-16">
      <?php if ($agentFilter): ?>
        No public listings are assigned to <?= e((string) $agentFilter['name']) ?> yet.
      <?php else: ?>
        No properties match these filters.
      <?php endif; ?>
    </p>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <?php if ($featuredRow): $property = $featuredRow; require __DIR__ . '/includes/property-card-featured.php'; endif; ?>
      <?php foreach ($standardRows as $property): require __DIR__ . '/includes/property-card-list.php'; endforeach; ?>
    </div>
    <?php if ($offset + count($rows) < $total): ?>
      <div class="mt-12 text-center">
        <a class="inline-flex items-center gap-2 px-8 py-3 border border-primary text-primary font-label-sm text-label-sm uppercase tracking-widest hover:bg-primary hover:text-on-primary transition-colors"
           href="<?= e(base_url('properties.php?' . http_build_query(array_filter(array_merge($filters, ['sort' => $sort, 'page' => $page + 1]), static fn ($v) => $v !== '' && $v !== 0 && $v !== null)))) ?>">
          Load More Estates <span class="material-symbols-outlined">arrow_downward</span>
        </a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php';
