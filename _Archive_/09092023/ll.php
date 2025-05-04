<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'system.php';

// Email And Password Form
$error_message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $login_result = processLoginSignup($db, $_POST['email'], $_POST['password']);
        /*echo 'Login Result: ';
        var_dump($login_result); // Debug statement*/

        if ($login_result !== null && $login_result['status'] === "success") {
            $_SESSION['user_id'] = $login_result['user_id'];
            $_SESSION['session_version'] = $login_result['session_version'];
            header("Location: ll");
            exit;
        } else {
            if ($login_result['status'] === "invalid_password") {
                $error_message = "Invalid password. A login token has been sent to your email.";
            } else {
                $registration_result = new_user($_POST['email'], $_POST['password'], $db);
                /*echo 'Registration Result: ';
                var_dump($registration_result); // Debug statement*/

                if ($registration_result === "") {
                    $login_result = processLoginSignup($db, $_POST['email'], $_POST['password']);
                    /*echo 'Login Result (After Registration): ';
                    var_dump($login_result); // Debug statement*/

                    if ($login_result !== null && $login_result['status'] === "success") {
                        $_SESSION['user_id'] = $login_result['user_id'];
                        $_SESSION['session_version'] = $login_result['session_version'];
                        header("Location: ll");
                        exit;
                    }
                } else {
                    $error_message = $registration_result; // Displays "Email already exists" or any other error message
                }
            }
        }
    } elseif (isset($_POST['update_profile'])) {
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];

            // Validate the session before allowing profile update
            if (validateSession($db, $user_id)) {
                $new_email = isset($_POST['new_email']) ? $_POST['new_email'] : '';
                $new_password = $_POST['new_password'];

                try {
                    // Update the profile and retrieve the updated user data
                    $updated_user = updateProfile($db, $user_id, $new_email, $new_password);

                    // Set the updated email in the user variable
                    $user['email'] = $updated_user['email'];

                    // Display success message or handle redirect
                } catch (Exception $e) {
                    $error_message = $e->getMessage(); // Handle the exception/error condition appropriately
                }
            } else {
                // Invalid session, handle redirect or display error message
            }
        } else {
            // User not logged in, handle redirect or display error message
        }
    }
} else {
    // Handle other GET requests or page load logic here
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
/* ******* NOT LOGGED IN ******* ******* NOT LOGGED IN ******* */
$title = 'W/A . COM - WALL';
$description = 'Written Is The Holy Army - WALL';
$path = "";
$add = "add/";
$favicon = $add.'favicon.png';
$favicon16 = $add.'favicon16.png';
$iphone = 'touch-icon-iphone.png';
$ipad = 'touch-icon-ipad.png';
$iphoner = 'touch-icon-iphone-retina.png';
$ipadr = 'touch-icon-ipad-retina.png';
$browserconfig = $add.'browserconfig.xml';
$style = $add.'style.css';
$internal_style = '<link rel="stylesheet" href="../add/buttons.css">';
include $add.'a-html.php';
include $add.'head.php';
include $add.'a-body.php';
include $add.'a-container.php';
include $add.'navigation.php';
include $add.'a-10.php';
?>
<div class="divspace">
<form action="" method="post" class="width100">
    <label for="email" class="ib padding10px">Email</label>
    <input type="email" name="email" id="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required class="width100 bspaced">
    <label for="password" class="ib padding10px">Password</label>
    <input type="password" name="password" id="password" required class="width100 bspaced">
    <button type="submit" name="submit" class="glowing-btn"><span class='glowing-txt'>L<span class='faulty-letter'>O</span>GIN</span></button>
</form>
<?php if (!empty($error_message)): ?>
    <p class="error-message"><?= $error_message ?></p>
<?php endif; ?>
<?php echo $root; ?>
</div>
<?php
include $add.'c-div.php';
include $add.'c-div.php';
include $add.'script.php';
include $add.'c-body_html.php';
} else {
    $user_id = $_SESSION['user_id'];

    // Validate the session
    if (!validateSession($db, $user_id)) {
        // Invalid session, log the user out
        header("location:jett");
    } else {
/* ******* DEFINITELY LOGGED IN ******* ******* DEFINITELY LOGGED IN ******* */
$user_id = $_SESSION['user_id'];
$user = getUserById($db, $user_id);
$user_email = $user['email']; // Set the user's email in the $user_email variable

function getUserByIdAccess($db, $user_id) {
    $sql = "SELECT users.*, access.user_style, access.user_data, access.user_body, access.user_tags, access.user_save, access.user_backup, access.edited, access.access_loc FROM users JOIN access ON users.id = access.user_id WHERE users.id = ?";
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
    $access_loc = $user['access_loc'];
    
    $_SESSION['user_data'] = $user['user_data'];
    $_SESSION['user_style'] = $user['user_style'];
    $_SESSION['user_body'] = $user['user_body'];
    $_SESSION['user_tags'] = $user['user_tags'];
    $_SESSION['user_save'] = $user['user_save'];
    $_SESSION['user_backup'] = $user['user_backup'];
    $_SESSION['user_edited'] = $user['edited'];
    $_SESSION['access_loc'] = $user['access_log'];

// Dashboard
// REFER TO ../functions.php for Access table creation and row creation associated with user

//
// Function to update Access table

function updateAccessTable($db, $user_id, $user_data, $user_style, $user_body, $user_tags, $user_save, $user_backup, $edited, $access_loc) {
    // Get user by ID
    $access = getUserByIdAccess($db, $user_id);

    // Check if the data has edits
    $data_edits = ($user_data !== $user['user_data']) ? $user_data : '';
    $style_edits = ($user_style !== $user['user_style']) ? $user_style : '';
    $body_edits = ($user_body !== $user['user_body']) ? $user_body : '';
    $tags_edits = ($user_tags !== $user['user_tags']) ? $user_tags : '';
    $user_save_edits = ($user_save !== $user['user_save']) ? $user_save : '';
    $user_backup_edits = ($user_backup !== $user['user_backup']) ? $user_backup : '';
    $loc_edits = ($access_loc !== $user['access_loc']) ? $access_loc : '';

    // Check altogether if any data has edits
    if (!$data_edits && !$style_edits && !$body_edits && !$tags_edits && !$user_save_edits && !$user_backup_edits && !$loc_edits) {
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
    if ($loc_edits !== '') {
        $updated_fields[] = "access_loc";
    }

    // Check if any data has edits
    if (empty($updated_fields)) {
        return;
    }

    // Update user's information in the access table
    $sql = "UPDATE access SET user_data=?, user_style=?, user_body=?, user_tags=?, user_save=?, user_backup=?, edited=?, access_loc=? WHERE id=?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ssssssssi', $user_data, $user_style, $user_body, $user_tags, $user_save, $user_backup, $edited, $access_loc, $user_id);

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
        $access_loc = $_POST['access_loc'];

        $update_successful = updateAccessTable($db, $user_id, $user_data, $user_style, $user_body, $user_tags, $user_save, $user_backup, $edited, $access_loc);

        // Refresh the page if the update was successful
        if ($update_successful) {
            header("Location: " . $_SERVER["REQUEST_URI"]);
            exit;
        }
    }
}
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

// Retrieve the user's preferences from the users table
$user_id = $_SESSION['user_id'];
$query = "SELECT preferences FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($preferences);
$stmt->fetch();
$stmt->close();
?>
    <form action="" method="post">
        <label for="new_email" class="ib padding10px"><a target="_blank" href="mylog">Email</a></label>
        <input type="email" name="new_email" id="new_email" value="<?php echo isset($user_email) ? htmlspecialchars($user_email) : ''; ?>" class="width100 bspaced">
        <label for="new_password" class="ib padding10px">Password</label>
        <input type="password" name="new_password" id="new_password" class="width100 bspaced">
        <div class="button_string">
        <?php if ($preferences == 1) { echo '<a href="page" class="button all">Search Results</a>'; } ?>
        <button type="submit" name="update_profile" class="button">Safeguard</button>
          <?php if ($valid_token): ?>
              <form action="" method="post">
                  <button type="submit" name="invalidate_token" class="button invalidate">Invalidate Token</button>
              </form>
          <?php endif; ?>
            <a href="jett" class="button bye">Logout</a>
            <a href="jettison" class="button warn">Allout</a>
            <a href="donate" class="button all">Donate</a>
        </div>
    </form>
    <?php if (!empty($error_message)): ?>
        <p class="error-message"><?= $error_message ?></p>
    <?php endif; ?>
   <br/><br/><br/><h1><a style="opacity:.8" id="wall" href="wall">Wall</a></h1>
<?php
/* Data *
$words = explode(' ', $user_data); // Split the string into an array of words
if (isset($words[0]) && is_numeric($words[0])) {
    $number_of_words = intval($words[0]);
    array_shift($words); // Remove the first element (number) from the array
} else {
    $number_of_words = 4;
}
$selected_words = implode(' ', array_slice($words, 0, $number_of_words)); // Extract the desired number of words starting from the first word
/* Tags *
$tags = explode(' ', $user_tags); // Split the string into an array of tags
if (isset($tags[0]) && is_numeric($tags[0])) {
    $number_of_tags = intval($tags[0]);
    array_shift($tags); // Remove the first element (number) from the array
} else {
    $number_of_tags = 4;
}
$selected_tags = implode(' ', array_slice($tags, 0, $number_of_tags)); // Extract the desired number of tags starting from the first tag
/* Data */
$words = explode(' ', $user_data); // Split the string into an array of words
if (isset($words[count($words) - 1]) && is_numeric($words[count($words) - 1])) {
    $number_of_words = intval($words[count($words) - 1]);
    array_pop($words); // Remove the last element (number) from the array
} else {
    $number_of_words = 4;
}
$selected_words = implode(' ', array_slice($words, 0, $number_of_words)); // Extract the desired number of words starting from the first word

/* Tags */
$tags = explode(' ', $user_tags); // Split the string into an array of tags
if (isset($tags[count($tags) - 1]) && is_numeric($tags[count($tags) - 1])) {
    $number_of_tags = intval($tags[count($tags) - 1]);
    array_pop($tags); // Remove the last element (number) from the array
} else {
    $number_of_tags = 4;
}
$selected_tags = implode(' ', array_slice($tags, 0, $number_of_tags)); // Extract the desired number of tags starting from the first tag
?>
<form action="" method="post">
    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
    <label for="access_loc"><a href="https://witha.com/?search_query=<?php echo urlencode($access_loc); ?>&search=GO">Location</a></label>
    <br>
    <textarea id="access_loc" name="access_loc" rows="4" cols="50" maxlength="512"><?php echo $access_loc; ?></textarea>
    <br>
    <label for="user_data"><a href="https://witha.com/?search_query=<?php echo urlencode($selected_words); ?>&search=GO">Data</a></label>
    <br>
    <textarea id="user_data" name="user_data" rows="4" cols="50" maxlength="512"><?php echo $user_data; ?></textarea>
    <br>
    <label for="user_style"><a href="#" id="editCSS">Style</a></label>
    <br>
    <textarea id="user_style" name="user_style" rows="8" cols="50" maxlength="4096"><?php echo $user_style; ?></textarea>
    <br>
    <label for="user_body"><a href="#" id="editBody">Body</a></label>
    <br>
    <textarea id="user_body" name="user_body" rows="16" cols="50" maxlength="16384"><?php echo htmlspecialchars_decode($user_body); ?></textarea>
    <br>
    <label for="user_tags"><a href="https://witha.com/?search_query=<?php echo urlencode($selected_tags); ?>&search=GO">Tags</a></label>
    <br>
    <textarea type="text" id="user_tags" name="user_tags" size="50" maxlength="1024"><?php echo $user_tags; ?></textarea>
    <br><br>
    <label><?php echo $user_edited; ?></label>
    <br><br>
    <label for="user_save">User Save:</label>
    <br>
    <textarea id="user_save" name="user_save" rows="4" cols="50" maxlength="512"><?php echo $user_save; ?></textarea>
    <br>
    <label for="user_backup">User Backup:</label>
    <br>
    <textarea id="user_backup" name="user_backup" rows="4" cols="50" maxlength="512"><?php echo $user_backup; ?></textarea>
    <br>
    <input type="submit" name="submit" value="Save Changes" class="button">
   </form>
<!--
<br/>
    <p>Email: <?php /* $user['email'] ?></p>
    <p>Padless: <?php $user['access'] */ ?></p>
   <ul style="display:flex;flex-direction:column-reverse;overflow:auto">
    <?php /* foreach($ip_log as $ip_log_entry) { ?>
        <li><?php echo $ip_log_entry; ?></li>
    <?php } */ ?>
   </ul>-->
<?php
include $path.$add.'c-div.php';
include $path.$add.'c-div.php';
?>
<script>
  var cssTextarea = document.getElementById('user_style');
  var cssToggleLink = document.getElementById('editCSS');
  var styleTag = document.createElement('style');
  styleTag.id = 'user_style';

  function updateStyleTag() {
    styleTag.textContent = cssTextarea.value;
  }

  function toggleCSSMode() {
    if (styleTag.parentNode) {
      // Remove the style tag from the head
      styleTag.parentNode.removeChild(styleTag);
      cssToggleLink.textContent = 'Preview Style';
    } else {
      // Append the style tag to the head
      updateStyleTag();
      document.head.appendChild(styleTag);
      cssToggleLink.textContent = 'Disable Style';
    }
  }

  cssToggleLink.addEventListener('click', function(e) {
    e.preventDefault();
    toggleCSSMode();
  });

  cssTextarea.addEventListener('input', function() {
    updateStyleTag();
  });

  // Show textarea initially
  cssTextarea.style.display = 'block';
</script>
<script>
  var textarea = document.getElementById('user_body');
  var toggleLink = document.getElementById('editBody');
  var displayHTML = document.createElement('div');
  displayHTML.id = 'displayHTML';
  var isHTMLMode = false;

  function updateDisplayHTML() {
    displayHTML.innerHTML = textarea.value;
  }

  function toggleHTMLMode() {
    if (isHTMLMode) {
      // Switch to plain text mode
      textarea.value = displayHTML.innerHTML;
      textarea.style.display = 'block';
      displayHTML.contentEditable = 'false';
      displayHTML.style.pointerEvents = 'none';
      displayHTML.style.userSelect = 'none';
      displayHTML.style.display = 'none';
      toggleLink.textContent = 'Rendered Edit';
    } else {
      // Switch to HTML mode
      displayHTML.innerHTML = textarea.value;
      textarea.style.display = 'none';
      displayHTML.contentEditable = 'true';
      displayHTML.style.pointerEvents = 'auto';
      displayHTML.style.userSelect = 'auto';
      displayHTML.style.display = 'block';
      toggleLink.textContent = 'Text Edit';
    }
    isHTMLMode = !isHTMLMode;
  }

  toggleLink.addEventListener('click', function(e) {
    e.preventDefault();
    if (!displayHTML.parentNode) {
      // Insert displayHTML after label element
      var label = document.querySelector('label[for="user_body"]');
      label.parentNode.insertBefore(displayHTML, label.nextSibling);
    }
    toggleHTMLMode();
  });

  textarea.addEventListener('input', function() {
    if (isHTMLMode) {
      updateDisplayHTML();
    }
  });

  // Show textarea initially
  textarea.style.display = 'block';
</script>
<script>
  function expandAnchorText() {
    var anchor = document.getElementById('wall');
    var container = anchor.parentNode;
    var containerWidth = container.offsetWidth;

    var text = anchor.textContent;
    var newText = text;

    // Calculate the average character width
    var charWidth = anchor.scrollWidth / text.length;

    // Incrementally add "l" characters until the anchor width exceeds the container width
    while (anchor.offsetWidth < containerWidth) {
      newText += 'l';
      anchor.textContent = newText;
    }

    // Reduce the number of "l" characters until the anchor width fits within the container width
    while (anchor.offsetWidth > containerWidth) {
      newText = newText.slice(0, -1);
      anchor.textContent = newText;
    }
  }

  window.addEventListener('DOMContentLoaded', expandAnchorText);
  window.addEventListener('resize', expandAnchorText);
</script>
<?php
include $path.$add.'script.php';
include $path.$add.'c-body_html.php';
    }
}
?>