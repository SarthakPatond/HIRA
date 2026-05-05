<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
handle_cors();

try {
    $page = trim((string) ($_GET['page'] ?? 'home'));
    $content = get_resolved_page_content($page);

    json_response([
        'success' => true,
        'data' => [
            'page_name' => $page,
            'content' => $content,
        ],
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
