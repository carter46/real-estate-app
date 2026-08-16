<?php
/**
 * Regions CMS — view / add / edit / deactivate (no hard delete).
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
        $result = taxonomy_region_save(
            $id,
            (string) ($_POST['name'] ?? ''),
            (string) ($_POST['slug'] ?? ''),
            (int) ($_POST['sort_order'] ?? 0),
            !empty($_POST['is_active'])
        );
        if (!$result['ok']) {
            $errors[] = (string) $result['error'];
            $editing = [
                'id' => $id,
                'name' => $_POST['name'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
            ];
            $openModal = true;
        } else {
            flash_set('region_ok', $id ? 'Region updated.' : 'Region created.');
            redirect('admin/regions.php');
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
    <p class="admin-page-lead">Destinations used on property forms and public filters. Deactivate to hide from new listings without changing existing data.</p>
  </div>
  <button type="button" class="admin-btn" data-modal-open="region-modal">Add region</button>
</div>

<?php if ($ok): ?><div class="admin-alert admin-alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php foreach ($errors as $err): ?><div class="admin-alert admin-alert--error"><?= e($err) ?></div><?php endforeach; ?>

<div class="admin-panel">
  <h2>All regions</h2>
  <?php if ($rows === []): ?>
    <p class="admin-note">No regions yet. Import <code>migrations_regions.sql</code> or use “Add region”.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Slug</th><th>Sort</th><th>Status</th><th>In use</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e((string) $row['name']) ?></td>
          <td><?= e((string) $row['slug']) ?></td>
          <td><?= e((string) $row['sort_order']) ?></td>
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
    <form method="post" action="">
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
        <label for="sort_order">Sort order</label>
        <input id="sort_order" name="sort_order" type="number" value="<?= e((string) ($editing['sort_order'] ?? '0')) ?>">
      </div>
      <div class="admin-field">
        <label><input type="checkbox" name="is_active" value="1" <?= !isset($editing['is_active']) || !empty($editing['is_active']) ? 'checked' : '' ?>> Active</label>
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
