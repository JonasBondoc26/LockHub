<?php
    // Start the session
    session_start();
    include "pass_generator.php";
   
    // Logout logic
    if (isset($_POST['logout'])) {
        session_unset(); 
        session_destroy(); 
        header("Location: login.php"); 
        exit();
    }

    // Database connection
    $conn = mysqli_connect("localhost", "root", "", "lockhub_db");
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Check if the user is logged in
    if (!isset($_SESSION['id'])) {
        echo "Please log in.";
        exit;
    }

    // Add new password
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_password'])) {
        $website = $_POST['website'];
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Check if password already exists for the user
        $sql_check = "SELECT * FROM passwords WHERE user_id = '" . $_SESSION['id'] . "' AND website = '$website' AND username = '$username'";
        $result_check = mysqli_query($conn, $sql_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            echo "<p style='color:red;'>This password already exists.</p>";
        } else {
            // If password doesn't exist, insert it
            $sql = "INSERT INTO passwords (user_id, website, username, password) 
                    VALUES ('" . $_SESSION['id'] . "', '$website', '$username', '$password')";
            
            if (mysqli_query($conn, $sql)) {
                echo "<p>Password added successfully!</p>";
                header("Location: home.php");  // Redirect to avoid re-submission
                exit();
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        }
    }


    // Generate password
    $generated_password = "";
    if (isset($_POST['gen_pass_btn'])) {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['gen_pass_btn'])) {
            $length = !empty($_POST['gen_pass_len']) && $_POST['gen_pass_len'] > 0 ? $_POST['gen_pass_len'] : 12;
            $generated_password = pass_generator($length); 
        }
    }

    // Delete password
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_password'])) {
        if (!isset($_POST['delete_id'])) {
            echo "<p style='color:red;'>Error: Missing ID.</p>";
            exit();
        }
    
        $delete_id = $_POST['delete_id'];
    
        $sql = "DELETE FROM passwords WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $delete_id);
    
        if ($stmt->execute()) {
            echo "<script>setTimeout(() => { window.location.href = 'home.php'; }, 10);</script>";
            exit();
        } else {
            echo "<p style='color:red;'>Error deleting password: " . $stmt->error . "</p>";
        }
    }

    // Fetch stored passwords for display
    $sql = "SELECT * FROM passwords WHERE user_id = '" . $_SESSION['id'] . "'";
    $result = mysqli_query($conn, $sql);

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_account_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
    
        // Fetch the current password from the database
        $sql = "SELECT password FROM users WHERE id = '" . $_SESSION['id'] . "'";
        $result = mysqli_query($conn, $sql);
        $user = mysqli_fetch_assoc($result);
    
        if (password_verify($current_password, $user['password'])) {
            // If current password matches, update to the new password
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $hashed_new_password, $_SESSION['id']);
            
            if ($stmt->execute()) {
                echo "<p>Password updated successfully!</p>";
            } else {
                echo "<p style='color:red;'>Error updating password: " . $stmt->error . "</p>";
            }
        } else {
            echo "<p style='color:red;'>Current password is incorrect.</p>";
        }
    }
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="keywords" content="" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <link rel="shortcut icon" href="images/favicon.png" type="">
  <title> LOCKHUB </title>
  <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
  <link href="css/font-awesome.min.css" rel="stylesheet" />
  <link href="css/style.css" rel="stylesheet" />
  <link href="css/responsive.css" rel="stylesheet" />

</head>

<body>

  <div class="hero_area">

    <div class="hero_bg_box">
      <div class="bg_img_box">
        <img src="images/hero.png" alt="">
      </div>
    </div>
    <!-- header section starts -->
    <header class="header_section">
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg custom_nav-container">
        <a class="navbar-brand" href="home.php">
            <span>
            LOCKHUB
            </span>
        </a>

        <!-- Logout button aligned to the right -->
        <div class="ml-auto">
            <button type="button" class="btn logout-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
        
        </nav>
    </div>
    </header>
    <!-- end header section -->

    <!-- Main Content Section -->
    <div class="home-page">
        <!-- Password Management Section -->
        <h2>Your Stored Passwords</h2>
        <table>
            <tr>
                <th>Website</th>
                <th>Username</th>
                <th>Password</th>
                <th>Action</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo $row['website']; ?></td>
                <td><?php echo $row['username']; ?></td>
                <td class="password-field">
                    <input type="password" id="password-<?php echo $row['id']; ?>" value="<?php echo $row['password']; ?>" disabled>
                    <button type="button" class="show-btn" onclick="togglePasswordVisibility(<?php echo $row['id']; ?>)">Show</button>
                </td>
                <td>
                    <form action="home.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $row['id'] ?>">
                        <input type="text" name="new_username" placeholder="New Username" value="<?php echo $row['username']; ?>" required><br><br>
                        <input type="password" name="new_password" placeholder="New Password" required><br><br>
                        <button type="submit" name="update_password">Update Password</button>
                    </form>

                    <!-- Delete Button -->
                    <form action="home.php" method="POST">
                        <input type="hidden" name="delete_id" value="<?php echo $row['id'] ?>">
                        <button type="submit" name="delete_password" class="btn delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
            <?php } ?>
        </table>

        <h3>Add New Password</h3>
        <form action="" method="POST">
            <label for="website">Website:</label>
            <input type="text" id="website" name="website" required><br><br>

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required><br><br>

            <label for="password">Password:</label>
            <input type="text" id="password" name="password" required><br><br>

            <button type="submit" name="add_password">Add Password</button>
        </form>

        <!-- Password Generator -->
        <h3>Generate Strong Password</h3>
        <form action="" method="POST">
            <label for="gen_pass_len">Length of password (Minimum of 12):</label>
            <input type="number" id="gen_pass_len" name="gen_pass_len" min="12"><br><br>

            <label for="gen_pass">Generated password:</label>
            <input type="text" id="gen_pass" name="gen_pass" readonly value="<?php echo $generated_password ?>"><br><br>
            <button type="submit" name="gen_pass_btn">Generate</button>
        </form>
    
        <h2>Account Settings</h2>

        <!-- HTML Form for Updating Password -->
        <h3>Update Account Password</h3>
        <form action="" method="POST" onsubmit="return confirmUpdate();">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required><br><br>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required><br><br>

            <button type="submit" name="update_account_password">Update Password</button>
        </form>

        <script>
            // Function to confirm password update
            function confirmUpdate() {
                return confirm("Are you sure you want to update your password?");
            }
        </script>

        <!-- Delete Account Form -->
        <h3>Delete Account</h3>
        <form action="account_settings.php" method="POST" onsubmit="return confirmDelete();">
            <button type="submit" name="delete_account" style="background-color:red; color:white;">Delete Account</button>
        </form>
                
        
        <script>
            function confirmDelete() {
                return confirm("Are you sure you want to delete your account? This action is irreversible!");
            }
        </script>
    </div>


    <!-- Info Section -->
    <section class="info_section layout_padding2">
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-lg-3 info_col">
                    <div class="info_contact">
                        <h4>Contact Information</h4>
                        <div class="contact_link_box">
                            <a href="mailto:support@lockhub.com"><i class="fa fa-envelope" aria-hidden="true"></i> support@lockhub.com</a>
                            <a href="tel:+011234567890"><i class="fa fa-phone" aria-hidden="true"></i> Call +01 1234567890</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 info_col">
                    <div class="info_detail">
                        <h4>About Us</h4>
                        <p>LockHub is a secure password manager that stores your passwords and sensitive data, accessible anytime, anywhere.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mx-auto info_col">
                    <div class="info_link_box">
                        <h4>Quick Links</h4>
                        <div class="info_links">
                            <a href="home.php">Home</a>
                            <a href="about.php">About</a>
                            <a href="why.php">Why Us</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 info_col">
                    <h4>Subscribe</h4>
                    <form action="#">
                        <input type="text" placeholder="Enter email" />
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <section class="footer_section">
        <div class="container">
            <p>&copy; <span id="displayYear"></span> All Rights Reserved By <a href="https://lockhub.com/">LockHub</a></p>
        </div>
    </section>

    <script>
        // Toggle the visibility of the password
        function togglePasswordVisibility(id) {
            var passwordField = document.getElementById('password-' + id);
            var currentType = passwordField.type;

            if (currentType === 'password') {
                passwordField.type = 'text';
            } else {
                passwordField.type = 'password';
            }
        }
    </script>


</body>
</html>