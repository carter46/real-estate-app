<?php
/**
 * Website settings — SSoT for branding & public contact + mail test.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
auth_require();

$errors = [];
$ok = flash_get('settings_ok');
$testToPrefill = '';
$mailTestMessage = '';
$mailTestOk = false;

$wantsJson = str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')
    || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

/**
 * @param array{ok:bool,message:string,error?:?string,driver?:string,to?:string} $payload
 */
function settings_mail_test_respond(bool $wantsJson, array $payload): void
{
    if (!$wantsJson) {
        return;
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($payload['ok'] ? 200 : 422);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Invalid security token.';
        if ($wantsJson && (string) ($_POST['action'] ?? '') === 'test_mail') {
            settings_mail_test_respond(true, ['ok' => false, 'message' => 'Invalid security token.', 'error' => 'csrf']);
        }
    } else {
        $action = (string) ($_POST['action'] ?? 'save');

        if ($action === 'test_mail') {
            $testTo = strtolower(trim((string) ($_POST['test_email'] ?? '')));
            $testToPrefill = $testTo;
            if ($testTo === '' || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
                $mailTestMessage = 'Enter a valid recipient email for the test.';
                settings_mail_test_respond($wantsJson, ['ok' => false, 'message' => $mailTestMessage]);
                $errors[] = $mailTestMessage;
            } else {
                $limit = rate_limit_hit('admin_mail_test', 10, 3600);
                if (!$limit['allowed']) {
                    $mailTestMessage = 'Too many test emails. Try again later.';
                    settings_mail_test_respond($wantsJson, ['ok' => false, 'message' => $mailTestMessage]);
                    $errors[] = $mailTestMessage;
                } else {
                    $brand = site_name();
                    $driver = strtolower((string) app_config('mail.driver', 'brevo'));
                    $subject = $brand . ' — mail test';
                    $text = "This is a test message from the {$brand} admin settings page.\n"
                        . 'Driver: ' . $driver . "\n"
                        . 'Sent at: ' . date('c') . "\n";
                    $html = '<p>This is a test message from the <strong>' . e($brand) . '</strong> admin settings page.</p>'
                        . '<p>Driver: <code>' . e($driver) . '</code><br>Sent at: ' . e(date('c')) . '</p>';
                    $result = send_mail($testTo, $subject, $html, $text);
                    if ($result['ok']) {
                        $mailTestOk = true;
                        $mailTestMessage = 'Test email sent to ' . $testTo . ' via ' . $driver
                            . '. Check the inbox (and spam).';
                        settings_mail_test_respond($wantsJson, [
                            'ok' => true,
                            'message' => $mailTestMessage,
                            'driver' => $driver,
                            'to' => $testTo,
                        ]);
                    } else {
                        $mailTestMessage = 'Test email failed: ' . (string) ($result['error'] ?? 'Unknown error')
                            . ' Check storage/logs/mail.log on the server.';
                        settings_mail_test_respond($wantsJson, [
                            'ok' => false,
                            'message' => $mailTestMessage,
                            'error' => (string) ($result['error'] ?? 'Unknown error'),
                        ]);
                        $errors[] = $mailTestMessage;
                    }
                }
            }
        } else {
            $name = trim((string) ($_POST['site_name'] ?? ''));
            $phone = trim((string) ($_POST['site_phone'] ?? ''));
            $email = trim((string) ($_POST['site_email'] ?? ''));
            $mailFrom = trim((string) ($_POST['mail_from_name'] ?? ''));

            if ($name === '') {
                $errors[] = 'Site name is required.';
            } elseif (strlen($name) > 191) {
                $errors[] = 'Site name is too long.';
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Public site email is invalid.';
            }
            if (strlen($phone) > 40) {
                $errors[] = 'Phone is too long.';
            }

            if ($errors === []) {
                setting_set('site_name', $name);
                setting_set('site_phone', $phone !== '' ? $phone : null);
                setting_set('site_email', $email !== '' ? $email : null);
                setting_set('mail_from_name', $mailFrom !== '' ? $mailFrom : null);

                if (!empty($_FILES['logo']['name'])) {
                    $prev = setting_get('site_logo_path');
                    $prev = ($prev && str_starts_with($prev, 'uploads/branding/')) ? $prev : null;
                    $up = branding_upload_image($_FILES['logo'], 'logo', $prev);
                    if (!$up['ok']) {
                        $errors[] = (string) $up['error'];
                    } else {
                        setting_set('site_logo_path', $up['path']);
                    }
                }

                if (!empty($_FILES['favicon']['name'])) {
                    $prev = setting_get('site_favicon_path');
                    $prev = ($prev && str_starts_with($prev, 'uploads/branding/')) ? $prev : null;
                    $up = branding_upload_image($_FILES['favicon'], 'favicon', $prev);
                    if (!$up['ok']) {
                        $errors[] = (string) $up['error'];
                    } else {
                        setting_set('site_favicon_path', $up['path']);
                    }
                }

                if ($errors === []) {
                    flash_set('settings_ok', 'Settings saved. Public site branding will use these values.');
                    redirect('admin/settings.php');
                }
            }
        }
    }
}

$mailDriver = strtolower((string) app_config('mail.driver', 'brevo'));
$brevoKeySet = trim((string) app_config('mail.brevo_api_key', ''));
$brevoKeyOk = $brevoKeySet !== '' && $brevoKeySet !== 'YOUR_BREVO_API_KEY';
$fromEmail = (string) app_config('mail.from_email', 'noreply@example.com');
$phpMailerOk = class_exists(\PHPMailer\PHPMailer\PHPMailer::class) || mail_ensure_phpmailer();

$adminPageTitle = 'Website Settings';
$adminActiveNav = 'settings';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<span class="admin-eyebrow">Configuration</span>
<h1 class="admin-page-title">Website Settings</h1>
<p class="admin-page-lead">Single source of truth for site name, logo, favicon, and public contact details.</p>

<?php if ($ok): ?><div class="admin-alert admin-alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php foreach ($errors as $err): ?><div class="admin-alert admin-alert--error"><?= e($err) ?></div><?php endforeach; ?>

<form class="admin-panel" method="post" enctype="multipart/form-data" action="">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <div class="admin-field">
    <label for="site_name">Site name *</label>
    <input id="site_name" name="site_name" required maxlength="191" value="<?= e(site_name()) ?>">
  </div>
  <div class="admin-field">
    <label for="mail_from_name">Email display name</label>
    <input id="mail_from_name" name="mail_from_name" maxlength="120" value="<?= e((string) (setting_get('mail_from_name') ?: '')) ?>" placeholder="<?= e(site_name()) ?>">
    <p class="admin-note">Used as the From name on outgoing mail. Leave blank to use the site name.</p>
  </div>
  <div class="admin-field">
    <label for="site_phone">Public phone</label>
    <input id="site_phone" name="site_phone" maxlength="40" value="<?= e(site_phone()) ?>">
  </div>
  <div class="admin-field">
    <label for="site_email">Public email</label>
    <input id="site_email" name="site_email" type="email" maxlength="191" value="<?= e(site_email()) ?>">
    <p class="admin-note">Also used as inquiry notify fallback when mail.admin_notify_email is empty.</p>
  </div>
  <div class="admin-field">
    <label>Current logo</label>
    <p><img src="<?= e(site_logo_url()) ?>" alt="" style="max-height:48px;background:#fff;padding:4px;"></p>
    <label for="logo">Replace logo</label>
    <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml">
  </div>
  <div class="admin-field">
    <label>Current favicon</label>
    <?php if (site_favicon_url() !== ''): ?>
      <p><img src="<?= e(site_favicon_url()) ?>" alt="" style="max-height:32px;"></p>
    <?php else: ?>
      <p class="admin-note">No favicon uploaded yet.</p>
    <?php endif; ?>
    <label for="favicon">Replace favicon</label>
    <input id="favicon" name="favicon" type="file" accept="image/png,image/jpeg,image/webp,image/x-icon,.ico">
  </div>
  <button class="admin-btn" type="submit">Save settings</button>
</form>

<section class="admin-panel" id="mail-test">
  <h2>Test email delivery</h2>
  <p class="admin-note" style="margin-top:0;">
    Sends a short test via the configured mailer.
    Driver: <strong><?= e($mailDriver) ?></strong>
    · From: <strong><?= e($fromEmail) ?></strong>
    · PHPMailer: <strong><?= $phpMailerOk ? 'loaded' : 'missing' ?></strong>
    <?php if ($mailDriver === 'brevo'): ?>
      · Brevo API key: <strong><?= $brevoKeyOk ? 'set' : 'missing (will skip Brevo)' ?></strong>
      · SMTP host: <strong><?= mail_smtp_configured() ? e((string) app_config('mail.smtp_host', '')) : 'not set' ?></strong>
      <br>Order when driver=brevo: Brevo (if key set) → PHPMailer SMTP (if host set) → PHPMailer mail transport.
    <?php elseif ($mailDriver === 'smtp'): ?>
      · SMTP host: <strong><?= mail_smtp_configured() ? e((string) app_config('mail.smtp_host', '')) : 'not set' ?></strong>
    <?php endif; ?>
  </p>
  <div id="mail-test-status" class="admin-alert<?= $mailTestMessage === '' ? '' : ($mailTestOk ? ' admin-alert--ok' : ' admin-alert--error') ?>"<?= $mailTestMessage === '' ? ' hidden' : '' ?> role="status" aria-live="polite"><?= e($mailTestMessage) ?></div>
  <form id="mail-test-form" method="post" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="test_mail">
    <div class="admin-field">
      <label for="test_email">Send test to *</label>
      <input id="test_email" name="test_email" type="email" required maxlength="191"
             value="<?= e($testToPrefill !== '' ? $testToPrefill : (string) (auth_user()['email'] ?? '')) ?>"
             placeholder="you@example.com">
    </div>
    <button class="admin-btn" type="submit" id="mail-test-submit">Send test email</button>
  </form>
</section>
<script>
(function () {
  var form = document.getElementById('mail-test-form');
  var statusEl = document.getElementById('mail-test-status');
  var submitBtn = document.getElementById('mail-test-submit');
  if (!form || !statusEl || !submitBtn) return;

  function showStatus(ok, message) {
    statusEl.hidden = false;
    statusEl.classList.remove('admin-alert--ok', 'admin-alert--error');
    statusEl.classList.add(ok ? 'admin-alert--ok' : 'admin-alert--error');
    statusEl.textContent = message;
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var email = (form.querySelector('#test_email') || {}).value || '';
    if (!email.trim()) {
      showStatus(false, 'Enter a valid recipient email for the test.');
      return;
    }

    submitBtn.disabled = true;
    var previousLabel = submitBtn.textContent;
    submitBtn.textContent = 'Sending…';
    showStatus(true, 'Sending test email…');
    statusEl.classList.remove('admin-alert--ok', 'admin-alert--error');

    fetch(window.location.href, {
      method: 'POST',
      body: new FormData(form),
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { okHttp: res.ok, data: data || {} };
        }).catch(function () {
          return { okHttp: res.ok, data: { ok: false, message: 'Unexpected server response.' } };
        });
      })
      .then(function (result) {
        var data = result.data;
        var ok = !!(data && data.ok);
        var message = (data && data.message) ? String(data.message)
          : (ok ? 'Test email sent.' : 'Test email failed.');
        showStatus(ok, message);
      })
      .catch(function () {
        showStatus(false, 'Could not reach the server. Try again.');
      })
      .finally(function () {
        submitBtn.disabled = false;
        submitBtn.textContent = previousLabel;
      });
  });
})();
</script>
<?php require dirname(__DIR__) . '/includes/admin-footer.php';
