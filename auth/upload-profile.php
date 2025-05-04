<?php
/*--------------------------------------------------------------------
  /auth/upload-profile.php
  Avatar uploader — validates, stores, and cleans up previous image
--------------------------------------------------------------------*/
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/schema.php';   // guarantees users, profile_image column

header('Content-Type: application/json');

/* ── configuration ──────────────────────────────────────────────── */
const MAX_BYTES = 1 * 1024 * 1024;      // 1 MB
$ALLOWED_MIME   = ['image/png','image/jpeg','image/gif','image/svg+xml'];
/* ---------------------------------------------------------------- */

/* 1 ▸ must be logged-in */
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Not logged in']); exit;
}

/* 2 ▸ basic upload checks */
if (empty($_FILES['profile_image']) ||
    $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'No file uploaded']); exit;
}

/* 3 ▸ size & MIME validation */
if ($_FILES['profile_image']['size'] > MAX_BYTES) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'File too large (max 1 MB)']); exit;
}

$mime = mime_content_type($_FILES['profile_image']['tmp_name']);
if (!in_array($mime, $ALLOWED_MIME)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Unsupported file type, try: jpeg, png, gif, svg']); exit;
}

/* 4 ▸ generate target path */
$ext   = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION) ?: 'png';
$name  = 'u'.$_SESSION['user'].'_'.bin2hex(random_bytes(4)).'.'.$ext;
$dir   = __DIR__.'/../media/profiles';
$url   = '/media/profiles/'.$name;

if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Cannot create folder']); exit;
}

if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], "$dir/$name")) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'File move failed']); exit;
}

/* 5 ▸ remove previous avatar (if any) */
$prev = $pdo->prepare("SELECT profile_image FROM users WHERE id=?");
$prev->execute([$_SESSION['user']]);
$oldPath = $prev->fetchColumn();

if ($oldPath && str_starts_with($oldPath, '/media/profiles/') &&
    file_exists(__DIR__.'/..'.$oldPath)) {
    @unlink(__DIR__.'/..'.$oldPath);                // suppress error if missing
}

/* 6 ▸ update DB */
$ok = $pdo->prepare("UPDATE users SET profile_image=? WHERE id=?")
          ->execute([$url, $_SESSION['user']]);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'DB update failed']); exit;
}

/* 7 ▸ success */
echo json_encode(['success'=>true,'url'=>$url]);
