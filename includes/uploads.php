<?php
/**
 * Safe property image uploads — Phase 3.
 */

declare(strict_types=1);

/**
 * @return array{ok:bool, error:?string, path:?string}
 */
function property_upload_image(array $file, int $propertyId): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No file uploaded.', 'path' => null];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed (error code ' . (int) $file['error'] . ').', 'path' => null];
    }

    $max = (int) app_config('uploads.max_bytes', 10 * 1024 * 1024);
    if (($file['size'] ?? 0) > $max) {
        return ['ok' => false, 'error' => 'File exceeds maximum size.', 'path' => null];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Invalid upload.', 'path' => null];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $allowedMime = app_config('uploads.allowed_mime', ['image/jpeg', 'image/png', 'image/webp']);
    if (!is_array($allowedMime) || !in_array($mime, $allowedMime, true)) {
        return ['ok' => false, 'error' => 'Only JPEG, PNG, and WebP images are allowed.', 'path' => null];
    }

    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $ext = $extMap[$mime] ?? null;
    if ($ext === null) {
        return ['ok' => false, 'error' => 'Unsupported image type.', 'path' => null];
    }

    $dir = (string) app_config('uploads.properties_dir', APP_ROOT . '/uploads/properties');
    $subdir = $dir . '/' . $propertyId;
    if (!is_dir($subdir) && !mkdir($subdir, 0755, true) && !is_dir($subdir)) {
        return ['ok' => false, 'error' => 'Could not create upload directory.', 'path' => null];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $subdir . '/' . $filename;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => 'Could not store uploaded file.', 'path' => null];
    }

    // Relative path from app root for media_url()
    $relative = 'uploads/properties/' . $propertyId . '/' . $filename;
    return ['ok' => true, 'error' => null, 'path' => $relative];
}

function property_delete_image_file(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }
    if (preg_match('#^https?://#i', $relativePath)) {
        return;
    }
    $full = APP_ROOT . '/' . ltrim(str_replace(['..', '\\'], '', $relativePath), '/');
    if (is_file($full)) {
        @unlink($full);
    }
}
