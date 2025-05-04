<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (
    isset($_SESSION['otp_email'], $_SESSION['otp_code'], $_SESSION['otp_time']) &&
    $_SESSION['otp_email'] === $email &&
    time() - $_SESSION['otp_time'] < 300
) {
    echo json_encode(['code' => $_SESSION['otp_code']]);
} else {
    echo json_encode(['code' => null]);
}
?>