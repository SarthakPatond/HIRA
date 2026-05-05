<?php

declare(strict_types=1);

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function handle_cors(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function request_data(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '[]', true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function decode_json_array(?string $value): array
{
    if (!$value) {
        return [];
    }

    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function normalize_multiline_list(?string $value): array
{
    if (!$value) {
        return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
    $items = array_map(static fn(string $item): string => trim($item), $lines);
    return array_values(array_filter($items, static fn(string $item): bool => $item !== ''));
}

function encode_json_value(mixed $value): string
{
    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function base_upload_url(): string
{
    $projectRoot = base_project_url();
    return rtrim($projectRoot, '/') . '/backend/uploads/';
}

function request_scheme(): string
{
    $https = $_SERVER['HTTPS'] ?? '';
    if ($https && strtolower((string) $https) !== 'off') {
        return 'https';
    }

    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if ($forwardedProto !== '') {
        return strtolower((string) $forwardedProto) === 'https' ? 'https' : 'http';
    }

    return 'http';
}

function base_project_path(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    foreach (['/backend/', '/admin/'] as $marker) {
        $position = strpos($scriptName, $marker);
        if ($position !== false) {
            return substr($scriptName, 0, $position) ?: '';
        }
    }

    return '/HIRA';
}

function base_project_url(): string
{
    $path = rtrim(base_project_path(), '/');
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));

    if ($host === '') {
        return $path !== '' ? $path : '/HIRA';
    }

    return request_scheme() . '://' . $host . ($path !== '' ? $path : '');
}

function asset_url(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    if (preg_match('/^(https?:)?\/\//i', $path)) {
        return $path;
    }

    $normalizedPath = ltrim($path, '/');

    if (str_starts_with($normalizedPath, 'backend/uploads/')) {
        return rtrim(base_project_url(), '/') . '/' . $normalizedPath;
    }

    if (str_starts_with($normalizedPath, 'uploads/')) {
        return rtrim(base_project_url(), '/') . '/backend/' . $normalizedPath;
    }

    return rtrim(base_upload_url(), '/') . '/' . ltrim($path, '/');
}

function normalized_media_value(?string $value): ?string
{
    return asset_url($value);
}

function normalized_page_content(string $page, array $content): array
{
    if ($page === 'home') {
        $content['hero']['media_url'] = normalized_media_value($content['hero']['media_url'] ?? null);
        $content['story']['image'] = normalized_media_value($content['story']['image'] ?? null);

        foreach ($content['categories'] ?? [] as $index => $category) {
            $content['categories'][$index]['image'] = normalized_media_value($category['image'] ?? null);
        }

        foreach ($content['coming_soon']['items'] ?? [] as $index => $item) {
            $content['coming_soon']['items'][$index]['image'] = normalized_media_value($item['image'] ?? null);
        }
    }

    if ($page === 'about') {
        $content['hero_image'] = normalized_media_value($content['hero_image'] ?? null);
    }

    if ($page === 'contact') {
        $content['hero_image'] = normalized_media_value($content['hero_image'] ?? null);
    }

    return $content;
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');
    return $value !== '' ? $value : 'item';
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function page_defaults(): array
{
    return [
        'home' => [
            'hero' => [
                'eyebrow' => 'A pantry story from Ujjain',
                'title' => "Some traditions don't change. They evolve.",
                'subtitle' => 'From Ujjain to the world, Hira brings familiar comfort into modern kitchens with warmth, care, and authenticity.',
                'media_type' => 'image',
                'media_url' => 'https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=1600&q=80',
                'cta_label' => 'Discover the Story',
                'cta_link' => '#home-story',
                'secondary_label' => 'Become Distributor',
                'secondary_link' => '/distributor',
            ],
            'story' => [
                'title' => 'It started with a journey...',
                'intro' => 'A few years in the USA changed how we looked at food brands. The products were modern, but the strongest ones still carried memory, belonging, and pride. That realization brought us back to Ujjain with a simple question: what if staples from home could feel just as meaningful, and just as beautifully presented?',
                'steps' => [
                    [
                        'title' => 'The USA Journey',
                        'text' => 'Exposure to global shelves showed how everyday food can feel elevated when story, trust, and design come together.',
                    ],
                    [
                        'title' => 'A Realization',
                        'text' => 'The strongest products were never just functional. They carried identity, warmth, and a sense of home.',
                    ],
                    [
                        'title' => 'Return to Ujjain',
                        'text' => 'That insight brought the vision back to a city known for food heritage, authenticity, and long-standing market trust.',
                    ],
                    [
                        'title' => 'Building Hira',
                        'text' => 'Hira was shaped to bring traditional staples and evolving snack ideas into a premium, story-led FMCG experience.',
                    ],
                ],
                'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=80',
            ],
            'heritage' => [
                'title' => 'Rooted in Ujjain',
                'text' => 'Ujjain has long been a trusted name in staple sourcing, especially across the poha cluster. Hira grows from that credibility, pairing regional depth with a cleaner, more contemporary brand expression.',
                'highlights' => [
                    [
                        'title' => 'Poha Cluster',
                        'text' => 'A region widely recognized for reliable quality and staple expertise.',
                    ],
                    [
                        'title' => 'Generational Familiarity',
                        'text' => 'Foods that already belong to everyday rituals, breakfasts, and shared family moments.',
                    ],
                    [
                        'title' => 'Modern Authenticity',
                        'text' => 'Careful packaging, consistent sourcing, and a brand language built for today.',
                    ],
                ],
            ],
            'showcase' => [
                'eyebrow' => 'Kitchen Essentials',
                'title' => 'What we bring to your kitchen',
                'text' => 'Not a catalog of products, but a thoughtful pantry of staples and snacks shaped by comfort, utility, and familiarity.',
            ],
            'categories' => [
                [
                    'name' => 'Poha',
                    'description' => 'Light, familiar, and ready for the kind of breakfast memories that stay with you.',
                    'image' => 'https://images.unsplash.com/photo-1617191519105-d07b98b10de4?auto=format&fit=crop&w=800&q=80',
                ],
                [
                    'name' => 'Sabudana',
                    'description' => 'Soft pearls for fasting rituals, comfort recipes, and pantry staples that feel timeless.',
                    'image' => 'https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=800&q=80',
                ],
                [
                    'name' => 'Snacks',
                    'description' => 'Playful crunch, festive color, and shelf-friendly formats made for modern families.',
                    'image' => 'https://images.unsplash.com/photo-1512152272829-e3139592d56f?auto=format&fit=crop&w=800&q=80',
                ],
            ],
            'coming_soon' => [
                'title' => 'Something exciting is taking shape...',
                'text' => 'New ideas are quietly coming together behind the scenes, designed to surprise shelves and delight kitchens without revealing everything just yet.',
                'items' => [
                    [
                        'title' => 'A bolder snack story',
                        'image' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'title' => 'Convenience with comfort',
                        'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'title' => 'A fresh shelf experience',
                        'image' => 'https://images.unsplash.com/photo-1506368249639-73a05d6f6488?auto=format&fit=crop&w=800&q=80',
                    ],
                ],
            ],
            'trust' => [
                'title' => 'Built with care. Backed by standards.',
                'text' => 'Every pack is supported by the discipline and certifications that help modern retailers and families trust what reaches their shelves and kitchens.',
                'items' => [
                    [
                        'title' => 'ISO 22000',
                        'text' => 'Food safety management',
                    ],
                    [
                        'title' => 'ISO 9001',
                        'text' => 'Quality management systems',
                    ],
                    [
                        'title' => 'APEDA',
                        'text' => 'Export readiness and compliance',
                    ],
                    [
                        'title' => 'FSSAI',
                        'text' => 'Licensed food standards',
                    ],
                ],
            ],
            'cta' => [
                'title' => "Let's grow together",
                'text' => 'Partner with Hira to bring trusted staples, stronger shelf presence, and a more meaningful brand story into new markets.',
                'button_label' => 'Become Distributor',
                'button_link' => '/distributor',
            ],
        ],
        'about' => [
            'hero_title' => 'A brand built on memory, movement, and modern food trust',
            'hero_text' => 'Hira translates a family-inspired origin story into a future-facing FMCG company with systems, consistency, and emotional resonance.',
            'hero_image' => 'https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=1200&q=80',
            'sections' => [
                [
                    'title' => 'How It Started',
                    'text' => 'The spark came from seeing how thoughtfully branded food products earn trust abroad, then asking how that same confidence could be built around staples from central India.',
                ],
                [
                    'title' => 'Vision',
                    'text' => 'To become the most trusted household name for Indian staples and snack essentials across homes, modern trade, and distribution networks.',
                ],
                [
                    'title' => 'Mission',
                    'text' => "Deliver clean, dependable, attractive FMCG products that respect tradition while meeting today's expectations for packaging, quality, and convenience.",
                ],
                [
                    'title' => 'Objective',
                    'text' => 'Scale responsibly from Ujjain with a strong backend, memorable branding, and category depth that grows with the market.',
                ],
                [
                    'title' => 'Values',
                    'text' => "Authenticity, trust, operational discipline, and pride in every pack that enters a customer's kitchen.",
                ],
            ],
            'timeline' => [
                [
                    'year' => '2019',
                    'title' => 'Idea Takes Shape',
                    'text' => 'The brand vision is shaped through exposure to international FMCG storytelling and retail presentation.',
                ],
                [
                    'year' => '2021',
                    'title' => 'Rooted In Ujjain',
                    'text' => 'Planning shifts toward building a business grounded in local sourcing and manufacturing credibility.',
                ],
                [
                    'year' => '2024',
                    'title' => 'Portfolio Expansion',
                    'text' => 'Categories, packaging systems, and distribution conversations mature into a market-ready platform.',
                ],
            ],
        ],
        'contact' => [
            'title' => 'Let us build a stronger FMCG network together',
            'subtitle' => 'Reach out for retail, distribution, institutional supply, or general brand inquiries.',
            'hero_image' => 'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=1200&q=80',
            'address' => 'Ujjain, Madhya Pradesh, India',
            'email' => 'hello@hirafmcg.com',
            'phone' => '+91 98765 43210',
            'map_embed' => '',
        ],
    ];
}

function upgrade_legacy_page_content(string $page, array $content): array
{
    if ($page !== 'home') {
        return $content;
    }

    $homeDefaults = default_page_content('home');
    $legacyHeroTitles = [
        'From Ujjainâ€™s Legacy to Modern Kitchens',
        "From Ujjain's Legacy to Modern Kitchens",
    ];

    if (in_array((string) ($content['hero']['title'] ?? ''), $legacyHeroTitles, true)) {
        $content['hero'] = $homeDefaults['hero'];
    }

    if (($content['story']['title'] ?? '') === 'A journey that crosses continents and returns to its roots') {
        $content['story'] = $homeDefaults['story'];
    }

    if (($content['showcase']['title'] ?? '') === 'Premium staples and snacks for every shelf') {
        $content['showcase'] = $homeDefaults['showcase'];
    }

    if (($content['cta']['title'] ?? '') === 'Looking to grow distribution in your market?') {
        $content['cta'] = $homeDefaults['cta'];
    }

    $legacyCategories = array_map(
        static fn(array $item): string => (string) ($item['name'] ?? ''),
        $content['categories'] ?? []
    );

    if ($legacyCategories === ['Poha', 'Sabudana', 'Parmal', 'Fryums / Snacks']) {
        $content['categories'] = $homeDefaults['categories'];
    }

    $legacyComingSoonTitles = array_map(
        static fn(array $item): string => (string) ($item['title'] ?? ''),
        $content['coming_soon']['items'] ?? []
    );

    if (
        ($content['coming_soon']['title'] ?? '') === 'Coming Soon' &&
        $legacyComingSoonTitles === ['Masala Fryums', 'Instant Breakfast Mixes', 'Retail Combo Packs']
    ) {
        $content['coming_soon'] = $homeDefaults['coming_soon'];
    }

    return $content;
}

function default_page_content(string $page): array
{
    $defaults = page_defaults();
    return $defaults[$page] ?? [];
}

function seed_default_pages(): void
{
    $pdo = get_db();
    $defaults = page_defaults();

    foreach ($defaults as $pageName => $content) {
        $stmt = $pdo->prepare('SELECT id FROM cms_pages WHERE page_name = :page_name LIMIT 1');
        $stmt->execute(['page_name' => $pageName]);

        if (!$stmt->fetch()) {
            $insert = $pdo->prepare('INSERT INTO cms_pages (page_name, content) VALUES (:page_name, :content)');
            $insert->execute([
                'page_name' => $pageName,
                'content' => encode_json_value($content),
            ]);
        }
    }
}

function seed_default_products(): void
{
    $pdo = get_db();
    $count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

    if ($count > 0) {
        return;
    }

    $products = [
        [
            'name' => 'Classic Premium Poha',
            'category' => 'Poha',
            'description' => 'Clean, flattened rice crafted for soft texture, fast cooking, and dependable taste in everyday breakfast preparations.',
            'benefits' => ['Light and easy to cook', 'Consistent grain quality', 'Ideal for breakfast and snack recipes'],
            'pack_sizes' => ['500 g', '1 kg', '5 kg'],
            'image' => 'https://images.unsplash.com/photo-1617191519105-d07b98b10de4?auto=format&fit=crop&w=900&q=80',
            'is_coming_soon' => 0,
        ],
        [
            'name' => 'Select Sabudana Pearls',
            'category' => 'Sabudana',
            'description' => 'Uniform sabudana pearls suitable for fasting menus, khichdi, snacks, and family pantry use.',
            'benefits' => ['Even pearl size', 'Works across sweet and savory recipes', 'Packed for freshness'],
            'pack_sizes' => ['250 g', '500 g', '1 kg'],
            'image' => 'https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=900&q=80',
            'is_coming_soon' => 0,
        ],
        [
            'name' => 'Crisp Parmal Mix',
            'category' => 'Parmal',
            'description' => 'A pantry-friendly parmal offering designed for crunchy snacks, namkeen use, and value-focused retail formats.',
            'benefits' => ['Crisp bite', 'Multi-use in snack recipes', 'Retail-friendly packaging'],
            'pack_sizes' => ['200 g', '400 g', '800 g'],
            'image' => 'https://images.unsplash.com/photo-1515543904379-3d757afe72e4?auto=format&fit=crop&w=900&q=80',
            'is_coming_soon' => 0,
        ],
        [
            'name' => 'Rainbow Fryums Pack',
            'category' => 'Fryums / Snacks',
            'description' => 'Colorful fryums that bring playful appeal to family tables, kids menus, and festive snacking occasions.',
            'benefits' => ['Bright visual appeal', 'Great for family snacking', 'High shelf attention'],
            'pack_sizes' => ['150 g', '300 g', '700 g'],
            'image' => 'https://images.unsplash.com/photo-1512152272829-e3139592d56f?auto=format&fit=crop&w=900&q=80',
            'is_coming_soon' => 0,
        ],
        [
            'name' => 'Masala Fryums Bites',
            'category' => 'Fryums / Snacks',
            'description' => 'A bold snack extension being prepared for flavor-forward households and fast-moving retail counters.',
            'benefits' => ['Flavor-led extension', 'Festival-ready shelf appeal', 'Designed for expansion'],
            'pack_sizes' => ['120 g', '250 g'],
            'image' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=900&q=80',
            'is_coming_soon' => 1,
        ],
        [
            'name' => 'Instant Poha Cup',
            'category' => 'Poha',
            'description' => 'A convenience-led format that brings traditional comfort into busy modern routines.',
            'benefits' => ['Quick convenience format', 'Portable serving idea', 'Launch-ready innovation'],
            'pack_sizes' => ['65 g', '4 x 65 g'],
            'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80',
            'is_coming_soon' => 1,
        ],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO products (name, category, description, benefits, pack_sizes, image, is_coming_soon)
         VALUES (:name, :category, :description, :benefits, :pack_sizes, :image, :is_coming_soon)'
    );

    foreach ($products as $product) {
        $stmt->execute([
            'name' => $product['name'],
            'category' => $product['category'],
            'description' => $product['description'],
            'benefits' => encode_json_value($product['benefits']),
            'pack_sizes' => encode_json_value($product['pack_sizes']),
            'image' => $product['image'],
            'is_coming_soon' => $product['is_coming_soon'],
        ]);
    }
}

function get_page_content(string $page): array
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT content FROM cms_pages WHERE page_name = :page_name LIMIT 1');
    $stmt->execute(['page_name' => $page]);
    $row = $stmt->fetch();

    if (!$row) {
        return default_page_content($page);
    }

    $content = json_decode((string) $row['content'], true);

    if (!is_array($content)) {
        return default_page_content($page);
    }

    $content = upgrade_legacy_page_content($page, $content);
    return array_replace_recursive(default_page_content($page), $content);
}

function get_resolved_page_content(string $page): array
{
    return normalized_page_content($page, get_page_content($page));
}

function update_page_content(string $page, array $content): void
{
    $pdo = get_db();
    $stmt = $pdo->prepare('UPDATE cms_pages SET content = :content WHERE page_name = :page_name');
    $stmt->execute([
        'content' => encode_json_value($content),
        'page_name' => $page,
    ]);
}

function format_product(array $product): array
{
    return [
        'id' => (int) $product['id'],
        'name' => $product['name'],
        'slug' => slugify($product['name']) . '-' . (int) $product['id'],
        'category' => $product['category'],
        'description' => $product['description'],
        'benefits' => decode_json_array($product['benefits'] ?? ''),
        'pack_sizes' => decode_json_array($product['pack_sizes'] ?? ''),
        'image' => asset_url($product['image']),
        'image_path' => $product['image'],
        'is_coming_soon' => (bool) $product['is_coming_soon'],
        'created_at' => $product['created_at'],
    ];
}

function fetch_products(bool $includeComingSoon = true, ?string $category = null): array
{
    $pdo = get_db();
    $sql = 'SELECT * FROM products WHERE 1=1';
    $params = [];

    if (!$includeComingSoon) {
        $sql .= ' AND is_coming_soon = 0';
    }

    if ($category) {
        $sql .= ' AND category = :category';
        $params['category'] = $category;
    }

    $sql .= ' ORDER BY created_at DESC, id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $rows = $stmt->fetchAll();
    return array_map('format_product', $rows);
}

function fetch_product_by_id(int $id): ?array
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row ? format_product($row) : null;
}

function delete_local_upload(?string $path): void
{
    if (!$path || preg_match('/^https?:\/\//i', $path)) {
        return;
    }

    $fullPath = dirname(__DIR__) . '/uploads/' . ltrim($path, '/');
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}
