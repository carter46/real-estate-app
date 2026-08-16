<?php
/**
 * Admin inquiries — list + detail, status updates, draft notes / reply email.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
auth_require();

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$selectedId = (int) ($_GET['id'] ?? 0);
$flashOk = null;
$flashErr = null;

if (is_post()) {
    if (!csrf_verify()) {
        flash_set('inquiry_error', 'Invalid security token.');
        redirect('admin/inquiries.php' . ($selectedId ? ('?id=' . $selectedId) : ''));
    }

    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $inquiry = $id > 0 ? inquiry_find($id) : null;
    if (!$inquiry) {
        flash_set('inquiry_error', 'Inquiry not found.');
        redirect('admin/inquiries.php');
    }

    if ($action === 'status') {
        $status = (string) ($_POST['status'] ?? '');
        inquiry_update_status($id, $status);
        flash_set('inquiry_ok', 'Status updated.');
        redirect('admin/inquiries.php?id=' . $id);
    }

    if ($action === 'notes') {
        inquiry_save_notes($id, trim((string) ($_POST['admin_notes'] ?? '')));
        flash_set('inquiry_ok', 'Notes saved.');
        redirect('admin/inquiries.php?id=' . $id);
    }

    if ($action === 'reply') {
        $reply = trim((string) ($_POST['reply_body'] ?? ''));
        if ($reply === '') {
            flash_set('inquiry_error', 'Reply message is required.');
            redirect('admin/inquiries.php?id=' . $id);
        }
        $to = (string) $inquiry['email'];
        $brand = site_name();
        $subject = 'Re: your inquiry — ' . $brand;
        $html = '<p>' . nl2br(e($reply)) . '</p><p>— ' . e($brand) . '</p>';
        $result = send_mail($to, $subject, $html, $reply . "\n\n— " . $brand);
        $notes = trim((string) ($inquiry['admin_notes'] ?? ''));
        $notes .= ($notes !== '' ? "\n\n" : '') . '[' . date('Y-m-d H:i') . "] Reply sent:\n" . $reply;
        inquiry_save_notes($id, $notes);
        if (($inquiry['status'] ?? '') === 'new') {
            inquiry_update_status($id, 'in_progress');
        }
        if ($result['ok']) {
            flash_set('inquiry_ok', 'Reply emailed to client.');
        } else {
            flash_set('inquiry_error', 'Notes saved, but email failed. Check mail configuration and logs.');
        }
        redirect('admin/inquiries.php?id=' . $id);
    }
}

$flashOk = flash_get('inquiry_ok');
$flashErr = flash_get('inquiry_error');
$rows = inquiry_list($statusFilter, 'newest', 100);
$selected = $selectedId > 0 ? inquiry_find($selectedId) : ($rows[0] ?? null);
if ($selected) {
    $selectedId = (int) $selected['id'];
}

$total = (int) db()->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
$actionRequired = (int) db()->query("SELECT COUNT(*) FROM inquiries WHERE status IN ('new','in_progress')")->fetchColumn();

$adminPageTitle = 'Inquiries';
$adminActiveNav = 'inquiries';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<span class="admin-eyebrow">Client Inquiries</span>
<h1 class="admin-page-title">Inquiries</h1>
<p class="admin-page-lead"><?= e((string) $actionRequired) ?> action required · <?= e((string) $total) ?> total</p>

<?php if ($flashOk): ?><div class="admin-alert admin-alert--ok"><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="admin-alert admin-alert--error"><?= e($flashErr) ?></div><?php endif; ?>

<form method="get" class="admin-actions" style="align-items:end;">
    <div class="admin-field" style="margin:0;min-width:10rem;">
        <label for="status">Filter</label>
        <select id="status" name="status" onchange="this.form.submit()">
            <option value="">All</option>
            <?php foreach (inquiry_statuses() as $st): ?>
                <option value="<?= e($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $st))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="inquiry-admin-layout">
    <section class="admin-panel" style="padding:0;">
        <table class="admin-table">
            <thead>
                <tr><th>Status</th><th>Client</th><th>Ref</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="4" style="padding:1rem;" class="admin-note">No inquiries yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php $active = (int) $row['id'] === $selectedId; ?>
                    <tr class="<?= $active ? 'is-selected' : '' ?>">
                        <td><span class="admin-badge"><?= e(str_replace('_', ' ', (string) $row['status'])) ?></span></td>
                        <td>
                            <a href="<?= e(base_url('admin/inquiries.php?id=' . (int) $row['id'] . ($statusFilter !== '' ? '&status=' . rawurlencode($statusFilter) : ''))) ?>">
                                <?= e(trim($row['first_name'] . ' ' . $row['last_name'])) ?>
                            </a>
                        </td>
                        <td class="admin-muted"><?= e((string) ($row['property_title'] ?? $row['interest'] ?? $row['type'])) ?></td>
                        <td class="admin-muted"><?= e((string) $row['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="admin-panel">
        <?php if (!$selected): ?>
            <p class="admin-note">Select an inquiry to view details.</p>
        <?php else: ?>
            <h2><?= e(trim($selected['first_name'] . ' ' . $selected['last_name'])) ?></h2>
            <p class="admin-muted">
                <a href="mailto:<?= e((string) $selected['email']) ?>"><?= e((string) $selected['email']) ?></a>
                <?php if (!empty($selected['phone'])): ?> · <a href="tel:<?= e((string) $selected['phone']) ?>"><?= e((string) $selected['phone']) ?></a><?php endif; ?>
            </p>
            <p><span class="admin-badge"><?= e((string) $selected['type']) ?></span>
               <?php if (!empty($selected['interest'])): ?> · <?= e((string) $selected['interest']) ?><?php endif; ?>
            </p>

            <?php if (!empty($selected['property_id'])): ?>
                <div class="admin-panel" style="margin-top:1rem;">
                    <span class="admin-eyebrow">Referenced Property</span>
                    <?php if (!empty($selected['property_cover'])): ?>
                        <img src="<?= e(media_url((string) $selected['property_cover'])) ?>" alt="" style="width:100%;max-height:10rem;object-fit:cover;margin:0.75rem 0;">
                    <?php endif; ?>
                    <p><strong><?= e((string) ($selected['property_title'] ?? '')) ?></strong></p>
                    <p class="admin-muted"><?= e((string) ($selected['property_address'] ?? '')) ?> <?= e((string) ($selected['property_city'] ?? '')) ?></p>
                    <?php if (!empty($selected['property_slug'])): ?>
                        <p><a href="<?= e(base_url('property.php?slug=' . rawurlencode((string) $selected['property_slug']))) ?>" target="_blank" rel="noopener">View on site</a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <h3 class="admin-eyebrow" style="margin-top:1.25rem;">Client Message</h3>
            <p style="white-space:pre-line;"><?= e((string) $selected['message']) ?></p>

            <form method="post" style="margin-top:1.25rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $selected['id'] ?>">
                <input type="hidden" name="action" value="status">
                <div class="admin-field">
                    <label for="inq_status">Status</label>
                    <select id="inq_status" name="status">
                        <?php foreach (inquiry_statuses() as $st): ?>
                            <option value="<?= e($st) ?>" <?= $selected['status'] === $st ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $st))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="admin-btn admin-btn--ghost" type="submit">Update Status</button>
            </form>

            <form method="post" style="margin-top:1.25rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $selected['id'] ?>">
                <input type="hidden" name="action" value="notes">
                <div class="admin-field">
                    <label for="admin_notes">Admin Notes</label>
                    <textarea id="admin_notes" name="admin_notes" rows="4"><?= e((string) ($selected['admin_notes'] ?? '')) ?></textarea>
                </div>
                <button class="admin-btn admin-btn--ghost" type="submit">Save Notes</button>
            </form>

            <form method="post" style="margin-top:1.25rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $selected['id'] ?>">
                <input type="hidden" name="action" value="reply">
                <div class="admin-field">
                    <label for="reply_body">Draft Response</label>
                    <textarea id="reply_body" name="reply_body" rows="5" placeholder="Write a reply to email the client…"></textarea>
                </div>
                <button class="admin-btn" type="submit">Send Reply</button>
            </form>
        <?php endif; ?>
    </section>
</div>
<?php
require dirname(__DIR__) . '/includes/admin-footer.php';
