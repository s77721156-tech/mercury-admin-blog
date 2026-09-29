<?php
/**
 * Mercury Admin - Save / Autosave Blog Post Draft API
 * 
 * Supports both manual "Save Draft" button and periodic background autosaving.
 * If ID is provided, updates existing draft. If not, inserts a new draft.
 * 
 * Method: POST (JSON or multipart/form-data)
 * URL: /admin1/api/blog/save-draft.php
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensurePostsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

$rawBody = getApiRequestBody();
$id = $_GET['id'] ?? $rawBody['id'] ?? null;
$id = (int)$id;

// Allow untitled drafts with fallback title for autosave
if (empty($rawBody['title'])) {
    $rawBody['title'] = 'Untitled Draft ' . date('M j, Y H:i');
}
$rawBody['status'] = 'Draft';
$rawBody['published'] = 0;

$postData = parseBlogPostPayload($rawBody);

try {
    if ($id > 0) {
        // Verify existence
        $check = $db->prepare("SELECT id, cover_image FROM posts WHERE id = ? LIMIT 1");
        $check->execute([$id]);
        $existing = $check->fetch();

        if ($existing) {
            if (empty($postData['cover_image']) && !empty($existing['cover_image'])) {
                $postData['cover_image'] = $existing['cover_image'];
            }

            $sql = "UPDATE posts SET
                title = :title, slug = :slug, excerpt = :excerpt, content = :content,
                cover_image = :cover_image, image_alt = :image_alt, author = :author, category = :category,
                tags = :tags, seo_title = :seo_title, meta_description = :meta_description,
                focus_keyword = :focus_keyword, secondary_keywords = :secondary_keywords,
                canonical_url = :canonical_url, robots_indexing = :robots_indexing, link_behavior = :link_behavior,
                direct_answer = :direct_answer, key_takeaways = :key_takeaways, faq_items = :faq_items,
                related_posts = :related_posts, related_products = :related_products,
                cta_type = :cta_type, cta_heading = :cta_heading, cta_description = :cta_description,
                cta_button_text = :cta_button_text, cta_button_url = :cta_button_url,
                og_title = :og_title, og_description = :og_description,
                status = 'Draft', published = 0
            WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':title'              => $postData['title'],
                ':slug'               => $postData['slug'],
                ':excerpt'            => $postData['excerpt'],
                ':content'            => $postData['content'],
                ':cover_image'        => $postData['cover_image'],
                ':image_alt'          => $postData['image_alt'],
                ':author'             => $postData['author'],
                ':category'           => $postData['category'],
                ':tags'               => $postData['tags'],
                ':seo_title'          => $postData['seo_title'],
                ':meta_description'   => $postData['meta_description'],
                ':focus_keyword'      => $postData['focus_keyword'],
                ':secondary_keywords' => $postData['secondary_keywords'],
                ':canonical_url'      => $postData['canonical_url'],
                ':robots_indexing'    => $postData['robots_indexing'],
                ':link_behavior'      => $postData['link_behavior'],
                ':direct_answer'      => $postData['direct_answer'],
                ':key_takeaways'      => $postData['key_takeaways'],
                ':faq_items'          => $postData['faq_items'],
                ':related_posts'      => $postData['related_posts'],
                ':related_products'   => $postData['related_products'],
                ':cta_type'           => $postData['cta_type'],
                ':cta_heading'        => $postData['cta_heading'],
                ':cta_description'    => $postData['cta_description'],
                ':cta_button_text'    => $postData['cta_button_text'],
                ':cta_button_url'     => $postData['cta_button_url'],
                ':og_title'           => $postData['og_title'],
                ':og_description'     => $postData['og_description'],
                ':id'                 => $id,
            ]);

            sendJsonSuccess([
                'id'         => $id,
                'status'     => 'Draft',
                'saved_at'   => date('H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], "Draft updated successfully");
        }
    }

    // Insert new draft
    $slugCheck = $db->prepare("SELECT id FROM posts WHERE slug = ? LIMIT 1");
    $slugCheck->execute([$postData['slug']]);
    if ($slugCheck->fetch()) {
        $postData['slug'] .= '-draft-' . time();
    }

    $sql = "INSERT INTO posts (
        title, slug, excerpt, content, cover_image, image_alt,
        author, category, tags,
        seo_title, meta_description, focus_keyword, secondary_keywords,
        canonical_url, robots_indexing, link_behavior,
        direct_answer, key_takeaways, faq_items,
        related_posts, related_products,
        cta_type, cta_heading, cta_description, cta_button_text, cta_button_url,
        og_title, og_description,
        status, published, views
    ) VALUES (
        :title, :slug, :excerpt, :content, :cover_image, :image_alt,
        :author, :category, :tags,
        :seo_title, :meta_description, :focus_keyword, :secondary_keywords,
        :canonical_url, :robots_indexing, :link_behavior,
        :direct_answer, :key_takeaways, :faq_items,
        :related_posts, :related_products,
        :cta_type, :cta_heading, :cta_description, :cta_button_text, :cta_button_url,
        :og_title, :og_description,
        'Draft', 0, 0
    )";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':title'              => $postData['title'],
        ':slug'               => $postData['slug'],
        ':excerpt'            => $postData['excerpt'],
        ':content'            => $postData['content'],
        ':cover_image'        => $postData['cover_image'],
        ':image_alt'          => $postData['image_alt'],
        ':author'             => $postData['author'],
        ':category'           => $postData['category'],
        ':tags'               => $postData['tags'],
        ':seo_title'          => $postData['seo_title'],
        ':meta_description'   => $postData['meta_description'],
        ':focus_keyword'      => $postData['focus_keyword'],
        ':secondary_keywords' => $postData['secondary_keywords'],
        ':canonical_url'      => $postData['canonical_url'],
        ':robots_indexing'    => $postData['robots_indexing'],
        ':link_behavior'      => $postData['link_behavior'],
        ':direct_answer'      => $postData['direct_answer'],
        ':key_takeaways'      => $postData['key_takeaways'],
        ':faq_items'          => $postData['faq_items'],
        ':related_posts'      => $postData['related_posts'],
        ':related_products'   => $postData['related_products'],
        ':cta_type'           => $postData['cta_type'],
        ':cta_heading'        => $postData['cta_heading'],
        ':cta_description'    => $postData['cta_description'],
        ':cta_button_text'    => $postData['cta_button_text'],
        ':cta_button_url'     => $postData['cta_button_url'],
        ':og_title'           => $postData['og_title'],
        ':og_description'     => $postData['og_description'],
    ]);

    $newId = (int)$db->lastInsertId();

    sendJsonSuccess([
        'id'         => $newId,
        'status'     => 'Draft',
        'saved_at'   => date('H:i:s'),
        'created_at' => date('Y-m-d H:i:s'),
    ], "Draft created successfully", 201);

} catch (\PDOException $e) {
    sendJsonError("Database error saving draft: " . $e->getMessage(), 500);
}
