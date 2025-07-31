<?php
session_start();
include 'dbconnect.php';

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit();
}

$id = intval($_SESSION['uid']);
$sql = "SELECT * FROM store WHERE ID = $id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bookstore - Profile</title>
    <style>
        /* General styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f5f5;
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
            display: flex;
            justify-content: space-around;
            background-color: #FBFAF9;
            margin-bottom: 50px;
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

        /* PROFILE CARD */
        .profile-container {
            max-width: 600px;
            margin: 0px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-card {
            text-align: center;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .profile-card button {
            background: #3498db;
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .favorites {
            margin-top: 30px;
        }

        .favorites h3 {
            margin-bottom: 10px;
        }

        .favorites ul {
            list-style: disc;
            padding-left: 20px;
        }

        /* MODAL */
        .modal {
            
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            
            background: white;
            padding: 20px;
            width: 300px;
            border-radius: 10px;
        }

        .modal-content input {
            width: 100%;
            padding: 8px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-between;
        }

        .modal-buttons button {
           background: black;
           color:white;
           border-radius:5px;
            padding:0px -5px;
            height:40px;
        }

        /* FOOTER */
        footer {
            margin-top: 50px;
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
    <!-- NAVIGATION BAR -->
    <nav>
        <a href="trial.php"><img src="./images/logo.png" id="logo"></a>
        <ul>
            <li><a href="trial.php">Home</a></li>
            <li><a href="allbooks.php">All Books</a></li>
            <li><a href="bestsellers.php">Bestsellers</a></li>
            <li><a href="bookcategories.php">Categories</a></li>
            <li><input type="search" name="" id="ni"></li>
            <li><a href=""><i class="fa-solid fa-heart nav-icon"></i></a></li>
            <li><a href=""><i class="fa-solid fa-cart-shopping nav-icon"></i></a></li>
        </ul>
    </nav>

    <!-- PROFILE SECTION -->
    <div class="profile-container">
        <div class="profile-card">
            <img src="<?php echo $row['picture']; ?>" alt="User Avatar" class="avatar">
            <h2 id="username"><?php echo $row['Username'] ?></h2><br>
            <h6 id="email"><?php echo $row['Email'] ?></h6><br>
            <button onclick="openEditModal()">Edit Profile</button>
            <form action="logout.php"> <br> <button type="submit">Logout</button></form>
        </div>

        <div class="favorites">
            <h3>📖 Favorite Books</h3>
            <ul id="favorite-books">
                <li>The Alchemist</li>
                <li>Atomic Habits</li>
                <li>Rich Dad Poor Dad</li>
            </ul>
        </div>
    </div>

    <!-- EDIT MODAL -->
     <form action="" method="POST">
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Edit Profile</h2>
            <label>Name:</label>
            <input type="text" id="editName" name="Name" />
            <label>Email:</label>
            <input type="email" id="editEmail" name="Email" />
            <div class="modal-buttons">
                <button onclick="saveChanges()"><input type="submit" value="Save" name="Submit" style="background:black; color:white; border:none;"></button>
                <button onclick="closeEditModal()">Cancel</button>
            </div>
        </div>
    </div>
    </form>

    <!-- FOOTER -->
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
        <p>© 2024 PageTurner. All rights reserved. </p>
    </span>
    <script>
        function openEditModal() {
            document.getElementById("editModal").style.display = "flex";
            document.getElementById("editName").value = document.getElementById("username").textContent;
            document.getElementById("editEmail").value = document.getElementById("email").textContent;
        }

        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }

        function saveChanges() {
            const newName = document.getElementById("editName").value;
            const newEmail = document.getElementById("editEmail").value;
            document.getElementById("username").textContent = newName;
            document.getElementById("email").textContent = newEmail;
            closeEditModal();
        }

        // Login/Logout toggle (dummy)
        let loggedIn = false;
        function toggleAuth() {
            const btn = document.getElementById("authBtn");
            loggedIn = !loggedIn;
            btn.textContent = loggedIn ? "Logout" : "Login";
        }

    </script>
    <?php
    if (isset($_POST['Submit'])) {
        $name = $_POST['Name'];
        $email = $_POST['Email'];
        
        // Update the user profile in the database
        $update_sql = "UPDATE store SET Username='$name', Email='$email' WHERE ID=$id";
        if (mysqli_query($conn, $update_sql)) {
            echo "<script>alert('Profile updated successfully!');</script>";
            echo "<script>window.location.href='profile.php?ID=$id';</script>";
        } else {
            echo "<script>alert('Error updating profile.');</script>";
        }
    }
    ?>
</body>

</html>