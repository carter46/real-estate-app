<?php
/**
 * Manage Properties — list, search, status filter (Phase 3).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
auth_require();

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$total = property_count_admin($q, $status);
$rows = property_list_admin($q, $status, 50, 0);
$flashOk = flash_get('property_ok');
$flashErr = flash_get('property_error');

$adminPageTitle = 'Manage Properties';
$adminActiveNav = 'properties';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<span class="admin-eyebrow">Portfolio Manager</span>
<h1 class="admin-page-title">Manage Properties</h1>
<p class="admin-page-lead">Showing <?= e((string) count($rows)) ?> of <?= e((string) $total) ?> matching listings.</p>

<?php if ($flashOk): ?><div class="admin-alert admin-alert--ok"><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="admin-alert admin-alert--error"><?= e($flashErr) ?></div><?php endif; ?>

<div class="admin-actions">
    <a class="admin-btn" href="<?= e(base_url('admin/property-form.php')) ?>">New Listing</a>
</div>

<form class="admin-panel" method="get" action="">
    <div class="admin-toolbar">
        <div class="admin-field" style="flex:1;min-width:12rem;margin:0;">
            <label for="q">Search</label>
            <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Address, MLS #, reference, title…">
        </div>
        <div class="admin-field" style="min-width:10rem;margin:0;">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach (property_statuses() as $st): ?>
                    <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $st))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="admin-btn" type="submit" style="align-self:end;">Filter</button>
        <a class="admin-btn admin-btn--ghost" style="align-self:end;" href="<?= e(base_url('admin/properties.php')) ?>">Clear</a>
    </div>
</form>

<section class="admin-panel" style="padding:0;overflow:visible;">
    <table class="admin-table">
        <thead>
            <tr>
                <th></th>
                <th>Property</th>
                <th>Price</th>
                <th>Status</th>
                <th>Featured</th>
                <th>Listed</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($rows === []): ?>
            <tr><td colspan="7" class="admin-note" style="padding:1.25rem;">No properties match these filters.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <?php
                $id = (int) $row['id'];
                $cover = (string) ($row['cover_path'] ?? '');
                $priceLabel = format_price(
                    isset($row['price']) ? (float) $row['price'] : null,
                    !empty($row['price_on_request']),
                    (string) ($row['currency'] ?? 'USD')
                );
                $public = is_property_status_public((string) $row['status']);
                ?>
                <tr>
                    <td class="admin-table__thumb">
                        <?php if ($cover !== ''): ?>
                            <img src="<?= e(media_url($cover)) ?>" alt="">
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= e((string) $row['title']) ?></strong><br>
                        <span class="admin-muted"><?= e(trim(($row['city'] ?? '') . ' ' . ($row['postal_code'] ?? ''))) ?></span><br>
                        <span class="admin-muted"><?= e((string) ($row['reference_code'] ?? '')) ?><?= !empty($row['mls_number']) ? ' · MLS# ' . e((string) $row['mls_number']) : '' ?></span>
                    </td>
                    <td><?= e($priceLabel) ?></td>
                    <td><span class="admin-badge"><?= e(str_replace('_', ' ', (string) $row['status'])) ?></span></td>
                    <td><?= !empty($row['is_featured']) ? 'Yes' : '—' ?></td>
                    <td><?= e((string) ($row['listed_at'] ?? '—')) ?></td>
                    <td>
                        <div class="admin-menu">
                            <button type="button" class="admin-menu__toggle" aria-label="Actions" aria-haspopup="true">⋯</button>
                            <div class="admin-menu__panel" hidden>
                                <a href="<?= e(base_url('admin/property-form.php?id=' . $id)) ?>">Edit</a>
                                <?php if ($public): ?>
                                    <a href="<?= e(base_url('property.php?slug=' . rawurlencode((string) $row['slug']))) ?>" target="_blank" rel="noopener">View on site</a>
                                <?php endif; ?>
                                <?php if (($row['status'] ?? '') !== 'archived'): ?>
                                    <form method="post" action="<?= e(base_url('admin/property-archive.php')) ?>" onsubmit="return confirm('Archive this listing?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <button type="submit">Archive</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php
require dirname(__DIR__) . '/includes/admin-footer.php';
