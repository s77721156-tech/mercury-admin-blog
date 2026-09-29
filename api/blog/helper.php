<?php
/**
 * Mercury Admin - Blog API Shared Helper
 * 
 * Provides database schema auto-migration for all 3-step wizard fields,
 * request parsing, validation, and post formatting.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Ensures the `posts` table has all columns for Content, SEO, and Publishing
 */
function ensurePostsSchema(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    $requiredColumns = [
        'title'               => 'VARCHAR(255) NOT NULL',
        'slug'                => 'VARCHAR(255) NOT NULL UNIQUE',
        'excerpt'             => 'TEXT',
        'content'             => 'LONGTEXT',
        'cover_image'         => 'VARCHAR(255)',
        'image_alt'           => 'VARCHAR(255)',
        'author'              => "VARCHAR(100) DEFAULT 'Admin'",
        'category'            => "VARCHAR(100) DEFAULT 'Updates'",
        'tags'                => 'TEXT',
        'seo_title'           => 'VARCHAR(255)',
        'meta_description'    => 'TEXT',
        'focus_keyword'       => 'VARCHAR(255)',
        'secondary_keywords'  => 'TEXT',
        'canonical_url'       => 'VARCHAR(255)',
        'robots_indexing'     => "VARCHAR(50) DEFAULT 'Index'",
        'link_behavior'       => "VARCHAR(50) DEFAULT 'Follow'",
        'direct_answer'       => 'TEXT',
        'key_takeaways'       => 'TEXT',
        'faq_items'           => 'LONGTEXT',
        'related_posts'       => 'TEXT',
        'related_products'    => 'TEXT',
        'cta_type'            => 'VARCHAR(100)',
        'cta_heading'         => 'VARCHAR(255)',
        'cta_description'     => 'TEXT',
        'cta_button_text'     => 'VARCHAR(100)',
        'cta_button_url'      => 'VARCHAR(255)',
        'og_title'            => 'VARCHAR(255)',
        'og_description'      => 'TEXT',
        'status'              => "VARCHAR(50) DEFAULT 'Draft'",
        'publish_date'        => 'VARCHAR(50)',
        'publish_time'        => 'VARCHAR(50)',
        'featured_post'       => 'TINYINT(1) DEFAULT 0',
        'published'           => 'TINYINT(1) DEFAULT 0',
        'views'               => 'INT DEFAULT 0',
    ];

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM posts")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($requiredColumns as $colName => $colDef) {
            if (!in_array($colName, $cols, true)) {
                $pdo->exec("ALTER TABLE posts ADD COLUMN `{$colName}` {$colDef}");
            }
        }
    } catch (\Throwable $e) {
        error_log("Schema ensure error: " . $e->getMessage());
    }
    $checked = true;
}

/**
 * Handle file upload for cover image if present in $_FILES
 */
function handleCoverImageUpload(): ?string {
    if (!isset($_FILES['coverImage']) || $_FILES['coverImage']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES['coverImage'];
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        sendJsonError("Cover image exceeds 5 MB maximum allowed size.", 400);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtMap = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif'
    ];

    $mime = null;
    $imgInfo = @getimagesize($file['tmp_name']);
    if ($imgInfo && !empty($imgInfo['mime'])) {
        $mime = $imgInfo['mime'];
    }

    if ((!$mime || $mime === 'application/octet-stream') && function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected = @finfo_file($finfo, $file['tmp_name']);
            @finfo_close($finfo);
            if ($detected && $detected !== 'application/octet-stream') {
                $mime = $detected;
            }
        }
    }

    if (!$mime || $mime === 'application/octet-stream') {
        if (isset($allowedExtMap[$extension])) {
            $mime = $allowedExtMap[$extension];
        } elseif (!empty($file['type']) && $file['type'] !== 'application/octet-stream') {
            $mime = $file['type'];
        }
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowedMimes, true)) {
        sendJsonError("Invalid cover image type. Allowed: JPG, PNG, WebP.", 400);
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => $extension ?: 'jpg'
    };

    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = 'cover_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
        sendJsonError("Failed to save cover image.", 500);
    }

    return 'uploads/' . $fileName;
}

/**
 * Parse and validate all incoming post fields from request body (JSON or FormData)
 */
function parseBlogPostPayload(array $rawInput = []): array {
    $data = !empty($rawInput) ? $rawInput : getApiRequestBody();

    $title = trim($data['title'] ?? '');
    if ($title === '') {
        sendJsonError("Post title is required.", 422);
    }

    $slug = trim($data['slug'] ?? '');
    if ($slug === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    } else {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $slug));
        $slug = trim($slug, '-');
    }

    $category = trim($data['category'] ?? 'Updates');
    $author   = trim($data['author'] ?? 'Admin User');
    $excerpt  = trim($data['excerpt'] ?? '');
    $content  = trim($data['content'] ?? '');

    // Cover image: either uploaded file or existing string
    $uploadedCover = handleCoverImageUpload();
    $coverImage = $uploadedCover ?? trim($data['coverImage'] ?? $data['cover_image'] ?? '');

    // Status & Publishing
    $status = trim($data['post_status'] ?? $data['status'] ?? 'Draft');
    $publishedRaw = $data['published'] ?? false;
    $published = ($status === 'Published' || $publishedRaw === true || $publishedRaw === 'true' || $publishedRaw === 1 || $publishedRaw === '1') ? 1 : 0;
    if ($published === 1 && $status === 'Draft') {
        $status = 'Published';
    }

    // Step 2: SEO & AI Search fields
    $seoTitle        = trim($data['seo_title'] ?? $title);
    $metaDescription = trim($data['meta_description'] ?? $excerpt);
    $focusKeyword    = trim($data['focus_keyword'] ?? '');
    $secKeywords     = trim($data['secondary_keywords'] ?? '');
    $canonicalUrl    = trim($data['canonical_url'] ?? '');
    $robotsIndexing  = trim($data['robots_indexing'] ?? 'Index');
    $linkBehavior    = trim($data['link_behavior'] ?? 'Follow');
    $directAnswer    = trim($data['direct_answer'] ?? '');
    $keyTakeaways    = trim($data['key_takeaways'] ?? '');

    // FAQ Items (can be JSON string or array)
    $faqItems = $data['faq_items'] ?? '';
    if (is_array($faqItems)) {
        $faqItems = json_encode($faqItems, JSON_UNESCAPED_UNICODE);
    }

    // Step 3: Publish & Distribution fields
    $tags            = trim($data['tags'] ?? '');
    $imageAlt        = trim($data['image_alt'] ?? '');
    $relatedPosts    = trim($data['related_posts'] ?? '');
    
    // Related products (JSON string or array)
    $relatedProducts = $data['related_products'] ?? '';
    if (is_array($relatedProducts)) {
        $relatedProducts = json_encode($relatedProducts, JSON_UNESCAPED_UNICODE);
    }

    $ctaType         = trim($data['cta_type'] ?? 'Book a Demo');
    $ctaHeading      = trim($data['cta_heading'] ?? '');
    $ctaDescription  = trim($data['cta_description'] ?? '');
    $ctaButtonText   = trim($data['cta_button_text'] ?? '');
    $ctaButtonUrl    = trim($data['cta_button_url'] ?? '');
    $ogTitle         = trim($data['og_title'] ?? $seoTitle);
    $ogDescription   = trim($data['og_description'] ?? $metaDescription);
    $publishDate     = trim($data['publish_date'] ?? '');
    if ($status === 'Published' && empty($publishDate)) {
        $publishDate = date('Y-m-d');
    }
    $publishTime     = trim($data['publish_time'] ?? '');
    
    $featuredRaw     = $data['featured_post'] ?? 0;
    $featuredPost    = ($featuredRaw === true || $featuredRaw === 'true' || $featuredRaw === 1 || $featuredRaw === '1') ? 1 : 0;

    return [
        'title'               => $title,
        'slug'                => $slug,
        'excerpt'             => $excerpt,
        'content'             => $content,
        'cover_image'         => $coverImage,
        'image_alt'           => $imageAlt,
        'author'              => $author,
        'category'            => $category,
        'tags'                => $tags,
        'seo_title'           => $seoTitle,
        'meta_description'    => $metaDescription,
        'focus_keyword'       => $focusKeyword,
        'secondary_keywords'  => $secKeywords,
        'canonical_url'       => $canonicalUrl,
        'robots_indexing'     => $robotsIndexing,
        'link_behavior'       => $linkBehavior,
        'direct_answer'       => $directAnswer,
        'key_takeaways'       => $keyTakeaways,
        'faq_items'           => $faqItems,
        'related_posts'       => $relatedPosts,
        'related_products'    => $relatedProducts,
        'cta_type'            => $ctaType,
        'cta_heading'         => $ctaHeading,
        'cta_description'     => $ctaDescription,
        'cta_button_text'     => $ctaButtonText,
        'cta_button_url'      => $ctaButtonUrl,
        'og_title'            => $ogTitle,
        'og_description'      => $ogDescription,
        'status'              => $status,
        'publish_date'        => $publishDate,
        'publish_time'        => $publishTime,
        'featured_post'       => $featuredPost,
        'published'           => $published,
    ];
}
