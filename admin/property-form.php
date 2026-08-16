<?php
/**
 * Add / Edit property form + gallery management (Phase 3).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
auth_require();

$user = auth_user();
$userId = (int) ($user['id'] ?? 0);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$property = $isEdit ? property_find($id) : null;

if ($isEdit && !$property) {
    flash_set('property_error', 'Property not found.');
    redirect('admin/properties.php');
}

$types = property_types_for_select($isEdit && $property ? (int) ($property['property_type_id'] ?? 0) : null);
$agents = property_agents_all();
$selectedAmenities = $isEdit ? property_amenity_ids($id) : [];
$amenities = property_amenities_for_select($selectedAmenities);
$images = $isEdit ? property_images($id) : [];
$errors = [];
$warning = null;

$defaults = [
    'title' => '',
    'slug' => '',
    'reference_code' => '',
    'mls_number' => '',
    'description' => '',
    'property_type_id' => '',
    'listing_purpose' => 'sale',
    'status' => 'draft',
    'price' => '',
    'price_on_request' => 0,
    'address_line' => '',
    'city' => '',
    'region' => '',
    'state' => 'CO',
    'postal_code' => '',
    'country' => 'USA',
    'bedrooms' => '',
    'bathrooms' => '',
    'sqft' => '',
    'lot_acres' => '',
    'year_built' => '',
    'badge' => '',
    'is_featured' => 0,
    'agent_id' => '',
    'agent_quote' => '',
    'listed_at' => date('Y-m-d'),
    'confirm_duplicate' => 0,
];

if ($property) {
    foreach ($defaults as $key => $_) {
        if (array_key_exists($key, $property) && $property[$key] !== null) {
            $defaults[$key] = $property[$key];
        }
    }
}

$form = $defaults;
$pendingDeleteIds = [];
$pendingCoverId = 0;
if ($isEdit) {
    foreach ($images as $imgRow) {
        if (!empty($imgRow['is_cover'])) {
            $pendingCoverId = (int) ($imgRow['id'] ?? 0);
            break;
        }
    }
}

// Image actions (edit only) — multi-upload + caption post immediately.
// Cover + deletes are deferred until property save (no reload per click).
if ($isEdit && is_post() && isset($_POST['image_action'])) {
    if (!csrf_verify()) {
        flash_set('property_error', 'Invalid security token.');
        redirect('admin/property-form.php?id=' . $id);
    }
    $action = (string) $_POST['image_action'];
    $imageId = (int) ($_POST['image_id'] ?? 0);

    if ($action === 'upload') {
        $fileField = [];
        if (!empty($_FILES['images']) && is_array($_FILES['images'])) {
            $fileField = $_FILES['images'];
        } elseif (!empty($_FILES['image']) && is_array($_FILES['image'])) {
            $fileField = $_FILES['image'];
        }
        $files = property_uploaded_files_list($fileField);
        if ($files === []) {
            flash_set('property_error', 'No files selected.');
            redirect('admin/property-form.php?id=' . $id . '#media');
        }

        $st = db()->prepare('SELECT COALESCE(MAX(sort_order), -1) FROM property_images WHERE property_id = ?');
        $st->execute([$id]);
        $maxSort = (int) $st->fetchColumn();
        $caption = trim((string) ($_POST['caption'] ?? ''));
        $ins = db()->prepare(
            'INSERT INTO property_images (property_id, path, caption, sort_order, is_cover) VALUES (?, ?, ?, ?, 0)'
        );

        $uploaded = 0;
        $errorsUpload = [];
        foreach ($files as $file) {
            $up = property_upload_image($file, $id);
            if (!$up['ok']) {
                $label = trim((string) ($file['name'] ?? 'file'));
                $errorsUpload[] = ($label !== '' ? $label . ': ' : '') . (string) $up['error'];
                continue;
            }
            $maxSort++;
            $ins->execute([
                $id,
                $up['path'],
                $caption !== '' ? $caption : null,
                $maxSort,
            ]);
            $uploaded++;
        }
        property_ensure_single_cover($id);

        if ($uploaded > 0) {
            $msg = $uploaded === 1 ? '1 image uploaded.' : $uploaded . ' images uploaded.';
            if ($errorsUpload !== []) {
                $msg .= ' Some failed: ' . implode(' ', array_slice($errorsUpload, 0, 3));
            }
            flash_set('property_ok', $msg);
        } else {
            flash_set('property_error', $errorsUpload !== [] ? implode(' ', $errorsUpload) : 'Upload failed.');
        }
        redirect('admin/property-form.php?id=' . $id . '#media');
    }

    if ($action === 'caption' && $imageId > 0) {
        $caption = trim((string) ($_POST['caption'] ?? ''));
        db()->prepare('UPDATE property_images SET caption = ? WHERE id = ? AND property_id = ?')
            ->execute([$caption !== '' ? $caption : null, $imageId, $id]);
        flash_set('property_ok', 'Caption saved.');
        redirect('admin/property-form.php?id=' . $id . '#media');
    }

    // Legacy cover/delete posts are ignored; use deferred actions on save.
    redirect('admin/property-form.php?id=' . $id . '#media');
}

if (is_post() && !isset($_POST['image_action'])) {
    if (!csrf_verify()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $form = array_merge($form, $_POST);
        $rawDelete = $_POST['delete_image_ids'] ?? [];
        if (!is_array($rawDelete)) {
            $rawDelete = [];
        }
        $pendingDeleteIds = array_values(array_unique(array_filter(array_map('intval', $rawDelete), static fn (int $v): bool => $v > 0)));
        $pendingCoverId = (int) ($_POST['cover_image_id'] ?? 0);

        $validated = property_validate_input($_POST, $isEdit ? $id : null);
        $errors = $validated['errors'];
        $warning = $validated['warning'];
        if ($validated['ok']) {
            try {
                if ($isEdit) {
                    $removed = property_delete_images_by_ids($id, $pendingDeleteIds);
                    if ($pendingCoverId > 0 && !in_array($pendingCoverId, $pendingDeleteIds, true)) {
                        property_ensure_single_cover($id, $pendingCoverId);
                    } else {
                        property_ensure_single_cover($id);
                    }
                    property_update($id, $validated['data']);
                    $msg = 'Property updated.';
                    if ($removed > 0) {
                        $msg .= ' Removed ' . $removed . ' gallery image' . ($removed === 1 ? '' : 's') . '.';
                    }
                    flash_set('property_ok', $msg);
                    redirect('admin/property-form.php?id=' . $id);
                } else {
                    $newId = property_insert($validated['data'], $userId);
                    flash_set('property_ok', 'Property created.');
                    redirect('admin/property-form.php?id=' . $newId);
                }
            } catch (Throwable $e) {
                error_log('[SDC] property save: ' . $e->getMessage());
                $errors[] = 'Could not save property. Check unique slug / reference / MLS values.';
            }
        }
        $selectedAmenities = $validated['data']['amenity_ids'] ?? $selectedAmenities;
    }
}

$flashOk = flash_get('property_ok');
$flashErr = flash_get('property_error');
$adminPageTitle = $isEdit ? 'Edit Property' : 'Add New Property';
$adminActiveNav = 'properties';
require dirname(__DIR__) . '/includes/admin-header.php';

$regions = regions_for_select((string) ($form['region'] ?? ''));
$viewSlug = $isEdit ? trim((string) ($property['slug'] ?? $form['slug'] ?? '')) : '';
$canViewPublic = $isEdit && $viewSlug !== '' && is_property_status_public((string) ($form['status'] ?? $property['status'] ?? 'draft'));
?>
<div class="admin-page-head">
  <div>
    <span class="admin-eyebrow">Property Sections</span>
    <h1 class="admin-page-title"><?= e($adminPageTitle) ?></h1>
    <p class="admin-page-lead"><?= $isEdit ? 'Update listing #' . e((string) $id) . ' · ' . e((string) ($property['reference_code'] ?? '')) : 'Create a new listing in MySQL.' ?></p>
  </div>
  <?php if ($isEdit && $viewSlug !== ''): ?>
    <a class="admin-btn admin-btn--ghost" href="<?= e(base_url('property.php?slug=' . rawurlencode($viewSlug))) ?>" target="_blank" rel="noopener">
      <?= $canViewPublic ? 'View property' : 'Preview URL' ?>
    </a>
  <?php endif; ?>
</div>

<?php if ($flashOk): ?><div class="admin-alert admin-alert--ok"><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="admin-alert admin-alert--error"><?= e($flashErr) ?></div><?php endif; ?>
<?php foreach ($errors as $err): ?>
    <div class="admin-alert admin-alert--error"><?= e($err) ?></div>
<?php endforeach; ?>
<?php if ($warning && !in_array($warning, $errors, true)): ?>
    <div class="admin-alert"><?= e($warning) ?></div>
<?php endif; ?>

<form id="property-main-form" method="post" action="">
    <?= csrf_field() ?>
    <div id="pending-image-deletes" hidden>
      <?php foreach ($pendingDeleteIds as $pendingId): ?>
        <input type="hidden" name="delete_image_ids[]" value="<?= (int) $pendingId ?>" data-image-id="<?= (int) $pendingId ?>">
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="cover_image_id" id="cover-image-id" value="<?= (int) $pendingCoverId ?>">

    <section class="admin-panel" id="basic-info">
        <h2>Basic Info</h2>
        <div class="admin-field">
            <label for="title">Property Title *</label>
            <input id="title" name="title" type="text" required value="<?= e((string) $form['title']) ?>">
        </div>
        <div class="admin-grid-2">
            <div class="admin-field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" type="text" value="<?= e((string) $form['slug']) ?>" placeholder="auto-generated from title if blank">
            </div>
            <div class="admin-field">
                <label for="reference_code">Reference code</label>
                <input id="reference_code" name="reference_code" type="text" value="<?= e((string) $form['reference_code']) ?>" placeholder="auto if blank">
            </div>
        </div>
        <?php if ($isEdit && !empty($property['source_name'])): ?>
        <div class="admin-field">
          <label>Import source (internal)</label>
          <p class="admin-note" style="margin:0;">
            <?= e((string) $property['source_name']) ?>
            <?php if (!empty($property['source_reference'])): ?>
              · <?= e((string) $property['source_reference']) ?>
            <?php endif; ?>
            <?php if (!empty($property['source_url'])): ?>
              · <a href="<?= e((string) $property['source_url']) ?>" target="_blank" rel="noopener noreferrer">Parent listing</a>
            <?php endif; ?>
          </p>
        </div>
        <?php endif; ?>
        <div class="admin-grid-2">
            <div class="admin-field">
                <label for="mls_number">MLS number</label>
                <input id="mls_number" name="mls_number" type="text" value="<?= e((string) $form['mls_number']) ?>">
            </div>
            <div class="admin-field">
                <label for="badge">Badge</label>
                <input id="badge" name="badge" type="text" value="<?= e((string) $form['badge']) ?>" placeholder="Just Listed, Exclusive Listing…">
            </div>
        </div>
        <div class="admin-grid-2">
            <div class="admin-field">
                <label for="property_type_id">Property Type</label>
                <select id="property_type_id" name="property_type_id">
                    <option value="">Select type</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" <?= (string) $form['property_type_id'] === (string) $t['id'] ? 'selected' : '' ?>><?= e((string) $t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-field">
                <label for="status">Current Status</label>
                <select id="status" name="status">
                    <?php foreach (property_statuses() as $st): ?>
                        <?php if ($st === 'archived' && (string) $form['status'] !== 'archived') { continue; } ?>
                        <option value="<?= e($st) ?>" <?= (string) $form['status'] === $st ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $st))) ?><?= $st === 'archived' ? ' (use Archive action)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="admin-grid-2">
            <div class="admin-field">
                <label for="listing_purpose">Listing purpose</label>
                <select id="listing_purpose" name="listing_purpose">
                    <?php foreach (['sale' => 'Sale', 'rent' => 'Rent', 'lease' => 'Lease'] as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= (string) $form['listing_purpose'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-field">
                <label for="listed_at">Listed date</label>
                <input id="listed_at" name="listed_at" type="date" value="<?= e((string) $form['listed_at']) ?>">
            </div>
        </div>
        <div class="admin-grid-2">
            <div class="admin-field">
                <label for="price">Listing Price</label>
                <input id="price" name="price" type="text" value="<?= e((string) $form['price']) ?>" placeholder="0.00">
            </div>
            <div class="admin-field" style="display:flex;align-items:flex-end;gap:1rem;padding-bottom:0.35rem;">
                <label><input type="checkbox" name="price_on_request" value="1" <?= !empty($form['price_on_request']) ? 'checked' : '' ?>> Price upon request</label>
                <label><input type="checkbox" name="is_featured" value="1" <?= !empty($form['is_featured']) ? 'checked' : '' ?>> Featured</label>
            </div>
        </div>
        <div class="admin-field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="6"><?= e((string) $form['description']) ?></textarea>
        </div>
    </section>

    <section class="admin-panel" id="specifications">
        <h2>Specifications</h2>
        <div class="admin-grid-3">
            <div class="admin-field"><label for="bedrooms">Bedrooms</label><input id="bedrooms" name="bedrooms" type="number" step="0.5" min="0" value="<?= e((string) $form['bedrooms']) ?>"></div>
            <div class="admin-field"><label for="bathrooms">Bathrooms</label><input id="bathrooms" name="bathrooms" type="number" step="0.5" min="0" value="<?= e((string) $form['bathrooms']) ?>"></div>
            <div class="admin-field"><label for="sqft">Square Footage</label><input id="sqft" name="sqft" type="text" value="<?= e((string) $form['sqft']) ?>"></div>
            <div class="admin-field"><label for="lot_acres">Lot Size (Acres)</label><input id="lot_acres" name="lot_acres" type="text" value="<?= e((string) $form['lot_acres']) ?>"></div>
            <div class="admin-field"><label for="year_built">Year Built</label><input id="year_built" name="year_built" type="text" value="<?= e((string) $form['year_built']) ?>" placeholder="YYYY"></div>
        </div>
    </section>

    <section class="admin-panel" id="location">
        <h2>Location</h2>
        <div class="admin-field"><label for="address_line">Street Address</label><input id="address_line" name="address_line" type="text" value="<?= e((string) $form['address_line']) ?>"></div>
        <div class="admin-grid-2">
            <div class="admin-field"><label for="city">City</label><input id="city" name="city" type="text" value="<?= e((string) $form['city']) ?>"></div>
            <div class="admin-field">
                <label for="region">Region</label>
                <select id="region" name="region">
                    <option value="">Select region</option>
                    <?php foreach ($regions as $regionRow): ?>
                        <?php $regionName = (string) ($regionRow['name'] ?? ''); ?>
                        <option value="<?= e($regionName) ?>" <?= (string) $form['region'] === $regionName ? 'selected' : '' ?>><?= e($regionName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-field"><label for="state">State</label><input id="state" name="state" type="text" value="<?= e((string) $form['state']) ?>"></div>
            <div class="admin-field"><label for="postal_code">ZIP / Postal Code</label><input id="postal_code" name="postal_code" type="text" value="<?= e((string) $form['postal_code']) ?>"></div>
        </div>
        <div class="admin-field"><label for="country">Country</label><input id="country" name="country" type="text" value="<?= e((string) $form['country']) ?>"></div>
    </section>

    <section class="admin-panel" id="agent">
        <h2>Agent</h2>
        <div class="admin-field">
            <label for="agent_id">Listing agent</label>
            <select id="agent_id" name="agent_id">
                <option value="">Unassigned</option>
                <?php foreach ($agents as $agent): ?>
                    <option value="<?= (int) $agent['id'] ?>" <?= (string) $form['agent_id'] === (string) $agent['id'] ? 'selected' : '' ?>>
                        <?= e((string) $agent['name']) ?><?= $agent['region'] ? ' — ' . e((string) $agent['region']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field">
            <label for="agent_quote">Detail-page agent quote</label>
            <textarea id="agent_quote" name="agent_quote" rows="3"><?= e((string) $form['agent_quote']) ?></textarea>
        </div>
    </section>

    <section class="admin-panel" id="amenities">
        <h2>Amenities</h2>
        <div class="admin-amenity-grid">
            <?php
            $byCat = [];
            foreach ($amenities as $am) {
                $byCat[$am['category']][] = $am;
            }
            foreach ($byCat as $cat => $items):
            ?>
                <div>
                    <h3 class="admin-eyebrow"><?= e((string) $cat) ?></h3>
                    <?php foreach ($items as $am): ?>
                        <label class="admin-check">
                            <input type="checkbox" name="amenities[]" value="<?= (int) $am['id'] ?>" <?= in_array((int) $am['id'], $selectedAmenities, true) ? 'checked' : '' ?>>
                            <?= e((string) $am['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="admin-field" style="margin-bottom:1.25rem;">
        <label>
            <input type="checkbox" name="confirm_duplicate" value="1" <?= !empty($form['confirm_duplicate']) ? 'checked' : '' ?>>
            Confirm save despite possible duplicate address
        </label>
    </div>
</form>

<?php if ($isEdit): ?>
<section class="admin-panel" id="media">
    <h2>Media Gallery</h2>
    <p class="admin-note">JPEG, PNG, or WebP. <strong>Cover</strong> / <strong>Remove</strong> apply on Save (no reload). <strong>Undo</strong> only appears after Remove. Upload multiple images at once.</p>
    <p id="gallery-pending-note" class="admin-alert<?= ($pendingDeleteIds === [] && !($pendingCoverId > 0)) ? '' : ' is-visible' ?>"<?= ($pendingDeleteIds === [] && !($pendingCoverId > 0)) ? ' hidden' : '' ?> style="margin-top:0.75rem;">Gallery changes (cover / deletions) are applied when you save this property.</p>

    <div class="admin-gallery" id="admin-gallery">
        <?php foreach ($images as $img): ?>
            <?php
              $imgId = (int) $img['id'];
              $isPending = in_array($imgId, $pendingDeleteIds, true);
              $isCover = $pendingCoverId > 0 ? ($imgId === $pendingCoverId) : !empty($img['is_cover']);
            ?>
            <figure class="admin-gallery__item<?= $isCover ? ' is-cover' : '' ?><?= $isPending ? ' is-pending-delete' : '' ?>" data-image-id="<?= $imgId ?>">
                <img src="<?= e(media_url((string) $img['path'])) ?>" alt="">
                <figcaption>
                    <span class="admin-gallery__pending-label">Marked for deletion</span>
                    <span class="admin-gallery__cover-label">Cover photo</span>
                    <span class="admin-gallery__caption-text"><?= e((string) ($img['caption'] ?? '')) ?></span>
                </figcaption>
                <div class="admin-gallery__actions">
                    <form method="post" action="#media">
                        <?= csrf_field() ?>
                        <input type="hidden" name="image_id" value="<?= $imgId ?>">
                        <input type="text" name="caption" value="<?= e((string) ($img['caption'] ?? '')) ?>" placeholder="Room label / caption">
                        <button class="admin-gallery__btn" type="submit" name="image_action" value="caption">Save caption</button>
                    </form>
                    <div class="admin-gallery__toolbar">
                      <button class="admin-gallery__btn admin-gallery__cover-btn" type="button" data-gallery-cover="<?= $imgId ?>">Cover</button>
                      <button class="admin-gallery__btn admin-gallery__delete-btn" type="button" data-gallery-delete="<?= $imgId ?>"<?= $isPending ? ' hidden' : '' ?>>Remove</button>
                      <button class="admin-gallery__btn admin-gallery__undo-btn" type="button" data-gallery-undo="<?= $imgId ?>"<?= $isPending ? '' : ' hidden' ?>>Undo</button>
                    </div>
                </div>
            </figure>
        <?php endforeach; ?>
    </div>

    <form method="post" action="#media" enctype="multipart/form-data" class="admin-upload">
        <?= csrf_field() ?>
        <input type="hidden" name="image_action" value="upload">
        <div class="admin-field">
            <label for="images">Upload images</label>
            <input id="images" name="images[]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple required>
            <p class="admin-note">Select one or many files (Ctrl/Cmd+click or Shift+click). Max size per file applies individually.</p>
        </div>
        <div class="admin-field">
            <label for="caption_new">Caption / room label (optional, applied to all in this upload)</label>
            <input id="caption_new" name="caption" type="text" placeholder="e.g. GREAT ROOM">
        </div>
        <button class="admin-btn admin-btn--sm" type="submit">Upload</button>
    </form>
</section>
<script>
(function () {
  var form = document.getElementById('property-main-form');
  var gallery = document.getElementById('admin-gallery');
  if (!form || !gallery) return;

  var box = document.getElementById('pending-image-deletes');
  if (!box) {
    box = document.createElement('div');
    box.id = 'pending-image-deletes';
    box.hidden = true;
    form.appendChild(box);
  }

  var coverInput = document.getElementById('cover-image-id');
  if (!coverInput) {
    coverInput = document.createElement('input');
    coverInput.type = 'hidden';
    coverInput.name = 'cover_image_id';
    coverInput.id = 'cover-image-id';
    coverInput.value = '0';
    form.appendChild(coverInput);
  }

  var note = document.getElementById('gallery-pending-note');

  function syncNote() {
    if (!note) return;
    var deletes = box.querySelectorAll('input[name="delete_image_ids[]"]').length;
    note.hidden = false;
    note.textContent = deletes > 0
      ? 'Images marked Remove will be deleted when you save. Use Undo to keep one.'
      : 'Cover / Remove marks apply when you Save Draft or Publish. Uploads save immediately.';
  }

  function findBtn(item, sel) {
    return item ? item.querySelector(sel) : null;
  }

  function setPendingUi(item, pending) {
    if (!item) return;
    var delBtn = findBtn(item, '.admin-gallery__delete-btn');
    var undoBtn = findBtn(item, '.admin-gallery__undo-btn');
    var coverBtn = findBtn(item, '.admin-gallery__cover-btn');
    if (pending) {
      item.classList.add('is-pending-delete');
      if (delBtn) delBtn.hidden = true;
      if (undoBtn) undoBtn.hidden = false;
      if (coverBtn) coverBtn.hidden = true;
    } else {
      item.classList.remove('is-pending-delete');
      if (delBtn) delBtn.hidden = false;
      if (undoBtn) undoBtn.hidden = true;
      if (coverBtn && !item.classList.contains('is-cover')) coverBtn.hidden = false;
    }
  }

  function setCover(id) {
    var item = gallery.querySelector('.admin-gallery__item[data-image-id="' + id + '"]');
    if (!item || item.classList.contains('is-pending-delete')) return;
    gallery.querySelectorAll('.admin-gallery__item.is-cover').forEach(function (el) {
      el.classList.remove('is-cover');
      var btn = findBtn(el, '.admin-gallery__cover-btn');
      if (btn && !el.classList.contains('is-pending-delete')) btn.hidden = false;
    });
    item.classList.add('is-cover');
    var coverBtn = findBtn(item, '.admin-gallery__cover-btn');
    if (coverBtn) coverBtn.hidden = true;
    coverInput.value = String(id);
    syncNote();
  }

  function markDelete(id) {
    var item = gallery.querySelector('.admin-gallery__item[data-image-id="' + id + '"]');
    if (!item || item.classList.contains('is-pending-delete')) return;
    setPendingUi(item, true);
    if (!box.querySelector('input[data-image-id="' + id + '"]')) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'delete_image_ids[]';
      input.value = String(id);
      input.setAttribute('data-image-id', String(id));
      box.appendChild(input);
    }
    if (coverInput.value === String(id)) {
      var next = gallery.querySelector('.admin-gallery__item:not(.is-pending-delete)');
      if (next) setCover(next.getAttribute('data-image-id'));
      else {
        coverInput.value = '0';
        gallery.querySelectorAll('.admin-gallery__item.is-cover').forEach(function (el) {
          el.classList.remove('is-cover');
        });
      }
    }
    syncNote();
  }

  function undoDelete(id) {
    var item = gallery.querySelector('.admin-gallery__item[data-image-id="' + id + '"]');
    setPendingUi(item, false);
    box.querySelectorAll('input[data-image-id="' + id + '"]').forEach(function (el) { el.remove(); });
    syncNote();
  }

  gallery.addEventListener('click', function (e) {
    var t = e.target;
    if (!t || !t.closest) return;
    var cover = t.closest('[data-gallery-cover]');
    if (cover) {
      e.preventDefault();
      e.stopPropagation();
      setCover(cover.getAttribute('data-gallery-cover'));
      return;
    }
    var del = t.closest('[data-gallery-delete]');
    if (del) {
      e.preventDefault();
      e.stopPropagation();
      markDelete(del.getAttribute('data-gallery-delete'));
      return;
    }
    var undo = t.closest('[data-gallery-undo]');
    if (undo) {
      e.preventDefault();
      e.stopPropagation();
      undoDelete(undo.getAttribute('data-gallery-undo'));
    }
  });

  // Sync initial pending state from PHP marks
  gallery.querySelectorAll('.admin-gallery__item.is-pending-delete').forEach(function (item) {
    setPendingUi(item, true);
  });
  gallery.querySelectorAll('.admin-gallery__item.is-cover').forEach(function (item) {
    var btn = findBtn(item, '.admin-gallery__cover-btn');
    if (btn) btn.hidden = true;
  });
  syncNote();
})();
</script>
<?php else: ?>
<section class="admin-panel" id="media">
    <h2>Media Gallery</h2>
    <p class="admin-note">Save the property first, then upload gallery images on this screen.</p>
</section>
<?php endif; ?>

<div class="admin-actions">
    <button class="admin-btn admin-btn--ghost" type="submit" form="property-main-form" name="save_action" value="draft" onclick="document.getElementById('status').value='draft';">Save Draft</button>
    <button class="admin-btn" type="submit" form="property-main-form" name="save_action" value="publish" onclick="var s=document.getElementById('status'); if(s.value==='draft'){s.value='available';}">Publish / Save</button>
    <a class="admin-btn admin-btn--ghost" href="<?= e(base_url('admin/properties.php')) ?>">Back to list</a>
</div>

<?php
require dirname(__DIR__) . '/includes/admin-footer.php';
