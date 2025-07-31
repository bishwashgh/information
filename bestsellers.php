<?php
session_start();
include 'dbconnect.php';

$userProfile = null;

if (!empty($_SESSION['uid'])) {
    $id = intval($_SESSION['uid']);
    $sql = "SELECT * FROM store WHERE ID = $id";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) === 1) {
        $userProfile = mysqli_fetch_assoc($result);
    } else {
        $_SESSION = [];
        session_destroy();
        $userProfile = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best-Selling Books</title>
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

        nav {
            z-index:100;
            width: 100%;
            position: fixed;
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

        /* body */
        .bsellers-body {
            padding: 150px 0px 5px 0px;
            text-align: center;
            color: #563C29;
        }

        /* content */
        .bsellers-content {
            text-align: center;
            padding: 30px;
            font-size: 20px;
            color: #563C29;
        }

        /* books */
        .thiss {
            overflow: hidden;
            height: 100%;
        }

        .images {
            opacity: 1;
            display: block;
            width: 40vh;
            transition: .5s ease;

            height: auto;
            /* width: 40vh;
            object-fit: cover; */
        }

        .middle {
            transition: .5s ease;
            opacity: 0;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .container:hover .image {
            opacity: 0.3;
        }

        .container:hover .middle {
            opacity: 1;
        }
        
        .text {
            background:black;
            color: white;
            font-size: 16px;
            padding: 16px 32px;
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

        /* footer */

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

        p {
            text-align: center;
            padding: 15px;
            background-color: #ECE7DF;
        }
    </style>
    <script src="https://kit.fontawesome.com/2d0afc1b84.js" crossorigin="anonymous"></script>
</head>

<body>
    <nav>
        <a href="trial.html"><img src="./images/logo.png" id="logo"></a>
        <ul>
            <li><a href="trial.php">Home</a></li>
            <li><a href="allbooks.php">All Books</a></li>
            <li><a href="bestsellers.php">Bestsellers</a></li>
            <li><a href="bookcategories.php">Categories</a></li>
            <li>
    <form method="get" action="bestsellers.php" style="display:inline;">
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
    <div class="bsellers-body">
        <h1 style="font-size: 45px; text-align: center;">Bestselling Books</h1>
    </div>
    <div class="bsellers-content">
        Discover the most popular books loved by readers worldwide. These <br> titles have captured hearts and minds
        across
        the globe.
    </div>
    <div class="bsellers-books">

        <?php
        // Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    // No escaping per user preference
    $books = mysqli_query($conn, "SELECT * FROM books WHERE title LIKE '%$search%' OR author LIKE '%$search%'");
} else {
    $books = mysqli_query($conn, "SELECT * FROM books");
}
        while ($book = mysqli_fetch_assoc($books)) {
        ?>
        <div class="thiss">
            <div class="bsellers-images">
                <div class="container" style="position:relative;">
                    <img id="myImg" src="<?= htmlspecialchars($book['image'] ?? 'images/default_book.png') ?>" class="images" alt="<?= htmlspecialchars($book['title']) ?>">
                    <div class="middle">
                        <form method="get" action="add_to_cart.php">
                            <input type="hidden" name="id" value="<?= $book['id'] ?>">
                            <button type="submit" class="text">Add to Cart</button>
                        </form>
                    </div>
                </div>
                <div class="b-images-content">
                    <h3><?= htmlspecialchars($book['title']) ?></h3>
                    <address>
                        Written by: <span><?= htmlspecialchars($book['author']) ?></span>
                    </address>
                    <div class="stars">
                        <span class="fa fa-star checked"></span>
                        <span class="fa fa-star checked"></span>
                        <span class="fa fa-star checked"></span>
                        <span class="fa fa-star checked"></span>
                        <span class="fa fa-star"></span>
                    </div>
                    <span style="color: #563C29; font-weight: bold; font-size: 25px;">$<?= number_format($book['price'], 2) ?></span>
                </div>
            </div>
        </div>
        <?php } ?>
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
                <li><a href="bestsellers.php">Bestsellers</a></li>
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
        <p>© 2024 PageTurner. All rights reserved. </p>
    </span>
</body>

</html>