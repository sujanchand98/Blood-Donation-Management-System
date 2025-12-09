<?php
// session_start();
include 'config/database.php';
include 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($auth->login($username, $password)) {
        $auth->redirectBasedOnRole();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Blood Donation System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    
    
    <div class="container">
        <div class="form-container">
            <h2>Login</h2>
            
            <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 1rem;"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
            
            <p style="margin-top: 1rem;">
                Don't have an account? 
                <a href="register.php">Register here</a>  
            </p>
        </div>
    </div>
    
  
</body>
</html>