<?php
require_once 'db.php';

echo "<h1>Database Initialization</h1>";

try {
    echo "<p>Ensuring database `{$db}` exists...</p>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
    $pdo->exec("USE `$db`");

    echo "<p>Creating posts table...</p>";
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        excerpt TEXT,
        content LONGTEXT,
        cover_image VARCHAR(255),
        author VARCHAR(100) DEFAULT 'Admin',
        category VARCHAR(100) DEFAULT 'Updates',
        published BOOLEAN DEFAULT false,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      )
    ");

    // Upgrade existing table if it doesn't have the category column
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN category VARCHAR(100) DEFAULT 'Updates'");
        echo "<p>Database upgraded: added category column.</p>";
    } catch (\PDOException $e) {
        // Column probably already exists, ignore
    }

    echo "<p>Creating jobs table...</p>";
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        department VARCHAR(100) NOT NULL,
        type VARCHAR(100) NOT NULL,
        experience VARCHAR(100) DEFAULT '',
        description TEXT,
        requirements TEXT,
        status VARCHAR(50) DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      )
    ");

    try {
        $pdo->exec("ALTER TABLE jobs ADD COLUMN experience VARCHAR(100) DEFAULT ''");
        echo "<p>Database upgraded: added experience column to jobs.</p>";
    } catch (\PDOException $e) {
        // Column probably already exists, ignore
    }

    echo "<p>Creating applications table...</p>";
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS job_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        resume_link VARCHAR(255),
        status VARCHAR(50) DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    ");

    echo "<p>Creating gallery_photos table...</p>";
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS gallery_photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        status VARCHAR(50) DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      )
    ");

    echo "<p>Creating gallery_videos table...</p>";
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS gallery_videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        video_path VARCHAR(255) NOT NULL,
        status VARCHAR(50) DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      )
    ");

    echo "<h2 style='color: green;'>Database initialized successfully!</h2>";
    echo "<p>You can now use the backend.</p>";
} catch (\PDOException $e) {
    echo "<h2 style='color: red;'>Failed to initialize database:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>
