<?php
$conn=mysqli_connect('localhost','root','','bookstore');
if($conn){
    echo '';
}else{
    echo 'Error in database connection!';
}
?>