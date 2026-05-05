<?php

declare(strict_types=1);

function ensure_upload_directory(): string
{
    $dir = dirname(__DIR__) . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir;
}

function save_uploaded_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $tmpName = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmpName)) {
        return null;
    }

    $mimeType = mime_content_type($tmpName) ?: '';
    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException('Only JPG, PNG, WEBP, and GIF images are allowed.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Image size must be less than 5 MB.');
    }

    $directory = ensure_upload_directory();
    $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowedMimeTypes[$mimeType];
    $destination = $directory . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Image upload failed.');
    }

    return $fileName;
}
