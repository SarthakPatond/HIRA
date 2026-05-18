<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
handle_cors();

try {
    $data = request_data();
    $name = trim((string) ($data['name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $message = trim((string) ($data['message'] ?? ''));

    if ($name === '' || $phone === '' || $message === '') {
        json_response([
            'success' => false,
            'message' => 'Name, phone, and message are required.',
        ], 422);
    }

    $stmt = get_db()->prepare(
        'INSERT INTO leads (name, phone, business_type, message, source, city, business_details)
         VALUES (:name, :phone, :business_type, :message, :source, :city, :business_details)'
    );

    $stmt->execute([
        'name' => $name,
        'phone' => $phone,
        'business_type' => 'General Inquiry',
        'message' => $message,
        'source' => 'Contact',
        'city' => null,
        'business_details' => null,
    ]);

    json_response([
        'success' => true,
        'message' => 'Message sent successfully.',
    ], 201);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}

