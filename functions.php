<?php
// Create each table that does not exist
// Check if the 'users' table exists
$table_check_query = "SHOW TABLES LIKE 'users'";
$result = $db->query($table_check_query);

if ($result->num_rows == 0) {
    // If the 'users' table does not exist, create it
    $table_create_query = "CREATE TABLE users (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        token VARCHAR(255) DEFAULT NULL,
        token_expire DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        session_version TIMESTAMP NOT NULL,
        last_login DATETIME DEFAULT NULL,
        valid_login DATETIME DEFAULT NULL,
        ip_log TEXT,
        preferences TEXT,
        is_verified BOOLEAN DEFAULT FALSE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($db->query($table_create_query) !== TRUE) {
        die("Error creating table: " . $db->error);
    }
}

// Check if the 'access' table exists
$table_check_query = "SHOW TABLES LIKE 'access'";
$result = $db->query($table_check_query);

if ($result->num_rows == 0) {
    // If the 'access' table does not exist, create it
    $access_query = "CREATE TABLE access (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) UNSIGNED NOT NULL,
        access_loc TEXT NOT NULL,
        user_data TEXT NOT NULL,
        user_style TEXT NOT NULL,
        user_body TEXT NOT NULL,
        user_tags TEXT NOT NULL,
        edited DATETIME,
        user_save TEXT,
        user_backup TEXT,
        PRIMARY KEY (id),
        CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($db->query($access_query) !== TRUE) {
        die("Error creating access table: " . $db->error);
    }
}

// Check if the 'sessions' table exists
$table_check_query = "SHOW TABLES LIKE 'sessions'";
$result = $db->query($table_check_query);

if ($result->num_rows == 0) {
    // If the 'sessions' table does not exist, create it
    $session_table_create_query = "CREATE TABLE sessions (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NOT NULL,
        session_id VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_session_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($db->query($session_table_create_query) !== TRUE) {
        die("Error creating sessions table: " . $db->error);
    }
}

// Check if the 'view' table exists
$table_check_query = "SHOW TABLES LIKE 'view'";
$result = $db->query($table_check_query);

if ($result->num_rows == 0) {
    // If the 'view' table does not exist, create it
    $view_table_create_query = "CREATE TABLE `view` (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NOT NULL,
        view_loc TEXT NOT NULL,
        the_title TEXT NOT NULL,
        the_data TEXT NOT NULL,
        the_style TEXT NOT NULL,
        the_body TEXT NOT NULL,
        the_tags TEXT NOT NULL,
        edited DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        can_edit TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($db->query($view_table_create_query) !== TRUE) {
        die("Error creating view table: " . $db->error);
    }
}

// Check if the 'page' table exists
$table_check_query = "SHOW TABLES LIKE 'page'";
$result = $db->query($table_check_query);

if ($result->num_rows == 0) {
    // If the 'page' table does not exist, create it
    $page_table_create_query = "CREATE TABLE page (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NOT NULL,
        view_loc TEXT NOT NULL,
        the_title TEXT NOT NULL,
        the_data TEXT NOT NULL,
        the_style TEXT NOT NULL,
        the_body TEXT NOT NULL,
        the_tags TEXT NOT NULL,
        edited DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        can_edit TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($db->query($page_table_create_query) !== TRUE) {
        die("Error creating view table: " . $db->error);
    }
}


// Sessioning

function createUserSession($db, $user_id) {
    // Generate a new session id
    $session_id = bin2hex(random_bytes(32));

    // Store the session id in the session data
    $_SESSION['session_id'] = $session_id;

    // Insert the new session into the database
    $stmt = $db->prepare("INSERT INTO sessions (user_id, session_id) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $session_id);
    $stmt->execute();

    return $session_id;
}

function getAllSessionsByUserId($db, $user_id) {
    $stmt = $db->prepare("SELECT * FROM sessions WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sessions = array();
    
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    
    return $sessions;
}

function validateSession($db, $user_id) {
    if (!isset($_SESSION['session_id'])) {
        return false;
    }

    // Query the database for the session
    $stmt = $db->prepare("SELECT * FROM sessions WHERE session_id = ? AND user_id = ?");
    $stmt->bind_param("si", $_SESSION['session_id'], $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the session exists in the database, the session is valid
    return $result->num_rows > 0;
}

function invalidateUserSession($db) {
    // Delete the session from the database
    $stmt = $db->prepare("DELETE FROM sessions WHERE session_id = ?");
    $stmt->bind_param("s", $_SESSION['session_id']);
    $stmt->execute();

    // Unset the session_id from the session data
    unset($_SESSION['session_id']);
}

// Invalidate all sessions for a specific user
function invalidateUserSessions($db, $user_id) {
    // Retrieve all session IDs for the user
    $sql = "SELECT session_id FROM sessions WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $session_ids = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Loop through the session IDs and destroy the sessions
    foreach ($session_ids as $session) {
        $session_id = $session['session_id'];
        session_id($session_id);
        session_start();
        session_destroy();

        // Remove the session record from the sessions table
        $sql = "DELETE FROM sessions WHERE session_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $session_id);
        $stmt->execute();
        $stmt->close();
    }
}

function getUserBySessionId($db, $session_id) {
    $stmt = $db->prepare("SELECT u.* FROM users u INNER JOIN sessions s ON u.id = s.user_id WHERE s.session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}


// Add functions for getUserById, processLoginSignup, updateIp_log, sendWelcomeEmail, sendTokenEmail, sendEmailUpdateNotification, sendPasswordUpdateNotification, and updateProfile.

function getUserById($db, $id) {
    $sql = "SELECT * FROM users WHERE id=?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

function getUserByEmail($db, $email) {
    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Create the user’s Access row
function createAccessForUser($db, $user_id) {
    $sql = "INSERT INTO access (user_id, user_data, user_style, user_body, user_tags) VALUES (?, '', '', '', '')";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

function processLoginSignup($db, $email, $password) {
    $user = getUserByEmail($db, $email);
    if (!$user) {
        // Register new user
        registerUser($db, $email, $password);
        return ["status" => "success", "user_id" => $db->insert_id];
    } else {
        // Check password
        if (password_verify($password, $user['password'])) {
            // Log in with password
            loginUser($db, $user, "password_log", $user['last_login']);
            return ["status" => "success", "user_id" => $user['id']];
        } else {
            // Check if the input is a token
            if (preg_match('/^[a-f0-9]{32}$/', $password)) {
                // Invalid password, check for valid token
                if (checkToken($db, $user, $password)) {
                    // Log in with token
                    loginUser($db, $user, "token_log", $user['last_login']);
                    return ["status" => "success", "user_id" => $user['id']];
                } else {
                    return ["status" => "invalid_password", "user_id" => $user['id']];
                }
            } else {
                // Invalid password, generate and send new token
                generateToken($db, $user);
                return ["status" => "invalid_password", "user_id" => $user['id']];
            }
        }
    }
}

function registerUser($db, $email, $password) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $ip_log = json_encode([date("Y-m-d H:i:s") . ":" . $_SERVER['REMOTE_ADDR'] . ":created_password,welcome"]);

    $sql = "INSERT INTO users (email, password, ip_log) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sss", $email, $hashed_password, $ip_log);
    $stmt->execute();

    $user_id = $stmt->insert_id;
    createAccessForUser($db, $user_id);
    sendWelcomeEmail($email);
    loginUser($db, getUserById($db, $user_id), "password_log"); // Log in the user after registration
}

function loginUser($db, $user, $login_status) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['last_login'] = $user['last_login'];
    createUserSession($db, $user['id']); // Create a session for the user
    updateIpLog($db, $user, $login_status);
    header("Location: ll");
    exit;
}

function checkToken($db, $user, $token) {
    if ($user['token'] === $token && strtotime($user['token_expire']) > time()) {
        // Invalidate token
        updateIpLog($db, $user, "token_invalidated");
        $sql = "UPDATE users SET token=NULL, token_expire=NULL, last_login=NOW() WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        return true;
    }
    return false;
}

function generateToken($db, $user) {
    $token = bin2hex(random_bytes(16));
    //$token_expire = date("Y-m-d H:i:s", time() + 6 * 60 * 60); // 6 hours from now
    $token_expire = date("Y-m-d H:i:s", time() + 17 * 60 * 60); // 17 hours from now

    $sql = "UPDATE users SET token=?, token_expire=? WHERE id=?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssi", $token, $token_expire, $user['id']);
    $stmt->execute();

    sendTokenEmail($user['email'], $token);
    updateIpLog($db, $user, "invalid_password,token_requested");
}

function updateIpLog($db, $user, $login_status) {
    $max_log_size = 64 * 1024; // 64KB
    $ip_log = $user['ip_log'];
    $ip_log_arr = $ip_log ? json_decode($ip_log, true) : []; // Add check for null value

    $ip_log_arr[] = date("Y-m-d H:i:s") . ":" . $_SERVER['REMOTE_ADDR'] . ":" . $login_status;

    // Check if ip_log size exceeds the maximum allowed size
    while (strlen(json_encode($ip_log_arr)) > $max_log_size) {
        array_shift($ip_log_arr);
    }

    $ip_log_json = json_encode($ip_log_arr);

    $sql = "UPDATE users SET ip_log=?, last_login=NOW() WHERE id=?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("si", $ip_log_json, $user['id']);
    $stmt->execute();
}

function sendWelcomeEmail($email) {
    $subject = "Welcome to WithA.com";
    $message = "<html><head>";
    $message .= '<meta name="color-scheme" content="light dark">';
    $message .= '<style>';
    $message .= '@media(prefers-color-scheme:dark){';
    $message .= 'body{background-color:#000000;color:#d1dde3}a{color:lightblue}}';
    $message .= "</style></head><body>";
    $message .= "<h1>Welcome To W/A!</h1>&nbsp;";
    $message .= "<p>Thank you for signing up to <a href='https://witha.com'>W/A.com</a>: <br/>true free speech.</p>";
    $headers = "From: WithA.com@witha.com\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    //$headers .= "Content-type: text/plain; charset=UTF-8\r\n";

    return mail($email, $subject, $message, $headers);
}

function sendTokenEmail($email, $token) {
    $subject = "WithA.com Token";
    $message = "<html><head>";
    $message .= '<meta name="color-scheme" content="light dark">';
    $message .= '<style>';
    $message .= '@media(prefers-color-scheme:dark){';
    $message .= 'body{background-color:#000000;color:#d1dde3}a{color:lightblue}}';
    $message .= "</style></head><body>";
    $message .= "<h1>Seventeen Hour Expiration</h1>&nbsp;";
    $message .= "<p>{$token}</p>";
    $message .= '<p>Invalidate <em>or</em> <strong>use</strong> your <a href="https://witha.com/ll">Login <strong>Expiring</strong> Token</a>.</p>';
    $message .= "</body></html>";
    $headers = "From: WithA.com@witha.com\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    //$headers .= "Content-type: text/plain; charset=UTF-8\r\n";
    //Plain Text can include \n for a new line.

    return mail($email, $subject, $message, $headers);
}

function sendEmailUpdateNotification($old_email, $new_email) {
    $subject = "WithA.com Account Email";
    $message = "<html><head>";
    $message .= '<meta name="color-scheme" content="light dark">';
    $message .= '<style>';
    $message .= '@media(prefers-color-scheme:dark){';
    $message .= 'body{background-color:#000000;color:#d1dde3}a{color:lightblue}}';
    $message .= "</style></head><body>";
    $message .= "<h1>Modified Email</h1>&nbsp;";
    $message .= "<p>Old Email: {$old_email}</p>";
    $message .= "<p>New Email: {$new_email}</p>";
    $message .= '<p>Revert this at &lsquo;<a href="https://witha.com/ll">WithA.com</a>&rsquo;.</p>';
    $message .= "</body></html>";
    $headers = "From: WithA.com@witha.com\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    //$headers .= "Content-type: text/plain; charset=UTF-8\r\n";

    return mail($new_email, $subject, $message, $headers);
}

function sendPasswordUpdateNotification($email) {
    $subject = "WithA.com Account Password";
    $message = "<html><head>";
    $message .= '<meta name="color-scheme" content="light dark">';
    $message .= '<style>';
    $message .= '@media(prefers-color-scheme:dark){';
    $message .= 'body{background-color:#000000;color:#d1dde3}a{color:lightblue}}';
    $message .= "</style></head><body>";
    $message .= "<h1>Modified Password</h1>&nbsp;";
    $message .= '<p>Revert this at &lsquo;<a href="https://witha.com/ll">WithA.com</a>&rsquo;.</p>';
    $message .= "</body></html>";
    $headers = "From: WithA.com@witha.com\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    //$headers .= "Content-type: text/plain; charset=UTF-8\r\n";

    return mail($email, $subject, $message, $headers);
}

function sendEmailAndPasswordNotification($old_email, $new_email) {
    $subject = "WithA.com Account Email and Password";
    $message = "<html><head>";
    $message .= '<meta name="color-scheme" content="light dark">';
    $message .= '<style>';
    $message .= '@media(prefers-color-scheme:dark){';
    $message .= 'body{background-color:#000000;color:#d1dde3}a{color:lightblue}}';
    $message .= "</style></head><body>";
    $message .= "<h1>Modified Account</h1>&nbsp;";
    $message .= "<h2>Email</h2>";
    $message .= "<p>Old Email: {$old_email}</p>";
    $message .= "<p>New Email: {$new_email}</p>";
    $message .= "<h2>Password</h2>";
    $message .= '<p>Revert this at &lsquo;<a href="https://witha.com/ll">WithA.com</a>&rsquo;.</p>';
    $message .= "</body></html>";
    $headers = "From: WithA.com@witha.com\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    //$headers .= "Content-type: text/plain; charset=UTF-8\r\n";

    return mail($new_email, $subject, $message, $headers);
}

function logChange($db, $user_id, $change) {
    $user = getUserById($db, $user_id);
    updateIpLog($db, $user, $change);
}

function updateUserEmail($db, $user_id, $new_email) {
    $sql = "UPDATE users SET email = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("si", $new_email, $user_id);
    $stmt->execute();
    $stmt->close();
}

function updateUserPassword($db, $user_id, $new_password) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $user_id);
    $stmt->execute();
    $stmt->close();
}

function updateProfile($db, $user_id, $new_email, $new_password) {

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        if (validateSession($db, $user_id)) {
            // The session is valid, continue with the request

            $user = getUserById($db, $user_id);
            $email_changed = !empty($new_email) && $user['email'] !== $new_email;
            $password_changed = !empty($new_password);

            if ($email_changed) {
                if (isEmailInUse($db, $new_email)) {
                    throw new Exception("Email Unavailable");
                }
                updateUserEmail($db, $user_id, $new_email);
                sendEmailUpdateNotification($user['email'], $new_email);
                logChange($db, $user_id, "email_mod");
            }

            if ($password_changed) {
                updateUserPassword($db, $user_id, $new_password);
                sendPasswordUpdateNotification($user['email']);
                logChange($db, $user_id, "password_mod");
            }

            if ($email_changed && $password_changed) {
                sendEmailAndPasswordNotification($user['email'], $new_email);
                logChange($db, $user_id, "email_and_password_mod");
            }

            // Retrieve the updated user data
            $updated_user = getUserById($db, $user_id);
            return $updated_user;
        } else {
            // The session is not valid, redirect to login or show an error
            header("Location: jett.php");
            exit;
        }
    } else {
        // The user is not logged in, redirect to login or show an error
        header("Location: jett.php");
        exit;
    }
}

function isEmailInUse($db, $email) {
    $sql = "SELECT id FROM users WHERE email=?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();
    return $num_rows > 0;
}
?>