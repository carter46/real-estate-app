<?php
/**
 * Contact Us — form posts to inquiries + admin email notify.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Contact — ' . (string) app_config('app.name');
$navVariant = 'content';
$propertySlug = trim((string) ($_GET['property'] ?? ''));
$linkedProperty = null;
if ($propertySlug !== '') {
    try {
        $linkedProperty = property_find_public_by_slug($propertySlug);
    } catch (Throwable $e) {
        $linkedProperty = null;
    }
}

$errors = [];
$success = false;
$form = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'interest' => $linkedProperty ? 'Request Details' : 'General Inquiry',
    'message' => '',
];

$phone = setting_get('site_phone', '800.555.0123') ?? '800.555.0123';
$emailContact = setting_get('site_email', 'info@example.com') ?? 'info@example.com';
$offices = [];
try {
    $offices = offices_list_public();
} catch (Throwable $e) {
    $offices = [];
}

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $form = array_merge($form, [
            'first_name' => (string) ($_POST['first_name'] ?? ''),
            'last_name' => (string) ($_POST['last_name'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
            'phone' => (string) ($_POST['phone'] ?? ''),
            'interest' => (string) ($_POST['interest'] ?? ''),
            'message' => (string) ($_POST['message'] ?? ''),
        ]);
        $type = $linkedProperty ? 'property_inquiry' : 'contact';
        $payload = $form;
        $payload['property_id'] = $linkedProperty ? (int) $linkedProperty['id'] : null;
        $validated = inquiry_validate($payload, $type);
        $errors = $validated['errors'];
        if ($validated['ok']) {
            try {
                $id = inquiry_create($validated['data']);
                $inquiry = inquiry_find($id) ?? array_merge($validated['data'], ['id' => $id]);
                inquiry_notify_admin($inquiry);
                inquiry_ack_client($inquiry);
                $success = true;
                $form = [
                    'first_name' => '',
                    'last_name' => '',
                    'email' => '',
                    'phone' => '',
                    'interest' => 'General Inquiry',
                    'message' => '',
                ];
            } catch (Throwable $e) {
                error_log('[SDC] contact inquiry: ' . $e->getMessage());
                $errors[] = 'Could not send your message. Please try again shortly.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="listings-hero">
    <p class="eyebrow">Concierge</p>
    <h1 class="display" style="font-size:clamp(2rem,4vw,3rem);">Connect With Our Experts</h1>
    <p class="lead">Reach Sunview Development and Consultancy (SDC). Messages are stored securely and emailed to our team.</p>
</section>

<section class="section">
    <div class="container contact-layout">
        <div>
            <h2 class="headline">Direct Contact</h2>
            <p class="lead"><a href="tel:<?= e(preg_replace('/\D+/', '', $phone) ?: $phone) ?>"><?= e($phone) ?></a></p>
            <p class="lead"><a href="mailto:<?= e($emailContact) ?>"><?= e($emailContact) ?></a></p>

            <h2 class="headline" style="margin-top:2.5rem;">Our Offices</h2>
            <div class="cards-grid" style="grid-template-columns:1fr;">
                <?php if ($offices === []): ?>
                    <p class="lead">Office directory will appear when seeded.</p>
                <?php else: ?>
                    <?php foreach ($offices as $office): ?>
                        <article class="agent-tile">
                            <h3><?= e((string) $office['name']) ?></h3>
                            <p><?= e(trim(($office['city'] ?? '') . (($office['region'] ?? '') !== '' ? ' · ' . $office['region'] : ''))) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <h2 class="headline">Send a Message</h2>
            <?php if ($linkedProperty): ?>
                <p class="eyebrow">Regarding</p>
                <p class="lead"><a href="<?= e(base_url('property.php?slug=' . rawurlencode((string) $linkedProperty['slug']))) ?>"><?= e((string) $linkedProperty['title']) ?></a></p>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="form-success">
                    <h3 class="headline" style="font-size:1.5rem;">Inquiry Received</h3>
                    <p class="lead">Thank you. An SDC advisor will be in touch shortly.</p>
                </div>
            <?php else: ?>
                <?php foreach ($errors as $err): ?>
                    <div class="form-error"><?= e($err) ?></div>
                <?php endforeach; ?>
                <form class="contact-form" method="post" action="">
                    <?= csrf_field() ?>
                    <div class="form-row-2">
                        <div class="form-field">
                            <label for="first_name">First Name *</label>
                            <input id="first_name" name="first_name" type="text" required value="<?= e($form['first_name']) ?>">
                        </div>
                        <div class="form-field">
                            <label for="last_name">Last Name *</label>
                            <input id="last_name" name="last_name" type="text" required value="<?= e($form['last_name']) ?>">
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-field">
                            <label for="email">Email Address *</label>
                            <input id="email" name="email" type="email" required value="<?= e($form['email']) ?>">
                        </div>
                        <div class="form-field">
                            <label for="phone">Phone Number</label>
                            <input id="phone" name="phone" type="tel" value="<?= e($form['phone']) ?>">
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="interest">Area of Interest</label>
                        <select id="interest" name="interest">
                            <?php foreach (['Buying a Property', 'Selling a Property', 'Luxury Rentals', 'General Inquiry', 'Schedule Tour', 'Request Details'] as $opt): ?>
                                <option value="<?= e($opt) ?>" <?= $form['interest'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="6" required><?= e($form['message']) ?></textarea>
                    </div>
                    <button class="btn" type="submit">Send Message</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
