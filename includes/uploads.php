<?php
/**
 * Safe property image uploads — Phase 3 / hardened Phase 7.
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

    // Reject non-images / polyglots that only claim an image MIME.
    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
        return ['ok' => false, 'error' => 'File is not a valid image.', 'path' => null];
    }
    $detected = (string) ($imageInfo['mime'] ?? '');
    if ($detected !== '' && $detected !== $mime) {
        return ['ok' => false, 'error' => 'Image type mismatch.', 'path' => null];
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

    // Re-encode when GD is available to strip payloads embedded after image data.
    $sanitized = property_upload_reencode($tmp, $mime);
    if ($sanitized === null) {
        // GD missing or encode failed — still require getimagesize success above.
        $sanitized = false;
    }

    $dir = (string) app_config('uploads.properties_dir', APP_ROOT . '/uploads/properties');
    $subdir = $dir . '/' . $propertyId;
    if (!is_dir($subdir) && !mkdir($subdir, 0755, true) && !is_dir($subdir)) {
        return ['ok' => false, 'error' => 'Could not create upload directory.', 'path' => null];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $subdir . '/' . $filename;

    if (is_string($sanitized)) {
        if (@file_put_contents($dest, $sanitized) === false) {
            return ['ok' => false, 'error' => 'Could not store uploaded file.', 'path' => null];
        }
        @unlink($tmp);
    } elseif (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => 'Could not store uploaded file.', 'path' => null];
    }

    $relative = 'uploads/properties/' . $propertyId . '/' . $filename;
    return ['ok' => true, 'error' => null, 'path' => $relative];
}

/**
 * @return string|null binary image data, or null if GD unavailable / failed
 */
function property_upload_reencode(string $tmp, string $mime): ?string
{
    if (!extension_loaded('gd')) {
        return null;
    }

    $img = null;
    if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
        $img = @imagecreatefromjpeg($tmp);
    } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
        $img = @imagecreatefrompng($tmp);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $img = @imagecreatefromwebp($tmp);
    }

    if ($img === false || $img === null) {
        return null;
    }

    ob_start();
    $ok = false;
    if ($mime === 'image/jpeg') {
        $ok = imagejpeg($img, null, 90);
    } elseif ($mime === 'image/png') {
        imagesavealpha($img, true);
        $ok = imagepng($img, null, 6);
    } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
        $ok = imagewebp($img, null, 90);
    }
    imagedestroy($img);
    $data = ob_get_clean();

    if (!$ok || !is_string($data) || $data === '') {
        return null;
    }
    return $data;
}

function property_delete_image_file(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }
    if (preg_match('#^https?://#i', $relativePath)) {
        return;
    }

    $uploadsRoot = realpath(APP_ROOT . '/uploads');
    if ($uploadsRoot === false) {
        return;
    }

    $candidate = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
    $real = realpath($candidate);
    if ($real === false || !is_file($real)) {
        return;
    }

    $prefix = $uploadsRoot . DIRECTORY_SEPARATOR;
    if (!str_starts_with($real, $prefix)) {
        app_log('uploads', 'Blocked delete outside uploads: ' . $relativePath);
        return;
    }

    @unlink($real);
}

/**
 * Upload logo or favicon under uploads/branding/. Deletes previous local branding file when replaced.
 *
 * @return array{ok:bool, error:?string, path:?string}
 */
function branding_upload_image(array $file, string $kind, ?string $previousRelativePath = null): array
{
    $kind = $kind === 'favicon' ? 'favicon' : 'logo';
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No file uploaded.', 'path' => null];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed.', 'path' => null];
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
    $allowed = $kind === 'favicon'
        ? ['image/jpeg', 'image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon']
        : ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    if (!in_array($mime, $allowed, true)) {
        return ['ok' => false, 'error' => $kind === 'favicon' ? 'Favicon must be JPEG, PNG, WebP, or ICO.' : 'Logo must be JPEG, PNG, WebP, or SVG.', 'path' => null];
    }

    $isSvg = $mime === 'image/svg+xml';
    if (!$isSvg && !in_array($mime, ['image/x-icon', 'image/vnd.microsoft.icon'], true)) {
        $imageInfo = @getimagesize($tmp);
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return ['ok' => false, 'error' => 'File is not a valid image.', 'path' => null];
        }
    }
    if ($isSvg) {
        $raw = (string) file_get_contents($tmp);
        if ($raw === '' || stripos($raw, '<svg') === false || preg_match('/<script/i', $raw)) {
            return ['ok' => false, 'error' => 'Invalid or unsafe SVG.', 'path' => null];
        }
    }

    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];
    $ext = $extMap[$mime] ?? 'bin';

    $dir = APP_ROOT . '/uploads/branding';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Could not create branding directory.', 'path' => null];
    }

    $filename = $kind . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => 'Could not store uploaded file.', 'path' => null];
    }

    $relative = 'uploads/branding/' . $filename;
    if ($previousRelativePath !== null && $previousRelativePath !== '' && str_starts_with($previousRelativePath, 'uploads/branding/')) {
        property_delete_image_file($previousRelativePath);
    }

    return ['ok' => true, 'error' => null, 'path' => $relative];
}

/**
 * Upload homepage / collection image for a region under uploads/regions/.
 *
 * @return array{ok:bool, error:?string, path:?string}
 */
function region_upload_image(array $file, ?string $previousRelativePath = null): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No file uploaded.', 'path' => null];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed.', 'path' => null];
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
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowed, true)) {
        return ['ok' => false, 'error' => 'Region images must be JPEG, PNG, or WebP.', 'path' => null];
    }

    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
        return ['ok' => false, 'error' => 'File is not a valid image.', 'path' => null];
    }

    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $ext = $extMap[$mime] ?? 'jpg';

    $dir = APP_ROOT . '/uploads/regions';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Could not create regions upload directory.', 'path' => null];
    }

    $filename = 'region-' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => 'Could not store uploaded file.', 'path' => null];
    }

    $relative = 'uploads/regions/' . $filename;
    if (
        $previousRelativePath !== null
        && $previousRelativePath !== ''
        && str_starts_with($previousRelativePath, 'uploads/regions/')
    ) {
        property_delete_image_file($previousRelativePath);
    }

    return ['ok' => true, 'error' => null, 'path' => $relative];
}

