<?php
/**
 * Regions CMS — view / add / edit / deactivate + homepage featured + image.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
auth_require();

$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editing = $editId > 0 ? taxonomy_region_find($editId) : null;
$errors = [];
$ok = flash_get('region_ok');
$openModal = $editing !== null || $errors !== [];

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Invalid security token.';
        $openModal = true;
    } else {
        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'deactivate') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                taxonomy_region_deactivate($id);
                flash_set('region_ok', 'Region deactivated. Existing listings keep their region text.');
            }
            redirect('admin/regions.php');
        }
        if ($action === 'activate') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                db()->prepare('UPDATE regions SET is_active = 1 WHERE id = ?')->execute([$id]);
                flash_set('region_ok', 'Region activated.');
            }
            redirect('admin/regions.php');
        }

        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $sortRaw = trim((string) ($_POST['sort_order'] ?? ''));
        $sortOrder = $sortRaw === '' ? null : (int) $sortRaw;
        $isFeatured = !empty($_POST['is_featured']);
        // New regions always go live on Markets; edits keep the Active checkbox.
        $isActive = $id === null ? true : !empty($_POST['is_active']);
        $clearImage = !empty($_POST['clear_image']);
        $imagePath = null;

        $prevImage = '';
        if ($id) {
            $prev = taxonomy_region_find($id);
            $prevImage = is_array($prev) ? (string) ($prev['image_path'] ?? '') : '';
        }

        $file = $_FILES['image'] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $up = region_upload_image($file, str_starts_with($prevImage, 'uploads/regions/') ? $prevImage : null);
            if (!$up['ok']) {
                $errors[] = (string) $up['error'];
            } else {
                $imagePath = (string) $up['path'];
                $clearImage = false;
            }
        }

        if ($errors === []) {
            $result = taxonomy_region_save(
                $id,
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['slug'] ?? ''),
                $isActive,
                $isFeatured,
                $sortOrder,
                $imagePath,
                $clearImage
            );
            if (!$result['ok']) {
                $errors[] = (string) $result['error'];
            } else {
                if ($id) {
                    flash_set(
                        'region_ok',
                        $isActive
                            ? 'Region updated. Changes are live on the Markets page.'
                            : 'Region updated. It is inactive, so it is hidden from the Markets page.'
                    );
                } else {
                    flash_set('region_ok', 'Region created. It now appears on the Markets page.');
                }
                redirect('admin/regions.php');
            }
        }

        if ($errors !== []) {
            $editing = [
                'id' => $id,
                'name' => $_POST['name'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'sort_order' => $sortOrder ?? ($editing['sort_order'] ?? 0),
                'is_active' => $isActive ? 1 : 0,
                'is_featured' => $isFeatured ? 1 : 0,
                'image_path' => $imagePath ?? ($editing['image_path'] ?? $prevImage),
            ];
            $openModal = true;
        }
    }
}

$rows = regions_all(false);
$adminPageTitle = 'Regions';
$adminActiveNav = 'regions';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-page-head">
  <div>
    <span class="admin-eyebrow">Taxonomy</span>
    <h1 class="admin-page-title">Regions</h1>
    <p class="admin-page-lead">Destinations for listings and filters. Every <strong>Active</strong> region is listed on the public Markets page automatically (including new and updated ones). Featured + image controls the homepage Exclusive Collections preview (first 3 by sort order).</p>
  </div>
  <div class="admin-actions" style="gap:0.5rem;">
    <a class="admin-btn admin-btn--ghost" href="<?= e(base_url('markets.php')) ?>" target="_blank" rel="noopener">View Markets page</a>
    <button type="button" class="admin-btn" data-modal-open="region-modal">Add region</button>
  </div>
</div>

<?php if ($ok): ?><div class="admin-alert admin-alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php foreach ($errors as $err): ?><div class="admin-alert admin-alert--error"><?= e($err) ?></div><?php endforeach; ?>

<div class="admin-panel">
  <h2>All regions</h2>
  <?php if ($rows === []): ?>
    <p class="admin-note">No regions yet. Import <code>migrations_regions.sql</code> (and <code>migrations_regions_home.sql</code> if upgrading) or use “Add region”.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th></th><th>Name</th><th>Sort</th><th>Homepage</th><th>Status</th><th>In use</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <?php
          $thumb = trim((string) ($row['image_path'] ?? ''));
          $thumbUrl = $thumb !== '' ? media_url($thumb) : '';
        ?>
        <tr>
          <td style="width:3.5rem;">
            <?php if ($thumbUrl !== ''): ?>
              <img src="<?= e($thumbUrl) ?>" alt="" style="width:2.75rem;height:2.75rem;object-fit:cover;border-radius:4px;background:#eee;">
            <?php else: ?>
              <span class="admin-note">—</span>
            <?php endif; ?>
          </td>
          <td><?= e((string) $row['name']) ?></td>
          <td><?= e((string) $row['sort_order']) ?></td>
          <td><?= !empty($row['is_featured']) ? 'Featured' : '—' ?></td>
          <td><?= !empty($row['is_active']) ? 'Active' : 'Inactive' ?></td>
          <td><?= e((string) taxonomy_region_usage_count((int) $row['id'])) ?></td>
          <td>
            <div class="admin-menu">
              <button type="button" class="admin-menu__toggle" aria-label="Actions" aria-haspopup="true">⋯</button>
              <div class="admin-menu__panel" hidden>
                <a href="<?= e(base_url('admin/regions.php?id=' . (int) $row['id'])) ?>">Edit</a>
                <?php if (!empty($row['is_active'])): ?>
                  <form method="post" onsubmit="return confirm('Deactivate this region?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="deactivate">
                    <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                    <button type="submit">Deactivate</button>
                  </form>
                <?php else: ?>
                  <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                    <button type="submit">Activate</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="admin-modal<?= $openModal ? ' is-open' : '' ?>" id="region-modal" role="dialog" aria-modal="true" aria-labelledby="region-modal-title">
  <div class="admin-modal__backdrop" data-modal-close></div>
  <div class="admin-modal__dialog">
    <div class="admin-modal__head">
      <h2 id="region-modal-title"><?= $editing ? 'Edit region' : 'Add region' ?></h2>
      <button type="button" class="admin-modal__close" data-modal-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <?php if ($editing && !empty($editing['id'])): ?>
        <input type="hidden" name="id" value="<?= e((string) $editing['id']) ?>">
      <?php endif; ?>
      <div class="admin-field">
        <label for="name">Name *</label>
        <input id="name" name="name" required value="<?= e((string) ($editing['name'] ?? '')) ?>">
      </div>
      <div class="admin-field">
        <label for="slug">Slug</label>
        <input id="slug" name="slug" value="<?= e((string) ($editing['slug'] ?? '')) ?>" placeholder="auto from name">
      </div>
      <div class="admin-field">
        <label for="sort_order">Display order</label>
        <input id="sort_order" name="sort_order" type="number" value="<?= e((string) ($editing['sort_order'] ?? '')) ?>" placeholder="auto on create">
        <p class="admin-note">Lower numbers appear first on the Markets page and the homepage featured row.</p>
      </div>
      <div class="admin-field">
        <label><input type="checkbox" name="is_featured" value="1" <?= !empty($editing['is_featured']) ? 'checked' : '' ?>> Featured on homepage (Exclusive Collections, top 3 by order)</label>
      </div>
      <div class="admin-field">
        <label for="image">Collection image</label>
        <?php
          $editImg = trim((string) ($editing['image_path'] ?? ''));
          if ($editImg !== ''):
        ?>
          <p style="margin:0 0 0.5rem;"><img src="<?= e(media_url($editImg)) ?>" alt="" style="max-height:96px;border-radius:4px;"></p>
          <label><input type="checkbox" name="clear_image" value="1"> Remove current image</label>
        <?php endif; ?>
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
        <p class="admin-note">JPEG, PNG, or WebP. Recommended wide landscape photo.</p>
      </div>
      <div class="admin-field">
        <label><input type="checkbox" name="is_active" value="1" <?= !isset($editing['is_active']) || !empty($editing['is_active']) ? 'checked' : '' ?><?= empty($editing['id']) ? ' disabled' : '' ?>> Active (shown on Markets page and in listing filters)</label>
        <?php if (empty($editing['id'])): ?>
          <input type="hidden" name="is_active" value="1">
          <p class="admin-note">New regions are always Active and appear on the Markets page immediately. You can deactivate later from the list.</p>
        <?php endif; ?>
      </div>
      <div class="admin-actions">
        <button class="admin-btn" type="submit"><?= $editing ? 'Update' : 'Create' ?></button>
        <?php if ($editing): ?>
          <a class="admin-btn admin-btn--ghost" href="<?= e(base_url('admin/regions.php')) ?>">Cancel</a>
        <?php else: ?>
          <button type="button" class="admin-btn admin-btn--ghost" data-modal-close>Close</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php';
