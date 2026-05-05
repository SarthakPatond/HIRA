<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
handle_cors();

if (!is_admin_logged_in()) {
    json_response([
        'success' => false,
        'message' => 'Unauthorized.',
    ], 401);
}

try {
    $data = request_data();
    $page = trim((string) ($data['page_name'] ?? ''));
    $content = $data['content'] ?? null;

    if ($page === '' || !is_array($content)) {
        json_response([
            'success' => false,
            'message' => 'Page name and content are required.',
        ], 422);
    }

    update_page_content($page, $content);

    json_response([
        'success' => true,
        'message' => 'Page updated successfully.',
        'data' => [
            'page_name' => $page,
            'content' => get_resolved_page_content($page),
        ],
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
