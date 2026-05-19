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
        return null;
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return null;
    }

    $uploadDir = __DIR__ . "/../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    chmod($uploadDir, 0777);

    $filename = time() . "-" . basename($file["name"]);
    $fileName = pathinfo($filename, PATHINFO_FILENAME) . '.' . $allowedMimeTypes[$mimeType];
    $targetFile = $uploadDir . $fileName;

    if (!move_uploaded_file($tmpName, $targetFile)) {
        return null;
    }

    return $fileName;
}
