<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://kit.fontawesome.com/ae26b5f911.js" crossorigin="anonymous"></script>
  <title>Register</title>
  <style>
    /* your same CSS (unchanged) */
    * {
      margin: 0;
      padding: 0;
      overflow-y: scroll;
      scrollbar-width: none;
      -ms-overflow-style: none;
    }

    body {
      font-family: "Inter", system-ui, sans-serif;
      display: flex;
      flex-direction: column;
      background-color: #f9f9f9;
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

    .register-container {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 0;
    }

    .register {
      background: #fff;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.12);
      padding: 30px 40px;
      border-radius: 20px;
      width: 300px;
    }
    .plus-icon{
      z-index:1;
      position:absolute;
      margin-left:200px;
      margin-top:135px;
    }
    .circle-wrapper {
  position: relative;
  width: 150px;
  height: 150px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid #ccc;
  cursor: pointer;
  margin-left:60px;
}

.circle-wrapper input {
  position: absolute;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
  z-index: 2;
}

.circle-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 1;
  display: block;
}
    .register h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .register label {
      font-size: 14px;
      display: block;
      margin-top: 15px;
    }

    .field {
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

    .login-link {
      text-align: center;
      margin-top: 15px;
    }

    .login-link a {
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

<?php
session_start();
include 'dbconnect.php';
$userProfile = null;
if (isset($_SESSION['uid'])) {
    $id = intval($_SESSION['uid']);
    $result = mysqli_query($conn, "SELECT * FROM store WHERE ID = $id");
    if ($result && mysqli_num_rows($result) === 1) {
        $userProfile = mysqli_fetch_assoc($result);
    }
}
?>

<nav>
  <a href="trial.php"><img src="./images/logo.png" id="logo"></a>
  <ul>
    <li><a href="trial.php">Home</a></li>
    <li><a href="">All Books</a></li>
    <li><a href="bestsellers.php">Bestsellers</a></li>
    <li><a href="bookcategories.php">Categories</a></li>
    <li><input type="search" id="ni"></li>
    <li><a href=""><i class="fa-solid fa-heart nav-icon"></i></a></li>
    <li><a href=""><i class="fa-solid fa-cart-shopping nav-icon"></i></a></li>
    <?php if ($userProfile): ?>
    <li><a href="profile.php"><img src="<?php echo $userProfile['picture']; ?>" alt="Profile" height="40px" width="40px" style="border-radius:20px;margin-bottom:-13px; margin-left:10px"></a></li>
    <?php else: ?>
    <li><a href="login.php"><i class="fa-solid fa-user nav-icon">&nbsp;LOGIN</i></a></li>
    <?php endif; ?>
  </ul>
</nav>


<div class="register-container">
  <div class="register">
    <h2>Create Account</h2>
    <form action="" method="POST" enctype="multipart/form-data">
      <div class="plus-icon">
        <i class="fa-solid fa-plus "></i>
      </div>
     <div class="circle-wrapper">
    <input type="file" id="fileInput" name="image" accept="image/*" onchange="imagesrc()">
    <img id="preview" src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png" >
  </div>

      <label for="username">Username</label>
      <input type="text" class="field" name="username" required>

      <label for="email">Email</label>
      <input type="email" class="field" name="email" required>

      <label for="password">Password</label>
      <input type="password" class="field" name="password" required>
      
       

      <input type="submit" class="submit-button" name="Submit" value="REGISTER">
    </form>
    <div class="login-link">
      <p id="noti">Already have an account? <a href="login.php">Login</a></p>
    </div>
  </div>
</div>
<script>
function imagesrc() {
   upimage = document.getElementById('fileInput');
   preimage = document.getElementById('preview');

  if (upimage.files && upimage.files[0]) {
    preimage.src = URL.createObjectURL(upimage.files[0]);
    preimage.alt = upimage.files[0].name;
  }
}
</script>

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
    <h4>Customer Service</h4><br>
    <ul>
      <li><a href="">Help Center</a></li>
      <li><a href="">Shipping Info</a></li>
      <li><a href="">Returns</a></li>
      <li><a href="">Contact Us</a></li>
    </ul>
  </div>
  <div class="quicklinks">
    <h4>Quick Links</h4><br>
    <ul>
      <li><a href="bestsellers.html">Bestsellers</a></li>
      <li><a href="#">Gift Cards</a></li>
      <li><a href="#">New Releases</a></li>
    </ul>
  </div>
  <div class="connect">
    <h4>Connect</h4><br>
    <ul>
      <li><a href="">Newsletter</a></li>
      <li><a href="https://www.instagram.com/lowkyess" target="_blank">Social Media</a></li>
      <li><a href="">Book Club</a></li>
      <li><a href="">Events</a></li>
    </ul>
  </div>
</footer>
<span>
  <p>© 2024 PageTurner. All rights reserved.</p>
</span>
<script>
  upimage=document.getElementById('fileInput');
  preimage=document.getElementById('preview');
  
</script>
<?php

include 'dbconnect.php';
 if(isset($_POST['Submit'])){
  $username=$_POST['username'];
  $email=$_POST['email'];
  $password=$_POST['password'];
  $Pic=$_FILES['image']['name'];
  $temp1=$_FILES['image']['tmp_name'];
  $folder1='PIC/'.$Pic;
  move_uploaded_file($temp1,$folder1);
  $sql="INSERT INTO store (Username,Email,Password,picture) VALUES ('$username','$email','$password','$folder1')";
  $result=mysqli_query($conn,$sql);
  if ($result) {
    echo "<script>
        window.location.href = 'http://localhost/BOOKSTOREPROJECT/login.php/';
    </script>";
    exit();
}
 else {
                echo "<script>alert('Registration failed');</script>";
            }
 }
?>
</body>
</html>
