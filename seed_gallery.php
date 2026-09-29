<?php
require_once 'db.php';

echo "<h1>Seeding Database with Original Gallery Images</h1>";

$images = [
    ['title' => 'Team Discussion', 'category' => 'Team meeting', 'src' => '../assets/gallery/meeting1.jpeg'],
    ['title' => 'Collaborative Session', 'category' => 'Team meeting', 'src' => '../assets/gallery/meeting2.jpeg'],
    ['title' => 'Team Sync', 'category' => 'Team meeting', 'src' => '../assets/gallery/meeting3.jpeg'],
    ['title' => 'Brainstorming Session', 'category' => 'Team meeting', 'src' => '../assets/gallery/meeting4.jpeg'],
    ['title' => 'Review Meeting', 'category' => 'Team meeting', 'src' => '../assets/gallery/meeting5.jpeg'],
    ['title' => 'Strategy Talk', 'category' => 'Team meeting', 'src' => '../assets/gallery/meeting6.jpeg'],
    ['title' => 'Team Workshop', 'category' => 'Team meeting', 'src' => '../assets/gallery/meeting7.jpeg'],
    ['title' => 'Final Coordination', 'category' => 'Team meeting', 'src' => '../assets/gallery/meeting8.jpeg'],
    ['title' => 'Client Visit', 'category' => 'Client Visit', 'src' => '../assets/gallery/client1.jpeg'],
    ['title' => 'Client Interaction', 'category' => 'Client Visit', 'src' => '../assets/gallery/client2.jpeg'],
    ['title' => 'Client Presentation', 'category' => 'Client Visit', 'src' => '../assets/gallery/client3.jpeg'],
    ['title' => 'Birthday Celebration 1', 'category' => 'Celebration', 'src' => '../assets/gallery/bday1.jpeg'],
    ['title' => 'Birthday Celebration 2', 'category' => 'Celebration', 'src' => '../assets/gallery/bday2.jpeg'],
    ['title' => 'Birthday Celebration 3', 'category' => 'Celebration', 'src' => '../assets/gallery/bday3.jpeg'],
    ['title' => 'Birthday Celebration 4', 'category' => 'Celebration', 'src' => '../assets/gallery/bday4.jpeg'],
    ['title' => 'Team Achievement', 'category' => 'Celebration', 'src' => '../assets/gallery/celebration1.jpeg'],
    ['title' => 'Office Gathering', 'category' => 'Celebration', 'src' => '../assets/gallery/festival2.jpeg'],
    ['title' => 'Birthday Celebration 5', 'category' => 'Celebration', 'src' => '../assets/gallery/bday5.jpeg'],
    ['title' => 'Birthday Celebration 6', 'category' => 'Celebration', 'src' => '../assets/gallery/bday6.jpeg'],
    ['title' => 'Festival Celebration 1', 'category' => 'festival', 'src' => '../assets/gallery/festival1.jpeg'],
    ['title' => 'Festival Celebration 2', 'category' => 'festival', 'src' => '../assets/gallery/festival3.jpeg'],
    ['title' => 'Festival Celebration 3', 'category' => 'festival', 'src' => '../assets/gallery/festival4.jpeg']
];

try {
    foreach ($images as $img) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM gallery_photos WHERE image_path = ?');
        $stmt->execute([$img['src']]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare('INSERT INTO gallery_photos (title, category, image_path, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$img['title'], $img['category'], $img['src'], 'Active']);
            echo "<p style='color: green;'>Inserted: " . htmlspecialchars($img['title']) . "</p>";
        } else {
            echo "<p style='color: gray;'>Skipped (already exists): " . htmlspecialchars($img['title']) . "</p>";
        }
    }
    echo "<h2>Done importing static images!</h2>";
    echo "<p><a href='gallery.php'>Return to Gallery Admin</a></p>";
} catch (\PDOException $e) {
    echo "<h2 style='color: red;'>Failed to import:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>
