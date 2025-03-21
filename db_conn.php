<?php

$sname= "localhost";
$uname= "root";
$password = "";
$db_name = "lockhub_db";

$conn = mysqli_connect($sname, $uname, $password, $db_name);

if (!$conn) {
	die("Connection failed!");
}

if($_SERVER)
?>