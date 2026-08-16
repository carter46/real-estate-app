<?php
/**
 * Property type & amenity CMS helpers — soft-deactivate only (no orphaning).
 */

declare(strict_types=1);

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
function taxonomy_type_save(?int $id, string $name, string $slug, int $sortOrder, bool $isActive): array
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
            'UPDATE property_types SET name = ?, slug = ?, sort_order = ?, is_active = ? WHERE id = ?'
        )->execute([$name, $slug, $sortOrder, $isActive ? 1 : 0, $id]);
        return ['ok' => true, 'error' => null, 'id' => $id];
    }

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
function taxonomy_amenity_save(?int $id, string $name, string $slug, string $category, int $sortOrder, bool $isActive): array
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
            'UPDATE amenities SET name = ?, slug = ?, category = ?, sort_order = ?, is_active = ? WHERE id = ?'
        )->execute([$name, $slug, $category, $sortOrder, $isActive ? 1 : 0, $id]);
        return ['ok' => true, 'error' => null, 'id' => $id];
    }

    db()->prepare(
        'INSERT INTO amenities (name, slug, category, sort_order, is_active) VALUES (?, ?, ?, ?, ?)'
    )->execute([$name, $slug, $category, $sortOrder, $isActive ? 1 : 0]);
    return ['ok' => true, 'error' => null, 'id' => (int) db()->lastInsertId()];
}

function taxonomy_amenity_deactivate(int $id): void
{
    db()->prepare('UPDATE amenities SET is_active = 0 WHERE id = ?')->execute([$id]);
}
