<?php
/**
 * Amenities CMS — view / add / edit / deactivate (no hard delete).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
auth_require();

$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editing = $editId > 0 ? taxonomy_amenity_find($editId) : null;
$errors = [];
$ok = flash_get('amenity_ok');
$categories = ['interior', 'exterior', 'community', 'other'];
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
                taxonomy_amenity_deactivate($id);
                flash_set('amenity_ok', 'Amenity deactivated. Existing property assignments are kept.');
            }
            redirect('admin/amenities.php');
        }
        if ($action === 'activate') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                db()->prepare('UPDATE amenities SET is_active = 1 WHERE id = ?')->execute([$id]);
                flash_set('amenity_ok', 'Amenity activated.');
            }
            redirect('admin/amenities.php');
        }

        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $result = taxonomy_amenity_save(
            $id,
            (string) ($_POST['name'] ?? ''),
            (string) ($_POST['slug'] ?? ''),
            (string) ($_POST['category'] ?? 'other'),
            (int) ($_POST['sort_order'] ?? 0),
            !empty($_POST['is_active'])
        );
        if (!$result['ok']) {
            $errors[] = (string) $result['error'];
            $editing = [
                'id' => $id,
                'name' => $_POST['name'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'category' => $_POST['category'] ?? 'other',
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
            ];
            $openModal = true;
        } else {
            flash_set('amenity_ok', $id ? 'Amenity updated.' : 'Amenity created.');
            redirect('admin/amenities.php');
        }
    }
}

$rows = property_amenities_all(false);
$adminPageTitle = 'Amenities';
$adminActiveNav = 'amenities';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-page-head">
  <div>
    <span class="admin-eyebrow">Taxonomy</span>
    <h1 class="admin-page-title">Amenities</h1>
    <p class="admin-page-lead">Manage amenity checkboxes on the property editor. Deactivate to hide from new assignments without orphaning data.</p>
  </div>
  <button type="button" class="admin-btn" data-modal-open="amenity-modal">Add amenity</button>
</div>

<?php if ($ok): ?><div class="admin-alert admin-alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php foreach ($errors as $err): ?><div class="admin-alert admin-alert--error"><?= e($err) ?></div><?php endforeach; ?>

<div class="admin-panel">
  <h2>All amenities</h2>
  <?php if ($rows === []): ?>
    <p class="admin-note">No amenities yet. Use “Add amenity” to create one.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Category</th><th>Status</th><th>In use</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e((string) $row['name']) ?></td>
          <td><?= e((string) $row['category']) ?></td>
          <td><?= !empty($row['is_active']) ? 'Active' : 'Inactive' ?></td>
          <td><?= e((string) taxonomy_amenity_usage_count((int) $row['id'])) ?></td>
          <td>
            <div class="admin-menu">
              <button type="button" class="admin-menu__toggle" aria-label="Actions">⋯</button>
              <div class="admin-menu__panel">
                <a href="<?= e(base_url('admin/amenities.php?id=' . (int) $row['id'])) ?>">Edit</a>
                <?php if (!empty($row['is_active'])): ?>
                  <form method="post" onsubmit="return confirm('Deactivate this amenity?');">
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

<div class="admin-modal<?= $openModal ? ' is-open' : '' ?>" id="amenity-modal" role="dialog" aria-modal="true" aria-labelledby="amenity-modal-title">
  <div class="admin-modal__backdrop" data-modal-close></div>
  <div class="admin-modal__dialog">
    <div class="admin-modal__head">
      <h2 id="amenity-modal-title"><?= $editing ? 'Edit amenity' : 'Add amenity' ?></h2>
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
        <input id="slug" name="slug" value="<?= e((string) ($editing['slug'] ?? '')) ?>">
      </div>
      <div class="admin-field">
        <label for="category">Category</label>
        <select id="category" name="category">
          <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat) ?>" <?= (($editing['category'] ?? 'other') === $cat) ? 'selected' : '' ?>><?= e(ucfirst($cat)) ?></option>
          <?php endforeach; ?>
        </select>
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
          <a class="admin-btn admin-btn--ghost" href="<?= e(base_url('admin/amenities.php')) ?>">Cancel</a>
        <?php else: ?>
          <button type="button" class="admin-btn admin-btn--ghost" data-modal-close>Close</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php';
