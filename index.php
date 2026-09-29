<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Check if PHP dropped POST data because it exceeded post_max_size (only for multipart/form-data)
$cType = $_SERVER['CONTENT_TYPE'] ?? '';
$isFormUpload = str_contains($cType, 'multipart/form-data') || str_contains($cType, 'application/x-www-form-urlencoded');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isFormUpload && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
    http_response_code(413); // Payload Too Large
    echo json_encode(['success' => false, 'message' => 'The uploaded file exceeds the maximum allowed size (post_max_size in php.ini). Please choose a smaller file.']);
    exit;
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$method = $_SERVER['REQUEST_METHOD'];
// Get the requested path from the "route" GET parameter (provided by .htaccess)
$route = isset($_GET['route']) ? trim($_GET['route'], '/') : '';

$pathParts = explode('/', $route);
$resource = $pathParts[0] ?? ''; // e.g., 'posts'
$param = $pathParts[1] ?? null;  // e.g., 'slug-name' or 'id'

// We handle 'posts', 'jobs', and 'applications' resources
if ($resource === '') {
    // Redirect to the blog UI
    header('Location: blog.php');
    exit;
}

if (!in_array($resource, ['posts', 'jobs', 'applications', 'gallery_photos', 'gallery_videos', 'categories', 'tags', 'analytics', 'users', 'products'])) {
    echo json_encode(['success' => false, 'message' => 'Unknown endpoint']);
    exit;
}

require_once __DIR__ . '/api/blog/helper.php';
ensurePostsSchema($pdo);

if ($resource === 'posts' && $method === 'GET') {
    if ($param) {
        // GET /posts/:slug_or_id
        $stmt = is_numeric($param)
            ? $pdo->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1')
            : $pdo->prepare('SELECT * FROM posts WHERE slug = ? LIMIT 1');
        $stmt->execute([$param]);
        $post = $stmt->fetch();
        
        if ($post) {
            echo json_encode(['success' => true, 'data' => $post]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post not found']);
        }
    } else {
        // GET /posts
        $publishedOnly = isset($_GET['publishedOnly']) && $_GET['publishedOnly'] === 'true';
        $query = 'SELECT * FROM posts';
        if ($publishedOnly) {
            $query .= ' WHERE published = 1';
        }
        $query .= ' ORDER BY created_at DESC';
        
        $stmt = $pdo->query($query);
        $posts = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $posts]);
    }
} elseif ($resource === 'posts' && $method === 'POST') {
    // Check if this is a quick status update on an existing post
    if ($param) {
        $raw = getApiRequestBody();
        if ((isset($raw['status']) || isset($raw['post_status']) || isset($raw['published'])) && !isset($raw['title'])) {
            $chk = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
            $chk->execute([$param]);
            $existingPost = $chk->fetch();
            if (!$existingPost) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Post not found']);
                exit;
            }
            $newStatus = trim($raw['status'] ?? $raw['post_status'] ?? ($raw['published'] == '1' ? 'Published' : 'Draft'));
            $newPublished = ($newStatus === 'Published' || ($raw['published'] ?? '') === '1' || ($raw['published'] ?? '') === true || ($raw['published'] ?? '') === 1) ? 1 : 0;
            $newPublishDate = !empty($raw['publish_date']) ? $raw['publish_date'] : ($newPublished ? date('Y-m-d') : $existingPost['publish_date']);

            $upd = $pdo->prepare("UPDATE posts SET status = ?, published = ?, publish_date = ? WHERE id = ?");
            $upd->execute([$newStatus, $newPublished, $newPublishDate, $param]);
            echo json_encode(['success' => true, 'message' => 'Post status updated successfully']);
            exit;
        }
    }

    $postData = parseBlogPostPayload();

    try {
        if ($param) {
            // Update existing post
            if (empty($postData['cover_image'])) {
                $chk = $pdo->prepare("SELECT cover_image FROM posts WHERE id = ?");
                $chk->execute([$param]);
                $existingCover = $chk->fetchColumn();
                if ($existingCover) {
                    $postData['cover_image'] = $existingCover;
                }
            }

            $sql = "UPDATE posts SET
                title = :title, slug = :slug, excerpt = :excerpt, content = :content,
                cover_image = :cover_image, image_alt = :image_alt,
                author = :author, category = :category, tags = :tags,
                seo_title = :seo_title, meta_description = :meta_description,
                focus_keyword = :focus_keyword, secondary_keywords = :secondary_keywords,
                canonical_url = :canonical_url, robots_indexing = :robots_indexing, link_behavior = :link_behavior,
                direct_answer = :direct_answer, key_takeaways = :key_takeaways, faq_items = :faq_items,
                related_posts = :related_posts, related_products = :related_products,
                cta_type = :cta_type, cta_heading = :cta_heading, cta_description = :cta_description,
                cta_button_text = :cta_button_text, cta_button_url = :cta_button_url,
                og_title = :og_title, og_description = :og_description,
                status = :status, publish_date = :publish_date, publish_time = :publish_time,
                featured_post = :featured_post, published = :published
            WHERE id = :id";

            $stmt = $pdo->prepare($sql);
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
                ':id'                 => $param,
            ]);
            echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
        } else {
            // Insert new post
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
            $stmt = $pdo->prepare($sql);
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
            echo json_encode(['success' => true, 'message' => 'Post created successfully', 'id' => $pdo->lastInsertId()]);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save post: ' . $e->getMessage()]);
    }
} elseif ($method === 'DELETE') {
    // DELETE /posts/:id, /jobs/:id
    if ($param) {
        try {
            $table = 'posts';
            if ($resource === 'jobs') $table = 'jobs';
            if ($resource === 'applications') $table = 'job_applications';
            if ($resource === 'gallery_photos') $table = 'gallery_photos';
            if ($resource === 'gallery_videos') $table = 'gallery_videos';
            if ($resource === 'categories') $table = 'blog_categories';
            if ($resource === 'tags') $table = 'blog_tags';
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
            $stmt->execute([$param]);
            echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete record: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID required for deletion']);
    }
}
// Additional handlers for jobs and applications (GET/POST)
if ($resource === 'jobs' && $method === 'GET') {
    try {
        if ($param) {
            $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ? LIMIT 1');
            $stmt->execute([$param]);
            $job = $stmt->fetch();
            if ($job) {
                echo json_encode(['success' => true, 'data' => $job]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Job not found']);
            }
        } else {
            $activeOnly = isset($_GET['activeOnly']) && $_GET['activeOnly'] === 'true';
            $query = 'SELECT * FROM jobs';
            if ($activeOnly) {
                $query .= " WHERE status = 'Active'";
            }
            $query .= ' ORDER BY created_at DESC';
            $stmt = $pdo->query($query);
            $jobs = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $jobs]);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
} elseif ($resource === 'jobs' && $method === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?: $_POST;
    
    $title = $data['title'] ?? '';
    $department = $data['department'] ?? '';
    $type = $data['type'] ?? '';
    $experience = $data['experience'] ?? '';
    $description = $data['description'] ?? '';
    $requirements = $data['requirements'] ?? '';
    $status = $data['status'] ?? 'Active';
    
    try {
        if ($param) {
            $stmt = $pdo->prepare('UPDATE jobs SET title = ?, department = ?, type = ?, experience = ?, description = ?, requirements = ?, status = ? WHERE id = ?');
            $stmt->execute([$title, $department, $type, $experience, $description, $requirements, $status, $param]);
            echo json_encode(['success' => true, 'message' => 'Job updated']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO jobs (title, department, type, experience, description, requirements, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $department, $type, $experience, $description, $requirements, $status]);
            echo json_encode(['success' => true, 'message' => 'Job created']);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($resource === 'applications' && $method === 'GET') {
    try {
        $query = 'SELECT ja.*, j.title as job_title FROM job_applications ja LEFT JOIN jobs j ON ja.job_id = j.id ORDER BY ja.created_at DESC';
        $stmt = $pdo->query($query);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
} elseif ($resource === 'applications' && $method === 'POST') {
    if (isset($_GET['update_status']) && $param) {
        // Admin updating status
        $input = file_get_contents('php://input');
        $data = json_decode($input, true) ?: $_POST;
        $status = $data['status'] ?? 'Pending';
        try {
            $stmt = $pdo->prepare('UPDATE job_applications SET status = ? WHERE id = ?');
            $stmt->execute([$status, $param]);
            
            // Send email if Selected or Rejected
            if ($status === 'Selected' || $status === 'Rejected') {
                $stmt = $pdo->prepare('SELECT ja.name, ja.email, j.title as job_title FROM job_applications ja LEFT JOIN jobs j ON ja.job_id = j.id WHERE ja.id = ?');
                $stmt->execute([$param]);
                $app = $stmt->fetch();
                
                if ($app && $app['email']) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'mercurysoftech@gmail.com';
                        $mail->Password   = 'zcjn jvjz iavi azed'; // App password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('mercurysoftech@gmail.com', 'Mercury Softech HR');
                        $mail->addAddress($app['email'], $app['name']);
                        
                        $mail->isHTML(true);
                        $jobTitle = htmlspecialchars($app['job_title'] ?: 'a position');
                        $applicantName = htmlspecialchars($app['name']);
                        
                        if ($status === 'Selected') {
                            $mail->Subject = 'Update on your application for ' . $jobTitle . ' at Mercury Softech';
                            $mail->Body    = "
                                <h3>Dear {$applicantName},</h3>
                                <p>Congratulations! We are pleased to inform you that your profile has been <strong>Selected</strong> for the <strong>{$jobTitle}</strong> position at Mercury Softech.</p>
                                <p>Our HR team will be in touch with you shortly to discuss the next steps.</p>
                                <br>
                                <p>Best regards,</p>
                                <p><strong>Mercury Softech HR Team</strong></p>
                            ";
                        } else {
                            $mail->Subject = 'Update on your application for ' . $jobTitle . ' at Mercury Softech';
                            $mail->Body    = "
                                <h3>Dear {$applicantName},</h3>
                                <p>Thank you for taking the time to apply for the <strong>{$jobTitle}</strong> position at Mercury Softech.</p>
                                <p>While we were impressed with your background, we have decided to move forward with other candidates whose qualifications more closely align with our current needs for this role.</p>
                                <p>We appreciate your interest in our company and wish you the best in your career endeavors.</p>
                                <br>
                                <p>Best regards,</p>
                                <p><strong>Mercury Softech HR Team</strong></p>
                            ";
                        }
                        $mail->send();
                    } catch (Exception $e) {
                        // Silent fail for email errors
                    }
                }
            }

            echo json_encode(['success' => true, 'message' => 'Status updated']);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // Frontend submitting an application
    $job_id = $_POST['job_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Handle File Upload (Resume)
    $resume_link = '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/resumes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExtension = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        $fileName = uniqid('resume_') . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $uploadPath)) {
            $resume_link = 'uploads/resumes/' . $fileName;
        }
    }
    
    try {
        $stmt = $pdo->prepare('INSERT INTO job_applications (job_id, name, email, phone, resume_link, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$job_id, $name, $email, $phone, $resume_link, 'Pending']);
        echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to submit application']);
    }
    exit;
}

// Handlers for gallery_photos
if ($resource === 'gallery_photos' && $method === 'GET') {
    try {
        if ($param) {
            $stmt = $pdo->prepare('SELECT * FROM gallery_photos WHERE id = ? LIMIT 1');
            $stmt->execute([$param]);
            $photo = $stmt->fetch();
            if ($photo) {
                echo json_encode(['success' => true, 'data' => $photo]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Photo not found']);
            }
        } else {
            $activeOnly = isset($_GET['activeOnly']) && $_GET['activeOnly'] === 'true';
            $query = 'SELECT * FROM gallery_photos';
            if ($activeOnly) {
                $query .= " WHERE status = 'Active'";
            }
            $query .= ' ORDER BY created_at DESC';
            $stmt = $pdo->query($query);
            $photos = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $photos]);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
} elseif ($resource === 'gallery_photos' && $method === 'POST') {
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $status = $_POST['status'] ?? 'Active';
    $existingPath = $_POST['existingPath'] ?? '';
    $imagePath = $existingPath;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/gallery_photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $fileName = uniqid('photo_') . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
            $imagePath = 'uploads/gallery_photos/' . $fileName;
        }
    }

    try {
        if ($param) {
            $stmt = $pdo->prepare('UPDATE gallery_photos SET title = ?, category = ?, image_path = ?, status = ? WHERE id = ?');
            $stmt->execute([$title, $category, $imagePath, $status, $param]);
            echo json_encode(['success' => true, 'message' => 'Photo updated']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO gallery_photos (title, category, image_path, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$title, $category, $imagePath, $status]);
            echo json_encode(['success' => true, 'message' => 'Photo created']);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handlers for gallery_videos
if ($resource === 'gallery_videos' && $method === 'GET') {
    try {
        if ($param) {
            $stmt = $pdo->prepare('SELECT * FROM gallery_videos WHERE id = ? LIMIT 1');
            $stmt->execute([$param]);
            $video = $stmt->fetch();
            if ($video) {
                echo json_encode(['success' => true, 'data' => $video]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Video not found']);
            }
        } else {
            $activeOnly = isset($_GET['activeOnly']) && $_GET['activeOnly'] === 'true';
            $query = 'SELECT * FROM gallery_videos';
            if ($activeOnly) {
                $query .= " WHERE status = 'Active'";
            }
            $query .= ' ORDER BY created_at DESC';
            $stmt = $pdo->query($query);
            $videos = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $videos]);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
} elseif ($resource === 'gallery_videos' && $method === 'POST') {
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $status = $_POST['status'] ?? 'Active';
    $existingPath = $_POST['existingPath'] ?? '';
    $videoPath = $existingPath;

    if (isset($_FILES['chunk'])) {
        $chunkIndex = (int)$_POST['chunkIndex'];
        $totalChunks = (int)$_POST['totalChunks'];
        $uploadId = $_POST['uploadId'];
        $originalFileName = $_POST['fileName'];
        
        $tempUploadDir = __DIR__ . '/uploads/temp_videos/';
        if (!is_dir($tempUploadDir)) {
            mkdir($tempUploadDir, 0777, true);
        }
        
        $tempFilePath = $tempUploadDir . 'temp_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $uploadId);
        
        $out = @fopen($tempFilePath, $chunkIndex == 0 ? "wb" : "ab");
        if ($out) {
            $in = @fopen($_FILES['chunk']['tmp_name'], "rb");
            if ($in) {
                while ($buff = fread($in, 4096)) {
                    fwrite($out, $buff);
                }
                fclose($in);
            }
            fclose($out);
        }
        
        if ($chunkIndex == $totalChunks - 1) {
            // Final chunk
            $uploadDir = __DIR__ . '/uploads/gallery_videos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
            $fileName = uniqid('video_') . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            rename($tempFilePath, $uploadPath);
            $videoPath = 'uploads/gallery_videos/' . $fileName;
        } else {
            // Not final chunk
            echo json_encode(['success' => true, 'status' => 'chunk_saved']);
            exit;
        }
    } else if (isset($_FILES['file'])) {
        // Fallback for regular non-chunked file upload
        if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/gallery_videos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $fileName = uniqid('video_') . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
                $videoPath = 'uploads/gallery_videos/' . $fileName;
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to move uploaded video file.']);
                exit;
            }
        }
    }

    try {
        if ($param) {
            $stmt = $pdo->prepare('UPDATE gallery_videos SET title = ?, category = ?, video_path = ?, status = ? WHERE id = ?');
            $stmt->execute([$title, $category, $videoPath, $status, $param]);
            echo json_encode(['success' => true, 'message' => 'Video updated']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO gallery_videos (title, category, video_path, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$title, $category, $videoPath, $status]);
            echo json_encode(['success' => true, 'message' => 'Video created']);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Helper: Ensure blog_categories table
function ensureBlogCategoriesTable($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $count = $pdo->query("SELECT COUNT(*) FROM blog_categories")->fetchColumn();
        if ($count == 0) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO blog_categories (name, slug) VALUES (?, ?)");
            $defaults = [
                ['AI & Automation', 'ai-automation'],
                ['WhatsApp', 'whatsapp'],
                ['SaaS', 'saas'],
                ['Web Development', 'web-development'],
                ['Business Solutions', 'business-solutions']
            ];
            foreach ($defaults as $d) {
                $stmt->execute($d);
            }
        }
    } catch (\Throwable $e) {}
}

// Helper: Ensure blog_tags table
function ensureBlogTagsTable($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $count = $pdo->query("SELECT COUNT(*) FROM blog_tags")->fetchColumn();
        if ($count == 0) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO blog_tags (name, slug) VALUES (?, ?)");
            $defaults = [
                ['Artificial Intelligence', 'artificial-intelligence'],
                ['Automation', 'automation'],
                ['CRM', 'crm'],
                ['Cloud Software', 'cloud-software'],
                ['Web Tech', 'web-tech']
            ];
            foreach ($defaults as $d) {
                $stmt->execute($d);
            }
        }
    } catch (\Throwable $e) {}
}

// Categories Endpoint
if ($resource === 'categories') {
    ensureBlogCategoriesTable($pdo);
    if ($method === 'GET') {
        try {
            $stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM posts WHERE posts.category = c.name) as post_count FROM blog_categories c ORDER BY c.name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    } elseif ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true) ?: $_POST;
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '') ?: strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
        
        if (!$name) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Category name is required']);
            exit;
        }
        try {
            if ($param) {
                $stmt = $pdo->prepare("UPDATE blog_categories SET name = ?, slug = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $param]);
                echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO blog_categories (name, slug) VALUES (?, ?)");
                $stmt->execute([$name, $slug]);
                echo json_encode(['success' => true, 'message' => 'Category created successfully', 'id' => $pdo->lastInsertId()]);
            }
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    } elseif ($method === 'DELETE') {
        $catId = $param ?: ($_GET['id'] ?? null);
        if (!$catId) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            $catId = $data['id'] ?? null;
        }
        if (!$catId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Category ID is required']);
            exit;
        }
        try {
            // Get category name
            $nameStmt = $pdo->prepare("SELECT name FROM blog_categories WHERE id = ?");
            $nameStmt->execute([$catId]);
            $catName = $nameStmt->fetchColumn();

            if ($catName) {
                // Reassign posts to 'Uncategorized'
                $pdo->prepare("UPDATE posts SET category = 'Uncategorized' WHERE category = ?")->execute([$catName]);
            }

            $stmt = $pdo->prepare("DELETE FROM blog_categories WHERE id = ?");
            $stmt->execute([$catId]);
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Tags Endpoint
if ($resource === 'tags') {
    ensureBlogTagsTable($pdo);
    if ($method === 'GET') {
        try {
            $stmt = $pdo->query("SELECT * FROM blog_tags ORDER BY name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    } elseif ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true) ?: $_POST;
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? '') ?: strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
        
        if (!$name) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tag name is required']);
            exit;
        }
        try {
            if ($param) {
                $stmt = $pdo->prepare("UPDATE blog_tags SET name = ?, slug = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $param]);
                echo json_encode(['success' => true, 'message' => 'Tag updated successfully']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO blog_tags (name, slug) VALUES (?, ?)");
                $stmt->execute([$name, $slug]);
                echo json_encode(['success' => true, 'message' => 'Tag created successfully', 'id' => $pdo->lastInsertId()]);
            }
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    } elseif ($method === 'DELETE') {
        $tagId = $param ?: ($_GET['id'] ?? null);
        if (!$tagId) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            $tagId = $data['id'] ?? null;
        }
        if (!$tagId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tag ID is required']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM blog_tags WHERE id = ?");
            $stmt->execute([$tagId]);
            echo json_encode(['success' => true, 'message' => 'Tag deleted successfully']);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Analytics Endpoint
if ($resource === 'analytics' && $method === 'GET') {
    try {
        $totalPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        $publishedPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE published = 1")->fetchColumn();
        $draftPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE published = 0")->fetchColumn();
        
        $totalViews = 0;
        try {
            $totalViews = (int)$pdo->query("SELECT COALESCE(SUM(views), 0) FROM posts")->fetchColumn();
        } catch (\Throwable $e) {
            $totalViews = 0;
        }

        $topPosts = [];
        try {
            $stmt = $pdo->query("SELECT id, title, slug, views, category, published, created_at FROM posts ORDER BY views DESC, id DESC LIMIT 5");
            $topPosts = $stmt->fetchAll();
        } catch (\Throwable $e) {
            $stmt = $pdo->query("SELECT id, title, slug, category, published, created_at FROM posts ORDER BY id DESC LIMIT 5");
            $topPosts = $stmt->fetchAll();
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'total_posts' => $totalPosts,
                'published' => $publishedPosts,
                'drafts' => $draftPosts,
                'total_views' => $totalViews,
                'top_posts' => $topPosts
            ]
        ]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Users Endpoint
if ($resource === 'users') {
    require_once __DIR__ . '/api/user/helper.php';
    ensureUsersSchema($pdo);

    if ($method === 'GET') {
        if ($param) {
            $_GET['id'] = $param;
            require __DIR__ . '/api/user/get.php';
            exit;
        }
        require __DIR__ . '/api/user/list.php';
        exit;
    } elseif ($method === 'POST') {
        if ($param) {
            $_GET['id'] = $param;
            require __DIR__ . '/api/user/update.php';
            exit;
        }
        require __DIR__ . '/api/user/create.php';
        exit;
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        if ($param) {
            $_GET['id'] = $param;
        }
        require __DIR__ . '/api/user/update.php';
        exit;
    } elseif ($method === 'DELETE') {
        if ($param) {
            $_GET['id'] = $param;
        }
        require __DIR__ . '/api/user/delete.php';
        exit;
    }
}

// Products Endpoint
if ($resource === 'products') {
    require_once __DIR__ . '/api/product/helper.php';
    ensureProductsSchema($pdo);

    if ($method === 'GET') {
        if ($param) {
            $_GET['id'] = $param;
            require __DIR__ . '/api/product/get.php';
            exit;
        }
        require __DIR__ . '/api/product/list.php';
        exit;
    } elseif ($method === 'POST') {
        if ($param) {
            $_GET['id'] = $param;
            require __DIR__ . '/api/product/update.php';
            exit;
        }
        require __DIR__ . '/api/product/create.php';
        exit;
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        if ($param) {
            $_GET['id'] = $param;
        }
        require __DIR__ . '/api/product/update.php';
        exit;
    } elseif ($method === 'DELETE') {
        if ($param) {
            $_GET['id'] = $param;
        }
        require __DIR__ . '/api/product/delete.php';
        exit;
    }
}
?>
