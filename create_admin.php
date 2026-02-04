<?php
/**
 * Create Admin User Script
 * This script creates the default admin user if it doesn't exist
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>Admin User Creation Script</h2>";
    
    // Check if admin user exists
    $stmt = $conn->prepare("SELECT user_id, username, email, role FROM users WHERE username = ? OR email = ?");
    $stmt->execute(['admin', 'admin@studentregistration.edu']);
    $existingAdmin = $stmt->fetch();
    
    if ($existingAdmin) {
        echo "<div style='color: orange; padding: 10px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Admin user already exists:</strong><br>";
        echo "User ID: " . $existingAdmin['user_id'] . "<br>";
        echo "Username: " . $existingAdmin['username'] . "<br>";
        echo "Email: " . $existingAdmin['email'] . "<br>";
        echo "Role: " . $existingAdmin['role'] . "<br>";
        echo "</div>";
        
        // Option to reset password
        echo "<h3>Reset Admin Password</h3>";
        echo "<p>If you forgot the password, you can reset it to the default: <strong>Admin@123</strong></p>";
        
        if (isset($_POST['reset_password'])) {
            $newPasswordHash = hashPassword('Admin@123');
            $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
            $updateStmt->execute([$newPasswordHash]);
            
            echo "<div style='color: green; padding: 10px; background: #d4edda; border: 1px solid #28a745; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>✓ Password reset successfully!</strong><br>";
            echo "You can now login with:<br>";
            echo "Username: <strong>admin</strong><br>";
            echo "Password: <strong>Admin@123</strong>";
            echo "</div>";
        } else {
            echo "<form method='POST'>";
            echo "<button type='submit' name='reset_password' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>Reset Password to Admin@123</button>";
            echo "</form>";
        }
    } else {
        // Create admin user
        echo "<p>Admin user not found. Creating default admin user...</p>";
        
        $username = 'admin';
        $email = 'admin@studentregistration.edu';
        $password = 'Admin@123';
        $passwordHash = hashPassword($password);
        $role = 'admin';
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$username, $email, $passwordHash, $role]);
        
        echo "<div style='color: green; padding: 10px; background: #d4edda; border: 1px solid #28a745; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✓ Admin user created successfully!</strong><br><br>";
        echo "<strong>Login Credentials:</strong><br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Email: <strong>admin@studentregistration.edu</strong><br>";
        echo "Password: <strong>Admin@123</strong><br><br>";
        echo "<em>⚠️ Please change this password after your first login!</em>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h3>All Admin Users in Database:</h3>";
    
    $stmt = $conn->query("SELECT user_id, username, email, role, status, created_at FROM users ORDER BY user_id");
    $allUsers = $stmt->fetchAll();
    
    if (empty($allUsers)) {
        echo "<p>No users found in the database.</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Created At</th>";
        echo "</tr>";
        
        foreach ($allUsers as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p><a href='auth/login.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; background: #f8d7da; border: 1px solid #dc3545; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2 {
        color: #333;
    }
</style>
