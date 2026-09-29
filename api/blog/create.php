<?php
/**
 * Mercury Admin - Create Blog Post API
 * 
 * Creates a new blog post supporting all fields across:
 * Step 1: Content (Title, Slug, Category, Author, Tags, Cover Image, Alt, Excerpt, Content)
 * Step 2: SEO & AI Search (SEO Title, Meta Description, Keywords, Canonical, Robots, Direct Answer, FAQs)
 * Step 3: Publish & Distribution (Related Posts, Related Products, CTA, OG Tags, Publishing status, Dates)
 * 
 * Method: POST (JSON or multipart/form-data)
 * URL: /admin1/api/blog/create.php
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensurePostsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

// Parse & Validate incoming fields
$postData = parseBlogPostPayload();

// Check for slug uniqueness
$slugCheckStmt = $db->prepare("SELECT id FROM posts WHERE slug = ? LIMIT 1");
$slugCheckStmt->execute([$postData['slug']]);
if ($slugCheckStmt->fetch()) {
    // Append timestamp suffix to make slug unique
    $postData['slug'] .= '-' . time();
}

try {
    $sql = "INSERT INTO posts (
        title, slug, excerpt, content, cover_image, image_alt,
        author, category, tags,
        seo_title, meta_description, focus_keyword, secondary_keywords,
        canonical_url, robots_indexing, link_behavior,
        direct_answer, key_takeaways, faq_items,
        related_posts, related_products,
        cta_type, cta_heading, cta_description, cta_button_text, cta_button_url,
        og_title, og_description,
        status, publish_date, publish_time, featured_post, published, views
    ) VALUES (
        :title, :slug, :excerpt, :content, :cover_image, :image_alt,
        :author, :category, :tags,
        :seo_title, :meta_description, :focus_keyword, :secondary_keywords,
        :canonical_url, :robots_indexing, :link_behavior,
        :direct_answer, :key_takeaways, :faq_items,
        :related_posts, :related_products,
        :cta_type, :cta_heading, :cta_description, :cta_button_text, :cta_button_url,
        :og_title, :og_description,
        :status, :publish_date, :publish_time, :featured_post, :published, 0
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
        ':status'             => $postData['status'],
        ':publish_date'       => $postData['publish_date'],
        ':publish_time'       => $postData['publish_time'],
        ':featured_post'      => $postData['featured_post'],
        ':published'          => $postData['published'],
    ]);

    $newId = (int)$db->lastInsertId();

    // Fetch newly created post
    $fetchStmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $fetchStmt->execute([$newId]);
    $createdPost = $fetchStmt->fetch();

    sendJsonSuccess($createdPost, "Blog post created successfully", 201);

} catch (\PDOException $e) {
    sendJsonError("Database error while creating post: " . $e->getMessage(), 500);
}
