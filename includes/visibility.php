<?php
/**
 * Property visibility rules for public vs admin queries.
 */

declare(strict_types=1);

/**
 * @return list<string>
 */
function public_property_statuses(): array
{
    $map = app_config('visibility', []);
    if (!is_array($map)) {
        return ['available', 'pending', 'under_contract', 'sold'];
    }

    $public = [];
    foreach ($map as $status => $visible) {
        if ($visible) {
            $public[] = (string) $status;
        }
    }

    return $public;
}

function is_property_status_public(string $status): bool
{
    return in_array($status, public_property_statuses(), true);
}

/**
 * SQL fragment placeholders for public status IN (...).
 *
 * @return array{0: string, 1: list<string>} [placeholders, statuses]
 */
function public_status_sql_in(): array
{
    $statuses = public_property_statuses();
    if ($statuses === []) {
        // Impossible value so IN (?) never matches (avoids invalid IN () / IN (NULL)).
        return ['?', ['__no_public_status__']];
    }

    $placeholders = implode(', ', array_fill(0, count($statuses), '?'));
    return [$placeholders, $statuses];
}
