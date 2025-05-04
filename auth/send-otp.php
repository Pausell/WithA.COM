<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}

$otp = random_int(100000, 999999);
$_SESSION['otp_email'] = $email;
$_SESSION['otp_code'] = $otp;
$_SESSION['otp_time'] = time();

$subject = "Your WithA Login Code";
$headers = "From: no-reply@witha.com\r\nContent-Type: text/plain; charset=UTF-8\r\n";
$message = "Your login code is: $otp\n\nThis code is valid for 5 minutes.";

if (mail($email, $subject, $message, $headers)) {
    echo json_encode(['status' => 'sent']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send email']);
}
?>