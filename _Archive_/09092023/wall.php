<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'system.php';
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
/* ******* NOT LOGGED IN ******* ******* NOT LOGGED IN ******* */
header("location:ll");
    } else {
/* ******* DEFINITELY LOGGED IN ******* ******* DEFINITELY LOGGED IN ******* */
$user_id = $_SESSION['user_id'];
$user = getUserById($db, $user_id);
$user_email = $user['email']; // Set the user's email in the $user_email variable

// Establish your user context
$user_id = $_SESSION['user_id'];

// Get all views related to this user
function getViewsByUserId($db, $user_id, $user_email) {
    $sql = "SELECT * FROM view WHERE user_id = ? OR FIND_IN_SET(?, can_edit) > 0";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('is', $user_id, $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_views = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $user_views;
}

// Updating the View table
function updateViewTable($db, $id, $user_id, $the_title, $the_data, $the_style, $the_body, $the_tags, $edited, $can_edit, $user_email = '') {
    // Get the current view by ID
    $sql = "SELECT * FROM `view` WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $view = $result->fetch_assoc();
    $stmt->close();

    // If the data hasn't changed or can_edit is "no", don't update the row
    if (
        $the_data === $view['the_data'] &&
        $the_style === $view['the_style'] &&
        $the_body === $view['the_body'] &&
        $the_tags === $view['the_tags'] &&
        (
            $can_edit === 'no' ||
            $view['can_edit'] === 'no'
        )
    ) {
        return;
    }

    // Prepare the SQL statement
    $sql = "UPDATE `view` SET the_title = ?, the_data = ?, the_style = ?, the_body = ?, the_tags = ?, edited = ?, can_edit = ? WHERE id = ?";

    // Check if the user is the owner or added via email
    if (
        $can_edit === 'yes' ||
        $view['user_id'] === $user_id ||
        in_array($user_email, explode(',', $view['can_edit']))
    ) {
        if ($can_edit === 'no' && $view['can_edit'] === 'no') {
            return; // Don't update the row if can_edit is "no" for both cases
        }
        $sql .= " AND user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('sssssdsii', $the_title, $the_data, $the_style, $the_body, $the_tags, $edited, $can_edit, $id, $user_id);
    } else {
        $sql .= " AND user_id = ? AND FIND_IN_SET(?, can_edit) > 0";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('sssssdsiis', $the_title, $the_data, $the_style, $the_body, $the_tags, $edited, $can_edit, $id, $user_id, $user_email);
    }

    $stmt->execute();
    $stmt->close();
}

// Form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Fetch all user views from the database
    $user_views = getViewsByUserId($db, $user_id, $user_email);

    // Loop through each user view and update it if needed
    foreach ($user_views as $view) {
        $id = $view['id'];

        // Check if the user can edit the view
        $can_edit_current_user = false;

        if ($view['can_edit'] === 'yes' || $view['user_id'] === $user_id) {
            // User is the owner or can_edit is set to 'yes'
            $can_edit_current_user = true;
        } else {
            // Check if the user's email is in the allowed emails list
            $allowed_emails = explode(',', $view['can_edit']);
            $allowed_emails = array_map('trim', $allowed_emails); // Trim whitespace from email addresses

            if (in_array($user_email, $allowed_emails)) {
                $can_edit_current_user = true;
            }
        }

        // Fetch the new data from the form
        $the_title = $_POST['the_title'][$id];
        $the_data = $_POST['the_data'][$id];
        $the_style = $_POST['the_style'][$id];
        $the_body = $_POST['the_body'][$id];
        $the_tags = $_POST['the_tags'][$id];
        $can_edit = $_POST['can_edit'][$id];
        
        // Update the view in the database if the user has permission to edit and any data has changed
        if ($can_edit_current_user && (
            $the_title !== $view['the_title'] ||
            $the_data !== $view['the_data'] ||
            $the_style !== $view['the_style'] ||
            $the_body !== $view['the_body'] ||
            $the_tags !== $view['the_tags'] ||
            $can_edit !== $view['can_edit']
        )) {
            $edited = date("Y-m-d H:i:s");
            if ($can_edit === 'yes') {
                // User is the owner, update the view with the user_id
                updateViewTable($db, $id, $user_id, $the_title, $the_data, $the_style, $the_body, $the_tags, $edited, $can_edit);
            } else {
                // Can edit user, update the view with the user_email
                updateViewTable($db, $id, $view['user_id'], $the_title, $the_data, $the_style, $the_body, $the_tags, $edited, $can_edit, $user_email);
            }
        }
    }

    // Redirect back to the same page to prevent form resubmission on refresh
    header("Location: " . $_SERVER["REQUEST_URI"]);
    exit;
}



$title = 'W / A';
$description = 'Dashboard';
$path = "";
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
form textarea:focus,form textarea:active{outline:5px dashed #008000}
form textarea{
border:none;
background-color:transparent;
width:100%}
textarea{margin-bottom:1em}
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
include $path.$add.'a-10.php';
?>
<a href="ll" class="button" style="width:100%;margin-bottom:4em">Wall</a>
<a href="viewnew" class="button" style="width:100%;margin-bottom:4em;text-align:right">New</a><br/><br/>
<form method="POST">
<?php
echo "<input type='submit' name='submit' value='Submit' class='button' style='margin-bottom:4em'><br>";
echo "<ol class='vlist'>";
// Fetch all views for the current user from the database
$user_views = getViewsByUserId($db, $user_id, $user_email);

// Loop over each view
foreach ($user_views as $view) {
    $id = $view['id'];
    $the_title = htmlspecialchars($view['the_title']); // escape for HTML output
    $the_data = htmlspecialchars($view['the_data']);  // escape for HTML output
    $the_style = htmlspecialchars($view['the_style']);  // escape for HTML output
    $the_body = htmlspecialchars($view['the_body']);  // escape for HTML output
    $the_tags = htmlspecialchars($view['the_tags']);  // escape for HTML output
    $edited = $view['edited'];
    $created_at = $view['created_at'];
    $can_edit = htmlspecialchars($view['can_edit']); // escape HTML output
    
    // Determine if the user can edit the view
    $can_edit_current_user = false;
    
    if ($can_edit === 'yes' || $view['user_id'] === $user_id) {
        // User is the owner or can edit is set to 'yes'
        $can_edit_current_user = true;
    } elseif ($can_edit !== 'no') {
        // Check if the user's email is in the allowed emails list
        $allowed_emails = explode(',', $can_edit);
        $allowed_emails = array_map('trim', $allowed_emails); // Trim whitespace from email addresses
        
        if (in_array($user_email, $allowed_emails)) {
            $can_edit_current_user = true;
        }
    }

    // Skip rendering the view if it cannot be edited
    if (!$can_edit_current_user) {
        continue;
    }

    echo "<li style='margin-bottom:4em'>";
    // Echo out the inputs for each editable view
    echo "<label style='font-weight:700' for='the_title_{$id}'>Title</label>";
    echo "<h2 style='margin-top:0;padding-left:0'><input id='the_title_{$id}' name='the_title[{$id}]' value='{$the_title}'></h2>";
    echo "<input type='hidden' name='view_id[]' value='{$id}'>";
    echo "<label for='the_data_{$id}'>Data:</label>";
    echo "<textarea id='the_data_{$id}' name='the_data[{$id}]'>{$the_data}</textarea><br>";
    echo "<label for='the_style_{$id}'>Style:</label>";
    echo "<textarea id='the_style_{$id}' name='the_style[{$id}]'>{$the_style}</textarea><br>";
    echo "<label for='the_body_{$id}'>Body:</label>";
    echo "<textarea id='the_body_{$id}' name='the_body[{$id}]'>{$the_body}</textarea><br>";
    echo "<label for='the_tags_{$id}'>Tags:</label>";
    echo "<textarea id='the_tags_{$id}' name='the_tags[{$id}]'>{$the_tags}</textarea><br>";
    echo "<label for='can_edit_{$id}'>Can Edit:</label>";
    echo "<input id='can_edit_{$id}' name='can_edit[{$id}]' value='{$can_edit}'><br><br>";
    
    echo "</li>";
}
echo "</ol>";
echo "<input type='submit' name='submit' value='Submit' class='button'>";
echo "</form>";

include $path.$add.'c-div.php';
include $path.$add.'c-div.php';
?>
<!-- script tags -->
<?php
include $path.$add.'script.php';
include $path.$add.'c-body_html.php';

}
?>