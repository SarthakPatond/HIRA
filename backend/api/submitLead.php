<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
handle_cors();

try {
    $data = request_data();

    $name = trim((string) ($data['name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $businessType = trim((string) ($data['business_type'] ?? ''));
    $city = trim((string) ($data['city'] ?? ''));
    $businessDetails = trim((string) ($data['business_details'] ?? ''));
    $message = trim((string) ($data['message'] ?? ''));

    if ($name === '' || $phone === '' || $businessType === '' || $city === '' || $businessDetails === '' || $message === '') {
        json_response([
            'success' => false,
            'message' => 'All lead fields are required.',
        ], 422);
    }

    $stmt = get_db()->prepare(
        'INSERT INTO leads (name, phone, business_type, message, source, city, business_details)
         VALUES (:name, :phone, :business_type, :message, :source, :city, :business_details)'
    );

    $stmt->execute([
        'name' => $name,
        'phone' => $phone,
        'business_type' => $businessType,
        'message' => $message,
        'source' => 'Distributor',
        'city' => $city,
        'business_details' => $businessDetails,
    ]);

    json_response([
        'success' => true,
        'message' => 'Inquiry submitted successfully.',
    ], 201);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}

