<?php
/**
 * Property type & amenity CMS helpers — soft-deactivate only (no orphaning).
 */

declare(strict_types=1);

/**
 * Next sort_order for taxonomy tables (10, 20, 30…).
 */
function taxonomy_next_sort_order(string $table): int
{
    $allowed = ['property_types', 'amenities', 'regions'];
    if (!in_array($table, $allowed, true)) {
        return 10;
    }
    $max = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM `' . $table . '`')->fetchColumn();
    return $max + 10;
}

function taxonomy_type_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM property_types WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function taxonomy_type_usage_count(int $id): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM properties WHERE property_type_id = ?');
    $stmt->execute([$id]);
    return (int) $stmt->fetchColumn();
}

/**
 * @return array{ok:bool, error:?string, id:?int}
 */
function taxonomy_type_save(?int $id, string $name, string $slug, bool $isActive): array
{
    $name = trim($name);
    $slug = slugify($slug !== '' ? $slug : $name);
    if ($name === '') {
        return ['ok' => false, 'error' => 'Name is required.', 'id' => null];
    }
    if (strlen($name) > 120 || strlen($slug) > 80) {
        return ['ok' => false, 'error' => 'Name or slug is too long.', 'id' => null];
    }

    $check = db()->prepare('SELECT id FROM property_types WHERE slug = ? AND id != ? LIMIT 1');
    $check->execute([$slug, $id ?? 0]);
    if ($check->fetch()) {
        return ['ok' => false, 'error' => 'Slug already in use.', 'id' => null];
    }

    if ($id) {
        db()->prepare(
            'UPDATE property_types SET name = ?, slug = ?, is_active = ? WHERE id = ?'
        )->execute([$name, $slug, $isActive ? 1 : 0, $id]);
        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    $sortOrder = taxonomy_next_sort_order('property_types');
    db()->prepare(
        'INSERT INTO property_types (name, slug, sort_order, is_active) VALUES (?, ?, ?, ?)'
    )->execute([$name, $slug, $sortOrder, $isActive ? 1 : 0]);
    return ['ok' => true, 'error' => null, 'id' => (int) db()->lastInsertId()];
}

function taxonomy_type_deactivate(int $id): void
{
    db()->prepare('UPDATE property_types SET is_active = 0 WHERE id = ?')->execute([$id]);
}

function taxonomy_amenity_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM amenities WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function taxonomy_amenity_usage_count(int $id): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM property_amenity WHERE amenity_id = ?');
    $stmt->execute([$id]);
    return (int) $stmt->fetchColumn();
}

/**
 * @return array{ok:bool, error:?string, id:?int}
 */
function taxonomy_amenity_save(?int $id, string $name, string $slug, string $category, bool $isActive): array
{
    $name = trim($name);
    $slug = slugify($slug !== '' ? $slug : $name);
    $allowedCat = ['interior', 'exterior', 'community', 'other'];
    if (!in_array($category, $allowedCat, true)) {
        $category = 'other';
    }
    if ($name === '') {
        return ['ok' => false, 'error' => 'Name is required.', 'id' => null];
    }
    if (strlen($name) > 120 || strlen($slug) > 80) {
        return ['ok' => false, 'error' => 'Name or slug is too long.', 'id' => null];
    }

    $check = db()->prepare('SELECT id FROM amenities WHERE slug = ? AND id != ? LIMIT 1');
    $check->execute([$slug, $id ?? 0]);
    if ($check->fetch()) {
        return ['ok' => false, 'error' => 'Slug already in use.', 'id' => null];
    }

    if ($id) {
        db()->prepare(
            'UPDATE amenities SET name = ?, slug = ?, category = ?, is_active = ? WHERE id = ?'
        )->execute([$name, $slug, $category, $isActive ? 1 : 0, $id]);
        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    $sortOrder = taxonomy_next_sort_order('amenities');
    db()->prepare(
        'INSERT INTO amenities (name, slug, category, sort_order, is_active) VALUES (?, ?, ?, ?, ?)'
    )->execute([$name, $slug, $category, $sortOrder, $isActive ? 1 : 0]);
    return ['ok' => true, 'error' => null, 'id' => (int) db()->lastInsertId()];
}

function taxonomy_amenity_deactivate(int $id): void
{
    db()->prepare('UPDATE amenities SET is_active = 0 WHERE id = ?')->execute([$id]);
}

function taxonomy_region_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM regions WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function taxonomy_region_usage_count(int $id): int
{
    $row = taxonomy_region_find($id);
    if ($row === null) {
        return 0;
    }
    $name = (string) ($row['name'] ?? '');
    if ($name === '') {
        return 0;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM properties WHERE region = ?');
    $stmt->execute([$name]);
    return (int) $stmt->fetchColumn();
}

/**
 * @return array{ok:bool, error:?string, id:?int}
 */
function taxonomy_region_save(?int $id, string $name, string $slug, bool $isActive): array
{
    $name = trim($name);
    $slug = slugify($slug !== '' ? $slug : $name);
    if ($name === '') {
        return ['ok' => false, 'error' => 'Name is required.', 'id' => null];
    }
    if (strlen($name) > 120 || strlen($slug) > 80) {
        return ['ok' => false, 'error' => 'Name or slug is too long.', 'id' => null];
    }

    $check = db()->prepare('SELECT id FROM regions WHERE slug = ? AND id != ? LIMIT 1');
    $check->execute([$slug, $id ?? 0]);
    if ($check->fetch()) {
        return ['ok' => false, 'error' => 'Slug already in use.', 'id' => null];
    }

    $nameCheck = db()->prepare('SELECT id FROM regions WHERE name = ? AND id != ? LIMIT 1');
    $nameCheck->execute([$name, $id ?? 0]);
    if ($nameCheck->fetch()) {
        return ['ok' => false, 'error' => 'Region name already exists.', 'id' => null];
    }

    if ($id) {
        $prev = taxonomy_region_find($id);
        $oldName = is_array($prev) ? (string) ($prev['name'] ?? '') : '';
        db()->prepare(
            'UPDATE regions SET name = ?, slug = ?, is_active = ? WHERE id = ?'
        )->execute([$name, $slug, $isActive ? 1 : 0, $id]);
        // Keep property.region text in sync when renaming.
        if ($oldName !== '' && $oldName !== $name) {
            db()->prepare('UPDATE properties SET region = ? WHERE region = ?')->execute([$name, $oldName]);
        }
        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    $sortOrder = taxonomy_next_sort_order('regions');
    db()->prepare(
        'INSERT INTO regions (name, slug, sort_order, is_active) VALUES (?, ?, ?, ?)'
    )->execute([$name, $slug, $sortOrder, $isActive ? 1 : 0]);
    return ['ok' => true, 'error' => null, 'id' => (int) db()->lastInsertId()];
}

function taxonomy_region_deactivate(int $id): void
{
    db()->prepare('UPDATE regions SET is_active = 0 WHERE id = ?')->execute([$id]);
}

/**
 * @return list<array<string, mixed>>
 */
function regions_all(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM regions';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order, name';
    try {
        return db()->query($sql)->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Active regions plus current name (even if deactivated / unknown) for edit forms.
 *
 * @return list<array<string, mixed>>
 */
function regions_for_select(?string $currentName = null): array
{
    $rows = regions_all(true);
    $currentName = trim((string) $currentName);
    if ($currentName === '') {
        return $rows;
    }
    foreach ($rows as $row) {
        if ((string) ($row['name'] ?? '') === $currentName) {
            return $rows;
        }
    }
    array_unshift($rows, [
        'id' => 0,
        'slug' => slugify($currentName),
        'name' => $currentName,
        'sort_order' => 0,
        'is_active' => 0,
    ]);
    return $rows;
}
