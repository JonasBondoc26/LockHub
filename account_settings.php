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

$user_id = $_SESSION['id'];

// Process password update when submitted from home.php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_account_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // Fetch the hashed password from the database
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
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
        echo "<p style='color:red;'>Current password is incorrect.</p>";
        exit();
    }

    // Check if the new password matches an old password in history
    $historyQuery = "SELECT old_password_hash FROM password_history WHERE user_id = ?";
    $stmt = $conn->prepare($historyQuery);
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

    // Store the old password in password history before updating
    $insertHistoryQuery = "INSERT INTO password_history (user_id, old_password_hash) VALUES (?, ?)";
    $stmt = $conn->prepare($insertHistoryQuery);
    $stmt->bind_param("is", $user_id, $user['password']);
    if (!$stmt->execute()) {
        echo "<p style='color:red;'>Error saving old password: " . $stmt->error . "</p>";
        exit();
    }
    $stmt->close();

    // Hash the new password
    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password in users table
    $update_sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $hashed_new_password, $user_id);

    if ($stmt->execute()) {
        echo "<script>alert('Password updated successfully!'); window.location.href = 'home.php';</script>";
    } else {
        echo "<p style='color:red;'>Error updating password: " . $stmt->error . "</p>";
    }

    $stmt->close();
}

// Delete Account
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    // Delete stored passwords first
    $delete_passwords_sql = "DELETE FROM passwords WHERE user_id = ?";
    $stmt = $conn->prepare($delete_passwords_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Delete password history
    $delete_history_sql = "DELETE FROM password_history WHERE user_id = ?";
    $stmt = $conn->prepare($delete_history_sql);
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