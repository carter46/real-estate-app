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

// Image actions (edit only)
if ($isEdit && is_post() && isset($_POST['image_action'])) {
    if (!csrf_verify()) {
        flash_set('property_error', 'Invalid security token.');
        redirect('admin/property-form.php?id=' . $id);
    }
    $action = (string) $_POST['image_action'];
    $imageId = (int) ($_POST['image_id'] ?? 0);

    if ($action === 'upload' && !empty($_FILES['image'])) {
        $up = property_upload_image($_FILES['image'], $id);
        if (!$up['ok']) {
            flash_set('property_error', (string) $up['error']);
        } else {
            $st = db()->prepare('SELECT COALESCE(MAX(sort_order), -1) FROM property_images WHERE property_id = ?');
            $st->execute([$id]);
            $maxSort = (int) $st->fetchColumn();
            $caption = trim((string) ($_POST['caption'] ?? ''));
            $ins = db()->prepare(
                'INSERT INTO property_images (property_id, path, caption, sort_order, is_cover) VALUES (?, ?, ?, ?, 0)'
            );
            $ins->execute([$id, $up['path'], $caption !== '' ? $caption : null, $maxSort + 1]);
            property_ensure_single_cover($id);
            flash_set('property_ok', 'Image uploaded.');
        }
        redirect('admin/property-form.php?id=' . $id . '#media');
    }

    if ($action === 'cover' && $imageId > 0) {
        property_ensure_single_cover($id, $imageId);
        flash_set('property_ok', 'Cover image updated.');
        redirect('admin/property-form.php?id=' . $id . '#media');
    }

    if ($action === 'delete' && $imageId > 0) {
        $stmt = db()->prepare('SELECT * FROM property_images WHERE id = ? AND property_id = ? LIMIT 1');
        $stmt->execute([$imageId, $id]);
        $img = $stmt->fetch();
        if ($img) {
            db()->prepare('DELETE FROM property_images WHERE id = ?')->execute([$imageId]);
            property_delete_image_file((string) ($img['path'] ?? ''));
            property_ensure_single_cover($id);
            flash_set('property_ok', 'Image deleted.');
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
}

if (is_post() && !isset($_POST['image_action'])) {
    if (!csrf_verify()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $form = array_merge($form, $_POST);
        $validated = property_validate_input($_POST, $isEdit ? $id : null);
        $errors = $validated['errors'];
        $warning = $validated['warning'];
        if ($validated['ok']) {
            try {
                if ($isEdit) {
                    property_update($id, $validated['data']);
                    flash_set('property_ok', 'Property updated.');
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

$regions = ['Aspen', 'Vail', 'Telluride', 'Denver Metro', 'Beaver Creek', 'Snowmass', 'Steamboat'];
?>
<span class="admin-eyebrow">Property Sections</span>
<h1 class="admin-page-title"><?= e($adminPageTitle) ?></h1>
<p class="admin-page-lead"><?= $isEdit ? 'Update listing #' . e((string) $id) . ' · ' . e((string) ($property['reference_code'] ?? '')) : 'Create a new listing in MySQL.' ?></p>

<?php if ($flashOk): ?><div class="admin-alert admin-alert--ok"><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="admin-alert admin-alert--error"><?= e($flashErr) ?></div><?php endif; ?>
<?php foreach ($errors as $err): ?>
    <div class="admin-alert admin-alert--error"><?= e($err) ?></div>
<?php endforeach; ?>
<?php if ($warning && !in_array($warning, $errors, true)): ?>
    <div class="admin-alert"><?= e($warning) ?></div>
<?php endif; ?>

<form method="post" action="">
    <?= csrf_field() ?>

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
                    <?php foreach ($regions as $region): ?>
                        <option value="<?= e($region) ?>" <?= (string) $form['region'] === $region ? 'selected' : '' ?>><?= e($region) ?></option>
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

    <div class="admin-actions">
        <button class="admin-btn admin-btn--ghost" type="submit" name="save_action" value="draft" onclick="document.getElementById('status').value='draft';">Save Draft</button>
        <button class="admin-btn" type="submit" name="save_action" value="publish" onclick="var s=document.getElementById('status'); if(s.value==='draft'){s.value='available';}">Publish / Save</button>
        <a class="admin-btn admin-btn--ghost" href="<?= e(base_url('admin/properties.php')) ?>">Back to list</a>
    </div>
</form>

<?php if ($isEdit): ?>
<section class="admin-panel" id="media">
    <h2>Media Gallery</h2>
    <p class="admin-note">JPEG, PNG, or WebP. One cover image is maintained automatically.</p>

    <div class="admin-gallery">
        <?php foreach ($images as $img): ?>
            <figure class="admin-gallery__item<?= !empty($img['is_cover']) ? ' is-cover' : '' ?>">
                <img src="<?= e(media_url((string) $img['path'])) ?>" alt="">
                <figcaption>
                    <?= !empty($img['is_cover']) ? 'Cover · ' : '' ?><?= e((string) ($img['caption'] ?? '')) ?>
                </figcaption>
                <form method="post" action="#media" class="admin-gallery__actions">
                    <?= csrf_field() ?>
                    <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
                    <input type="text" name="caption" value="<?= e((string) ($img['caption'] ?? '')) ?>" placeholder="Room label / caption">
                    <button class="admin-btn admin-btn--ghost" type="submit" name="image_action" value="caption">Save caption</button>
                    <?php if (empty($img['is_cover'])): ?>
                        <button class="admin-btn admin-btn--ghost" type="submit" name="image_action" value="cover">Set cover</button>
                    <?php endif; ?>
                    <button class="admin-btn admin-btn--ghost" type="submit" name="image_action" value="delete" onclick="return confirm('Delete this image?');">Delete</button>
                </form>
            </figure>
        <?php endforeach; ?>
    </div>

    <form method="post" action="#media" enctype="multipart/form-data" class="admin-upload">
        <?= csrf_field() ?>
        <input type="hidden" name="image_action" value="upload">
        <div class="admin-field">
            <label for="image">Upload image</label>
            <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
        </div>
        <div class="admin-field">
            <label for="caption_new">Caption / room label</label>
            <input id="caption_new" name="caption" type="text" placeholder="e.g. GREAT ROOM">
        </div>
        <button class="admin-btn" type="submit">Upload</button>
    </form>
</section>
<?php else: ?>
<section class="admin-panel" id="media">
    <h2>Media Gallery</h2>
    <p class="admin-note">Save the property first, then upload gallery images on the edit screen.</p>
</section>
<?php endif; ?>

<?php
require dirname(__DIR__) . '/includes/admin-footer.php';
