<?php
/**
 * Add Custom Admin User
 * Creates admin12 user
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>Creating Custom Admin User</h2>";
    
    // New admin credentials
    $username = 'admin12';
    $email = 'admin12@example.com';
    $password = 'admin123';
    $role = 'admin';
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT user_id, username, email FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "<div style='color: orange; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; margin: 20px 0;'>";
        echo "<strong>⚠️ User already exists!</strong><br><br>";
        echo "User ID: " . $existingUser['user_id'] . "<br>";
        echo "Username: " . $existingUser['username'] . "<br>";
        echo "Email: " . $existingUser['email'] . "<br>";
        echo "</div>";
        
        // Option to update password
        echo "<h3>Update Password</h3>";
        echo "<p>Do you want to reset the password to: <strong>admin123</strong>?</p>";
        
        if (isset($_POST['update_password'])) {
            $newPasswordHash = hashPassword($password);
            $updateStmt = $conn->prepare("UPDATE users SET password_hash = ?, role = 'admin', status = 'active' WHERE username = ?");
            $updateStmt->execute([$newPasswordHash, $username]);
            
            echo "<div style='color: green; padding: 15px; background: #d4edda; border: 1px solid #28a745; border-radius: 8px; margin: 20px 0;'>";
            echo "<strong>✓ Password updated successfully!</strong><br><br>";
            echo "<strong>Login Credentials:</strong><br>";
            echo "Username: <strong>admin12</strong><br>";
            echo "Email: <strong>admin12@example.com</strong><br>";
            echo "Password: <strong>admin123</strong>";
            echo "</div>";
            
            echo "<p><a href='auth/login.php' style='padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; font-weight: bold;'>Go to Login Page →</a></p>";
        } else {
            echo "<form method='POST'>";
            echo "<button type='submit' name='update_password' style='padding: 12px 24px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold;'>Yes, Update Password</button>";
            echo "</form>";
        }
    } else {
        // Create new admin user
        echo "<p>Creating new admin user...</p>";
        
        $passwordHash = hashPassword($password);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$username, $email, $passwordHash, $role]);
        
        echo "<div style='color: green; padding: 15px; background: #d4edda; border: 1px solid #28a745; border-radius: 8px; margin: 20px 0;'>";
        echo "<strong>✓ Admin user created successfully!</strong><br><br>";
        echo "<strong>Login Credentials:</strong><br>";
        echo "Username: <strong>admin12</strong><br>";
        echo "Email: <strong>admin12@example.com</strong><br>";
        echo "Password: <strong>admin123</strong>";
        echo "</div>";
        
        echo "<p><a href='auth/login.php' style='padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; font-weight: bold;'>Go to Login Page →</a></p>";
    }
    
    echo "<hr style='margin: 30px 0;'>";
    echo "<h3>All Admin/Registrar Users:</h3>";
    
    $stmt = $conn->query("SELECT user_id, username, email, role, status, created_at FROM users ORDER BY user_id");
    $allUsers = $stmt->fetchAll();
    
    if (empty($allUsers)) {
        echo "<p style='color: #666;'>No users found in the database.</p>";
    } else {
        echo "<table border='1' cellpadding='12' style='border-collapse: collapse; width: 100%; margin-top: 15px;'>";
        echo "<tr style='background: #6366f1; color: white;'>";
        echo "<th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Created At</th>";
        echo "</tr>";
        
        foreach ($allUsers as $user) {
            $rowColor = $user['status'] === 'active' ? '#f8f9fa' : '#ffe6e6';
            echo "<tr style='background: $rowColor;'>";
            echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td><span style='padding: 4px 8px; background: #6366f1; color: white; border-radius: 4px; font-size: 12px;'>" . htmlspecialchars($user['role']) . "</span></td>";
            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 15px; background: #f8d7da; border: 1px solid #dc3545; border-radius: 8px; margin: 20px 0;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<p style='margin-top: 20px;'><strong>Troubleshooting:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Check your database credentials in the .env file</li>";
    echo "<li>Verify the database 'std_register' exists</li>";
    echo "</ul>";
}
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 1000px;
        margin: 50px auto;
        padding: 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }
    
    h2, h3 {
        color: #333;
    }
    
    h2 {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    p, ul {
        background: white;
        padding: 15px;
        border-radius: 8px;
        margin: 10px 0;
    }
    
    table {
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    hr {
        border: none;
        border-top: 2px solid rgba(255,255,255,0.3);
    }
</style>
