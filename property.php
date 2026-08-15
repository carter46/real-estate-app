<?php
/**
 * Dynamic property detail — Glass House section template for any public property.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(404);
    $pageTitle = 'Property not found';
    $navVariant = 'home';
    require __DIR__ . '/includes/header.php';
    echo '<p class="empty">Property not found.</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$property = null;
$images = [];
$amenities = [];
try {
    $property = property_find_public_by_slug($slug);
    if ($property) {
        $images = property_images((int) $property['id']);
        $amenities = property_amenities_for((int) $property['id']);
    }
} catch (Throwable $e) {
    error_log('[SDC] property detail: ' . $e->getMessage());
}

if (!$property) {
    http_response_code(404);
    $pageTitle = 'Property not found';
    $navVariant = 'home';
    require __DIR__ . '/includes/header.php';
    echo '<p class="empty">This property is not available.</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$cover = '';
foreach ($images as $img) {
    if (!empty($img['is_cover'])) {
        $cover = (string) $img['path'];
        break;
    }
}
if ($cover === '' && $images !== []) {
    $cover = (string) ($images[0]['path'] ?? '');
}

$priceLabel = format_price(
    isset($property['price']) ? (float) $property['price'] : null,
    !empty($property['price_on_request']),
    (string) ($property['currency'] ?? 'USD')
);
$locationEyebrow = trim(($property['city'] ?? '') . ', ' . ($property['state'] ?? ''));
$pageTitle = (string) $property['title'] . ' — ' . (string) app_config('app.name');
$navVariant = 'home';

$byCat = [];
foreach ($amenities as $am) {
    $byCat[$am['category']][] = $am;
}

$inquiryErrors = [];
$inquirySuccess = false;
$inquiryForm = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'interest' => 'Schedule Tour',
    'message' => '',
];

if (is_post() && ($_POST['form'] ?? '') === 'property_inquiry') {
    if (!csrf_verify()) {
        $inquiryErrors[] = 'Invalid security token. Please try again.';
    } else {
        $inquiryForm = [
            'first_name' => (string) ($_POST['first_name'] ?? ''),
            'last_name' => (string) ($_POST['last_name'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
            'phone' => (string) ($_POST['phone'] ?? ''),
            'interest' => (string) ($_POST['interest'] ?? 'Schedule Tour'),
            'message' => (string) ($_POST['message'] ?? ''),
            'property_id' => (int) $property['id'],
        ];
        $validated = inquiry_validate($inquiryForm, 'property_inquiry');
        $inquiryErrors = $validated['errors'];
        if ($validated['ok']) {
            try {
                $id = inquiry_create($validated['data']);
                $inquiry = inquiry_find($id) ?? array_merge($validated['data'], ['id' => $id]);
                inquiry_notify_admin($inquiry);
                inquiry_ack_client($inquiry);
                $inquirySuccess = true;
                $inquiryForm = [
                    'first_name' => '',
                    'last_name' => '',
                    'email' => '',
                    'phone' => '',
                    'interest' => 'Schedule Tour',
                    'message' => '',
                ];
            } catch (Throwable $e) {
                error_log('[SDC] property inquiry: ' . $e->getMessage());
                $inquiryErrors[] = 'Could not submit your inquiry. Please try again.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="detail-hero" style="<?= $cover !== '' ? 'background-image:url(' . e(media_url($cover)) . ');' : '' ?>">
    <div class="detail-hero__scrim"></div>
    <div class="detail-hero__inner">
        <p class="eyebrow" style="color:#ffb1c5;"><?= e($locationEyebrow) ?></p>
        <h1 class="display" style="font-size:clamp(2rem,4vw,3.5rem);color:#fff;"><?= e((string) $property['title']) ?></h1>
        <p class="headline" style="color:#fff;"><?= e($priceLabel) ?></p>
        <div class="hero__actions">
            <a class="btn btn--light" href="<?= e(base_url('contact.php?property=' . rawurlencode((string) $property['slug']))) ?>">Schedule Tour</a>
            <a class="btn btn--ghost" style="color:#fff;border-color:rgba(255,255,255,.5);" href="<?= e(base_url('contact.php?property=' . rawurlencode((string) $property['slug']))) ?>">Request Details</a>
        </div>
    </div>
</section>

<div class="spec-bar" aria-label="Property specifications">
    <div><strong><?= e($property['bedrooms'] !== null ? (string) $property['bedrooms'] : '—') ?></strong><span>Beds</span></div>
    <div><strong><?= e($property['bathrooms'] !== null ? (string) $property['bathrooms'] : '—') ?></strong><span>Baths</span></div>
    <div><strong><?= e($property['sqft'] !== null ? number_format((int) $property['sqft']) : '—') ?></strong><span>Sq Ft</span></div>
    <div><strong><?= e($property['lot_acres'] !== null ? (string) $property['lot_acres'] : '—') ?></strong><span>Acres</span></div>
</div>

<div class="detail-layout">
    <div>
        <section>
            <p class="eyebrow">The Vision</p>
            <h2 class="headline"><?= e((string) $property['title']) ?></h2>
            <div class="lead" style="max-width:none;white-space:pre-line;"><?= e((string) ($property['description'] ?? '')) ?></div>
        </section>

        <section style="margin-top:3rem;">
            <p class="eyebrow">Gallery</p>
            <h2 class="headline">Spaces</h2>
            <?php if ($images === []): ?>
                <p class="admin-note">No gallery images yet.</p>
            <?php else: ?>
                <?php $images = $images; require __DIR__ . '/includes/property-gallery.php'; ?>
            <?php endif; ?>
        </section>

        <section style="margin-top:3rem;">
            <p class="eyebrow">Amenities &amp; Features</p>
            <h2 class="headline">Details</h2>
            <?php if ($byCat === []): ?>
                <p class="lead">Amenities will appear here when assigned in admin.</p>
            <?php else: ?>
                <div class="amenities-cols">
                    <?php foreach ($byCat as $cat => $items): ?>
                        <div>
                            <h3 class="eyebrow"><?= e((string) $cat) ?></h3>
                            <ul>
                                <?php foreach ($items as $item): ?>
                                    <li><?= e((string) $item['name']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section style="margin-top:3rem;">
            <p class="eyebrow">Location</p>
            <h2 class="headline">Map</h2>
            <div class="editorial__media" style="min-height:16rem;">
                <div class="editorial__stat">
                    <strong><?= e((string) ($property['city'] ?? '')) ?></strong>
                    <span><?= e(trim(($property['address_line'] ?? '') . ' ' . ($property['postal_code'] ?? ''))) ?></span>
                </div>
            </div>
            <p class="lead" style="margin-top:1rem;">Interactive map arrives in a later phase; layout slot preserved from the reference detail page.</p>
        </section>
    </div>

    <aside class="agent-card">
        <?php if (!empty($property['agent_photo'])): ?>
            <img class="agent-card__photo" src="<?= e(media_url((string) $property['agent_photo'])) ?>" alt="">
        <?php else: ?>
            <div class="agent-card__photo" aria-hidden="true"></div>
        <?php endif; ?>
        <p class="eyebrow"><?= e((string) ($property['agent_badge'] ?? 'Listing Agent')) ?></p>
        <h2 class="headline" style="font-size:1.5rem;"><?= e((string) ($property['agent_name'] ?? 'SDC Advisors')) ?></h2>
        <p class="lead" style="font-size:0.95rem;">
            <?= e(trim(($property['agent_title'] ?? '') . (($property['agent_region'] ?? '') !== '' ? ', ' . $property['agent_region'] : ''))) ?>
        </p>
        <?php if (!empty($property['agent_quote'])): ?>
            <blockquote class="lead" style="font-size:1rem;border-left:2px solid var(--primary);padding-left:1rem;margin:1rem 0;">
                <?= e((string) $property['agent_quote']) ?>
            </blockquote>
        <?php endif; ?>
        <div class="hero__actions" style="margin-top:1.25rem;">
            <a class="btn" href="#inquiry">Schedule a Tour</a>
            <a class="btn btn--ghost" href="#inquiry">Request Details</a>
        </div>
        <p class="lead" style="margin-top:1rem;font-size:0.8rem;">Ref <?= e((string) $property['reference_code']) ?><?= !empty($property['mls_number']) ? ' · MLS# ' . e((string) $property['mls_number']) : '' ?></p>

        <section id="inquiry" style="margin-top:2rem;">
            <p class="eyebrow">Inquire</p>
            <h3 class="headline" style="font-size:1.35rem;">Request a private viewing</h3>
            <?php if ($inquirySuccess): ?>
                <div class="form-success"><p class="lead" style="margin:0;">Inquiry received. An SDC advisor will follow up shortly.</p></div>
            <?php else: ?>
                <?php foreach ($inquiryErrors as $err): ?>
                    <div class="form-error"><?= e($err) ?></div>
                <?php endforeach; ?>
                <form class="inquiry-form" method="post" action="#inquiry">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form" value="property_inquiry">
                    <div class="form-row-2">
                        <div class="form-field">
                            <label for="inq_first">First Name *</label>
                            <input id="inq_first" name="first_name" required value="<?= e($inquiryForm['first_name']) ?>">
                        </div>
                        <div class="form-field">
                            <label for="inq_last">Last Name *</label>
                            <input id="inq_last" name="last_name" required value="<?= e($inquiryForm['last_name']) ?>">
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="inq_email">Email *</label>
                        <input id="inq_email" name="email" type="email" required value="<?= e($inquiryForm['email']) ?>">
                    </div>
                    <div class="form-field">
                        <label for="inq_phone">Phone</label>
                        <input id="inq_phone" name="phone" type="tel" value="<?= e($inquiryForm['phone']) ?>">
                    </div>
                    <div class="form-field">
                        <label for="inq_interest">Interest</label>
                        <select id="inq_interest" name="interest">
                            <?php foreach (['Schedule Tour', 'Request Details', 'General Inquiry'] as $opt): ?>
                                <option value="<?= e($opt) ?>" <?= $inquiryForm['interest'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="inq_message">Message *</label>
                        <textarea id="inq_message" name="message" rows="4" required><?= e($inquiryForm['message']) ?></textarea>
                    </div>
                    <button class="btn" type="submit">Send Inquiry</button>
                </form>
            <?php endif; ?>
        </section>
    </aside>
</div>
<?php
require __DIR__ . '/includes/footer.php';
