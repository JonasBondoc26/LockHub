<?php 
session_start(); 
include "db_conn.php";

if (isset($_POST['uname']) && isset($_POST['password'])) {

	function validate($data){
       $data = trim($data);
	   $data = stripslashes($data);
	   $data = htmlspecialchars($data);
	   return $data;
	}

	$uname = validate($_POST['uname']);
	$pass = validate($_POST['password']);

	if (empty($uname)) {
		header("Location: index.php?error=User Name is required");
	    exit();
	} else if (empty($pass)) {
        header("Location: index.php?error=Password is required");
	    exit();
	} else {
		// Fetch the user from the database
		$sql = "SELECT * FROM users WHERE user_name = ?";
		$stmt = mysqli_prepare($conn, $sql);
		mysqli_stmt_bind_param($stmt, "s", $uname);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);

		if ($row = mysqli_fetch_assoc($result)) {
			// Verify the entered password against the stored hashed password
			if (password_verify($pass, $row['password'])) {
				// Correct login - Start session
				$_SESSION['user_name'] = $row['user_name'];
				$_SESSION['name'] = $row['name'];
				$_SESSION['id'] = $row['id'];
				header("Location: home.php");
				exit();
			} else {
				header("Location: index.php?error=Incorrect Username or Password");
				exit();
			}
		} else {
			header("Location: index.php?error=Incorrect Username or Password");
			exit();
		}
	}
} else {
	header("Location: index.php");
	exit();
}