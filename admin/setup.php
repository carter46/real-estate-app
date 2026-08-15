<?php
/**
 * One-time admin setup — creates the first admin password (never seeded).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!admin_setup_required()) {
    if (auth_check()) {
        redirect('admin/index.php');
    }
    flash_set('auth_error', 'Admin setup is already complete. Please sign in.');
    redirect('admin/login.php');
}

$error = null;
$name = '';
$email = '';

if (is_post()) {
    if (!csrf_verify()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = (string) ($_POST['name'] ?? '');
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
        $result = auth_create_first_admin($name, $email, $password, $confirm);
        if ($result['ok']) {
            flash_set('auth_ok', 'Admin account created. Welcome.');
            redirect('admin/index.php');
        }
        $error = $result['error'] ?? 'Setup failed.';
    }
}

$appName = (string) app_config('app.name', 'SDC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin setup — <?= e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:ital,wght@0,400;1,400&family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
<div class="admin-auth">
    <div class="admin-auth__card">
        <span class="admin-eyebrow">SDC Admin</span>
        <h1>Create admin account</h1>
        <p class="lead">One-time setup. No default password is seeded. Choose a strong password (12+ characters).</p>

        <?php if ($error): ?>
            <div class="admin-alert admin-alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_field() ?>
            <div class="admin-field">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" required autocomplete="name" value="<?= e($name) ?>">
            </div>
            <div class="admin-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required autocomplete="username" value="<?= e($email) ?>">
            </div>
            <div class="admin-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" minlength="12">
            </div>
            <div class="admin-field">
                <label for="password_confirm">Confirm password</label>
                <input id="password_confirm" name="password_confirm" type="password" required autocomplete="new-password" minlength="12">
            </div>
            <button class="admin-btn" type="submit">Create admin &amp; continue</button>
        </form>
        <p class="admin-auth__foot"><a href="<?= e(base_url('index.php')) ?>">Back to site</a></p>
    </div>
</div>
</body>
</html>
