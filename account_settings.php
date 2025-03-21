<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "lockhub_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Ensure user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Update Account Password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_account_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    
    // Fetch the current password from database
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        echo "Stored hashed password: " . $user['password'] . "<br>";
        echo "Entered password: " . $current_password . "<br>";

        if (password_verify($current_password, $user['password'])) {
            echo "Password match!";
        } else {
            echo "Password mismatch!";
        }
    }

    if ($user && password_verify($current_password, $user['password'])) {
        // Hash new password
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $hashed_new_password, $_SESSION['id']);

        if ($update_stmt->execute()) {
            echo "<script>alert('Password updated successfully!'); window.location.href = 'home.php';</script>";
        } else {
            echo "<p style='color:red;'>Error updating password: " . $update_stmt->error . "</p>";
        }
    } else {
        echo "<p style='color:red;'>Current password is incorrect.</p>";
    }
}

// Delete Account
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    $user_id = $_SESSION['id'];

    // Delete all stored passwords first (optional, to clean up data)
    $delete_passwords_sql = "DELETE FROM passwords WHERE user_id = ?";
    $stmt = $conn->prepare($delete_passwords_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Delete user account
    $delete_user_sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_user_sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        session_unset();
        session_destroy();
        echo "<script>alert('Account deleted successfully!'); window.location.href = 'login.php';</script>";
        exit();
    } else {
        echo "<p style='color:red;'>Error deleting account: " . $stmt->error . "</p>";
    }
}

mysqli_close($conn);
?>