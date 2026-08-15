<?php
/**
 * Archive property (soft delete via status=archived). POST + CSRF required.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
auth_require();

if (!is_post() || !csrf_verify()) {
    flash_set('property_error', 'Invalid archive request.');
    redirect('admin/properties.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0 || !property_find($id)) {
    flash_set('property_error', 'Property not found.');
    redirect('admin/properties.php');
}

property_archive($id);
flash_set('property_ok', 'Property archived.');
redirect('admin/properties.php');
