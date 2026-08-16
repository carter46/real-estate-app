<?php
/**
 * Admin logout — POST + CSRF only (prevents logout CSRF via GET).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!is_post() || !csrf_verify()) {
    flash_set('auth_error', 'Invalid sign-out request. Please try again.');
    redirect('admin/index.php');
}

auth_logout();
auth_session_start();
flash_set('auth_ok', 'You have been signed out.');
redirect('admin/login.php');
