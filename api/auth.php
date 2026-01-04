<?php
/**
 * Authentication API
 * Handles login, logout, registration, and session management
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle different actions
switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        checkSession();
        break;
    case 'update_profile':
        updateProfile();
        break;
    case 'change_password':
        changePassword();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

/**
 * Handle user login
 */
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = isset($data['password']) ? $data['password'] : '';

    if (empty($email) || empty($password)) {
        sendResponse(false, 'Email and password are required');
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            logActivity(null, 'login_failed', "Failed login attempt for email: $email");
            sendResponse(false, 'Invalid email or password');
            return;
        }

        if (!password_verify($password, $user['password'])) {
            logActivity($user['user_id'], 'login_failed', 'Invalid password');
            sendResponse(false, 'Invalid email or password');
            return;
        }

        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);

        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        logActivity($user['user_id'], 'login_success', 'User logged in successfully');

        sendResponse(true, 'Login successful', [
            'user_id' => $user['user_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'redirect' => getRedirectUrl($user['role'])
        ]);

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        sendResponse(false, 'An error occurred during login');
    }
}

/**
 * Handle user registration
 */
function handleRegister() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = isset($data['name']) ? trim($data['name']) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    $phone = isset($data['phone']) ? trim($data['phone']) : '';
    $password = isset($data['password']) ? $data['password'] : '';
    $confirm_password = isset($data['confirm_password']) ? $data['confirm_password'] : '';

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        sendResponse(false, 'Name, email, and password are required');
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Invalid email format');
        return;
    }

    if (strlen($password) < 6) {
        sendResponse(false, 'Password must be at least 6 characters');
        return;
    }

    if ($password !== $confirm_password) {
        sendResponse(false, 'Passwords do not match');
        return;
    }

    try {
        $db = getDB();
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendResponse(false, 'Email already registered');
            return;
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role, status, email_verified) VALUES (?, ?, ?, ?, 'guest', 'active', FALSE)");
        $stmt->execute([$name, $email, $phone, $hashed_password]);

        $user_id = $db->lastInsertId();

        logActivity($user_id, 'user_registered', 'New user registered');

        // Auto login after registration
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'guest';
        $_SESSION['logged_in'] = true;

        // Get base path for redirect
        $basePath = str_replace('/api/auth.php', '', $_SERVER['SCRIPT_NAME']);

        sendResponse(true, 'Registration successful', [
            'user_id' => $user_id,
            'name' => $name,
            'email' => $email,
            'role' => 'guest',
            'redirect' => $basePath . '/profile/index.php'
        ]);

    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        sendResponse(false, 'An error occurred during registration');
    }
}

/**
 * Handle user logout
 */
function handleLogout() {
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    }

    session_destroy();

    // Get base path for redirect
    $basePath = str_replace('/api/auth.php', '', $_SERVER['SCRIPT_NAME']);
    sendResponse(true, 'Logout successful', ['redirect' => $basePath . '/index.php']);
}

/**
 * Check if user is logged in
 */
function checkSession() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        sendResponse(true, 'Session active', [
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ]);
    } else {
        sendResponse(false, 'No active session');
    }
}

/**
 * Update user profile
 */
function updateProfile() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        sendResponse(false, 'Unauthorized');
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user_id'];

    $name = isset($data['name']) ? trim($data['name']) : '';
    $phone = isset($data['phone']) ? trim($data['phone']) : '';
    $address = isset($data['address']) ? trim($data['address']) : '';
    $city = isset($data['city']) ? trim($data['city']) : '';
    $country = isset($data['country']) ? trim($data['country']) : '';
    $postal_code = isset($data['postal_code']) ? trim($data['postal_code']) : '';

    if (empty($name)) {
        sendResponse(false, 'Name is required');
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ?, city = ?, country = ?, postal_code = ? WHERE user_id = ?");
        $stmt->execute([$name, $phone, $address, $city, $country, $postal_code, $user_id]);

        $_SESSION['user_name'] = $name;

        logActivity($user_id, 'profile_updated', 'User updated profile information');

        sendResponse(true, 'Profile updated successfully');

    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        sendResponse(false, 'An error occurred while updating profile');
    }
}

/**
 * Change user password
 */
function changePassword() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        sendResponse(false, 'Unauthorized');
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user_id'];

    $current_password = isset($data['current_password']) ? $data['current_password'] : '';
    $new_password = isset($data['new_password']) ? $data['new_password'] : '';
    $confirm_password = isset($data['confirm_password']) ? $data['confirm_password'] : '';

    if (empty($current_password) || empty($new_password)) {
        sendResponse(false, 'All fields are required');
        return;
    }

    if (strlen($new_password) < 6) {
        sendResponse(false, 'New password must be at least 6 characters');
        return;
    }

    if ($new_password !== $confirm_password) {
        sendResponse(false, 'New passwords do not match');
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!password_verify($current_password, $user['password'])) {
            sendResponse(false, 'Current password is incorrect');
            return;
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashed_password, $user_id]);

        logActivity($user_id, 'password_changed', 'User changed password');

        sendResponse(true, 'Password changed successfully');

    } catch (Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        sendResponse(false, 'An error occurred while changing password');
    }
}

/**
 * Get redirect URL based on user role
 */
function getRedirectUrl($role) {
    // Get the base path from the current script location
    $basePath = str_replace('/api/auth.php', '', $_SERVER['SCRIPT_NAME']);

    switch ($role) {
        case 'admin':
            return $basePath . '/admin/index.php';
        case 'clark':
            return $basePath . '/clark/index.php';
        case 'staff':
            return $basePath . '/staff/index.php';
        default:
            return $basePath . '/profile/index.php';
    }
}

/**
 * Log user activity
 */
function logActivity($user_id, $action_type, $description) {
    try {
        $db = getDB();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, action_type, action_description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action_type, $description, $ip_address, $user_agent]);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>

