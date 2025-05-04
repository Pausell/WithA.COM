<?php
session_start();

require_once '../config.php';
require_once '../functions.php';
require_once '../system.php';

function getUserByIdAccess($db, $user_id) {
    $sql = "SELECT users.*, access.user_style, access.user_data, access.user_body, access.user_tags, access.user_save, access.user_backup, access.edited FROM users JOIN access ON users.id = access.user_id WHERE users.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

function getAccessByUserId($db, $user_id) {
    $sql = "SELECT * FROM access WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_access = $result->fetch_assoc();
    $stmt->close();
    return $user_access;
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user = getUserByIdAccess($db, $user_id);
    $user_data = $user['user_data'];
    $user_style = $user['user_style'];
    $user_body = $user['user_body'];
    $user_tags = $user['user_tags'];
    $user_save = $user['user_save'];
    $user_backup = $user['user_backup'];
    $user_edited = $user['edited'];
    
    $_SESSION['user_data'] = $user['user_data'];
    $_SESSION['user_style'] = $user['user_style'];
    $_SESSION['user_body'] = $user['user_body'];
    $_SESSION['user_tags'] = $user['user_tags'];
    $_SESSION['user_save'] = $user['user_save'];
    $_SESSION['user_backup'] = $user['user_backup'];
    $_SESSION['user_edited'] = $user['edited'];

// Dashboard
// REFER TO ../functions.php for Access table creation and row creation associated with user

//
// Function to update Access table

function updateAccessTable($db, $user_id, $user_data, $user_style, $user_body, $user_tags, $user_save, $user_backup, $edited) {
    // Get user by ID
    $access = getUserByIdAccess($db, $user_id);

    // Check if the data has edits
    $data_edits = ($user_data !== $user['user_data']) ? $user_data : '';
    $style_edits = ($user_style !== $user['user_style']) ? $user_style : '';
    $body_edits = ($user_body !== $user['user_body']) ? $user_body : '';
    $tags_edits = ($user_tags !== $user['user_tags']) ? $user_tags : '';
    $user_save_edits = ($user_save !== $user['user_save']) ? $user_save : '';
    $user_backup_edits = ($user_backup !== $user['user_backup']) ? $user_backup : '';

    // Check altogether if any data has edits
    if (!$data_edits && !$style_edits && !$body_edits && !$tags_edits && !$user_save_edits && !$user_backup_edits) {
        return;
    }

    // Create array of edited fields
    $updated_fields = array();
    if ($data_edits !== '') {
        $updated_fields[] = "user_data";
    } if($data_edits){logChange($db, $user_id, "data_mod");}
    if ($style_edits !== '') {
        $updated_fields[] = "user_style";
    } if($style_edits){logChange($db, $user_id, "style_mod");}
    if ($body_edits !== '') {
        $updated_fields[] = "user_body";
    } if($body_edits){logChange($db, $user_id, "body_mod");}
    if ($tags_edits !== '') {
        $updated_fields[] = "user_tags";
    } if($tags_edits){logChange($db, $user_id, "tags_mod");}
    if ($user_save_edits !== '') {
        $updated_fields[] = "user_save";
    } if($user_save_edits){logChange($db, $user_id, "save_mod");}
    if ($user_backup_edits !== '') {
        $updated_fields[] = "user_backup";
    } if($user_backup_edits){logChange($db, $user_id, "backup_mod");}

    // Check if any data has edits
    if (empty($updated_fields)) {
        return;
    }

    // Update user's information in the access table
    $sql = "UPDATE access SET user_data=?, user_style=?, user_body=?, user_tags=?, user_save=?, user_backup=?, edited=? WHERE id=?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sssssssi', $user_data, $user_style, $user_body, $user_tags, $user_save, $user_backup, $edited, $user_id);

    // Execute the prepared statement and close it
    $stmt->execute();
    $stmt->close();

    // Return true if any data was updated
    return !empty($updated_fields);
}


// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];

    if (isset($_POST['update_profile'])) {
        $new_email = $_POST['new_email'];
        $new_password = $_POST['new_password'];

        // Update the user's profile
        $updated_user = updateProfile($db, $user_id, $new_email, $new_password);

        // Update the session variables with the new email and hashed password
        $_SESSION['email'] = $updated_user['email'];
        $_SESSION['password'] = $updated_user['password'];
    }

    if (isset($_POST['submit'])) {
        $user_data = $_POST['user_data'];
        $user_style = $_POST['user_style'];
        $user_body = $_POST['user_body'];
        $user_tags = $_POST['user_tags'];
        $user_save = $_POST['user_save'];
        $user_backup = $_POST['user_backup'];
        $edited = date("Y-m-d H:i:s");

        $update_successful = updateAccessTable($db, $user_id, $user_data, $user_style, $user_body, $user_tags, $user_save, $user_backup, $edited);

        // Refresh the page if the update was successful
        if ($update_successful) {
            header("Location: " . $_SERVER["REQUEST_URI"]);
            exit;
        }
    }
}

$title = 'W / A Contact';
$description = 'Contact';
$path = "../";
$add = "add/";
$favicon = $path.$add.'favicon.png';
$favicon16 = $path.$add.'favicon16.png';
$iphone = $path.'touch-icon-iphone.png';
$ipad = $path.'touch-icon-ipad.png';
$iphoner = $path.'touch-icon-iphone-retina.png';
$ipadr = $path.'touch-icon-ipad-retina.png';
$browserconfig = $path.$add.'browserconfig.xml';
$style = $path.$add.'style.css';
$internal_style = 
'<style>
form label{font-weight:700}
form textarea:focus,form textarea:active{outline:5px dashed #008000}
form textarea{
border:none;
background-color:transparent;
width:100%}
@media(prefers-color-scheme:dark){
form textarea:focus,form textarea:active{outline:5px dashed #009300}
form textarea{border:none;
color:#ffffff}
}
</style>';
include $path.$add.'a-html.php';
include $path.$add.'head.php';
include $path.$add.'a-body.php';
include $path.$add.'a-container.php';
include $path.$add.'navigation.php';

?>
<form action="" method="post">
    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
    <label for="user_data">Data:</label>
    <br>
    <textarea id="user_data" name="user_data" rows="4" cols="50" maxlength="512"><?php echo $user_data; ?></textarea>
    <br>
    <label for="user_style">Style:</label>
    <br>
    <textarea id="user_style" name="user_style" rows="8" cols="50" maxlength="4096"><?php echo $user_style; ?></textarea>
    <br>
    <label for="user_body">Body:</label>
    <br>
    <textarea id="user_body" name="user_body" rows="16" cols="50" maxlength="16384"><?php echo $user_body; ?></textarea>
    <br>
    <label for="user_tags">Tags:</label>
    <br>
    <textarea type="text" id="user_tags" name="user_tags" size="50" maxlength="1024"><?php echo $user_tags; ?></textarea>
    <br>
    <label><?php echo $user_edited; ?></label>
    <br>
    <label for="user_save">User Save:</label>
    <br>
    <textarea id="user_save" name="user_save" rows="4" cols="50" maxlength="512"><?php echo $user_save; ?></textarea>
    <br>
    <label for="user_backup">User Backup:</label>
    <br>
    <textarea id="user_backup" name="user_backup" rows="4" cols="50" maxlength="512"><?php echo $user_backup; ?></textarea>
    <br>
    <input type="submit" name="submit" value="Save Changes">
</form>
    <h1>Dashboard</h1>
    <p>Email: <?= $user['email'] ?></p>
    <p>Access: <?= $user['access'] ?></p>

       <?php if ($valid_token): ?>
        <h2>Valid Token</h2>
        <form action="" method="post">
            <button type="submit" name="invalidate_token">Invalidate Token</button>
        </form>
       <?php endif; ?>
    <h2>Profile</h2>
    <form action="" method="post">
        <label for="new_email">Email</label>
        <input type="email" name="new_email" id="new_email" value="<?= $user['email'] ?>" required>
        <br>
        <label for="new_password">Password</label>
        <input type="password" name="new_password" id="new_password">
        <br>
        <button type="submit" name="update_profile">Update Profile</button>
    </form>
    <?php if (!empty($error_message)): ?>
        <p class="error-message"><?= $error_message ?></p>
    <?php endif; ?>
    <form action="" method="post">
        <button type="submit" name="swap_save">Swap Save</button>
    </form>
    <a href="../jett">Logout</a>
    <ul style="display:flex;flex-direction:column-reverse">
    <?php foreach($ip_log as $ip_log_entry) { ?>
        <li><?php echo $ip_log_entry; ?></li>
    <?php } ?>
    </ul>
<?php
} else {
$title = 'W / A Contact';
$description = 'Contact';
$path = "../";
$add = "add/";
$favicon = $path.$add.'favicon.png';
$favicon16 = $path.$add.'favicon16.png';
$iphone = $path.'touch-icon-iphone.png';
$ipad = $path.'touch-icon-ipad.png';
$iphoner = $path.'touch-icon-iphone-retina.png';
$ipadr = $path.'touch-icon-ipad-retina.png';
$browserconfig = $path.$add.'browserconfig.xml';
$style = $path.$add.'style.css';
$internal_style = 
'<style>

</style>';
include $path.$add.'a-html.php';
include $path.$add.'head.php';
include $path.$add.'a-body.php';
include $path.$add.'a-container.php';
include $path.$add.'navigation.php';

require_once '../config.php';
require_once '../functions.php';
require_once '../system.php';
?>

<?php
}

include $path.$add.'c-div.php';
include $path.$add.'script.php';
include $path.$add.'c-body_html.php';
?>