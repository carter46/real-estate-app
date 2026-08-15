<?php
/**
 * Admin logout.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

auth_logout();
auth_session_start();
flash_set('auth_ok', 'You have been signed out.');
redirect('admin/login.php');
