<?php
/**
 * Permanently delete an archived property (DB + local image files).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
auth_require();

$redirectTo = 'admin/properties.php?status=archived';

if (!is_post() || !csrf_verify()) {
    flash_set('property_error', 'Invalid delete request.');
    redirect($redirectTo);
}

$id = (int) ($_POST['id'] ?? 0);
$property = $id > 0 ? property_find($id) : null;
if (!$property) {
    flash_set('property_error', 'Property not found.');
    redirect($redirectTo);
}

if ((string) ($property['status'] ?? '') !== 'archived') {
    flash_set('property_error', 'Only archived properties can be permanently deleted. Archive it first.');
    redirect('admin/properties.php');
}

$confirm = trim((string) ($_POST['confirm'] ?? ''));
if ($confirm !== 'DELETE') {
    flash_set('property_error', 'Permanent delete cancelled. Type DELETE to confirm.');
    redirect($redirectTo);
}

if (!property_purge_permanent($id)) {
    flash_set('property_error', 'Could not permanently delete this property.');
    redirect($redirectTo);
}

flash_set('property_ok', 'Property permanently deleted (database records and image files removed).');
redirect($redirectTo);
