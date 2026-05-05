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
    echo "FILES DATA:<br>";
    print_r($_FILES);
    
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        echo "Upload error: " . $file['error'] . "<br>";
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
        echo "Not uploaded file<br>";
        return null;
    }

    $mimeType = mime_content_type($tmpName) ?: '';
    if (!isset($allowedMimeTypes[$mimeType])) {
        echo "Invalid mime: " . $mimeType . "<br>";
        return null;
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        echo "File too large<br>";
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

    echo "Saving to: " . $targetFile . "<br>";
    echo "Dir writable: " . (is_writable($uploadDir) ? "YES" : "NO") . "<br>";

    if (!move_uploaded_file($tmpName, $targetFile)) {
        echo "Upload failed - check permissions<br>";
        return null;
    } else {
        echo "Upload SUCCESS: " . $targetFile . "<br>";
    }

    return $fileName;
}
