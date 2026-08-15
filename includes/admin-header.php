<?php
/**
 * Admin layout header — IA matches references/admin_* (full Stitch polish in Phase 6).
 *
 * @var string $adminPageTitle
 * @var string $adminActiveNav  overview|properties|inquiries
 */

declare(strict_types=1);

$adminPageTitle = $adminPageTitle ?? 'Admin';
$adminActiveNav = $adminActiveNav ?? 'overview';
$appName = (string) app_config('app.name', 'SDC');
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminPageTitle) ?> — <?= e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:ital,wght@0,400;1,400&family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,300,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
<header class="admin-topbar">
    <div class="admin-topbar__brand">
        <div class="admin-topbar__mark" aria-hidden="true">SDC</div>
        <div class="admin-topbar__titles">
            <span class="admin-eyebrow">Admin Console</span>
            <span class="admin-topbar__subtitle">Portfolio Manager · Colorado Region</span>
        </div>
    </div>
    <div class="admin-topbar__meta">
        <?php if ($user): ?>
            <span class="admin-topbar__user"><?= e((string) ($user['name'] ?: $user['email'])) ?></span>
        <?php endif; ?>
        <a class="admin-topbar__link" href="<?= e(base_url('admin/logout.php')) ?>">Sign out</a>
    </div>
</header>
<div class="admin-shell">
<?php require __DIR__ . '/admin-nav.php'; ?>
<main class="admin-main">
