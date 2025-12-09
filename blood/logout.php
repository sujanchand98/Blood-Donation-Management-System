<?php
// logout.php - Minimal version
session_start();
session_destroy();

// Redirect immediately to login page
header("Location: homepage.php");
exit();
?>