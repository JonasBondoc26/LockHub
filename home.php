<?php
    // Start the session
    session_start();
    include "pass_generator.php";
    $config = include('encrypt.php');
    define('ENCRYPTION_KEY', $config['encryption_key']);
   
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
        exit();
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_password'])) {
        $website = $_POST['website'];
        $username = $_POST['username'];
        $password = $_POST['password'];
    
        // Encrypt the password before storing it
        $encrypted_password = encryptPassword($password);
    
        // Check if password already exists
        $sql_check = "SELECT * FROM passwords WHERE user_id = ? AND website = ? AND username = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "iss", $_SESSION['id'], $website, $username);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
    
        if (mysqli_num_rows($result_check) > 0) {
            echo "<p style='color:red;'>This password already exists.</p>";
        } else {

            $sql = "INSERT INTO passwords (user_id, website, username, password) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isss", $_SESSION['id'], $website, $username, $encrypted_password);
    
            if (mysqli_stmt_execute($stmt)) {
                echo "<p>Password added successfully!</p>";
                header("Location: home.php");  // Redirect to avoid re-submission
                exit();
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        }
        
        mysqli_stmt_close($stmt_check);
        mysqli_stmt_close($stmt);
    }


    // Generate password
    $generated_password = "";
    if (isset($_POST['gen_pass_btn'])) {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['gen_pass_btn'])) {
            $length = !empty($_POST['gen_pass_len']) && $_POST['gen_pass_len'] > 0 ? $_POST['gen_pass_len'] : 12;
            $generated_password = pass_generator($length); 
        }
    }

    // Update password
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
        if (!isset($_POST['id']) || !isset($_POST['new_password']) || !isset($_POST['new_username'])) {
            echo "<p style='color:red;'>Error: Missing input fields.</p>";
            exit();
        }
    
        $id = $_POST['id'];
        $new_password = $_POST['new_password'];
        $new_username = $_POST['new_username'];
    
        if (empty($id) || empty($new_password) || empty($new_username)) {
            echo "<p style='color:red;'>Error: ID, username, or password is empty.</p>";
        } else {
            // Encrypt the new password before updating
            $encrypted_password = encryptPassword($new_password);
    
            $sql = "UPDATE passwords SET password = ?, username = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $encrypted_password, $new_username, $id);
    
            if ($stmt->execute()) {
                logAudit($conn, $_SESSION['id'], "Stored Password Updated", "Updated credentials for stored password ID: $id");
                echo "<script>setTimeout(() => { window.location.href = 'home.php'; }, 10);</script>";
                exit();
            } else {
                echo "<p style='color:red;'>Error updating password: " . $stmt->error . "</p>";
            }
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
            logAudit($conn, $_SESSION['id'], "Stored Password Deleted", "Deleted stored password ID: $delete_id");
            echo "<script>setTimeout(() => { window.location.href = 'home.php'; }, 10);</script>";
            exit();
        } else {
            echo "<p style='color:red;'>Error deleting password: " . $stmt->error . "</p>";
        }
    }

    $sql = "SELECT * FROM passwords WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
  // Handle account password update with password history tracking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_account_password'])) {

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $user_id = $_SESSION['id'];

    // Ensure input fields are not empty
    if (empty($current_password) || empty($new_password)) {
        echo "<p style='color:red;'>Error: All fields are required.</p>";
        exit();
    }

    // Fetch the user's current hashed password
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error (Fetch User Password): " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo "<p style='color:red;'>Error: User not found.</p>";
        exit();
    }

    // Verify the current password matches
    if (!password_verify($current_password, $user['password'])) {
        echo "<p style='color:red;'>Error: Current password is incorrect.</p>";
        exit();
    }

    // Check if the new password matches an old password in history
    $historyQuery = "SELECT old_password_hash FROM password_history WHERE user_id = ?";
    $stmt = $conn->prepare($historyQuery);
    if (!$stmt) {
        die("SQL Error (Fetch History): " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $historyResult = $stmt->get_result();
    $stmt->close();

    while ($row = $historyResult->fetch_assoc()) {
        if (password_verify($new_password, $row['old_password_hash'])) {
            echo "<p style='color:red;'>Error: You cannot reuse a previously used password.</p>";
            exit();
        }
    }

    // Store the old password in the password history table before updating
    $insertHistoryQuery = "INSERT INTO password_history (user_id, old_password_hash) VALUES (?, ?)";
    $stmt = $conn->prepare($insertHistoryQuery);
    if (!$stmt) {
        die("SQL Error (Insert History): " . $conn->error);
    }
    $stmt->bind_param("is", $user_id, $user['password']);
    if (!$stmt->execute()) {
        echo "<p style='color:red;'>Error saving old password: " . $stmt->error . "</p>";
        exit();
    }
    $stmt->close();

    // Hash the new password securely
    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the user's password in the database
    $update_sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    if (!$stmt) {
        die("SQL Error (Update Password): " . $conn->error);
    }
    $stmt->bind_param("si", $hashed_new_password, $user_id);

    if ($stmt->execute()) {
        logAudit($conn, $_SESSION['id'], "Account Password Updated", "User changed their account password.");
        echo "<script>alert('Password updated successfully!'); window.location.href = 'home.php';</script>";
    } else {
        echo "<p style='color:red;'>Error updating password: " . $stmt->error . "</p>";
    }

    $stmt->close();
    mysqli_close($conn);
}

    function encryptPassword($password) {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($password, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    function decryptPassword($encryptedPassword) {
        $data = base64_decode($encryptedPassword);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
    }
    function logAudit($conn, $user_id, $action_type, $action_description) {
        $sql = "INSERT INTO audit_logs (user_id, action_type, action_description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("SQL Error (Audit Log Insert): " . $conn->error);
        }
        $stmt->bind_param("iss", $user_id, $action_type, $action_description);
        $stmt->execute();
        $stmt->close();
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

    <!-- header section starts -->
    <header style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; background-color: #1A237E; color: white;">
    <h2 style="margin: 0px;">LOCKHUB</h2>
    <a href="logout.php" style="background-color: white; color: black; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: bold;">Logout</a>
    </header>
    <!-- end header section -->

    <!-- Main Content Section -->
    <div class="home-page">

        <!-- Password Management Section -->
</style>

<div style="display: flex; justify-content: center; gap: 50px; flex-wrap: wrap; margin-top: 50px;">

<!-- Add New Password Section -->
<div style="width: 500px; background: #e6f2ff; padding: 30px; border-radius: 10px;">
    <h3 style="text-align: center;">Add New Password</h3>
    <form action="" method="POST" style="display: flex; flex-direction: column; align-items: center;">
        <label for="website">Website:</label>
        <input type="text" id="website" name="website" required style="width: 95%; padding: 10px; margin-bottom: 15px;">

        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required style="width: 95%; padding: 10px; margin-bottom: 15px;">

        <label for="password">Password:</label>
        <input type="text" id="password" name="password" required style="width: 95%; padding: 10px; margin-bottom: 15px;">

        <button type="submit" name="add_password" style="width: 100%; padding: 12px; background-color: #0099ff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Add Password
        </button>
    </form>
</div>

<!-- Generate Strong Password Section -->
<div style="width: 500px; background: #e6f2ff; padding: 30px; border-radius: 10px;">
    <h3 style="text-align: center;">Generate Strong Password</h3>
    <form action="" method="POST" style="display: flex; flex-direction: column; align-items: center;">
        <label for="gen_pass_len">Length of password (Minimum of 12):</label>
        <input type="number" id="gen_pass_len" name="gen_pass_len" min="12" style="width: 95%; padding: 10px; margin-bottom: 15px;">

        <label for="gen_pass">Generated password:</label>
        <input type="text" id="gen_pass" name="gen_pass" readonly value="<?php echo $generated_password ?>" style="width: 95%; padding: 10px; margin-bottom: 15px;">

        <button type="submit" name="gen_pass_btn" style="width: 100%; padding: 12px; background-color: #0099ff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Generate
        </button>
    </form>
</div>

</div>

<h2>Your Stored Passwords</h2>
<table>
    <tr>
        <th>Website</th>
        <th>Username</th>
        <th>Password</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
    <tr>
        <td><?php echo htmlspecialchars($row['website']); ?></td>
        <td><?php echo htmlspecialchars($row['username']); ?></td>
        <td class="password-field">
            <?php 
                $decryptedPassword = decryptPassword($row['password']); 
            ?>
            <input type="password" id="password-<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($decryptedPassword); ?>" disabled>
            <button type="button" class="show-btn" onclick="togglePasswordVisibility(<?php echo $row['id']; ?>)">Show</button>
        </td>
    </tr>

    <!-- New Row for Actions -->
    <tr class="action-row">
        <td colspan="3" class="form-cell">
            <div class="form-wrapper">
                <!-- Update Form -->
                <form action="home.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <input type="text" name="new_username" placeholder="New Username" value="<?php echo htmlspecialchars($row['username']); ?>" required>
                    
                    <hr> <!-- Line between inputs -->
                    
                    <input type="password" name="new_password" placeholder="New Password" required>
                    <button type="submit" name="update_password" class="btn update-btn">Update</button>
                </form>
                
                <!-- Delete Form -->
                <form action="home.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this password?');">
                    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="delete_password" class="btn delete-btn">Delete</button>
                </form>
            </div>
        </td>
    </tr>

    <tr>
    <td colspan="3">
        <hr style="height: 3px; border: none; background: linear-gradient(to right, #007bff, #00aaff);">
    </td>
</tr>

    <?php } ?>
</table>

<style>
/* Center the form container */
.form-cell {
    padding: 20px;
    text-align: center;
}

/* Center and style the form container */
.form-wrapper {
    display: flex;
    flex-direction: column; 
    align-items: center; 
    width: 100%;
    max-width: 500px;
    background: white;
    padding: 30px;
    margin: 20px auto;
    border-radius: 12px;
    box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.15);
}

/* Style input fields */
input {
    width: 100%;
    padding: 14px;
    margin: 12px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 16px;
}

/* Center buttons and make them wider */
.btn-group {
    display: flex;
    flex-direction: column; 
    width: 100%;
    gap: 10px;
}

/* Make buttons longer */
.btn {
    width: 100%; 
    max-width: 450px; 
    padding: 16px; 
    border: none;
    cursor: pointer;
    border-radius: 6px;
    font-size: 18px; 
    text-align: center;
}

/* Update button */
.update-btn {
    background-color: #00bfff;
    color: white;
}

/* Delete button */
.delete-btn {
    background-color: #ff4d4d;
    color: white;
}

</style>

<form action="account_settings.php" method="POST">
    <h2>Account Settings</h2>

    <!-- Update Account Password Form -->
    <h3>Update Account Password</h3>
    <label for="current_password">Current Password:</label>
    <input type="password" id="current_password" name="current_password" required><br><br>

    <label for="new_password">New Password:</label>
    <input type="password" id="new_password" name="new_password" required><br><br>

    <button type="submit" name="update_account_password">Update Password</button>
</form>

<!-- Delete Account Form (Separate) -->
<form action="account_settings.php" method="POST" onsubmit="return confirmDeleteAcc();">
    <h3>Delete Account</h3>
    <button type="submit" name="delete_account" style="background-color:red; color:white;">Delete Account</button>
</form>

<script>
    function confirmDeleteAcc() {
        return confirm("Are you sure you want to delete your account? This action is irreversible!");
    }
</script>

<!-- Info Section -->
<section style="background-color: black; color: white; width: 100%; padding: 20px 0;">
    <div style="max-width: 100%; margin: auto; text-align: center;">
        <div style="display: flex; justify-content: space-around; flex-wrap: wrap;">
            <div>
                <h4>Contact Information</h4>
                <a href="mailto:support@lockhub.com" style="color: white; text-decoration: none; display: block;">
                    <i class="fa fa-envelope"></i> support@lockhub.com
                </a>
                <a href="tel:+011234567890" style="color: white; text-decoration: none; display: block;">
                    <i class="fa fa-phone"></i> Call +01 1234567890
                </a>
            </div>
            <div>
                <h4>About Us</h4>
                <p style="margin: 0;">LockHub is a secure password manager that stores your passwords and sensitive data, accessible anytime, anywhere.</p>
            </div>
            <div>
                <h4>Quick Links</h4>
                <a href="home.php" style="color: white; text-decoration: none; display: block;">Home</a>
                <a href="about.php" style="color: white; text-decoration: none; display: block;">About</a>
                <a href="why.php" style="color: white; text-decoration: none; display: block;">Why Us</a>
            </div>
        </div>
    </div>
</section>

<!-- Footer Section -->
<section style="background-color: white; text-align: center; padding: 10px 0; width: 100%;">
    <p style="margin: 0; color: black;">&copy; <span id="displayYear"></span> All Rights Reserved By 
        <a href="https://lockhub.com/" style="color: black; text-decoration: none;">LockHub</a>
    </p>
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