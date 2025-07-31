<?php
session_start();
include 'dbconnect.php';

$userProfile = null;

if (isset($_SESSION['uid'])) {
    $id = intval($_SESSION['uid']);
    $result = mysqli_query($conn, "SELECT * FROM store WHERE ID = $id");

    if ($result && mysqli_num_rows($result) === 1) {
        $userProfile = mysqli_fetch_assoc($result);
    } else {
        session_destroy();
        unset($_SESSION['uid']);
        $userProfile = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        * {
            margin: 0px;
            padding: 0px;
            overflow-y: scroll;
            scrollbar-width: none;
            /* for Firefox */
            -ms-overflow-style: none;
            /* for IE and Edge */
        }

        body {
            font-family: "Inter", system-ui, sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        b {
            font-family: "Crimson Text", Georgia, serif;
            font-weight: 600;
        }

        nav {
            width: 100%;
            position: relative;
            display: flex;
            justify-content: space-around;
            background-color: #FBFAF9;
        }

        nav ul {
            list-style: none;
            display: inline;
        }

        nav ul li {
            padding: 20px;
        }

        nav ul li {
            display: inline-block;
        }

        nav ul li a {
            padding: 10px 20px;
            transition: 0.5s;
        }

        nav ul li a:hover {
            background-color: #E9E4DC;
            transition: 0.5s;
            border-radius: 5px;
        }

        #ni {
            margin: auto;
        }

        a {
            text-decoration: none;
            color: black;
        }

        #logo {
            height: 60px;
            width: 60px;
        }

        .bc-head {
            padding: 85px 0px 5px 0px;
            text-align: center;
            color: #563C29;
        }

        .bc-body-1 {
            text-align: center;
            padding: 30px;
            font-size: 20px;
            color: #563C29;
        }

        .bsellers-books a {
            margin-left: 20px;
            transition: 0.35s ease-in-out;
        }

        .bsellers-books a:hover {
            transform: translateY(-10px);
            transition: 0.35s ease-in-out;
            cursor: pointer;
        }

        .bsellers-books {
            padding: 50px 165px;
            display: grid;
            row-gap: 10px;
            grid-template-columns: repeat(4, 1fr);
        }

        .bsellers-images-1,
        .bsellers-images-2,
        .bsellers-images-3,
        .bsellers-images-4,
        .bsellers-images-5,
        .bsellers-images-6 {
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;
            border: 2px solid none;
            border-radius: 30px;
            height: 30vh;
            color: white;
        }

        .bsellers-images-1 {
            background-image: url(/images/fantasy.png);
        }

        .bsellers-images-2 {
            background-image: url(/images/scifi.jpg);
        }

        .bsellers-images-3 {
            background-image: url(/images/fiction.png);
        }

        .bsellers-images-4 {
            background-image: url(/images/mystrey\ thriller.png);
        }

        .bsellers-images-5 {
            background-image: url(/images/romance.png);
        }

        .bsellers-images-6 {
            background-image: url(/images/biography.png);
        }

        .bsellers-images img {
            transition: 0.5s;
        }

        .bsellers-images:hover {
            overflow: hidden;
            transform: scale(1.1);
            transition: 0.5s;
            cursor: pointer;
        }

        .inside {
            padding-top: 40%;
            padding-left: 10px;
        }

        footer {
            text-align: left;
            display: flex;
            justify-content: space-around;
            background-color: #ECE7DF;
            padding: 50px 30px;
        }

        footer li {
            margin-bottom: 8px;
        }

        .foot {
            text-align: center;
            padding: 15px;
            background-color: #ECE7DF;
        }
        .categories-grid{

            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-left: 60px;
        }
        .category-card{
            transition: 0.5s;
            border: 2px solid none;
            border-radius: 30px;
            height: 30vh;
            color: white;
            width: 70%;
            overflow: hidden;
            position: relative;
        }
        .category-title{
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 10px;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            text-align: center;
        }
        .category-title:hover{
            background-color: rgba(0, 0, 0, 0.8);
        }
        .category-title a{
            color: white;
            text-decoration: none;
        }
        .category-title:hover a{
            color: #ECE7DF;
        }
        .category-card:hover{
            transform: scale(1.1);
            transition: 0.5s;
            cursor: pointer;
        }
        .category-card:hover .category-title{
            background-color: rgba(0, 0, 0, 0.8);
        }
        .category-card:hover .category-title a{
            color: #ECE7DF;
        }
        .category-card:hover .category-img{
            transform: scale(1.1);
            transition: 0.5s;
            cursor: pointer;
        }
        .category-img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
            display: block;
        }
    </style>
    <script src="https://kit.fontawesome.com/2d0afc1b84.js" crossorigin="anonymous"></script>
</head>

<body>
    <nav>
        <a href=""><img src="./images/logo.png" id="logo"></a>
        <ul>
            <li><a href="trial.php">Home</a></li>
            <li><a href="allbooks.php">All Books</a></li>
            <li><a href="bestsellers.php">Bestsellers</a></li>
            <li><a href="bookcategories.php">Categories</a></li>
            <li>
    <form method="get" action="bookcategories.php" style="display:inline;">
        <input type="search" name="search" id="ni" placeholder="Search books..." value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>" style="padding:6px 10px; border-radius:5px; border:1px solid #ccc;">
        <button type="submit" style="padding:6px 10px; border-radius:5px; background:#563C29; color:#fff; border:none; margin-left:4px;">Search</button>
    </form>
</li>
            <li><a href=""><i class="fa-solid fa-heart nav-icon"></i></a></li>
            <li><a href=""><i class="fa-solid fa-cart-shopping nav-icon"></i></a></li>
            <?php if ($userProfile): ?>
            <li><a href="profile.php"><img src="<?php echo $userProfile['picture']; ?>" alt="Profile" height="40px" width="40px" style="border-radius:20px;margin-bottom:-13px; margin-left:10px"></a></li>
            <?php else: ?>
            <li><a href="login.php"><i class="fa-solid fa-user nav-icon">&nbsp;LOGIN</i></a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="b-categories">
        <div class="bc-head">
            <h1 style="font-size: 40px; text-align: center;">Book Categories</b>
        </div>
        <div class="bc-body">
            <div class="bc-body-1">
                Browse books by genre and discover your next favorite readers worldwide. These <br> titles have captured
                heats and minds across the globe.
            </div>
            <div class="categories-grid">
                <?php
                
                $catQuery = mysqli_query($conn, "SELECT category, COUNT(*) as book_count FROM books GROUP BY category ORDER BY category ASC");
                $categoryImages = [
                    'Fantasy' => 'images/fantasy.png',
                    'Science Fiction' => 'images/scifi.jpg',
                    'Fiction' => 'images/fiction.png',
                    'Mystery & Thriller' => 'images/mystrey thriller.png',
                    'Romance' => 'images/romance.png',
                    'Biography' => 'images/biography.png',
                    'Non-Fiction' => 'images/nonfiction.png',
                    'Children' => 'images/children.png',
                    'Other' => 'images/books.png',
                    'General' => 'images/books.png',
                ];
                while ($cat = mysqli_fetch_assoc($catQuery)) {
                    $catName = $cat['category'];
                    $img = $categoryImages[$catName] ?? 'images/books.png';
                    echo '<div class="category-card">';
                    echo '<img src="' . $img . '" alt="' . $catName . '" class="category-img" height="100px" width="100px">';
                    echo '<div class="category-title">' . $catName . ' (' . $cat['book_count'] . ')</div>';
                    echo '</div>';
                }
                ?>
            </div>
            <div class="bsellers-books" style="padding: 50px 30px; display: flex; flex-direction: column; gap: 40px;">
                <?php
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                if ($search !== '') {
                    // No escaping per user preference
                    $bookQ = mysqli_query($conn, "SELECT * FROM books WHERE title LIKE '%$search%' OR author LIKE '%$search%' OR category LIKE '%$search%' ORDER BY title ASC");
                    echo '<div class="category-section">';
                    echo '<h2 style="color:#563C29; margin-bottom:10px;">Search Results</h2>';
                    echo '<div class="books-row" style="display:flex; flex-wrap:wrap; gap:24px;">';
                    $found = false;
                    while ($book = mysqli_fetch_assoc($bookQ)) {
                        $found = true;
                        $img = $book['image'] ?: 'images/default_book.png';
                        $title = $book['title'];
                        $author = $book['author'];
                        $price = number_format($book['price'], 2);
                        echo '<div class="book-card" style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:16px;width:220px;text-align:center;">';
                        echo '<img src="' . $img . '" alt="' . $title . '" style="width:100px;height:140px;object-fit:cover;border-radius:8px;">';
                        echo '<div class="book-title" style="font-weight:bold;margin:8px 0 2px 0;">' . $title . '</div>';
                        echo '<div class="book-author" style="color:#555;font-size:0.95em;">By ' . $author . '</div>';
                        echo '<div class="book-price" style="color:#563C29;font-weight:bold;margin-top:6px;">$' . $price . '</div>';
                        echo '</div>';
                    }
                    if (!$found) {
                        echo '<div style="color:#563C29; font-size:1.2em;">No books found matching your search.</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                } else {
                    
                    $catQuery = mysqli_query($conn, "SELECT DISTINCT category FROM books ORDER BY category ASC");
                    while ($cat = mysqli_fetch_assoc($catQuery)) {
                        $catName = $cat['category'];
                        echo '<div class="category-section">';
                        echo '<h2 style="color:#563C29; margin-bottom:10px;">' . $catName . '</h2>';
                        // Fetch books for this category
                        $bookQ = mysqli_query($conn, "SELECT * FROM books WHERE category = '" . $cat['category'] . "' ORDER BY title ASC");
                        echo '<div class="books-row" style="display:flex; flex-wrap:wrap; gap:24px;">';
                        while ($book = mysqli_fetch_assoc($bookQ)) {
                            $img = $book['image'] ?: 'images/default_book.png';
                            $title = $book['title'];
                            $author = $book['author'];
                            $price = number_format($book['price'], 2);
                            echo '<div class="book-card" style="background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:16px;width:220px;text-align:center;">';
                            echo '<img src="' . $img . '" alt="' . $title . '" style="width:100px;height:140px;object-fit:cover;border-radius:8px;">';
                            echo '<div class="book-title" style="font-weight:bold;margin:8px 0 2px 0;">' . $title . '</div>';
                            echo '<div class="book-author" style="color:#555;font-size:0.95em;">By ' . $author . '</div>';
                            echo '<div class="book-price" style="color:#563C29;font-weight:bold;margin-top:6px;">$' . $price . '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="company">
            <h4>Company</h4><br>
            <ul>
                <li><a href="">About Us</a></li>
                <li><a href="">Our Story</a></li>
                <li><a href="career.html">Careers</a></li>
                <li><a href="">Press</a></li>
            </ul>
        </div>
        <div class="c-service">
            <h4>Customer Service</h4> <br>
            <ul>
                <li><a href="">Help Center</a></li>
                <li><a href="">Shipping Info</a></li>
                <li><a href="">Returns</a></li>
                <li><a href="">Contact Us</a></li>
            </ul>
        </div>
        <div class="quicklinks">
            <h4>Quick Links</h4> <br>
            <ul>
                <li><a href="bestsellers.html">Bestsellers</a></li>
                <li><a href="#">Gift Cards</a></li>
                <li><a href="#">New Releases</a></li>
            </ul>
        </div>
        <div class="connect">
            <h4>Connect</h4> <br>
            <ul>
                <li><a href="">Newsletter</a></li>
                <li><a href="https://www.instagram.com/lowkyess" target="_blank">Social Media</a></li>
                <li><a href="">Book Club</a></li>
                <li><a href="">Events</a></li>
            </ul>
        </div>
    </footer>
    <span class="copyright">
        <p class="foot">© 2024 PageTurner. All rights reserved. </p>
    </span>
</body>

</html>