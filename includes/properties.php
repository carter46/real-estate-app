<?php
/**
 * Property repository / helpers — Phase 3.
 */

declare(strict_types=1);

/**
 * @return list<string>
 */
function property_statuses(): array
{
    return ['draft', 'available', 'pending', 'under_contract', 'sold', 'private', 'archived'];
}

function property_generate_reference(): string
{
    return 'REF-' . strtoupper(bin2hex(random_bytes(4)));
}

function property_unique_slug(string $title, ?int $excludeId = null): string
{
    $base = slugify($title);
    $slug = $base;
    $i = 2;
    while (property_slug_exists($slug, $excludeId)) {
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}

function property_slug_exists(string $slug, ?int $excludeId = null): bool
{
    if ($excludeId) {
        $stmt = db()->prepare('SELECT id FROM properties WHERE slug = ? AND id != ? LIMIT 1');
        $stmt->execute([$slug, $excludeId]);
    } else {
        $stmt = db()->prepare('SELECT id FROM properties WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
    }
    return (bool) $stmt->fetch();
}

function property_reference_exists(string $code, ?int $excludeId = null): bool
{
    if ($excludeId) {
        $stmt = db()->prepare('SELECT id FROM properties WHERE reference_code = ? AND id != ? LIMIT 1');
        $stmt->execute([$code, $excludeId]);
    } else {
        $stmt = db()->prepare('SELECT id FROM properties WHERE reference_code = ? LIMIT 1');
        $stmt->execute([$code]);
    }
    return (bool) $stmt->fetch();
}

function property_mls_exists(string $mls, ?int $excludeId = null): bool
{
    if ($excludeId) {
        $stmt = db()->prepare('SELECT id FROM properties WHERE mls_number = ? AND id != ? LIMIT 1');
        $stmt->execute([$mls, $excludeId]);
    } else {
        $stmt = db()->prepare('SELECT id FROM properties WHERE mls_number = ? LIMIT 1');
        $stmt->execute([$mls]);
    }
    return (bool) $stmt->fetch();
}

/**
 * Soft duplicate warning: same normalized address + city.
 */
function property_address_duplicate(?string $address, ?string $city, ?int $excludeId = null): ?array
{
    $address = trim((string) $address);
    $city = trim((string) $city);
    if ($address === '' || $city === '') {
        return null;
    }

    $sql = "SELECT id, title, slug, reference_code
            FROM properties
            WHERE LOWER(TRIM(address_line)) = LOWER(?)
              AND LOWER(TRIM(city)) = LOWER(?)
              AND status != 'archived'";
    $params = [$address, $city];
    if ($excludeId) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }
    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function property_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT p.*, t.slug AS type_slug, t.name AS type_name, a.name AS agent_name
         FROM properties p
         LEFT JOIN property_types t ON t.id = p.property_type_id
         LEFT JOIN agents a ON a.id = p.agent_id
         WHERE p.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

/**
 * @return list<array<string, mixed>>
 */
function property_list_admin(string $q = '', string $status = '', int $limit = 50, int $offset = 0): array
{
    $where = ['1=1'];
    $params = [];

    if ($q !== '') {
        $where[] = '(p.title LIKE ? OR p.address_line LIKE ? OR p.city LIKE ? OR p.reference_code LIKE ? OR p.mls_number LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    if ($status !== '' && in_array($status, property_statuses(), true)) {
        $where[] = 'p.status = ?';
        $params[] = $status;
    }

    $sql = 'SELECT p.*, t.name AS type_name,
            (SELECT path FROM property_images pi WHERE pi.property_id = p.id AND pi.is_cover = 1 ORDER BY pi.id ASC LIMIT 1) AS cover_path
            FROM properties p
            LEFT JOIN property_types t ON t.id = p.property_type_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY p.updated_at DESC
            LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
}

function property_count_admin(string $q = '', string $status = ''): int
{
    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        $where[] = '(title LIKE ? OR address_line LIKE ? OR city LIKE ? OR reference_code LIKE ? OR mls_number LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    if ($status !== '' && in_array($status, property_statuses(), true)) {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM properties WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Admin list excluding archived (default "active" portfolio view).
 *
 * @return list<array<string, mixed>>
 */
function property_list_admin_excluding_archived(string $q = '', int $limit = 50, int $offset = 0): array
{
    $where = ["p.status != 'archived'"];
    $params = [];
    if ($q !== '') {
        $where[] = '(p.title LIKE ? OR p.address_line LIKE ? OR p.city LIKE ? OR p.reference_code LIKE ? OR p.mls_number LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }

    $sql = 'SELECT p.*, t.name AS type_name,
            (SELECT path FROM property_images pi WHERE pi.property_id = p.id AND pi.is_cover = 1 ORDER BY pi.id ASC LIMIT 1) AS cover_path
            FROM properties p
            LEFT JOIN property_types t ON t.id = p.property_type_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY p.updated_at DESC
            LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
}

function property_count_admin_excluding_archived(string $q = ''): int
{
    $where = ["status != 'archived'"];
    $params = [];
    if ($q !== '') {
        $where[] = '(title LIKE ? OR address_line LIKE ? OR city LIKE ? OR reference_code LIKE ? OR mls_number LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM properties WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * @return list<array<string, mixed>>
 */
function property_types_all(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM property_types';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order, name';
    return db()->query($sql)->fetchAll() ?: [];
}

/**
 * Active types plus current type id (even if deactivated) for edit forms.
 *
 * @return list<array<string, mixed>>
 */
function property_types_for_select(?int $currentId = null): array
{
    $rows = property_types_all(true);
    if ($currentId === null || $currentId <= 0) {
        return $rows;
    }
    foreach ($rows as $row) {
        if ((int) $row['id'] === $currentId) {
            return $rows;
        }
    }
    $stmt = db()->prepare('SELECT * FROM property_types WHERE id = ? LIMIT 1');
    $stmt->execute([$currentId]);
    $extra = $stmt->fetch();
    if (is_array($extra)) {
        array_unshift($rows, $extra);
    }
    return $rows;
}

/**
 * @return list<array<string, mixed>>
 */
function property_agents_all(): array
{
    return db()->query('SELECT id, name, title, region FROM agents WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll() ?: [];
}

/**
 * @return list<array<string, mixed>>
 */
function property_amenities_all(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM amenities';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY category, sort_order, name';
    return db()->query($sql)->fetchAll() ?: [];
}

/**
 * Active amenities plus any selected (even if deactivated) for edit forms.
 *
 * @param list<int> $selectedIds
 * @return list<array<string, mixed>>
 */
function property_amenities_for_select(array $selectedIds = []): array
{
    $rows = property_amenities_all(true);
    $have = [];
    foreach ($rows as $row) {
        $have[(int) $row['id']] = true;
    }
    foreach ($selectedIds as $aid) {
        $aid = (int) $aid;
        if ($aid <= 0 || isset($have[$aid])) {
            continue;
        }
        $stmt = db()->prepare('SELECT * FROM amenities WHERE id = ? LIMIT 1');
        $stmt->execute([$aid]);
        $extra = $stmt->fetch();
        if (is_array($extra)) {
            $rows[] = $extra;
            $have[$aid] = true;
        }
    }
    usort($rows, static function ($a, $b) {
        $c = strcmp((string) ($a['category'] ?? ''), (string) ($b['category'] ?? ''));
        if ($c !== 0) {
            return $c;
        }
        return ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
    });
    return $rows;
}

/**
 * @return list<int>
 */
function property_amenity_ids(int $propertyId): array
{
    $stmt = db()->prepare('SELECT amenity_id FROM property_amenity WHERE property_id = ?');
    $stmt->execute([$propertyId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
}

/**
 * @return list<array<string, mixed>>
 */
function property_images(int $propertyId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM property_images WHERE property_id = ? ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$propertyId]);
    return $stmt->fetchAll() ?: [];
}

/**
 * Normalize and validate posted property fields.
 *
 * @param array<string, mixed> $input
 * @return array{ok:bool, errors:list<string>, data:array<string,mixed>, warning:?string}
 */
function property_validate_input(array $input, ?int $excludeId = null): array
{
    $errors = [];
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        $errors[] = 'Property title is required.';
    } elseif (strlen($title) > 255) {
        $errors[] = 'Property title must be 255 characters or fewer.';
    }

    $status = (string) ($input['status'] ?? 'draft');
    if ($status === 'archived') {
        $existing = $excludeId ? property_find($excludeId) : null;
        if (!$existing || ($existing['status'] ?? '') !== 'archived') {
            $errors[] = 'Use Archive to set archived status.';
        }
    } elseif (!in_array($status, property_statuses(), true)) {
        $errors[] = 'Invalid status.';
    }

    $purpose = (string) ($input['listing_purpose'] ?? 'sale');
    if (!in_array($purpose, ['sale', 'rent', 'lease'], true)) {
        $purpose = 'sale';
    }

    $slugInput = trim((string) ($input['slug'] ?? ''));
    $slug = $slugInput !== '' ? slugify($slugInput) : property_unique_slug($title !== '' ? $title : 'property', $excludeId);
    if (strlen($slug) > 191) {
        $errors[] = 'Slug must be 191 characters or fewer.';
    }
    if ($slugInput !== '' && property_slug_exists($slug, $excludeId)) {
        $errors[] = 'Slug is already in use.';
    }

    $reference = trim((string) ($input['reference_code'] ?? ''));
    if ($reference === '') {
        $reference = property_generate_reference();
        while (property_reference_exists($reference, $excludeId)) {
            $reference = property_generate_reference();
        }
    } elseif (property_reference_exists($reference, $excludeId)) {
        $errors[] = 'Reference code is already in use.';
    }

    $mls = trim((string) ($input['mls_number'] ?? ''));
    $mls = $mls === '' ? null : $mls;
    if ($mls !== null && property_mls_exists($mls, $excludeId)) {
        $errors[] = 'MLS number is already in use.';
    }

    $typeId = (int) ($input['property_type_id'] ?? 0);
    $typeId = $typeId > 0 ? $typeId : null;

    $agentId = (int) ($input['agent_id'] ?? 0);
    $agentId = $agentId > 0 ? $agentId : null;

    $priceOnRequest = !empty($input['price_on_request']) ? 1 : 0;
    $priceRaw = str_replace([',', '$', ' '], '', (string) ($input['price'] ?? ''));
    $price = $priceRaw === '' ? null : (float) $priceRaw;
    if (!$priceOnRequest && $price !== null && $price < 0) {
        $errors[] = 'Price cannot be negative.';
    }

    $beds = ($input['bedrooms'] ?? '') === '' ? null : (float) $input['bedrooms'];
    $baths = ($input['bathrooms'] ?? '') === '' ? null : (float) $input['bathrooms'];
    $sqft = ($input['sqft'] ?? '') === '' ? null : (int) preg_replace('/\D/', '', (string) $input['sqft']);
    $lot = ($input['lot_acres'] ?? '') === '' ? null : (float) $input['lot_acres'];
    $year = ($input['year_built'] ?? '') === '' ? null : (int) $input['year_built'];
    $yearMax = (int) date('Y') + 2;
    if ($beds !== null && ($beds < 0 || $beds > 50)) {
        $errors[] = 'Bedrooms must be between 0 and 50.';
    }
    if ($baths !== null && ($baths < 0 || $baths > 50)) {
        $errors[] = 'Bathrooms must be between 0 and 50.';
    }
    if ($sqft !== null && ($sqft < 0 || $sqft > 1000000)) {
        $errors[] = 'Square footage is out of range.';
    }
    if ($lot !== null && ($lot < 0 || $lot > 100000)) {
        $errors[] = 'Lot acres is out of range.';
    }
    if ($year !== null && ($year < 1800 || $year > $yearMax)) {
        $errors[] = 'Year built is out of range.';
    }

    $description = trim((string) ($input['description'] ?? ''));
    if (strlen($description) > 65000) {
        $errors[] = 'Description is too long.';
    }

    $address = trim((string) ($input['address_line'] ?? ''));
    $city = trim((string) ($input['city'] ?? ''));
    $warning = null;
    $dup = property_address_duplicate($address, $city, $excludeId);
    if ($dup) {
        $addressChanged = true;
        if ($excludeId) {
            $existing = property_find($excludeId);
            if (is_array($existing)) {
                $prevAddress = trim((string) ($existing['address_line'] ?? ''));
                $prevCity = trim((string) ($existing['city'] ?? ''));
                $addressChanged = strcasecmp($prevAddress, $address) !== 0
                    || strcasecmp($prevCity, $city) !== 0;
            }
        }
        // Shared community addresses (e.g. multiple floor plans) should not block a normal edit.
        // Only require confirmation on create, or when address/city is changed to match another listing.
        if ($addressChanged) {
            $warning = 'A property with the same address and city already exists: '
                . (string) ($dup['title'] ?? '') . ' (' . (string) ($dup['reference_code'] ?? '') . ').';
            if (empty($input['confirm_duplicate'])) {
                $errors[] = $warning . ' Check “Confirm save despite possible duplicate” to proceed.';
            }
        }
    }

    $listedAt = trim((string) ($input['listed_at'] ?? ''));
    if ($listedAt === '') {
        $listedAt = null;
    }

    $amenityIds = [];
    if (!empty($input['amenities']) && is_array($input['amenities'])) {
        foreach ($input['amenities'] as $aid) {
            $amenityIds[] = (int) $aid;
        }
        $amenityIds = array_values(array_unique(array_filter($amenityIds)));
    }

    $data = [
        'title' => $title,
        'slug' => $excludeId && $slugInput === '' ? null : $slug, // null means keep existing on edit when empty handled elsewhere
        'reference_code' => $reference,
        'mls_number' => $mls,
        'description' => $description,
        'property_type_id' => $typeId,
        'listing_purpose' => $purpose,
        'status' => $status,
        'price' => $priceOnRequest ? null : $price,
        'price_on_request' => $priceOnRequest,
        'currency' => 'USD',
        'address_line' => $address,
        'city' => $city,
        'region' => trim((string) ($input['region'] ?? '')),
        'state' => trim((string) ($input['state'] ?? 'CO')) ?: 'CO',
        'postal_code' => trim((string) ($input['postal_code'] ?? '')),
        'country' => trim((string) ($input['country'] ?? 'USA')) ?: 'USA',
        'bedrooms' => $beds,
        'bathrooms' => $baths,
        'sqft' => $sqft,
        'lot_acres' => $lot,
        'year_built' => $year,
        'badge' => trim((string) ($input['badge'] ?? '')) ?: null,
        'is_featured' => !empty($input['is_featured']) ? 1 : 0,
        'agent_id' => $agentId,
        'agent_quote' => trim((string) ($input['agent_quote'] ?? '')) ?: null,
        'listed_at' => $listedAt,
        'amenity_ids' => $amenityIds,
        'slug_final' => $slug,
    ];

    return [
        'ok' => $errors === [],
        'errors' => $errors,
        'data' => $data,
        'warning' => $warning,
    ];
}

/**
 * @param array<string, mixed> $data from property_validate_input
 */
function property_insert(array $data, int $userId): int
{
    $stmt = db()->prepare(
        'INSERT INTO properties (
            slug, reference_code, mls_number, title, description, property_type_id, listing_purpose,
            status, price, price_on_request, currency, address_line, city, region, state, postal_code, country,
            bedrooms, bathrooms, sqft, lot_acres, year_built, badge, is_featured, agent_id, agent_quote,
            listed_at, source_name, source_url, source_reference, created_by
         ) VALUES (
            ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
         )'
    );
    $stmt->execute([
        $data['slug_final'],
        $data['reference_code'],
        $data['mls_number'],
        $data['title'],
        $data['description'] !== '' ? $data['description'] : null,
        $data['property_type_id'],
        $data['listing_purpose'],
        $data['status'],
        $data['price'],
        $data['price_on_request'],
        $data['currency'],
        $data['address_line'],
        $data['city'],
        $data['region'],
        $data['state'],
        $data['postal_code'],
        $data['country'],
        $data['bedrooms'],
        $data['bathrooms'],
        $data['sqft'],
        $data['lot_acres'],
        $data['year_built'],
        $data['badge'],
        $data['is_featured'],
        $data['agent_id'],
        $data['agent_quote'],
        $data['listed_at'],
        $data['source_name'] ?? null,
        $data['source_url'] ?? null,
        $data['source_reference'] ?? null,
        $userId > 0 ? $userId : null,
    ]);
    $id = (int) db()->lastInsertId();
    property_sync_amenities($id, $data['amenity_ids'] ?? []);
    return $id;
}

/**
 * @param array<string, mixed> $data
 */
function property_update(int $id, array $data): void
{
    $stmt = db()->prepare(
        'UPDATE properties SET
            slug = ?, reference_code = ?, mls_number = ?, title = ?, description = ?, property_type_id = ?,
            listing_purpose = ?, status = ?, price = ?, price_on_request = ?, currency = ?,
            address_line = ?, city = ?, region = ?, state = ?, postal_code = ?, country = ?,
            bedrooms = ?, bathrooms = ?, sqft = ?, lot_acres = ?, year_built = ?, badge = ?,
            is_featured = ?, agent_id = ?, agent_quote = ?, listed_at = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $data['slug_final'],
        $data['reference_code'],
        $data['mls_number'],
        $data['title'],
        $data['description'] !== '' ? $data['description'] : null,
        $data['property_type_id'],
        $data['listing_purpose'],
        $data['status'],
        $data['price'],
        $data['price_on_request'],
        $data['currency'],
        $data['address_line'],
        $data['city'],
        $data['region'],
        $data['state'],
        $data['postal_code'],
        $data['country'],
        $data['bedrooms'],
        $data['bathrooms'],
        $data['sqft'],
        $data['lot_acres'],
        $data['year_built'],
        $data['badge'],
        $data['is_featured'],
        $data['agent_id'],
        $data['agent_quote'],
        $data['listed_at'],
        $id,
    ]);
    property_sync_amenities($id, $data['amenity_ids'] ?? []);
}

/**
 * @param list<int> $amenityIds
 */
function property_sync_amenities(int $propertyId, array $amenityIds): void
{
    $del = db()->prepare('DELETE FROM property_amenity WHERE property_id = ?');
    $del->execute([$propertyId]);
    if ($amenityIds === []) {
        return;
    }
    $ins = db()->prepare('INSERT INTO property_amenity (property_id, amenity_id) VALUES (?, ?)');
    foreach ($amenityIds as $aid) {
        $ins->execute([$propertyId, $aid]);
    }
}

function property_archive(int $id): void
{
    $stmt = db()->prepare("UPDATE properties SET status = 'archived', is_featured = 0 WHERE id = ?");
    $stmt->execute([$id]);
}

/**
 * Permanently delete an archived property, its DB rows, and local image files.
 * Returns false if missing or not archived.
 */
function property_purge_permanent(int $id): bool
{
    $property = property_find($id);
    if (!$property || (string) ($property['status'] ?? '') !== 'archived') {
        return false;
    }

    $images = property_images($id);
    $dirs = [];
    foreach ($images as $img) {
        $path = (string) ($img['path'] ?? '');
        property_delete_image_file($path);
        if ($path !== '' && !preg_match('#^https?://#i', $path)) {
            $dir = dirname(APP_ROOT . '/' . ltrim(str_replace('\\', '/', $path), '/'));
            $dirs[$dir] = true;
        }
    }

    // Also clear common upload folders for this listing (id-based and slug-based).
    $uploadsProps = (string) app_config('uploads.properties_dir', APP_ROOT . '/uploads/properties');
    $dirs[rtrim($uploadsProps, '/\\') . DIRECTORY_SEPARATOR . $id] = true;
    $slug = trim((string) ($property['slug'] ?? ''));
    if ($slug !== '') {
        $dirs[rtrim($uploadsProps, '/\\') . DIRECTORY_SEPARATOR . $slug] = true;
    }

    db()->prepare('DELETE FROM properties WHERE id = ? AND status = \'archived\'')->execute([$id]);

    foreach (array_keys($dirs) as $dir) {
        property_remove_empty_upload_dir($dir);
    }

    return true;
}

/**
 * Remove an empty directory under uploads/properties only.
 */
function property_remove_empty_upload_dir(string $dir): void
{
    $uploadsRoot = realpath(APP_ROOT . '/uploads/properties');
    if ($uploadsRoot === false || !is_dir($dir)) {
        return;
    }
    $real = realpath($dir);
    if ($real === false) {
        return;
    }
    $prefix = $uploadsRoot . DIRECTORY_SEPARATOR;
    if ($real === $uploadsRoot || !str_starts_with($real, $prefix)) {
        return;
    }
    $files = @scandir($real);
    if (!is_array($files)) {
        return;
    }
    $remaining = array_diff($files, ['.', '..']);
    if ($remaining === []) {
        @rmdir($real);
    }
}

function property_ensure_single_cover(int $propertyId, ?int $coverImageId = null): void
{
    $pdo = db();
    if ($coverImageId) {
        $pdo->prepare('UPDATE property_images SET is_cover = 0 WHERE property_id = ?')->execute([$propertyId]);
        $pdo->prepare('UPDATE property_images SET is_cover = 1 WHERE id = ? AND property_id = ?')
            ->execute([$coverImageId, $propertyId]);
        return;
    }

    $stmt = $pdo->prepare('SELECT id FROM property_images WHERE property_id = ? AND is_cover = 1 LIMIT 1');
    $stmt->execute([$propertyId]);
    if ($stmt->fetch()) {
        return;
    }
    $first = $pdo->prepare('SELECT id FROM property_images WHERE property_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1');
    $first->execute([$propertyId]);
    $row = $first->fetch();
    if ($row) {
        $pdo->prepare('UPDATE property_images SET is_cover = 1 WHERE id = ?')->execute([(int) $row['id']]);
    }
}

/**
 * Delete gallery rows (and local files) for a property. Returns how many were removed.
 *
 * @param list<int> $imageIds
 */
function property_delete_images_by_ids(int $propertyId, array $imageIds): int
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $imageIds), static fn (int $v): bool => $v > 0)));
    if ($propertyId < 1 || $ids === []) {
        return 0;
    }

    $removed = 0;
    $stmt = db()->prepare('SELECT id, path FROM property_images WHERE property_id = ? AND id = ? LIMIT 1');
    $del = db()->prepare('DELETE FROM property_images WHERE id = ? AND property_id = ?');
    foreach ($ids as $imageId) {
        $stmt->execute([$propertyId, $imageId]);
        $img = $stmt->fetch();
        if (!is_array($img)) {
            continue;
        }
        $del->execute([$imageId, $propertyId]);
        property_delete_image_file((string) ($img['path'] ?? ''));
        $removed++;
    }
    if ($removed > 0) {
        property_ensure_single_cover($propertyId);
    }
    return $removed;
}

/**
 * Escape % and _ for SQL LIKE (keep user input literal).
 */
function property_like_escape(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

/**
 * Parse free-text search for bedroom/bathroom hints and remaining keywords.
 *
 * @return array{bedrooms:?float, bathrooms:?float, tokens:list<string>}
 */
function property_parse_public_query(string $q): array
{
    $q = trim(preg_replace('/\s+/u', ' ', $q) ?? '');
    $bedrooms = null;
    $bathrooms = null;

    if ($q === '') {
        return ['bedrooms' => null, 'bathrooms' => null, 'tokens' => []];
    }

    if (preg_match('/\b(\d+(?:\.\d+)?)\s*[+\-]?\s*(?:bed(?:room)?s?|brs?|bdrs?|bdrms?)\b/iu', $q, $m)) {
        $bedrooms = (float) $m[1];
        $q = trim(preg_replace('/' . preg_quote($m[0], '/') . '/iu', ' ', $q) ?? '');
    } elseif (preg_match('/\b(\d+(?:\.\d+)?)(?:bed(?:room)?s?|brs?)\b/iu', $q, $m)) {
        $bedrooms = (float) $m[1];
        $q = trim(preg_replace('/' . preg_quote($m[0], '/') . '/iu', ' ', $q) ?? '');
    }

    if (preg_match('/\b(\d+(?:\.\d+)?)\s*[+\-]?\s*(?:bath(?:room)?s?|ba)\b/iu', $q, $m)) {
        $bathrooms = (float) $m[1];
        $q = trim(preg_replace('/' . preg_quote($m[0], '/') . '/iu', ' ', $q) ?? '');
    } elseif (preg_match('/\b(\d+(?:\.\d+)?)(?:bath(?:room)?s?|ba)\b/iu', $q, $m)) {
        $bathrooms = (float) $m[1];
        $q = trim(preg_replace('/' . preg_quote($m[0], '/') . '/iu', ' ', $q) ?? '');
    }

    $stop = ['a', 'an', 'the', 'in', 'at', 'near', 'with', 'for', 'of', 'and', 'or', 'to'];
    $tokens = [];
    if ($q !== '') {
        foreach (preg_split('/[\s,\/|+]+/u', $q) ?: [] as $tok) {
            $tok = trim($tok);
            $lower = function_exists('mb_strtolower') ? mb_strtolower($tok) : strtolower($tok);
            if ($tok === '' || in_array($lower, $stop, true)) {
                continue;
            }
            $tokens[] = $tok;
        }
    }

    return ['bedrooms' => $bedrooms, 'bathrooms' => $bathrooms, 'tokens' => $tokens];
}

/**
 * SQL fragment: token matches common property text fields (and type name).
 *
 * @return array{0: string, 1: list<string>}
 */
function property_public_text_match_sql(string $token): array
{
    $like = '%' . property_like_escape($token) . '%';
    $sql = '(p.title LIKE ? ESCAPE \'\\\\\'
        OR p.address_line LIKE ? ESCAPE \'\\\\\'
        OR p.city LIKE ? ESCAPE \'\\\\\'
        OR p.region LIKE ? ESCAPE \'\\\\\'
        OR p.state LIKE ? ESCAPE \'\\\\\'
        OR p.description LIKE ? ESCAPE \'\\\\\'
        OR p.badge LIKE ? ESCAPE \'\\\\\'
        OR p.reference_code LIKE ? ESCAPE \'\\\\\'
        OR p.mls_number LIKE ? ESCAPE \'\\\\\'
        OR t.name LIKE ? ESCAPE \'\\\\\')';
    return [$sql, array_fill(0, 10, $like)];
}

/**
 * @return array{0: string, 1: list<mixed>}
 */
function property_public_where_sql(array $filters = []): array
{
    [$in, $statuses] = public_status_sql_in();
    $where = ["p.status IN ($in)"];
    $params = $statuses;

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $parsed = property_parse_public_query($q);

        if ($parsed['bedrooms'] !== null) {
            // Whole numbers match 5 and 5.0; decimals match exactly.
            if (fmod($parsed['bedrooms'], 1.0) < 0.001) {
                $where[] = 'p.bedrooms IS NOT NULL AND FLOOR(p.bedrooms) = ?';
                $params[] = (int) $parsed['bedrooms'];
            } else {
                $where[] = 'p.bedrooms = ?';
                $params[] = $parsed['bedrooms'];
            }
        }

        if ($parsed['bathrooms'] !== null) {
            if (fmod($parsed['bathrooms'], 1.0) < 0.001) {
                $where[] = 'p.bathrooms IS NOT NULL AND FLOOR(p.bathrooms) = ?';
                $params[] = (int) $parsed['bathrooms'];
            } else {
                $where[] = 'p.bathrooms = ?';
                $params[] = $parsed['bathrooms'];
            }
        }

        $tokens = $parsed['tokens'];
        if ($tokens === [] && $parsed['bedrooms'] === null && $parsed['bathrooms'] === null) {
            // Fallback: full phrase text search (unchanged intent).
            $tokens = [$q];
        }

        foreach ($tokens as $token) {
            [$frag, $fragParams] = property_public_text_match_sql($token);
            $where[] = $frag;
            array_push($params, ...$fragParams);
        }
    }

    $location = trim((string) ($filters['location'] ?? ''));
    if ($location !== '') {
        $locTokens = property_parse_public_query($location)['tokens'];
        if ($locTokens === []) {
            $locTokens = [$location];
        }
        foreach ($locTokens as $token) {
            $like = '%' . property_like_escape($token) . '%';
            $where[] = '(p.city LIKE ? ESCAPE \'\\\\\' OR p.region LIKE ? ESCAPE \'\\\\\' OR p.address_line LIKE ? ESCAPE \'\\\\\' OR p.state LIKE ? ESCAPE \'\\\\\')';
            array_push($params, $like, $like, $like, $like);
        }
    }

    $region = trim((string) ($filters['region'] ?? ''));
    if ($region !== '') {
        $where[] = '(p.region LIKE ? OR p.city LIKE ?)';
        $like = '%' . $region . '%';
        array_push($params, $like, $like);
    }

    $agentId = (int) ($filters['agent_id'] ?? 0);
    if ($agentId > 0) {
        $where[] = 'p.agent_id = ?';
        $params[] = $agentId;
    }

    $type = trim((string) ($filters['type'] ?? ''));
    if ($type !== '') {
        $where[] = 't.slug = ?';
        $params[] = $type;
    }

    $price = trim((string) ($filters['price'] ?? ''));
    if ($price === '1-5') {
        $where[] = 'p.price_on_request = 0 AND p.price >= ? AND p.price < ?';
        array_push($params, 1000000, 5000000);
    } elseif ($price === '2-5') {
        $where[] = 'p.price_on_request = 0 AND p.price >= ? AND p.price < ?';
        array_push($params, 2000000, 5000000);
    } elseif ($price === '5-10') {
        $where[] = 'p.price_on_request = 0 AND p.price >= ? AND p.price < ?';
        array_push($params, 5000000, 10000000);
    } elseif ($price === '10+') {
        $where[] = 'p.price_on_request = 0 AND p.price >= ?';
        $params[] = 10000000;
    }

    if (!empty($filters['featured_only'])) {
        $where[] = 'p.is_featured = 1';
    }

    return [implode(' AND ', $where), $params];
}

function property_public_order_sql(string $sort): string
{
    return match ($sort) {
        'price_asc' => 'p.price_on_request ASC, p.price ASC, p.id DESC',
        'price_desc' => 'p.price_on_request ASC, p.price DESC, p.id DESC',
        default => 'p.listed_at DESC, p.created_at DESC, p.id DESC',
    };
}

/**
 * @return list<array<string, mixed>>
 */
function property_list_public(array $filters = [], string $sort = 'newest', int $limit = 24, int $offset = 0): array
{
    [$where, $params] = property_public_where_sql($filters);
    $order = property_public_order_sql($sort);
    $sql = "SELECT p.*, t.name AS type_name, t.slug AS type_slug,
            (SELECT path FROM property_images pi WHERE pi.property_id = p.id AND pi.is_cover = 1 ORDER BY pi.id ASC LIMIT 1) AS cover_path,
            (SELECT path FROM property_images pi2 WHERE pi2.property_id = p.id ORDER BY pi2.is_cover DESC, pi2.sort_order ASC, pi2.id ASC LIMIT 1) AS image_path
            FROM properties p
            LEFT JOIN property_types t ON t.id = p.property_type_id
            WHERE $where
            ORDER BY $order
            LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll() ?: [];
    foreach ($rows as &$row) {
        if (empty($row['cover_path']) && !empty($row['image_path'])) {
            $row['cover_path'] = $row['image_path'];
        }
    }
    unset($row);
    return $rows;
}

function property_count_public(array $filters = []): int
{
    [$where, $params] = property_public_where_sql($filters);
    $sql = "SELECT COUNT(*) FROM properties p LEFT JOIN property_types t ON t.id = p.property_type_id WHERE $where";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function property_find_public_by_slug(string $slug): ?array
{
    [$in, $statuses] = public_status_sql_in();
    $stmt = db()->prepare(
        "SELECT p.*, t.name AS type_name, t.slug AS type_slug,
                a.name AS agent_name, a.title AS agent_title, a.region AS agent_region,
                a.photo_path AS agent_photo, a.bio AS agent_bio, a.slug AS agent_slug, a.badge AS agent_badge
         FROM properties p
         LEFT JOIN property_types t ON t.id = p.property_type_id
         LEFT JOIN agents a ON a.id = p.agent_id
         WHERE p.slug = ? AND p.status IN ($in)
         LIMIT 1"
    );
    $stmt->execute(array_merge([$slug], $statuses));
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

/**
 * @return list<array<string, mixed>>
 */
function property_amenities_for(int $propertyId): array
{
    $stmt = db()->prepare(
        'SELECT am.* FROM amenities am
         INNER JOIN property_amenity pa ON pa.amenity_id = am.id
         WHERE pa.property_id = ?
         ORDER BY am.category, am.sort_order, am.name'
    );
    $stmt->execute([$propertyId]);
    return $stmt->fetchAll() ?: [];
}

/**
 * @return list<array<string, mixed>>
 */
function agents_list_public(?string $region = null): array
{
    $sql = 'SELECT * FROM agents WHERE is_active = 1';
    $params = [];
    if ($region !== null && $region !== '') {
        $sql .= ' AND region LIKE ?';
        $params[] = '%' . $region . '%';
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
}

/**
 * @return list<array<string, mixed>>
 */
function offices_list_public(): array
{
    return db()->query('SELECT * FROM offices WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll() ?: [];
}

/**
 * Resolve agent portrait URL from DB path or bundled assets/img/agent-{slug}.jpg fallback.
 */
function agent_photo_url(?string $photoPath, ?string $slug = null): string
{
    $photo = trim((string) $photoPath);
    if ($photo !== '') {
        $url = media_url($photo);
        if ($url !== '') {
            return $url;
        }
    }

    $slug = preg_replace('/[^a-z0-9\-]+/', '', strtolower(trim((string) $slug))) ?: '';
    if ($slug === '') {
        return '';
    }

    $candidate = 'assets/img/agent-' . $slug . '.jpg';
    if (is_readable(APP_ROOT . '/' . $candidate)) {
        return media_url($candidate);
    }

    return '';
}
