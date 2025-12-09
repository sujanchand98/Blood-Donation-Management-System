<?php
session_start();

class Auth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function register($username, $email, $password, $role, $full_name, $blood_group = null, $phone = null, $address = null) {
        // Password strength algorithm
        if (!$this->validatePasswordStrength($password)) {
            return "Password must be at least 8 characters with uppercase, lowercase, and numbers";
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, role, full_name, blood_group, phone, address) 
                  VALUES (:username, :email, :password, :role, :full_name, :blood_group, :phone, :address)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":role", $role);
        $stmt->bindParam(":full_name", $full_name);
        $stmt->bindParam(":blood_group", $blood_group);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":address", $address);
        
        if ($stmt->execute()) {
            return true;
        }
        return "Registration failed";
    }
    
    public function login($username, $password) {
        $query = "SELECT id, username, password, role FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
        }
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function redirectBasedOnRole() {
        if ($this->isLoggedIn()) {
            switch ($_SESSION['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'donor':
                    header("Location: donor/dashboard.php");
                    break;
                case 'seeker':
                    header("Location: seeker/dashboard.php");
                    break;
            }
            exit;
        }
    }
    
    public function logout() {
        session_destroy();
        header("Location: ../index.php");
        exit;
    }
    
    private function validatePasswordStrength($password) {
        // Password strength algorithm
        $min_length = 8;
        $has_uppercase = preg_match('@[A-Z]@', $password);
        $has_lowercase = preg_match('@[a-z]@', $password);
        $has_number = preg_match('@[0-9]@', $password);
        
        return strlen($password) >= $min_length && $has_uppercase && $has_lowercase && $has_number;
    }
}
?>