<?php
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user = getUserById($db, $user_id);
    $ip_log = isset($user['ip_log']) ? json_decode($user['ip_log'], true) : array();
    $valid_token = false;

    if ($user['token'] && $user['token_expire'] > date("Y-m-d H:i:s")) {
        $valid_token = true;
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['update_profile'])) {
            $new_email = !empty($_POST['new_email']) ? $_POST['new_email'] : "";
            $new_password = !empty($_POST['new_password']) ? $_POST['new_password'] : "";

            try {
                updateProfile($db, $user_id, $new_email, $new_password);
                header("Location:ll");
                exit;
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        }

    }
    if (isset($_POST['invalidate_token']) && $valid_token) {
        $is_token_invalidated = checkToken($db, $user, $user['token']);
        if ($is_token_invalidated) {
            updateIpLog($db, $user, "token_invalidated");
            $sql = "UPDATE users SET token_expire = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $now = date("Y-m-d H:i:s");
            $stmt->bind_param("si", $now, $user_id);
            $stmt->execute();
            $stmt->close();
            $valid_token = false;
            header("Location:ll");
            exit;
        } else {
         
        }
    }
}
?>