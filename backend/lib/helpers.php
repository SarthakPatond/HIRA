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

    return '';
}

function base_project_url(): string
{
    $path = rtrim(base_project_path(), '/');
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));

    if ($host === '') {
        return $path !== '' ? $path : '';
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
                'secondary_label' => 'Become a Distributor',
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
                'button_label' => 'Become a Distributor',
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
            // 'phone' => '+91 98765 43210',
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

    if (($content['cta']['button_label'] ?? '') === 'Become Distributor') {
        $content['cta']['button_label'] = 'Become a Distributor';
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

function seed_default_recipes(): void
{
    $pdo = get_db();
    $productRows = $pdo->query('SELECT id, category FROM products ORDER BY created_at ASC, id ASC')->fetchAll();

    if (empty($productRows)) {
        return;
    }

    $relatedProductsByGroup = [
        'poha' => [],
        'sabudana' => [],
        'snacks' => [],
    ];

    foreach ($productRows as $productRow) {
        $productId = (int) ($productRow['id'] ?? 0);
        $category = mb_strtolower(trim((string) ($productRow['category'] ?? '')));

        if ($productId <= 0) {
            continue;
        }

        if (str_contains($category, 'poha')) {
            $relatedProductsByGroup['poha'][] = $productId;
        }

        if (str_contains($category, 'sabudana')) {
            $relatedProductsByGroup['sabudana'][] = $productId;
        }

        if (preg_match('/snack|fryum|parmal|namkeen/', $category) === 1) {
            $relatedProductsByGroup['snacks'][] = $productId;
        }
    }

    $fallbackProductIds = array_values(array_filter(
        array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $productRows),
        static fn(int $id): bool => $id > 0
    ));

    $recipes = [
        [
            'name' => 'Kanda Poha',
            'slug' => 'kanda-poha',
            'category' => 'Poha',
            'short_description' => 'Soft poha tossed with onions, curry leaves, peanuts, and a bright squeeze of lemon for a classic breakfast finish.',
            'hero_image' => 'https://images.unsplash.com/photo-1506368249639-73a05d6f6488?auto=format&fit=crop&w=1200&q=80',
            'thumbnail_image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80',
            'cook_time_minutes' => 18,
            'servings' => 3,
            'difficulty' => 'Easy',
            'tips' => [
                'Rinse poha quickly so it stays fluffy instead of mushy.',
                'Finish with sev and fresh coriander just before serving for texture.'
            ],
            'ingredients' => [
                '2 cups HIRA poha',
                '2 medium onions, thinly sliced',
                '1/3 cup peanuts',
                '2 green chillies, chopped',
                '10 curry leaves',
                '1/2 tsp mustard seeds',
                '1/4 tsp turmeric powder',
                'Salt to taste',
                '2 tbsp fresh coriander',
                '1 lemon, cut into wedges'
            ],
            'steps' => [
                'Rinse the poha in a colander, drain well, and let it soften for 5 minutes.',
                'Heat oil, crackle mustard seeds, then saute peanuts until lightly golden.',
                'Add onions, green chillies, and curry leaves and cook until the onions turn soft.',
                'Mix in turmeric and salt, then fold in the softened poha gently until evenly coated.',
                'Cook for 2 to 3 minutes, finish with coriander and lemon, and serve warm.'
            ],
            'related_groups' => ['poha'],
            'is_featured' => 1,
            'is_published' => 1,
        ],
        [
            'name' => 'Vegetable Poha',
            'slug' => 'vegetable-poha',
            'category' => 'Poha',
            'short_description' => 'A colorful vegetable poha with peas, carrots, and capsicum that feels hearty while still staying light.',
            'hero_image' => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=1200&q=80',
            'thumbnail_image' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=80',
            'cook_time_minutes' => 22,
            'servings' => 4,
            'difficulty' => 'Easy',
            'tips' => [
                'Dice vegetables small so they cook quickly without softening the poha too much.',
                'A spoon of ghee at the end deepens the aroma.'
            ],
            'ingredients' => [
                '2 cups HIRA poha',
                '1/2 cup green peas',
                '1/2 cup carrots, finely diced',
                '1/2 cup capsicum, finely diced',
                '1 onion, chopped',
                '1 tomato, chopped',
                '1/2 tsp mustard seeds',
                '1/4 tsp turmeric powder',
                'Salt to taste',
                '2 tbsp coriander leaves'
            ],
            'steps' => [
                'Wash and drain the poha, then rest it until soft but separate.',
                'Saute mustard seeds and onions, then add peas, carrots, and capsicum and cook until tender.',
                'Add tomato, turmeric, and salt and cook until the tomato softens slightly.',
                'Fold in the poha gently and cook for 2 to 3 minutes on low heat.',
                'Garnish with coriander and serve immediately.'
            ],
            'related_groups' => ['poha'],
            'is_featured' => 0,
            'is_published' => 1,
        ],
        [
            'name' => 'Indori Poha',
            'slug' => 'indori-poha',
            'category' => 'Poha',
            'short_description' => 'The Ujjain-and-Indore style favorite with light sweetness, fennel notes, sev, and pomegranate for a layered finish.',
            'hero_image' => 'https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=1200&q=80',
            'thumbnail_image' => 'https://images.unsplash.com/photo-1499028344343-cd173ffc68a9?auto=format&fit=crop&w=900&q=80',
            'cook_time_minutes' => 20,
            'servings' => 3,
            'difficulty' => 'Medium',
            'tips' => [
                'A pinch of sugar balances the gentle spice and gives it the classic Indori tone.',
                'Add sev only at the end so it stays crisp.'
            ],
            'ingredients' => [
                '2 cups HIRA poha',
                '1 onion, finely chopped',
                '2 tbsp fennel seeds',
                '1/2 tsp mustard seeds',
                '1/4 tsp turmeric powder',
                '1 pinch sugar',
                'Salt to taste',
                'Fine sev for garnish',
                'Pomegranate pearls for garnish',
                'Fresh coriander and lemon'
            ],
            'steps' => [
                'Rinse the poha lightly and let it soften while you prepare the tempering.',
                'Heat oil, add mustard seeds and fennel seeds, then saute the onions until just translucent.',
                'Add turmeric, sugar, and salt, then fold in the poha gently to keep it light.',
                'Cook briefly on low heat, then plate the poha without pressing it down.',
                'Top with sev, coriander, pomegranate, and a squeeze of lemon before serving.'
            ],
            'related_groups' => ['poha'],
            'is_featured' => 1,
            'is_published' => 1,
        ],
        [
            'name' => 'Sabudana Khichdi',
            'slug' => 'sabudana-khichdi',
            'category' => 'Sabudana',
            'short_description' => 'Soft sabudana pearls with roasted peanuts, green chillies, and cumin for a fasting-friendly classic that still feels indulgent.',
            'hero_image' => 'https://images.unsplash.com/photo-1515543904379-3d757afe72e4?auto=format&fit=crop&w=1200&q=80',
            'thumbnail_image' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=900&q=80',
            'cook_time_minutes' => 25,
            'servings' => 3,
            'difficulty' => 'Medium',
            'tips' => [
                'Soak sabudana just enough to soften it; oversoaking makes it sticky.',
                'Use coarsely crushed peanuts for better bite and aroma.'
            ],
            'ingredients' => [
                '2 cups HIRA sabudana, soaked',
                '1/2 cup roasted peanuts, coarsely crushed',
                '2 potatoes, diced small',
                '2 green chillies, chopped',
                '1 tsp cumin seeds',
                '1 tbsp ghee',
                'Salt or fasting salt to taste',
                '2 tbsp coriander leaves',
                '1 lemon'
            ],
            'steps' => [
                'Drain the soaked sabudana and mix it with crushed peanuts and salt.',
                'Heat ghee, add cumin, then saute potatoes until cooked and lightly crisp.',
                'Add green chillies and toss briefly before adding the sabudana mixture.',
                'Cook on medium heat, stirring gently until the pearls turn glossy and separate.',
                'Finish with coriander and lemon and serve hot.'
            ],
            'related_groups' => ['sabudana'],
            'is_featured' => 1,
            'is_published' => 1,
        ],
        [
            'name' => 'Sabudana Vada',
            'slug' => 'sabudana-vada',
            'category' => 'Snacks',
            'short_description' => 'Golden sabudana fritters with potato and peanut that stay crisp outside and soft inside.',
            'hero_image' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1200&q=80',
            'thumbnail_image' => 'https://images.unsplash.com/photo-1505253216365-4e1a97b0f7d0?auto=format&fit=crop&w=900&q=80',
            'cook_time_minutes' => 30,
            'servings' => 4,
            'difficulty' => 'Medium',
            'tips' => [
                'Chill the mixture for 10 minutes if the vadas feel too soft to shape.',
                'Fry on medium heat so the center cooks through without darkening the crust.'
            ],
            'ingredients' => [
                '2 cups HIRA sabudana, soaked',
                '3 boiled potatoes, mashed',
                '1/2 cup roasted peanuts, crushed',
                '2 green chillies, chopped',
                '2 tbsp coriander leaves',
                'Salt to taste',
                'Oil for frying'
            ],
            'steps' => [
                'Combine sabudana, potatoes, peanuts, green chillies, coriander, and salt into a firm mixture.',
                'Shape the mixture into small flat vadas.',
                'Heat oil in a kadai and fry the vadas in batches until golden on both sides.',
                'Drain on paper towels and serve immediately with green chutney or curd.'
            ],
            'related_groups' => ['sabudana', 'snacks'],
            'is_featured' => 0,
            'is_published' => 1,
        ],
        [
            'name' => 'Poha Cutlet',
            'slug' => 'poha-cutlet',
            'category' => 'Snacks',
            'short_description' => 'Crisp shallow-fried cutlets made with poha, potatoes, and vegetables for tea-time snacking.',
            'hero_image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=80',
            'thumbnail_image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80',
            'cook_time_minutes' => 28,
            'servings' => 4,
            'difficulty' => 'Medium',
            'tips' => [
                'Rest the cutlets for a few minutes before frying so they hold shape better.',
                'Toast them on a tawa with a little oil for a lighter finish.'
            ],
            'ingredients' => [
                '1 1/2 cups HIRA poha',
                '2 boiled potatoes, mashed',
                '1/4 cup carrots, grated',
                '1/4 cup peas, boiled',
                '1 tsp ginger-chilli paste',
                '1 tsp chaat masala',
                'Salt to taste',
                'Breadcrumbs as needed',
                'Oil for shallow frying'
            ],
            'steps' => [
                'Soften the poha with a quick rinse, then squeeze out excess water.',
                'Mix poha with potatoes, vegetables, ginger-chilli paste, chaat masala, and salt.',
                'Shape into oval cutlets and coat lightly with breadcrumbs if needed.',
                'Shallow fry on a hot tawa until both sides turn crisp and golden.',
                'Serve hot with ketchup or green chutney.'
            ],
            'related_groups' => ['poha', 'snacks'],
            'is_featured' => 0,
            'is_published' => 1,
        ],
        [
            'name' => 'Masala Poha',
            'slug' => 'masala-poha',
            'category' => 'Poha',
            'short_description' => 'A spicier, masala-forward poha variation with tomatoes and warming pantry aromatics.',
            'hero_image' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1200&q=80',
            'thumbnail_image' => 'https://images.unsplash.com/photo-1482049016688-2d3e1b311543?auto=format&fit=crop&w=900&q=80',
            'cook_time_minutes' => 20,
            'servings' => 3,
            'difficulty' => 'Easy',
            'tips' => [
                'Use a little garam masala at the end instead of the beginning to keep the flavor fresh.',
                'A touch of tomato ketchup can make it more family-friendly for kids.'
            ],
            'ingredients' => [
                '2 cups HIRA poha',
                '1 onion, sliced',
                '1 tomato, chopped',
                '1 tsp ginger-garlic paste',
                '1/2 tsp red chilli powder',
                '1/2 tsp coriander powder',
                '1/4 tsp garam masala',
                'Salt to taste',
                'Coriander for garnish'
            ],
            'steps' => [
                'Rinse and rest the poha until soft and fluffy.',
                'Saute onions, then add ginger-garlic paste and tomato and cook into a light masala base.',
                'Add chilli powder, coriander powder, and salt, then fold in the poha gently.',
                'Cook for 2 minutes, finish with garam masala and coriander, and serve hot.'
            ],
            'related_groups' => ['poha'],
            'is_featured' => 0,
            'is_published' => 1,
        ],
        [
            'name' => 'Peanut Poha',
            'slug' => 'peanut-poha',
            'category' => 'Poha',
            'short_description' => 'A nutty, texture-rich poha with extra roasted peanuts and gentle spice for an easy weekday plate.',
            'hero_image' => 'https://images.unsplash.com/photo-1506086679525-a687a3a7f874?auto=format&fit=crop&w=1200&q=80',
            'thumbnail_image' => 'https://images.unsplash.com/photo-1466637574441-749b8f19452f?auto=format&fit=crop&w=900&q=80',
            'cook_time_minutes' => 16,
            'servings' => 2,
            'difficulty' => 'Easy',
            'tips' => [
                'Roast the peanuts separately if you want stronger crunch all the way through.',
                'A final spoon of fresh coconut makes the peanut flavor feel rounder and softer.'
            ],
            'ingredients' => [
                '2 cups HIRA poha',
                '1/2 cup peanuts',
                '1 onion, chopped',
                '1 green chilli, chopped',
                '1/2 tsp mustard seeds',
                '1/4 tsp turmeric powder',
                'Salt to taste',
                'Fresh coconut and coriander for garnish'
            ],
            'steps' => [
                'Rinse the poha gently and leave it to soften while you make the tempering.',
                'Roast or fry the peanuts until crisp and keep aside.',
                'Saute mustard seeds, onion, and chilli, then add turmeric and salt.',
                'Fold in the poha and peanuts and cook briefly until warm and fragrant.',
                'Finish with coconut and coriander before serving.'
            ],
            'related_groups' => ['poha'],
            'is_featured' => 0,
            'is_published' => 1,
        ],
    ];

    $stmtExists = $pdo->prepare('SELECT id FROM recipes WHERE slug = :slug LIMIT 1');
    $stmtInsertRecipe = $pdo->prepare(
        'INSERT INTO recipes
         (name, slug, category, short_description, hero_image, thumbnail_image,
          cook_time_minutes, servings, difficulty, tips, related_products_json,
          is_featured, is_published)
         VALUES
         (:name, :slug, :category, :short_description, :hero_image, :thumbnail_image,
          :cook_time_minutes, :servings, :difficulty, :tips, :related_products_json,
          :is_featured, :is_published)'
    );
    $stmtInsertIngredient = $pdo->prepare(
        'INSERT INTO recipe_ingredients (recipe_id, ingredient_order, ingredient_text)
         VALUES (:recipe_id, :ingredient_order, :ingredient_text)'
    );
    $stmtInsertStep = $pdo->prepare(
        'INSERT INTO recipe_steps (recipe_id, step_order, step_text)
         VALUES (:recipe_id, :step_order, :step_text)'
    );

    foreach ($recipes as $recipe) {
        $stmtExists->execute(['slug' => $recipe['slug']]);
        if ($stmtExists->fetch()) {
            continue;
        }

        $relatedIds = [];
        foreach ($recipe['related_groups'] as $group) {
            $relatedIds = array_merge($relatedIds, $relatedProductsByGroup[$group] ?? []);
        }
        $relatedIds = array_values(array_unique(array_filter($relatedIds, static fn(int $id): bool => $id > 0)));

        if (empty($relatedIds)) {
            $relatedIds = array_slice($fallbackProductIds, 0, 3);
        }

        try {
            $pdo->beginTransaction();
            $stmtInsertRecipe->execute([
                'name' => $recipe['name'],
                'slug' => $recipe['slug'],
                'category' => $recipe['category'],
                'short_description' => $recipe['short_description'],
                'hero_image' => $recipe['hero_image'],
                'thumbnail_image' => $recipe['thumbnail_image'],
                'cook_time_minutes' => $recipe['cook_time_minutes'],
                'servings' => $recipe['servings'],
                'difficulty' => $recipe['difficulty'],
                'tips' => encode_json_value($recipe['tips']),
                'related_products_json' => encode_json_value($relatedIds),
                'is_featured' => $recipe['is_featured'],
                'is_published' => $recipe['is_published'],
            ]);

            $recipeId = (int) $pdo->lastInsertId();

            $ingredientOrder = 1;
            foreach ($recipe['ingredients'] as $ingredientText) {
                $stmtInsertIngredient->execute([
                    'recipe_id' => $recipeId,
                    'ingredient_order' => $ingredientOrder,
                    'ingredient_text' => $ingredientText,
                ]);
                $ingredientOrder++;
            }

            $stepOrder = 1;
            foreach ($recipe['steps'] as $stepText) {
                $stmtInsertStep->execute([
                    'recipe_id' => $recipeId,
                    'step_order' => $stepOrder,
                    'step_text' => $stepText,
                ]);
                $stepOrder++;
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
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
