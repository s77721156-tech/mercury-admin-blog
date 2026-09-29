<?php
/**
 * Mercury Admin - Update Blog Post API
 * 
 * Updates an existing blog post across all 3-step wizard fields.
 * 
 * Method: POST or PUT (JSON or multipart/form-data)
 * URL: /admin1/api/blog/update.php?id=123
 */

require_once __DIR__ . '/helper.php';

try {
    $db = getDbConnection();
    ensurePostsSchema($db);
} catch (\Throwable $e) {
    sendJsonError("Database unavailable: " . $e->getMessage(), 500);
}

// Determine Post ID
$id = $_GET['id'] ?? null;
$rawBody = getApiRequestBody();
if (!$id && isset($rawBody['id'])) {
    $id = $rawBody['id'];
}
$id = (int)$id;

if ($id <= 0) {
    sendJsonError("A valid post ID is required for update.", 400);
}

// Verify post exists
$checkStmt = $db->prepare("SELECT * FROM posts WHERE id = ? LIMIT 1");
$checkStmt->execute([$id]);
$existingPost = $checkStmt->fetch();
if (!$existingPost) {
    sendJsonError("Blog post not found with ID {$id}.", 404);
}

// Check if this is a quick status update
if ((isset($rawBody['status']) || isset($rawBody['post_status']) || isset($rawBody['published'])) && !isset($rawBody['title'])) {
    $newStatus = trim($rawBody['status'] ?? $rawBody['post_status'] ?? ($rawBody['published'] == '1' ? 'Published' : 'Draft'));
    $newPublished = ($newStatus === 'Published' || ($rawBody['published'] ?? '') === '1' || ($rawBody['published'] ?? '') === true || ($rawBody['published'] ?? '') === 1) ? 1 : 0;
    $newPublishDate = !empty($rawBody['publish_date']) ? $rawBody['publish_date'] : ($newPublished ? date('Y-m-d') : $existingPost['publish_date']);

    $upd = $db->prepare("UPDATE posts SET status = ?, published = ?, publish_date = ? WHERE id = ?");
    $upd->execute([$newStatus, $newPublished, $newPublishDate, $id]);
    sendJsonSuccess(['id' => $id, 'status' => $newStatus, 'published' => $newPublished, 'publish_date' => $newPublishDate], 'Post status updated successfully');
}

// Parse incoming fields
$postData = parseBlogPostPayload($rawBody);

// Retain previous cover image if new one wasn't uploaded/provided
if (empty($postData['cover_image']) && !empty($existingPost['cover_image'])) {
    $postData['cover_image'] = $existingPost['cover_image'];
}

// Check slug uniqueness against other posts
$slugCheckStmt = $db->prepare("SELECT id FROM posts WHERE slug = ? AND id != ? LIMIT 1");
$slugCheckStmt->execute([$postData['slug'], $id]);
if ($slugCheckStmt->fetch()) {
    $postData['slug'] .= '-' . time();
}

try {
    $sql = "UPDATE posts SET
        title = :title,
        slug = :slug,
        excerpt = :excerpt,
        content = :content,
        cover_image = :cover_image,
        image_alt = :image_alt,
        author = :author,
        category = :category,
        tags = :tags,
        seo_title = :seo_title,
        meta_description = :meta_description,
        focus_keyword = :focus_keyword,
        secondary_keywords = :secondary_keywords,
        canonical_url = :canonical_url,
        robots_indexing = :robots_indexing,
        link_behavior = :link_behavior,
        direct_answer = :direct_answer,
        key_takeaways = :key_takeaways,
        faq_items = :faq_items,
        related_posts = :related_posts,
        related_products = :related_products,
        cta_type = :cta_type,
        cta_heading = :cta_heading,
        cta_description = :cta_description,
        cta_button_text = :cta_button_text,
        cta_button_url = :cta_button_url,
        og_title = :og_title,
        og_description = :og_description,
        status = :status,
        publish_date = :publish_date,
        publish_time = :publish_time,
        featured_post = :featured_post,
        published = :published
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
        ':status'             => $postData['status'],
        ':publish_date'       => $postData['publish_date'],
        ':publish_time'       => $postData['publish_time'],
        ':featured_post'      => $postData['featured_post'],
        ':published'          => $postData['published'],
        ':id'                 => $id,
    ]);

    // Fetch updated post
    $fetchStmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $fetchStmt->execute([$id]);
    $updatedPost = $fetchStmt->fetch();

    sendJsonSuccess($updatedPost, "Blog post updated successfully");

} catch (\PDOException $e) {
    sendJsonError("Database error while updating post: " . $e->getMessage(), 500);
}
