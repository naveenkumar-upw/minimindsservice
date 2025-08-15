<?php
require_once '../includes/auth.php';

// Initialize session
initSession();

// Clear all session data
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
