<?php
/**
 * Contact Us — form posts to inquiries + admin email notify.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Contact — ' . site_name();
$activeNav = 'contact';
$propertySlug = trim((string) ($_GET['property'] ?? ''));
$linkedProperty = null;
if ($propertySlug !== '') {
    try {
        $linkedProperty = property_find_public_by_slug($propertySlug);
    } catch (Throwable $e) {
        $linkedProperty = null;
        app_log('contact', 'linked property lookup: ' . $e->getMessage());
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

$phone = site_phone();
$emailContact = site_email();
$offices = [];
try {
    $offices = offices_list_public();
} catch (Throwable $e) {
    $offices = [];
    app_log('contact', 'offices list: ' . $e->getMessage());
}

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Invalid security token. Please try again.';
    } elseif (honeypot_tripped()) {
        // Silent success for bots
        $success = true;
    } else {
        $max = (int) app_config('security.inquiry_max_per_hour', 5);
        $limit = rate_limit_hit('inquiry', $max, 3600);
        if (!$limit['allowed']) {
            $errors[] = 'Too many messages sent. Please try again later.';
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
}

$fieldClass = 'w-full bg-transparent border-0 border-b border-outline-variant/50 focus:border-primary py-2 font-body-md text-body-md text-on-surface outline-none transition-colors';
$labelClass = 'absolute -top-5 left-0 font-label-sm text-label-sm text-on-surface-variant group-focus-within:text-primary transition-colors uppercase';

require __DIR__ . '/includes/header.php';
?>
<section class="relative w-full min-h-[42vh] flex items-center justify-center overflow-hidden hero-photo -mt-20 pt-20" style="background-image: linear-gradient(180deg, rgba(28,27,27,0.25), rgba(28,27,27,0.7)), url('<?= e(base_url('assets/img/contact-hero.jpg')) ?>');">
  <div class="relative z-10 text-center px-margin-mobile py-20">
    <span class="font-label-sm text-label-sm text-primary-fixed uppercase tracking-widest mb-6 opacity-90 block">Reach Out</span>
    <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-primary mb-6 drop-shadow-md">Connect With Our Experts</h1>
    <p class="font-body-lg text-body-lg text-inverse-on-surface max-w-2xl mx-auto opacity-90 font-light">
      Reach <?= e(site_name()) ?>. Messages are stored securely and emailed to our team.
    </p>
  </div>
</section>

<section class="max-w-[1440px] mx-auto px-margin-mobile lg:px-margin-desktop py-section-gap">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-16">
    <div class="lg:col-span-5 flex flex-col gap-12">
      <div>
        <h2 class="font-headline-md text-headline-md text-on-surface mb-8">Direct Contact</h2>
        <div class="flex flex-col gap-6">
          <div>
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1 block">Phone</span>
            <a class="font-body-lg text-body-lg text-on-surface hover:text-primary transition-colors no-underline" href="tel:<?= e(preg_replace('/\D+/', '', $phone) ?: $phone) ?>"><?= e($phone) ?></a>
          </div>
          <div>
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1 block">Email</span>
            <a class="font-body-lg text-body-lg text-on-surface hover:text-primary transition-colors break-all no-underline" href="mailto:<?= e($emailContact) ?>"><?= e($emailContact) ?></a>
          </div>
        </div>
      </div>

      <div>
        <h2 class="font-headline-md text-headline-md text-on-surface mb-8">Our Offices</h2>
        <div class="flex flex-col gap-6">
          <?php if ($offices === []): ?>
            <p class="font-body-md text-on-surface-variant">Office directory will appear when seeded.</p>
          <?php else: ?>
            <?php foreach ($offices as $office): ?>
              <div class="group border-b border-outline-variant/30 pb-4">
                <h3 class="font-subheading text-subheading text-on-surface group-hover:text-primary transition-colors mb-2"><?= e((string) $office['name']) ?></h3>
                <p class="font-body-md text-body-md text-on-surface-variant">
                  <?php if (!empty($office['address_line'])): ?>
                    <?= e((string) $office['address_line']) ?><br/>
                  <?php endif; ?>
                  <?= e(trim(($office['city'] ?? '') . (($office['region'] ?? '') !== '' ? ' · ' . $office['region'] : ''))) ?>
                </p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="lg:col-span-7">
      <h2 class="font-headline-md text-headline-md text-on-surface mb-2">Send a Message</h2>
      <p class="font-body-md text-body-md text-on-surface-variant mb-10">Please fill out the form below and a member of our team will contact you shortly.</p>

      <?php if ($linkedProperty): ?>
        <p class="font-label-sm text-label-sm uppercase tracking-widest text-primary mb-2">Regarding</p>
        <p class="font-body-md mb-8">
          <a class="text-primary hover:underline" href="<?= e(base_url('property.php?slug=' . rawurlencode((string) $linkedProperty['slug']))) ?>"><?= e((string) $linkedProperty['title']) ?></a>
        </p>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="flex flex-col items-center justify-center text-center gap-4 py-16 border border-outline-variant/30">
          <span class="material-symbols-outlined text-primary text-6xl">check_circle</span>
          <h3 class="font-headline-md text-headline-md text-on-surface">Inquiry Received</h3>
          <p class="font-body-md text-body-md text-on-surface-variant">Thank you for reaching out. An SDC advisor will be in touch shortly.</p>
        </div>
      <?php else: ?>
        <?php foreach ($errors as $err): ?>
          <p class="font-body-md text-error mb-3"><?= e($err) ?></p>
        <?php endforeach; ?>
        <form class="flex flex-col gap-10" method="post" action="">
          <?= csrf_field() ?>
          <?= honeypot_field() ?>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div class="relative group">
              <label class="<?= e($labelClass) ?>" for="first_name">First Name *</label>
              <input class="<?= e($fieldClass) ?>" id="first_name" name="first_name" required type="text" value="<?= e($form['first_name']) ?>"/>
            </div>
            <div class="relative group">
              <label class="<?= e($labelClass) ?>" for="last_name">Last Name *</label>
              <input class="<?= e($fieldClass) ?>" id="last_name" name="last_name" required type="text" value="<?= e($form['last_name']) ?>"/>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div class="relative group">
              <label class="<?= e($labelClass) ?>" for="email">Email Address *</label>
              <input class="<?= e($fieldClass) ?>" id="email" name="email" required type="email" value="<?= e($form['email']) ?>"/>
            </div>
            <div class="relative group">
              <label class="<?= e($labelClass) ?>" for="phone">Phone Number</label>
              <input class="<?= e($fieldClass) ?>" id="phone" name="phone" type="tel" value="<?= e($form['phone']) ?>"/>
            </div>
          </div>
          <div class="relative group">
            <label class="<?= e($labelClass) ?>" for="interest">Area of Interest</label>
            <select class="<?= e($fieldClass) ?> appearance-none cursor-pointer" id="interest" name="interest">
              <?php foreach (['Buying a Property', 'Selling a Property', 'Luxury Rentals', 'General Inquiry', 'Schedule Tour', 'Request Details'] as $opt): ?>
                <option value="<?= e($opt) ?>" <?= $form['interest'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="relative group">
            <label class="<?= e($labelClass) ?>" for="message">Message *</label>
            <textarea class="<?= e($fieldClass) ?> resize-none min-h-[120px]" id="message" name="message" rows="4" required><?= e($form['message']) ?></textarea>
          </div>
          <button class="bg-primary hover:bg-primary-container text-on-primary font-label-sm text-label-sm uppercase px-10 py-4 shadow-md hover:shadow-xl transition-all duration-300 inline-flex items-center gap-3 w-full md:w-auto justify-center" type="submit">
            Send Message <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php';
