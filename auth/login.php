<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__.'/schema.php';

header('Content-Type: application/json');

// If the request is just to check login and fetch profile picture
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['user'])) {
        $stmt = $pdo->prepare("SELECT email, profile_image FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']]);
        $user = $stmt->fetch();
        if ($user) {
            echo json_encode([
                'loggedIn' => true,
                'email' => $user['email'],
                'profile_image' => $user['profile_image']
            ]);
            exit;
        }
    }
    echo json_encode(['loggedIn' => false]);
    exit;
}

// Handle login attempt
$data = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($data['email'] ?? ''));
$otp = trim($data['otp'] ?? '');

$debugLog = __DIR__ . '/debug/login-debug.log';
file_put_contents($debugLog, "\n----- LOGIN ATTEMPT " . date('c') . " -----\n", FILE_APPEND);
file_put_contents($debugLog, print_r([
  'session_id' => session_id(),
  'session' => $_SESSION,
  'email_input' => $email,
  'otp_input' => $otp,
  'timestamp' => time(),
  'session_age' => isset($_SESSION['otp_time']) ? time() - $_SESSION['otp_time'] : null
], true), FILE_APPEND);

if (
    isset($_SESSION['otp_email'], $_SESSION['otp_code'], $_SESSION['otp_time']) &&
    strtolower($_SESSION['otp_email']) === $email &&
    $_SESSION['otp_code'] == $otp &&
    time() - $_SESSION['otp_time'] < 300
) {
    try {
        // Get or create user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $userId = $user['id'];
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (email, last_login) VALUES (?, NOW())");
            $stmt->execute([$email]);
            $userId = $pdo->lastInsertId();
        }

        if (!$userId || !is_numeric($userId)) {
            throw new Exception('Invalid user ID');
        }

        // Log session
        $sid = session_id();
        $ip = $_SERVER['REMOTE_ADDR'];
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $stmt = $pdo->prepare("INSERT INTO sessions (user_id, session_id, ip_address, user_agent)
                               VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $sid, $ip, $agent]);

        $_SESSION['user'] = $userId;
        unset($_SESSION['otp_code'], $_SESSION['otp_time']);

        file_put_contents($debugLog, "Login success: user ID $userId\n", FILE_APPEND);
        echo json_encode(['status' => 'logged-in']);
    } catch (Throwable $e) {
        $errorMessage = 'Server error: ' . $e->getMessage();
        file_put_contents($debugLog, $errorMessage . "\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode(['error' => 'Server error', 'details' => $e->getMessage()]);
    }
} else {
    file_put_contents($debugLog, "Login failed: OTP or session mismatch\n", FILE_APPEND);
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired code']);
}
?>
