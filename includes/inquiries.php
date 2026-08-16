<?php
/**
 * Inquiry helpers — Phase 5.
 */

declare(strict_types=1);

/**
 * @return list<string>
 */
function inquiry_statuses(): array
{
    return ['new', 'in_progress', 'resolved'];
}

/**
 * @param array<string, mixed> $input
 * @return array{ok:bool, errors:list<string>, data:array<string,mixed>}
 */
function inquiry_validate(array $input, string $type = 'contact'): array
{
    $errors = [];
    $first = trim((string) ($input['first_name'] ?? ''));
    $last = trim((string) ($input['last_name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $phone = trim((string) ($input['phone'] ?? ''));
    $interest = trim((string) ($input['interest'] ?? ''));
    $message = trim((string) ($input['message'] ?? ''));
    $propertyId = isset($input['property_id']) ? (int) $input['property_id'] : null;
    if ($propertyId !== null && $propertyId <= 0) {
        $propertyId = null;
    }

    if (!in_array($type, ['contact', 'property_inquiry'], true)) {
        $type = 'contact';
    }
    if ($first === '') {
        $errors[] = 'First name is required.';
    } elseif (strlen($first) > 80) {
        $errors[] = 'First name must be 80 characters or fewer.';
    }
    if ($last === '') {
        $errors[] = 'Last name is required.';
    } elseif (strlen($last) > 80) {
        $errors[] = 'Last name must be 80 characters or fewer.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    } elseif (strlen($email) > 191) {
        $errors[] = 'Email is too long.';
    }
    if ($phone !== '' && strlen($phone) > 40) {
        $errors[] = 'Phone must be 40 characters or fewer.';
    }
    if ($message === '') {
        $errors[] = 'Message is required.';
    } elseif (strlen($message) > 5000) {
        $errors[] = 'Message must be 5000 characters or fewer.';
    }
    if ($type === 'property_inquiry' && !$propertyId) {
        $errors[] = 'Property is required for this inquiry.';
    }

    $allowedInterest = [
        '',
        'Buying a Property',
        'Selling a Property',
        'Luxury Rentals',
        'General Inquiry',
        'Schedule Tour',
        'Buy Now',
        'Request Details',
    ];
    if (!in_array($interest, $allowedInterest, true)) {
        $interest = 'General Inquiry';
    }

    return [
        'ok' => $errors === [],
        'errors' => $errors,
        'data' => [
            'type' => $type,
            'property_id' => $propertyId,
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'interest' => $interest !== '' ? $interest : null,
            'message' => $message,
        ],
    ];
}

/**
 * @param array<string, mixed> $data
 */
function inquiry_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO inquiries (type, status, property_id, first_name, last_name, email, phone, interest, message)
         VALUES (?, \'new\', ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['type'],
        $data['property_id'],
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['phone'],
        $data['interest'],
        $data['message'],
    ]);
    return (int) db()->lastInsertId();
}

function inquiry_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT i.*,
                p.title AS property_title, p.slug AS property_slug, p.price AS property_price,
                p.price_on_request, p.currency, p.city AS property_city, p.address_line AS property_address,
                (SELECT path FROM property_images pi WHERE pi.property_id = p.id AND pi.is_cover = 1 LIMIT 1) AS property_cover
         FROM inquiries i
         LEFT JOIN properties p ON p.id = i.property_id
         WHERE i.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

/**
 * @return list<array<string, mixed>>
 */
function inquiry_list(string $status = '', string $sort = 'newest', int $limit = 50): array
{
    $where = ['1=1'];
    $params = [];
    if ($status !== '' && in_array($status, inquiry_statuses(), true)) {
        $where[] = 'i.status = ?';
        $params[] = $status;
    }
    $order = $sort === 'oldest' ? 'i.created_at ASC' : 'i.created_at DESC';
    $sql = 'SELECT i.*, p.title AS property_title, p.slug AS property_slug
            FROM inquiries i
            LEFT JOIN properties p ON p.id = i.property_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $order . '
            LIMIT ' . (int) $limit;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
}

function inquiry_update_status(int $id, string $status): void
{
    if (!in_array($status, inquiry_statuses(), true)) {
        return;
    }
    db()->prepare('UPDATE inquiries SET status = ? WHERE id = ?')->execute([$status, $id]);
}

function inquiry_save_notes(int $id, string $notes): void
{
    db()->prepare('UPDATE inquiries SET admin_notes = ? WHERE id = ?')->execute([$notes, $id]);
}

/**
 * Notify admin of a new inquiry. Uses mailer (Brevo API / PHP mail / log). Failure is logged but does not roll back insert.
 *
 * @param array<string, mixed> $inquiry
 */
function inquiry_notify_admin(array $inquiry): array
{
    $to = (string) app_config('mail.admin_notify_email', '');
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $to = setting_get('site_email', '') ?? '';
    }
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'No admin notify email configured.'];
    }

    $name = trim(($inquiry['first_name'] ?? '') . ' ' . ($inquiry['last_name'] ?? ''));
    $type = (string) ($inquiry['type'] ?? 'contact');
    $brand = site_name();
    $subject = $type === 'property_inquiry'
        ? $brand . ' property inquiry #' . (int) ($inquiry['id'] ?? 0)
        : $brand . ' contact inquiry #' . (int) ($inquiry['id'] ?? 0);

    $propertyLine = '';
    if (!empty($inquiry['property_id'])) {
        $propertyLine = 'Property ID: ' . (int) $inquiry['property_id'];
        if (!empty($inquiry['property_title'])) {
            $propertyLine .= ' — ' . $inquiry['property_title'];
        }
        if (!empty($inquiry['property_slug'])) {
            $propertyLine .= ' (' . base_url('property.php?slug=' . rawurlencode((string) $inquiry['property_slug'])) . ')';
        }
    }

    $text = "New inquiry received\n\n"
        . "Type: {$type}\n"
        . "Name: {$name}\n"
        . "Email: " . ($inquiry['email'] ?? '') . "\n"
        . "Phone: " . ($inquiry['phone'] ?? '') . "\n"
        . "Interest: " . ($inquiry['interest'] ?? '') . "\n"
        . ($propertyLine !== '' ? $propertyLine . "\n" : '')
        . "\nMessage:\n" . ($inquiry['message'] ?? '') . "\n\n"
        . "Admin: " . base_url('admin/inquiries.php?id=' . (int) ($inquiry['id'] ?? 0)) . "\n";

    $html = '<p><strong>New inquiry received</strong></p>'
        . '<ul>'
        . '<li>Type: ' . e($type) . '</li>'
        . '<li>Name: ' . e($name) . '</li>'
        . '<li>Email: ' . e((string) ($inquiry['email'] ?? '')) . '</li>'
        . '<li>Phone: ' . e((string) ($inquiry['phone'] ?? '')) . '</li>'
        . '<li>Interest: ' . e((string) ($inquiry['interest'] ?? '')) . '</li>'
        . ($propertyLine !== '' ? '<li>' . e($propertyLine) . '</li>' : '')
        . '</ul>'
        . '<p>' . nl2br(e((string) ($inquiry['message'] ?? ''))) . '</p>';

    return send_mail($to, $subject, $html, $text);
}

/**
 * Optional client acknowledgement (best-effort).
 *
 * @param array<string, mixed> $inquiry
 */
function inquiry_ack_client(array $inquiry): array
{
    $email = (string) ($inquiry['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid client email.'];
    }
    $name = trim(($inquiry['first_name'] ?? '') . ' ' . ($inquiry['last_name'] ?? ''));
    $brand = site_name();
    $subject = 'We received your message — ' . $brand;
    $text = "Hello {$name},\n\nThank you for contacting {$brand}. "
        . "An advisor will respond shortly.\n\n— {$brand}\n";
    $html = '<p>Hello ' . e($name) . ',</p>'
        . '<p>Thank you for contacting <strong>' . e($brand) . '</strong>. An advisor will respond shortly.</p>'
        . '<p>— ' . e($brand) . '</p>';
    return send_mail($email, $subject, $html, $text);
}
