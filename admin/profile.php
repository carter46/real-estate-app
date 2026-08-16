<?php
/**
 * Admin profile — change email / password.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
auth_require();

$user = auth_user();
$errors = [];
$ok = flash_get('profile_ok');
$name = (string) ($user['name'] ?? '');
$email = (string) ($user['email'] ?? '');

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = (string) ($_POST['action'] ?? 'profile');
        if ($action === 'profile') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            if ($name === '' || $email === '') {
                $errors[] = 'Name and email are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address.';
            } else {
                $check = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
                $check->execute([$email, (int) $user['id']]);
                if ($check->fetch()) {
                    $errors[] = 'That email is already in use.';
                } else {
                    db()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')
                        ->execute([$name, $email, (int) $user['id']]);
                    $_SESSION['auth_user']['name'] = $name;
                    $_SESSION['auth_user']['email'] = $email;
                    flash_set('profile_ok', 'Profile updated.');
                    redirect('admin/profile.php');
                }
            }
        } elseif ($action === 'password') {
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');
            $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int) $user['id']]);
            $hash = (string) ($stmt->fetchColumn() ?: '');
            if ($hash === '' || !password_verify($current, $hash)) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($new) < 12) {
                $errors[] = 'New password must be at least 12 characters.';
            } elseif (!hash_equals($new, $confirm)) {
                $errors[] = 'Password confirmation does not match.';
            } else {
                $algo = app_config('security.password_algo', PASSWORD_DEFAULT);
                $newHash = password_hash($new, is_int($algo) || is_string($algo) ? $algo : PASSWORD_DEFAULT);
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, (int) $user['id']]);
                auth_logout();
                auth_session_start();
                flash_set('auth_ok', 'Password changed. Please sign in again.');
                redirect('admin/login.php');
            }
        }
    }
}

$adminPageTitle = 'Account';
$adminActiveNav = 'profile';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<span class="admin-eyebrow">Account</span>
<h1 class="admin-page-title">Admin Profile</h1>
<p class="admin-page-lead">Update your display name, sign-in email, or password.</p>

<?php if ($ok): ?><div class="admin-alert admin-alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php foreach ($errors as $err): ?><div class="admin-alert admin-alert--error"><?= e($err) ?></div><?php endforeach; ?>

<form class="admin-panel" method="post" action="">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="profile">
  <h2>Profile</h2>
  <div class="admin-field">
    <label for="name">Name</label>
    <input id="name" name="name" required value="<?= e($name) ?>">
  </div>
  <div class="admin-field">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" required value="<?= e($email) ?>">
  </div>
  <button class="admin-btn" type="submit">Save profile</button>
</form>

<form class="admin-panel" method="post" action="" style="margin-top:1.5rem;">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <h2>Change password</h2>
  <div class="admin-field">
    <label for="current_password">Current password</label>
    <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
  </div>
  <div class="admin-field">
    <label for="new_password">New password</label>
    <input id="new_password" name="new_password" type="password" required minlength="12" autocomplete="new-password">
  </div>
  <div class="admin-field">
    <label for="confirm_password">Confirm new password</label>
    <input id="confirm_password" name="confirm_password" type="password" required minlength="12" autocomplete="new-password">
  </div>
  <button class="admin-btn" type="submit">Change password</button>
  <p class="admin-note">Changing your password signs you out of this session.</p>
</form>
<?php require dirname(__DIR__) . '/includes/admin-footer.php';
