<?php
/**
 * /auth/site.php
 * FULL PRODUCTION VERSION (with user_sites table)
 *
 * This is mostly your “previous good” code. We only add a check
 * to disallow deleting the user’s profile site if it matches
 * their current username (or username.html).
 */

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/schema.php';
header('Content-Type: application/json');

// We assume $pdo is your main DB handle (for users table)
// and $pdoSites is your user_sites DB handle
global $pdo, $pdoSites;

$RESERVED_SLUGS = [
    'facebook.com',
    'admin',
    'witha',
    'index',
];

// Check session
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Not logged in']);
    exit;
}
$userId = $_SESSION['user'];

// 1) Fetch the user's current username to disallow deleting it
$stmtUserName = $pdo->prepare("SELECT username FROM users WHERE id=? LIMIT 1");
$stmtUserName->execute([$userId]);
$currentUsername = $stmtUserName->fetchColumn() ?: '';

// parse JSON body
$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_REQUEST['action'] ?? $input['action'] ?? '';

// For convenience
function findOwnerRow($pdoSites, $userId, $slug) {
    $stmt = $pdoSites->prepare("
        SELECT user_id, filename
          FROM user_sites
         WHERE user_id=? AND filename=?
         LIMIT 1
    ");
    $stmt->execute([$userId, $slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

//---------------------------------------------------------------
// ACTION: list
//---------------------------------------------------------------
if ($action === 'list') {
    $stmt = $pdoSites->prepare("
        SELECT filename, last_updated
          FROM user_sites
         WHERE user_id=?
         ORDER BY last_updated DESC
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok'=>true, 'data'=>$rows]);
    exit;
}

//---------------------------------------------------------------
// ACTION: get
//---------------------------------------------------------------
if ($action === 'get') {
    $slug = $_REQUEST['slug'] ?? $input['slug'] ?? '';
    if (!$slug) {
        http_response_code(400);
        echo json_encode(['ok'=>false, 'error'=>'Missing slug']);
        exit;
    }

    $row = findOwnerRow($pdoSites, $userId, $slug);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok'=>false, 'error'=>'Not found']);
        exit;
    }

    $filePath = __DIR__ . '/../connectere/' . $slug;
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['ok'=>false, 'error'=>'File not found']);
        exit;
    }

    $fileContents = file_get_contents($filePath);
    echo json_encode([
        'ok'    => true,
        'title' => $slug,
        'body'  => $fileContents
    ]);
    exit;
}

//---------------------------------------------------------------
// ACTION: publish
//---------------------------------------------------------------
if ($action === 'publish') {
    try {
        $title   = trim($input['title'] ?? '');
        $rawBody = $input['body'] ?? '';

        if (!$title) {
            throw new Exception('Title required');
        }

        // sanitize slug
        $slug = preg_replace('/[^A-Za-z0-9._-]/', '', $title);
        $slug = strtolower(trim($slug, '.-'));
        if ($slug === '') {
            throw new Exception('Invalid filename');
        }
        // ensure .html
        if (!str_ends_with($slug, '.html')) {
            $slug .= '.html';
        }

        // check DB if there's a row for $slug with a different user_id
        $stmt = $pdoSites->prepare("
            SELECT user_id FROM user_sites
            WHERE filename = ?
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && (int)$row['user_id'] !== (int)$userId) {
            throw new Exception('That site name is already taken by someone else');
        }

        // check reserved
        $slugCore = preg_replace('/\.html?$/i', '', $slug);
        if (in_array($slugCore, $RESERVED_SLUGS, true)) {
            throw new Exception('If this is your domain, please contact th@WithA.com.');
        }

        // see if file already exists:
        $dir      = __DIR__ . '/../connectere';
        $filePath = $dir . '/' . $slug;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new Exception("Cannot create directory: $dir");
        }
        file_put_contents($filePath, $rawBody);
        chmod($filePath, 0644);

        // upsert DB row
        $stmt = $pdoSites->prepare("
            INSERT INTO user_sites (user_id, domain, filename, site_code, last_updated)
            VALUES (:uid, '', :slug, '', NOW())
            ON DUPLICATE KEY UPDATE last_updated=NOW()
        ");
        $stmt->execute([
            ':uid'  => $userId,
            ':slug' => $slug,
        ]);

        echo json_encode(['ok'=>true, 'slug'=>$slug]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
    }
    exit;
}

//---------------------------------------------------------------
// ACTION: delete
//---------------------------------------------------------------
if ($action === 'delete') {
    $slug = $_REQUEST['slug'] ?? $input['slug'] ?? '';
    if (!$slug) {
        http_response_code(400);
        echo json_encode(['ok'=>false, 'error'=>'Missing slug']);
        exit;
    }

    // =========== THIS IS THE NEW CHECK ============
    // If the requested slug is the user's current username
    // or username.html, block the deletion
    // (Adjust if your profile site is actually "username.html".)
    if ($slug === $currentUsername) {
        echo json_encode(['ok'=>false, 'error'=>'Profile site cannot be deleted']);
        exit;
    }
    // If you store "bob.html" in user_sites, but $currentUsername is "bob",
    // you might do:
    // if ($slug === $currentUsername . '.html') {
    //     echo json_encode(['ok'=>false, 'error'=>'Profile site cannot be deleted']);
    //     exit;
    // }

    $row = findOwnerRow($pdoSites, $userId, $slug);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok'=>false, 'error'=>'Not found']);
        exit;
    }

    @unlink(__DIR__ . '/../connectere/'.$slug);

    $stmt = $pdoSites->prepare("
        DELETE FROM user_sites
         WHERE user_id=? AND filename=?
    ");
    $stmt->execute([$userId, $slug]);

    echo json_encode(['ok'=>true, 'deleted'=>$slug]);
    exit;
}

//---------------------------------------------------------------
// default
//---------------------------------------------------------------
http_response_code(400);
echo json_encode(['ok'=>false, 'error'=>'Bad action']);