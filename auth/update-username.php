<?php
/**
 * /auth/update-username.php
 * FULL PRODUCTION VERSION
 *
 * This endpoint merges the logic of:
 *  - Changing the user’s username in the `users` table.
 *  - Renaming oldUsername.html -> newUsername.html in /connectere/.
 *  - Possibly updating a `user_sites` table to reflect the new filename.
 *  - Overwriting the new file with the provided HTML `body`.
 *
 * Input JSON:
 *   {
 *     "username": "NEW_NAME",
 *     "body": "<html>Some HTML here...</html>"
 *   }
 *
 * Output JSON:
 *   {
 *     "ok": true
 *   }
 * or on error:
 *   {
 *     "ok": false,
 *     "error": "Description of error"
 *   }
 */

session_start();
header('Content-Type: application/json');

// You must have db.php / schema.php to define $pdo for users table
// and possibly $pdoSites for user_sites if you track all sites in a separate DB.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/schema.php';

global $pdo, $pdoSites;

// 1) Must be logged in
$userId = $_SESSION['user'] ?? 0;
if (!$userId) {
    echo json_encode(["ok" => false, "error" => "Not logged in"]);
    exit;
}

// 2) Parse the incoming JSON body
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$newUsername = trim($input['username'] ?? '');
$newBody     = $input['body'] ?? '';

if (!$newUsername) {
    echo json_encode(["ok"=>false, "error"=>"Username is required"]);
    exit;
}

// 3) Fetch the user’s old username from the `users` table
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$oldUsername = $stmt->fetchColumn() ?: '';

// If the username is unchanged, we can just overwrite the file:
$renaming = (strcasecmp($oldUsername, $newUsername) !== 0);

// We'll physically store them as {username}.html in /connectere/
$oldSlug = $oldUsername ? (strtolower($oldUsername) . '.html') : '';
$newSlug = strtolower($newUsername) . '.html';

// 4) Start transactions if you have two DB handles
try {
    $pdo->beginTransaction();
    if (isset($pdoSites)) {
        $pdoSites->beginTransaction();
    }

    // 5) Check if newSlug belongs to someone else in user_sites (optional)
    //    If you don't have user_sites or don't track ownership, skip this.
    if ($newSlug && isset($pdoSites)) {
        $check = $pdoSites->prepare("
            SELECT user_id
              FROM user_sites
             WHERE filename = ?
             LIMIT 1
        ");
        $check->execute([$newSlug]);
        $taken = $check->fetch(PDO::FETCH_ASSOC);
        if ($taken && (int)$taken['user_id'] !== (int)$userId) {
            throw new Exception("That username is taken by another user");
        }
    }

    // 6) Physically rename oldUsername.html -> newUsername.html if needed
    $connectereDir = __DIR__ . '/../connectere/';
    $oldPath = $connectereDir . $oldSlug;
    $newPath = $connectereDir . $newSlug;

    if ($renaming) {
        // If oldPath exists, attempt rename
        if ($oldSlug && file_exists($oldPath)) {
            if (!@rename($oldPath, $newPath)) {
                throw new Exception("Could not rename file on disk");
            }
        }
        // else if old didn't exist physically, we'll just create new
    }

    // 7) Overwrite the new file with the provided HTML body
    //    (If the rename just happened, we still overwrite to update content.)
    file_put_contents($newPath, $newBody);

    // 8) If you track this in user_sites, rename old row => new row if needed
    if (isset($pdoSites)) {
        // remove .html from the user_sites if you want, or store it exactly
        // depends on how your table is designed.
        $stmtOld = $pdoSites->prepare("
            SELECT filename
              FROM user_sites
             WHERE user_id = ?
               AND filename = ?
             LIMIT 1
        ");
        // We check if there's a row for oldSlug
        $stmtOld->execute([$userId, $oldSlug]);
        $hadOldRow = $stmtOld->fetch(PDO::FETCH_ASSOC);

        if ($renaming && $hadOldRow) {
            // rename the row to newSlug
            $upd = $pdoSites->prepare("
                UPDATE user_sites
                   SET filename = ?,
                       last_updated = NOW()
                 WHERE user_id   = ?
                   AND filename   = ?
                 LIMIT 1
            ");
            $upd->execute([$newSlug, $userId, $oldSlug]);
        } else {
            // If there's no row, we can upsert one for newSlug
            // or do nothing if you only track normal sites in that table.
            $ins = $pdoSites->prepare("
                INSERT INTO user_sites (user_id, filename, last_updated)
                VALUES (?,?,NOW())
                ON DUPLICATE KEY UPDATE last_updated=NOW()
            ");
            $ins->execute([$userId, $newSlug]);
        }
    }

    // 9) Update the `users` table with the new username
    $stmtU = $pdo->prepare("UPDATE users SET username=? WHERE id=? LIMIT 1");
    $stmtU->execute([$newUsername, $userId]);

    // 10) commit
    if (isset($pdoSites)) {
        $pdoSites->commit();
    }
    $pdo->commit();

    echo json_encode(["ok" => true]);
} catch (Exception $ex) {
    if (isset($pdoSites)) {
        $pdoSites->rollBack();
    }
    $pdo->rollBack();
    echo json_encode(["ok"=>false, "error"=>$ex->getMessage()]);
}
