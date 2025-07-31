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

        nav {
            width: 100%;
            margin-top: 0%;
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

        svg {
            width: 18px;
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

        /* body starting */
        .bsellers-body {
            padding: 100px 0px 5px 0px;
            text-align: center;
            color: #563C29;
        }

        .bsellers-content {
            text-align: center;
            padding: 30px;
            font-size: 20px;
            color: #563C29;
        }


        .container {
            width: 1000px;
            margin: auto;
            max-width: 90vw;
            text-align: center;
            padding-top: 10px;
        }

       .icon-cart {
            cursor: pointer;
            position: relative;
            margin-bottom: -8px;
            padding: 10px 20px;
            transition: 0.5s;
        }

        .icon-cart:hover{
            background-color: #E9E4DC;
            transition: 0.5s;
            border-radius: 5px;
        }



        .title {
            font-size: xx-large;
        }

        .listProduct .item img {
            height: 70%;
            width: 90%;
            filter: drop-shadow(0 50px 40px #0009);
            transition: 0.6s;
            border-radius: 20px;
        }

        .listProduct .item img:hover {
            transform: scale(1.07);
            transition: 0.5s;
        }

        .listProduct {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }


        .listProduct .item {
            background-color: #EEEEE6;
            padding: 20px;
            border-radius: 20px;
        }

        .listProduct .item h2 {
            font-weight: 500;
            font-size: large;
        }

        .listProduct .item .price {
            letter-spacing: 2px;
            font-size: small;
        }

        .listProduct .item button {
            background-color: #353432;
            color: #eee;
            border: none;
            padding: 5px 10px;
            margin-top: 10px;
            border-radius: 20px;
        }

        /* cart */
        .cartTab {
            width: 400px;
            background-color: #353432;
            color: #eee;
            position: fixed;
            top: 0;
            right: -400px;
            bottom: 0;
            display: grid;
            grid-template-rows: 70px 1fr 70px;
            transition: .5s;
        }

        body.showCart .cartTab {
            right: 0;
        }

        .cartTab h1 {
            padding: 20px;
            margin: 0;
            font-weight: 300;
        }

        .cartTab .btn {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
        }

        .cartTab button {
            background-color: #E8BC0E;
            border: none;
            font-family: Poppins;
            font-weight: 500;
            cursor: pointer;
        }

        .cartTab .close {
            background-color: #eee;
        }

        .listCart .item img {
            width: 100%;
        }

        .listCart .item {
            display: grid;
            grid-template-columns: 70px 150px 50px 1fr;
            gap: 10px;
            text-align: center;
            align-items: center;
        }

        .listCart .quantity span {
            display: inline-block;
            width: 25px;
            height: 25px;
            background-color: #eee;
            border-radius: 50%;
            color: #555;
            cursor: pointer;
        }

        .listCart .quantity span:nth-child(2) {
            background-color: transparent;
            color: #eee;
            cursor: auto;
        }

        .listCart .item:nth-child(even) {
            background-color: #eee1;
        }

        .listCart {
            overflow: auto;
        }

        .listCart::-webkit-scrollbar {
            width: 0;
        }

        @media only screen and (max-width: 992px) {
            .listProduct {
                grid-template-columns: repeat(3, 1fr);
            }
        }


        /* mobile */
        @media only screen and (max-width: 768px) {
            .listProduct {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* mobile */
        @media only screen and (max-width: 425px) {
            .listProduct {
                grid-template-columns: repeat(1, 1fr);
            }
        }



        footer {
            margin-top: 20px;
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
    <form method="get" action="allbooks.php" style="display:inline;">
        <input type="search" name="search" id="ni" placeholder="Search books..." value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>" style="padding:6px 10px; border-radius:5px; border:1px solid #ccc;">
        <button type="submit" style="padding:6px 10px; border-radius:5px; background:#563C29; color:#fff; border:none; margin-left:4px;">Search</button>
    </form>
</li>
            <li><a href=""><i class="fa-solid fa-heart nav-icon"></i></a></li>
            <li>
    <a href="cartview.php">
        <i class="fa-solid fa-cart-shopping nav-icon"></i>
    </a>
</li>
            <?php if ($userProfile): ?>
            <li><a href="profile.php"><img src="<?php echo $userProfile['picture']; ?>" alt="Profile" height="40px" width="40px" style="border-radius:20px;margin-bottom:-13px; margin-left:10px"></a></li>
            <?php else: ?>
            <li><a href="login.php"><i class="fa-solid fa-user nav-icon">&nbsp;LOGIN</i></a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="bsellers-body">
        <h1 style="font-size: 45px; text-align: center;">All Books</h1>
    </div>
    <div class="bsellers-content">
        Explore our extensive collection of books across various genres. Whether you're looking for the latest
        bestsellers, <br> classic literature, or hidden gems, we have something for every reader.
    </div>
    <div class="container">
        <div class="listProduct">
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
            <div class="item">
                <img src="<?= $book['image'] ?? 'images/default_book.png' ?>" alt="<?= $book['title'] ?>">
                <h2><?= $book['title'] ?></h2>
                <div class="price">$<?= number_format($book['price'], 2) ?></div>
                <div class="author">By <?= $book['author'] ?></div>
                <form method="get" action="add_to_cart.php">
                    <input type="hidden" name="id" value="<?= $book['id'] ?>">
                    <button type="submit" class="addCart">Add to Cart</button>
                </form>
            </div>
            <?php } ?>
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

    <div class="cartTab">
        <h1>Shopping Cart</h1>
        <div class="listCart">

        </div>
        <div class="btn">
            <button class="close">CLOSE</button>
            <?php 
        if ($userProfile == 'null') {?>
            <button class="checkOut"><a href="login.php">Check out</a></button>
        <?php } else { ?>
            <button class="checkOut"><a href="checkout.php">Check out</a></button>
        <?php } ?>
        </div>
    </div>

    <script>
        let listCartHTML = document.querySelector('.listCart');
        let iconCart = document.querySelector('.icon-cart');
        let iconCartSpan = document.querySelector('.icon-cart span');
        let body = document.querySelector('body');
        let closeCart = document.querySelector('.close');
        let cart = [];

        iconCart.addEventListener('click', () => {
            body.classList.toggle('showCart');
        })
        closeCart.addEventListener('click', () => {
            body.classList.toggle('showCart');
        })

        const addCartToHTML = () => {
            listCartHTML.innerHTML = '';
            let totalQuantity = 0;
            if (cart.length > 0) {
                cart.forEach(item => {
                    totalQuantity = totalQuantity + item.quantity;
                    let newItem = document.createElement('div');
                    newItem.classList.add('item');
                    newItem.dataset.id = item.product_id;
                    listCartHTML.appendChild(newItem);
                    newItem.innerHTML = `
            <div class="images">
                    <img src="${item.images}">
                </div>
                
                <div class="name">
                ${item.name}
                </div>
                <div class="totalPrice">$${item.price * item.quantity}</div>
                <div class="quantity">
                    <span class="minus"><</span>
                    <span>${item.quantity}</span>
                    <span class="plus">></span>
                </div>
            `;
                })
            }
            iconCartSpan.innerText = totalQuantity;
        }

        listCartHTML.addEventListener('click', (event) => {
            let positionClick = event.target;
            if (positionClick.classList.contains('minus') || positionClick.classList.contains('plus')) {
                let product_id = positionClick.parentElement.parentElement.dataset.id;
                let type = 'minus';
                if (positionClick.classList.contains('plus')) {
                    type = 'plus';
                }
                changeQuantityCart(product_id, type);
            }
        })
        const changeQuantityCart = (product_id, type) => {
            let positionItemInCart = cart.findIndex((value) => value.product_id == product_id);
            if (positionItemInCart >= 0) {
                let info = cart[positionItemInCart];
                switch (type) {
                    case 'plus':
                        cart[positionItemInCart].quantity = cart[positionItemInCart].quantity + 1;
                        break;

                    default:
                        let changeQuantity = cart[positionItemInCart].quantity - 1;
                        if (changeQuantity > 0) {
                            cart[positionItemInCart].quantity = changeQuantity;
                        } else {
                            cart.splice(positionItemInCart, 1);
                        }
                        break;
                }
            }
            addCartToHTML();
        }
    </script>
</body>

</html>