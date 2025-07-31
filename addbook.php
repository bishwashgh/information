<?php
session_start();
include 'dbconnect.php';

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? 'General');
    $imagePath = '';

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileTmp = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExt, $allowed)) {
            $newFileName = uniqid('book_', true) . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmp, $destPath)) {
                $imagePath = $destPath;
            }
        }
    }
    if (!$imagePath) {
        $imagePath = 'images/default_book.png';
    }

    if ($title && $author && $price && $category) {
        $stmt = $conn->prepare("INSERT INTO books (title, author, price, image, category) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssdss', $title, $author, $price, $imagePath, $category);
        if ($stmt->execute()) {
            $message = 'Book added successfully!';
        } else {
            $message = 'Error adding book: ' . $conn->error;
        }
        $stmt->close();
    } else {
        $message = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        form { max-width: 400px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; background: #fafafa; }
        label { display: block; margin-top: 15px; }
        input[type="text"], input[type="number"] { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #bbb; border-radius: 4px; }
        button { margin-top: 20px; padding: 10px 20px; background: #353432; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .message { text-align: center; color: green; margin-bottom: 10px; }
        .error { color: red; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Add a New Book</h2>
    <?php if ($message): ?>
        <div class="message<?= strpos($message, 'Error') !== false ? ' error' : '' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post" action="" enctype="multipart/form-data">
        <label for="title">Title *</label>
        <input type="text" id="title" name="title" required>

        <label for="author">Author *</label>
        <input type="text" id="author" name="author" required>

        <label for="price">Price (USD) *</label>
        <input type="number" id="price" name="price" min="0" step="0.01" required>

        <label for="category">Category *</label>
        <select id="category" name="category" required style="width:100%;padding:8px;margin-top:5px;border:1px solid #bbb;border-radius:4px;">
            <option value="">Select Category</option>
            <option value="Fantasy">Fantasy</option>
            <option value="Science Fiction">Science Fiction</option>
            <option value="Fiction">Fiction</option>
            <option value="Mystery & Thriller">Mystery & Thriller</option>
            <option value="Romance">Romance</option>
            <option value="Biography">Biography</option>
            <option value="Non-Fiction">Non-Fiction</option>
            <option value="Children">Children</option>
            <option value="Other">Other</option>
        </select>
        <label for="image">Book Image (optional)</label>
        <input type="file" id="image" name="image" accept="image/*">

        <button type="submit">Add Book</button>
    </form>
    <div style="text-align:center; margin-top:20px;">
        <a href="allbooks.php">Back to All Books</a>
    </div>
</body>
</html>
