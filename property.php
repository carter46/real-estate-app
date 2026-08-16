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
    $activeNav = 'properties';
    require __DIR__ . '/includes/header.php';
    echo '<p class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop py-24 font-body-lg text-on-surface-variant">Property not found.</p>';
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
    $activeNav = 'properties';
    require __DIR__ . '/includes/header.php';
    echo '<p class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop py-24 font-body-lg text-on-surface-variant">This property is not available.</p>';
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
$locationEyebrow = trim(($property['city'] ?? '') . (($property['state'] ?? '') !== '' ? ', ' . $property['state'] : ''));
$pageTitle = (string) $property['title'] . ' — ' . site_name();
$activeNav = 'properties';

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
    'interest' => 'Buy Now',
    'message' => '',
];

if (is_post() && ($_POST['form'] ?? '') === 'property_inquiry') {
    if (!csrf_verify()) {
        $inquiryErrors[] = 'Invalid security token. Please try again.';
    } elseif (honeypot_tripped()) {
        $inquirySuccess = true;
    } else {
        $max = (int) app_config('security.inquiry_max_per_hour', 5);
        $limit = rate_limit_hit('inquiry', $max, 3600);
        if (!$limit['allowed']) {
            $inquiryErrors[] = 'Too many messages sent. Please try again later.';
        } else {
            $inquiryForm = [
                'first_name' => (string) ($_POST['first_name'] ?? ''),
                'last_name' => (string) ($_POST['last_name'] ?? ''),
                'email' => (string) ($_POST['email'] ?? ''),
                'phone' => (string) ($_POST['phone'] ?? ''),
                'interest' => (string) ($_POST['interest'] ?? 'Buy Now'),
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
                        'interest' => 'Buy Now',
                        'message' => '',
                    ];
                } catch (Throwable $e) {
                    error_log('[SDC] property inquiry: ' . $e->getMessage());
                    $inquiryErrors[] = 'Could not submit your inquiry. Please try again.';
                }
            }
        }
    }
}

$coverUrl = $cover !== '' ? media_url($cover) : '';
$contactHref = base_url('contact.php?property=' . rawurlencode((string) $property['slug']));

require __DIR__ . '/includes/header.php';
?>
<section class="relative w-full h-[80vh] min-h-[600px] flex items-end pb-16 pt-32 -mt-20 overflow-hidden">
  <div class="absolute inset-0 z-0 bg-black">
    <?php if ($coverUrl !== ''): ?>
      <img alt="" class="w-full h-full object-cover opacity-80" src="<?= e($coverUrl) ?>"/>
    <?php else: ?>
      <div class="w-full h-full img-placeholder opacity-80"></div>
    <?php endif; ?>
    <div class="absolute inset-0 bg-gradient-to-t from-primary/90 via-primary/40 to-transparent mix-blend-multiply"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
  </div>
  <div class="relative z-10 max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop w-full text-on-primary">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-8">
      <div class="max-w-3xl">
        <p class="font-subheading text-subheading uppercase tracking-[0.2em] mb-4 text-primary-fixed-dim/90"><?= e($locationEyebrow !== '' ? $locationEyebrow : 'Colorado') ?></p>
        <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg mb-6 leading-tight"><?= e((string) $property['title']) ?></h1>
        <div class="inline-block bg-primary/80 backdrop-blur-md px-6 py-2 border border-outline-variant/30">
          <span class="font-body-lg text-body-lg text-on-primary tracking-wide"><?= e($priceLabel) ?></span>
        </div>
      </div>
      <div class="flex gap-4 md:flex-col md:text-right">
        <a href="#inquiry" class="bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm px-8 py-4 hover:bg-primary-fixed-dim transition-colors uppercase tracking-widest inline-flex items-center justify-center gap-2 no-underline">
          Buy Now <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
        <a href="<?= e($contactHref) ?>" class="bg-transparent border border-on-primary/30 text-on-primary font-label-sm text-label-sm px-8 py-4 hover:bg-on-primary/10 transition-colors uppercase tracking-widest text-center no-underline">
          Request Details
        </a>
      </div>
    </div>
  </div>
</section>

<section class="w-full bg-surface-container-low border-b border-outline-variant/20 py-8">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop">
    <div class="flex flex-wrap items-center justify-between gap-8 md:gap-12">
      <div class="flex items-center gap-4">
        <span class="material-symbols-outlined text-primary text-2xl font-light">bed</span>
        <div>
          <p class="font-headline-md text-headline-md text-primary leading-none"><?= e($property['bedrooms'] !== null ? (string) $property['bedrooms'] : '—') ?></p>
          <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">BEDROOMS</p>
        </div>
      </div>
      <div class="hidden md:block w-px h-12 bg-outline-variant/40"></div>
      <div class="flex items-center gap-4">
        <span class="material-symbols-outlined text-primary text-2xl font-light">shower</span>
        <div>
          <p class="font-headline-md text-headline-md text-primary leading-none"><?= e($property['bathrooms'] !== null ? (string) $property['bathrooms'] : '—') ?></p>
          <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">BATHROOMS</p>
        </div>
      </div>
      <div class="hidden md:block w-px h-12 bg-outline-variant/40"></div>
      <div class="flex items-center gap-4">
        <span class="material-symbols-outlined text-primary text-2xl font-light">square_foot</span>
        <div>
          <p class="font-headline-md text-headline-md text-primary leading-none"><?= e($property['sqft'] !== null ? number_format((int) $property['sqft']) : '—') ?></p>
          <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">SQ FT</p>
        </div>
      </div>
      <div class="hidden md:block w-px h-12 bg-outline-variant/40"></div>
      <div class="flex items-center gap-4">
        <span class="material-symbols-outlined text-primary text-2xl font-light">landscape</span>
        <div>
          <p class="font-headline-md text-headline-md text-primary leading-none"><?= e($property['lot_acres'] !== null ? (string) $property['lot_acres'] : '—') ?></p>
          <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">ACRES</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="w-full py-section-gap">
  <div class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
      <div class="lg:col-span-8 flex flex-col gap-16">
        <div class="pr-0 md:pr-12">
          <p class="font-subheading text-subheading text-primary mb-6 flex items-center gap-4">
            <span class="w-12 h-px bg-primary"></span> THE VISION
          </p>
          <h2 class="font-display-lg text-display-lg-mobile text-on-surface mb-8"><?= e((string) $property['title']) ?></h2>
          <div class="font-body-lg text-body-lg text-on-surface-variant whitespace-pre-line"><?= e((string) ($property['description'] ?? '')) ?></div>
        </div>

        <div>
          <p class="font-subheading text-subheading text-primary mb-8 flex items-center gap-4">
            <span class="w-12 h-px bg-primary"></span> GALLERY
          </p>
          <?php if ($images === []): ?>
            <p class="font-body-md text-on-surface-variant">No gallery images yet.</p>
          <?php else: ?>
            <?php require __DIR__ . '/includes/property-gallery.php'; ?>
          <?php endif; ?>
        </div>

        <div>
          <p class="font-subheading text-subheading text-primary mb-8 flex items-center gap-4">
            <span class="w-12 h-px bg-primary"></span> AMENITIES &amp; FEATURES
          </p>
          <?php if ($byCat === []): ?>
            <p class="font-body-md text-on-surface-variant">Amenities will appear here when assigned in admin.</p>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-10">
              <?php foreach ($byCat as $cat => $items): ?>
                <div>
                  <h4 class="font-display-lg text-2xl text-on-surface mb-4"><?= e((string) $cat) ?></h4>
                  <ul class="flex flex-col gap-3">
                    <?php foreach ($items as $item): ?>
                      <li class="flex items-start gap-3 py-2 border-b border-outline-variant/30">
                        <span class="material-symbols-outlined text-primary text-[18px] mt-1">done</span>
                        <span class="font-body-md text-body-md text-on-surface-variant"><?= e((string) $item['name']) ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="lg:col-span-4 relative">
        <div class="flex flex-col gap-8">
          <div class="bg-surface-container p-8 shadow-sm border border-outline-variant/20 flex flex-col items-center text-center">
            <?php if (!empty($property['agent_photo'])): ?>
              <div class="w-32 h-32 rounded-full overflow-hidden mb-6 border-4 border-surface">
                <img class="w-full h-full object-cover" src="<?= e(media_url((string) $property['agent_photo'])) ?>" alt="">
              </div>
            <?php else: ?>
              <div class="w-32 h-32 rounded-full mb-6 border-4 border-surface img-placeholder" aria-hidden="true"></div>
            <?php endif; ?>
            <p class="font-label-sm text-label-sm text-primary tracking-widest uppercase mb-2"><?= e((string) ($property['agent_badge'] ?? 'Listing Agent')) ?></p>
            <h3 class="font-headline-md text-headline-md text-on-surface mb-1"><?= e((string) ($property['agent_name'] ?? 'SDC Advisors')) ?></h3>
            <p class="font-label-sm text-label-sm text-on-surface-variant tracking-widest uppercase mb-6">
              <?= e(trim(($property['agent_title'] ?? '') . (($property['agent_region'] ?? '') !== '' ? ', ' . $property['agent_region'] : ''))) ?>
            </p>
            <div class="w-12 h-px bg-outline-variant/50 mb-6"></div>
            <?php if (!empty($property['agent_quote'])): ?>
              <p class="font-body-md text-body-md text-on-surface-variant mb-8 italic">“<?= e((string) $property['agent_quote']) ?>”</p>
            <?php endif; ?>
            <div class="w-full flex flex-col gap-4">
              <a href="#inquiry" class="w-full bg-primary text-on-primary font-label-sm text-label-sm py-4 hover:bg-primary-container transition-colors uppercase tracking-widest inline-flex items-center justify-center gap-2 no-underline">
                <span class="material-symbols-outlined text-[18px]">shopping_bag</span> Buy Now
              </a>
              <a href="#inquiry" class="w-full bg-transparent border border-primary text-primary font-label-sm text-label-sm py-4 hover:bg-primary/5 transition-colors uppercase tracking-widest inline-flex items-center justify-center gap-2 no-underline">
                <span class="material-symbols-outlined text-[18px]">mail</span> Request Details
              </a>
            </div>
            <p class="mt-6 font-label-sm text-label-sm text-on-surface-variant">Ref <?= e((string) $property['reference_code']) ?><?= !empty($property['mls_number']) ? ' · MLS# ' . e((string) $property['mls_number']) : '' ?></p>
          </div>

          <div class="bg-surface-container-low border border-outline-variant/20 overflow-hidden h-64 relative img-placeholder">
            <div class="absolute bottom-4 left-4 bg-surface px-4 py-2 shadow-sm border border-outline-variant/20 flex items-center gap-2">
              <span class="material-symbols-outlined text-primary text-[16px]">location_on</span>
              <span class="font-label-sm text-label-sm text-on-surface uppercase tracking-wider"><?= e((string) ($property['city'] ?? 'Map')) ?></span>
            </div>
          </div>

          <div id="inquiry" class="bg-surface-container-lowest border border-outline-variant/30 p-6">
            <p class="font-subheading text-subheading text-primary mb-3 uppercase tracking-widest">Inquire</p>
            <h3 class="font-headline-md text-[24px] text-on-surface mb-4">Request a private viewing</h3>
            <?php if ($inquirySuccess): ?>
              <p class="font-body-md text-on-surface-variant">Inquiry received. An SDC advisor will follow up shortly.</p>
            <?php else: ?>
              <?php foreach ($inquiryErrors as $err): ?>
                <p class="font-body-md text-error mb-2"><?= e($err) ?></p>
              <?php endforeach; ?>
              <form method="post" action="#inquiry" class="flex flex-col gap-5">
                <?= csrf_field() ?>
                <?= honeypot_field() ?>
                <input type="hidden" name="form" value="property_inquiry">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="relative pt-5">
                    <label class="absolute top-0 left-0 font-label-sm text-label-sm text-on-surface-variant uppercase" for="inq_first">First Name *</label>
                    <input id="inq_first" name="first_name" required value="<?= e($inquiryForm['first_name']) ?>" class="w-full bg-transparent border-0 border-b border-outline-variant/50 focus:border-primary py-2 font-body-md outline-none">
                  </div>
                  <div class="relative pt-5">
                    <label class="absolute top-0 left-0 font-label-sm text-label-sm text-on-surface-variant uppercase" for="inq_last">Last Name *</label>
                    <input id="inq_last" name="last_name" required value="<?= e($inquiryForm['last_name']) ?>" class="w-full bg-transparent border-0 border-b border-outline-variant/50 focus:border-primary py-2 font-body-md outline-none">
                  </div>
                </div>
                <div class="relative pt-5">
                  <label class="absolute top-0 left-0 font-label-sm text-label-sm text-on-surface-variant uppercase" for="inq_email">Email *</label>
                  <input id="inq_email" name="email" type="email" required value="<?= e($inquiryForm['email']) ?>" class="w-full bg-transparent border-0 border-b border-outline-variant/50 focus:border-primary py-2 font-body-md outline-none">
                </div>
                <div class="relative pt-5">
                  <label class="absolute top-0 left-0 font-label-sm text-label-sm text-on-surface-variant uppercase" for="inq_phone">Phone</label>
                  <input id="inq_phone" name="phone" type="tel" value="<?= e($inquiryForm['phone']) ?>" class="w-full bg-transparent border-0 border-b border-outline-variant/50 focus:border-primary py-2 font-body-md outline-none">
                </div>
                <div class="relative pt-5">
                  <label class="absolute top-0 left-0 font-label-sm text-label-sm text-on-surface-variant uppercase" for="inq_interest">Interest</label>
                  <select id="inq_interest" name="interest" class="w-full bg-transparent border-0 border-b border-outline-variant/50 focus:border-primary py-2 font-body-md outline-none">
                    <?php foreach (['Buy Now', 'Request Details', 'General Inquiry'] as $opt): ?>
                      <option value="<?= e($opt) ?>" <?= $inquiryForm['interest'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="relative pt-5">
                  <label class="absolute top-0 left-0 font-label-sm text-label-sm text-on-surface-variant uppercase" for="inq_message">Message *</label>
                  <textarea id="inq_message" name="message" rows="4" required class="w-full bg-transparent border-0 border-b border-outline-variant/50 focus:border-primary py-2 font-body-md outline-none resize-none"><?= e($inquiryForm['message']) ?></textarea>
                </div>
                <button type="submit" class="bg-primary text-on-primary font-label-sm text-label-sm uppercase tracking-widest px-8 py-3 hover:bg-primary-container transition-colors">Send Inquiry</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
require __DIR__ . '/includes/footer.php';
