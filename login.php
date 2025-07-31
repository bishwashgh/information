<?php
session_start();
include 'dbconnect.php';

$userProfile = null;

if (isset($_POST['Login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM store WHERE Username = '$username' AND Password = '$password'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['uid'] = $row['ID'];
        header("Location: trial.php");
        exit();
    } else {
        $login_error = "Invalid username or password.";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <script src="https://kit.fontawesome.com/ae26b5f911.js" crossorigin="anonymous"></script>
  <title>Login</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      overflow-y: scroll;
      scrollbar-width: none;
      -ms-overflow-style: none;
    }

    body {
      font-family: "Inter", system-ui, sans-serif;
      background-color: #f9f9f9;
      display: flex;
      flex-direction: column;
    }

    h1, h2, h3, h4, h5, h6, b {
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
      display: inline-block;
      padding: 20px;
    }

    nav ul li a {
      padding: 10px 20px;
      text-decoration: none;
      color: black;
      transition: 0.5s;
    }

    nav ul li a:hover {
      background-color: #E9E4DC;
      border-radius: 5px;
    }

    #logo {
      height: 60px;
      width: 60px;
    }

    #ni {
      margin: auto;
    }

    .login-container {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 0;
    }

    .login {
      background: #fff;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.12);
      padding: 30px 40px;
      border-radius: 20px;
      width: 300px;
    }

    .login h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .login label {
      font-size: 14px;
      display: block;
      margin-top: 15px;
      font-weight: 500;
    }

    input {
      width: 92%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 10px;
      margin-top: 5px;
      font-size: 14px;
      background-color: #f1f1f1;
    }
     
    .submit-button {
      width: 100%;
      margin-top: 20px;
      padding: 12px;
      background: linear-gradient(90deg, #bfa77a 0%, #e9e4dc 100%);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      text-align: center;
      display: block;
    }

    .submit-button:hover {
      background: linear-gradient(90deg, #a88b5a 0%, #e9e4dc 100%);
      color: #222;
    }

    .forgot-link {
      text-align: center;
      margin-top: 10px;
    }

    .forgot-link a {
      color: #4a68d4;
      text-decoration: underline;
      font-size: 14px;
    }

    footer {
      text-align: left;
      display: flex;
      justify-content: space-around;
      background-color: #ECE7DF;
      padding: 50px 30px;
      margin-top: 20px;
    }

    footer li {
      margin-bottom: 8px;
    }

    span p {
      text-align: center;
      padding: 15px;
      background-color: #ECE7DF;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav>
  <a href="trial.php"><img src="./images/logo.png" id="logo"></a>
  <ul>
    <li><a href="trial.php">Home</a></li>
    <li><a href="allbooks.php">All Books</a></li>
    <li><a href="bestsellers.php">Bestsellers</a></li>
    <li><a href="bookcategories.php">Categories</a></li>
    <li><input type="search" id="ni"></li>
    <li><a href=""><i class="fa-solid fa-heart nav-icon"></i></a></li>
    <li><a href=""><i class="fa-solid fa-cart-shopping nav-icon"></i></a></li>
    <?php if ($userProfile): ?>
    <li><a href="profile.php?ID=<?php echo $id ?>"><img src="<?php echo $userProfile['picture']; ?>" alt="Profile" height="40px" width="40px" style="border-radius:20px;margin-bottom:-13px; margin-left:10px"></a></li>
    <?php else: ?>
    <li><a href="login.php"><i class="fa-solid fa-user nav-icon"></i></a></li>
    <?php endif; ?>
  </ul>
</nav>

<!-- Login Form -->
<div class="login-container">
  <div class="login">
    <h2>Login</h2>
    <form action="" method="POST">
      <label >Username</label>
      <input type="text" name="username" id="" required>

      <label>Password</label>
      <input type="text" name="password" id="" required>

      <input type="submit" value="Login" class="submit-button" name="Login">
    </form>
    <div class="forgot-link">
      <p>Don't have account?<a href="registration.php" >Register Now</a></p>
    </div>
  </div>
</div>

<?php if (isset($login_error)) { echo '<div style="color:red;text-align:center;">'.$login_error.'</div>'; } ?>

<!-- Footer -->
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

<span>
  <p>&copy; 2024 PageTurner. All rights reserved.</p>
</span>


</body>
</html>
