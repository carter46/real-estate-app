<?php
/**
 * Admin login.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (admin_setup_required()) {
    redirect('admin/setup.php');
}

if (auth_check()) {
    redirect('admin/index.php');
}

$error = flash_get('auth_error');
$ok = flash_get('auth_ok');
$email = '';

if (is_post()) {
    if (!csrf_verify()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $result = auth_attempt_login($email, $password);
        if ($result['ok']) {
            redirect('admin/index.php');
        }
        $error = $result['error'] ?? 'Sign-in failed.';
    }
}

$appName = (string) app_config('app.name', 'SDC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin login — <?= e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:ital,wght@0,400;1,400&family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
<div class="admin-auth">
    <div class="admin-auth__card">
        <span class="admin-eyebrow">Admin Console</span>
        <h1>Sign in</h1>
        <p class="lead">Sunview Development and Consultancy (SDC) portfolio manager.</p>

        <?php if ($ok): ?>
            <div class="admin-alert admin-alert--ok"><?= e($ok) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="admin-alert admin-alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_field() ?>
            <div class="admin-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required autocomplete="username" value="<?= e($email) ?>">
            </div>
            <div class="admin-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </div>
            <button class="admin-btn" type="submit">Sign in</button>
        </form>
        <p class="admin-auth__foot"><a href="<?= e(base_url('index.php')) ?>">Exit to site</a></p>
    </div>
</div>
</body>
</html>
