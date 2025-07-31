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
        $userProfile = null;
        unset($_SESSION['uid']);
        session_destroy();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project</title>
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
        h6 {
            font-family: "Crimson Text", Georgia, serif;
            font-weight: 600;
        }

        /* nav */
        nav {
            z-index:100;
            width: 100%;
            position: fixed;
            display: flex;
            justify-content: space-around;
            background-color: #FBFAF9;
            border-bottom: #E9E4DC 1px solid;
        }

        nav ul {
            list-style: none;
            display: inline;
        }

        nav ul li {
            padding: 20px;
        }

        nav ul li a {
            padding: 10px 20px;
            transition: 0.5s;
        }

        nav ul li {
            display: inline-block;
        }

        nav ul li a:hover {
            background-color: #E9E4DC;
            transition: 0.5s;
            border-radius: 5px;
            /* padding: 10px; */
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

        /* search body */

        .body-content {
            padding: 150px;
            background-color: #F6F1E9;
        }

        .downn {
            margin: 20px 0px;
        }

        .headd,
        .downn {
            text-align: center;
        }

        .searchinsite,
        .browse {
            display: flex;
            justify-content: center;
        }

        #searchh {
            padding: 10px;
            width: 350px;
            border: none;
            border-radius: 3px;
            align-self: center;
            box-shadow: #E9E4DC 5px 5px;
        }


        .browse-1 a,
        .browse-2 a {
            margin: 0px 20px;
            padding: 10px 20px;
            border-bottom: none;
            border-radius: 5px;
            text-decoration: white;
            background-color: #ECE7DF;
            width: auto;
            height: 20px;
            display: flex;
            align-items: center;
            font-size: 10px;
            transition: 0.5s;
        }

        .browse-1 a:hover,
        .browse-2 a:hover {
            background-color: #4B413A;
            color: white;
        }

        #browse-1,
        #browse-2 {
            background-color: transparent;
            border: none;
        }

        .star {
            background-color: rgba(97, 25, 25, 0);
        }

        #myimg:hover {
            transform: scale(1.1);
            transition: 0.5s;
            cursor: pointer;
        }

        #myImg{
            height: 300px;
            width: 225px;
        }
        .books:hover {
            height: 95%;
            width: 95%;
            transform: scale(1.035);
            transition: 0.5s;
            cursor: pointer;
        }

        /* featured */

        .featured {
            padding: 100px;
            text-align: center;
            background-color: #FBFAF9;
        }

        .f-listings {
            display: flex;
            justify-content: space-around;
        }

        .f-listings p {
            text-align: left;
            padding: 20px;
            background-color: #FBFAF9;
        }

        .f-listings img {
            height: 30vh;
            width: 20vh;
        }

        /* Book Categories */
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

        .b-cat-books a {
            margin-left: 20px;
            transition: 0.35s ease-in-out;
        }

        .b-cat-books a:hover {
            transform: translateY(-10px);
            transition: 0.35s ease-in-out;
            cursor: pointer;
        }

        .b-cat-books {
            padding: 50px 165px;
            display: grid;
            row-gap: 10px;
            grid-template-columns: repeat(4, 1fr);
        }

        .b-cat-images-1,
        .b-cat-images-2,
        .b-cat-images-3,
        .b-cat-images-4,
        .b-cat-images-5,
        .b-cat-images-6 {
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;
            border: 2px solid none;
            border-radius: 30px;
            height: 30vh;
            color: white;
        }

        .b-cat-images-1 {
            background-image: url(/images/fantasy.png);
        }

        .b-cat-images-2 {
            background-image: url(/images/scifi.jpg);
        }

        .b-cat-images-3 {
            background-image: url(/images/fiction.png);
        }

        .b-cat-images-4 {
            background-image: url(/images/mystrey\ thriller.png);
        }

        .b-cat-images-5 {
            background-image: url(/images/romance.png);
        }

        .b-cat-images-6 {
            background-image: url(/images/biography.png);
        }

        .b-cat-images img {
            transition: 0.5s;
        }

        .b-cat-images:hover {
            overflow: hidden;
            transform: scale(1.1);
            transition: 0.5s;
            cursor: pointer;
        }

        .inside {
            padding-top: 40%;
            padding-left: 10px;
        }

        /* Bestsellers */
        .bestsellers {
            padding: 50px;
            text-align: center;
            background-color: #FBFAF9;
        }
        .b-head{
            color: #4B413A;
        }

        .b-listings {
            padding: 50px 100px;
            display: grid;
            row-gap: 10px;
            grid-template-columns: repeat(4, 1fr);
        }
        /* books */
        .thiss {
            overflow: hidden;
            height: 100%;
        }

        .images {
            height: 50vh;
            width: 40vh;
            display: flex;
            align-self: center;
            justify-content: center;
            align-items: center;
        }

        .bsellers-books {
            padding: 50px 100px;
            display: grid;
            row-gap: 10px;
            grid-template-columns: repeat(3, 1fr);
        }

        /* books */
        .bsellers-images:hover {
            height: 98%;
            width: 98%;
            overflow: hidden;
            transform: scale(1.03);
            transition: 0.5s;
            cursor: pointer;
        }

        .bsellers-images {
            height: 100%;
            width: 100%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: 0.4s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            margin-left: 10px;
        }

        .bsellers-images img {
            margin: 20px;
        }

        .bsellers-images h3 {
            font-size: 20px;
            color: #563C29;
        }

        .b-images-content {
            overflow: hidden;
            margin-top: -20px;
            text-align: center;
            padding: 20px;
        }


        /* Stay Updated */

        .update {
            padding: 100px;
            color: white;
            text-align: center;
            background-color: #563C29;
        }

        #subscribe {
            border: 1px solid black;
            border-radius: 5px;
            padding: 8px;
            background-color: #DAA520;
        }

        #u-email {
            width: 200px;
            border: 1px solid black;
            border-radius: 5px;
            padding: 8px 10px;
        }

        .f-listings img,
        .b-listings img,
        .e-listings img {
            margin-right: 100px;
            height: 300px;
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
            <form method="get" action="trial.php" style="display:inline;">
                <input type="search" name="search" id="ni" placeholder="Search books..." value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>" style="padding:6px 10px; border-radius:5px; border:1px solid #ccc;">
                <button type="submit" style="padding:6px 10px; border-radius:5px; background:#563C29; color:#fff; border:none; margin-left:4px;">Search</button>
            </form>
        </li>
        <li><a href=""><i class="fa-solid fa-heart nav-icon"></i></a></li>
        <li><a href="cartview.php"><i class="fa-solid fa-cart-shopping nav-icon"></i></a></li>
        <?php if ($userProfile): ?>
            <li><a href="profile.php"><img src="<?php echo $userProfile['picture']; ?>" alt="Profile" height="40px" width="40px" style="border-radius:20px;margin-bottom:-13px; margin-left:10px"></a></li>
        <?php else: ?>
            <li><a href="login.php"><i class="fa-solid fa-user nav-icon">&nbsp;LOGIN</i></a></li>
        <?php endif; ?>
    </ul>
</nav>
    <div class="body">
        <div class="body-content" id="a">
            <div class="headd">
                <h1>Discover Your Next<br>Great Read</h1>
            </div>
            <div class="downn">
                Explore our curated collection of exceptional books, from <br> bestselling novels to hidden literary
                gems.
                Your perfect story is <br> waiting to be discovered.
            </div> <br>
            <div class="searchinsite">
                <input type="search" name="" id="searchh" placeholder="Search books, authors, or genres...">
            </div> <br>
            <div class="browse">
                <div class="browse-1">
                    <a href="">Browse All Books <i class="fa-solid fa-arrow-right-long fa-arrow"></i> </a>
                </div>
                <div class="browse-2">
                    <a href=""><i class="fa-solid fa-star star"></i> View Bestsellers </a>
                </div>
            </div>
        </div>
        <div class="featured">
    <div class="f-head">
        <h2>Featured Books</h2>
    </div>
    <div class="b-listings">
        <?php
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($searchTerm !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $searchTerm);
    $books = mysqli_query($conn, "SELECT * FROM books WHERE title LIKE '%$safeSearch%'");
} else {
    $books = mysqli_query($conn, "SELECT * FROM books");
}
while ($book = mysqli_fetch_assoc($books)) {
        ?>
        <div class="item bsellers-images">
            <img id="myImg" src="<?= $book['image'] ?? 'images/default_book.png' ?>" alt="<?= $book['title'] ?>">
            <div class="b-images-content">
                <h3><?= $book['title'] ?></h3>
                <address>
                    Written by: <span><?= $book['author'] ?></span>
                </address>
                <div class="price" style="color: #563C29; font-weight: bold; font-size: 25px;">$<?= number_format($book['price'], 2) ?></div>
                <form method="get" action="add_to_cart.php" style="margin-top:10px;">
                    <input type="hidden" name="id" value="<?= $book['id'] ?>">
                    <button type="submit" class="addCart" style="padding:6px 18px; background:#28a745; color:#fff; border:none; border-radius:4px; cursor:pointer;">Add to Cart</button>
                </form>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
            </div>
        </div>
    </div>
    <!-- ... -->
    <div class="bestsellers">
        <div class="b-head">
            <h1 style="font-size: 40px; text-align: center; margin-top: 25px;">Bestsellers</h1>
        </div> <br>
        <div class="b-body">
            Most picked books by customer, these exceptional books are must-<br>reads for every book lover.
            <div class="b-listings">
                <?php
                $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($searchTerm !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $searchTerm);
    $books = mysqli_query($conn, "SELECT * FROM books WHERE title LIKE '%$safeSearch%'");
} else {
    $books = mysqli_query($conn, "SELECT * FROM books");
}
while ($book = mysqli_fetch_assoc($books)) {
                ?>
                    <div class="item bsellers-images">
                        <img id="myImg" src="<?= htmlspecialchars($book['image'] ?? 'images/default_book.png') ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                        <div class="b-images-content">
                            <h3><?= htmlspecialchars($book['title']) ?></h3>
                            <address>
                                Written by: <span><?= htmlspecialchars($book['author']) ?></span>
                            </address>
                            <div class="price" style="color: #563C29; font-weight: bold; font-size: 25px;">$<?= number_format($book['price'], 2) ?></div>
                            <form method="get" action="add_to_cart.php" style="margin-top:10px;">
                                <input type="hidden" name="id" value="<?= $book['id'] ?>">
                                <button type="submit" class="addCart" style="padding:6px 18px; background:#28a745; color:#fff; border:none; border-radius:4px; cursor:pointer;">Add to Cart</button>
                            </form>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <!-- ... -->
        </div> <br>
        <div class="u-body">
            Subscribe to our newsletter and be the first to know about new releases, exclusive offers, and literary
            events.
        </div> <br>
        <div class="u-emails">
            <input type="email" name="" id="u-email" placeholder="Enter your email address">
            <button id="subscribe" style="text-align: center;"><a href="">Subscribe</a></button>
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
    <p class="foot">© 2024 PageTurner. All rights reserved. </p>
</body>

</html>