<?php
//Declare credentials
$servername = "localhost";
$username = "admin";
$password = "cuba2001";
$db = "NHS_INT";

//Create connection
$conn = mysqli_connect($servername, $username, $password, $db);
$error = null;
//Check connection
if(!$conn){
	die("Connection database failed: " . $conn->connect_error);
}
//echo "Connection established!";
?>
