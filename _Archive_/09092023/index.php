<?php
session_start();

require_once 'config.php';
require_once 'functions.php';
require_once 'system.php';

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Validate the session
    if (!validateSession($db, $user_id)) {
        // Invalid session, log the user out
        header("location:jett");
    }
}

function searchUsers($db, $search_query) {
    $search_query = '%' . $search_query . '%'; // Ensure the search term is properly formatted for a LIKE query

    $sql = "
        (SELECT p.user_id AS user_id, p.view_loc AS view_loc, p.the_title AS title, p.the_data AS data, p.the_body AS body, p.the_tags AS tags, 'page' AS source
        FROM page p
        WHERE (p.view_loc LIKE ? OR p.the_title LIKE ? OR p.the_data LIKE ? OR p.the_body LIKE ? OR p.the_tags LIKE ?))
        
        UNION ALL
        
        (SELECT a.user_id AS user_id, a.access_loc AS view_loc, a.user_data AS title, a.user_data AS data, a.user_body AS body, a.user_tags AS tags, 'access' AS source
        FROM access a
        WHERE (a.access_loc LIKE ? OR a.user_data LIKE ? OR a.user_body LIKE ? OR a.user_tags LIKE ?))
        
        UNION ALL
        
        (SELECT v.user_id AS user_id, v.view_loc AS view_loc, v.the_title AS title, v.the_data AS data, v.the_body AS body, v.the_tags AS tags, 'view' AS source
        FROM `view` v
        WHERE (v.view_loc LIKE ? OR v.the_title LIKE ? OR v.the_data LIKE ? OR v.the_body LIKE ? OR v.the_tags LIKE ?))
    ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        echo "Error preparing statement: " . $db->error;
        return [];
    }

    $stmt->bind_param(
        "ssssssssssssss",
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query
    );

    if (!$stmt->execute()) {
        echo "Error executing statement: " . $stmt->error;
        return [];
    }

    $result = $stmt->get_result();
    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    return $results;
}

function searchViews($db, $search_query) {
    $search_query = '%' . $search_query . '%';

    $sql = "SELECT * FROM `view`
            WHERE (the_title LIKE ? OR the_data LIKE ? OR the_body LIKE ? OR the_tags LIKE ? OR view_loc LIKE ?)";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        echo "Error preparing statement: " . $db->error . "<br>";
        return [];
    }

    $stmt->bind_param("sssss", $search_query, $search_query, $search_query, $search_query, $search_query);

    if (!$stmt->execute()) {
        echo "Error executing statement: " . $stmt->error . "<br>";
        return [];
    }

    $result = $stmt->get_result();
    $views = $result->fetch_all(MYSQLI_ASSOC);

    return $views;
}

function searchPages($db, $search_query) {
    $search_query = '%' . $search_query . '%';

    $sql = "SELECT * FROM `page`
            WHERE (the_title LIKE ? OR the_data LIKE ? OR the_body LIKE ? OR the_tags LIKE ? OR view_loc LIKE ?)";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        echo "Error preparing statement: " . $db->error . "<br>";
        return [];
    }

    $stmt->bind_param("sssss", $search_query, $search_query, $search_query, $search_query, $search_query);

    if (!$stmt->execute()) {
        echo "Error executing statement: " . $stmt->error . "<br>";
        return [];
    }

    $result = $stmt->get_result();
    $pages = $result->fetch_all(MYSQLI_ASSOC);

    return $pages;
}

$search_results = [];

if (isset($_GET['search_query'])) {
    $search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
    
    if (!empty($search_query)) {
        $search_results = searchUsers($db, $search_query);
        $search_results_views = searchViews($db, $search_query);
        $search_results_pages = searchPages($db, $search_query);
    }
}

// Query to fetch all the_title values from the page table
$sql = "SELECT the_title FROM page";
$result = $db->query($sql);

if ($result && $result->num_rows > 0) {
    // Fetch all the_title values into an array
    $titles = [];
    while ($row = $result->fetch_assoc()) {
        $titles[] = $row['the_title'];
    }

    // Choose a random index from the titles array
    $randomIndex = array_rand($titles);

    // Assign the randomly chosen title to $randomString
    $randomString = $titles[$randomIndex];
} else {
    // Default value if there are no titles in the page table
    $randomString = "Write something clever!";
}


$title = 'W/A . COM';
$description = 'Written Is The Holy Army';
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
if (count($search_results) == 1) {
    $user = $search_results[0];
    
    $internal_style = '<style>';
    
    if ($user['source'] === 'access') {
        // Retrieve all columns from 'access' table
        $access_query = "SELECT * FROM access WHERE user_id = ? LIMIT 1";
        $stmt = $db->prepare($access_query);
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $access = $result->fetch_assoc();
        $internal_style .= $access['user_style'] ?? '';
    } elseif ($user['source'] === 'page') {
        // Retrieve all columns from 'page' table
        $page_query = "SELECT * FROM page WHERE user_id = ? LIMIT 1";
        $stmt = $db->prepare($page_query);
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $page = $result->fetch_assoc();
        $internal_style .= $page['the_style'] ?? '';
    } elseif ($user['source'] === 'view') {
        // Retrieve all columns from 'view' table
        $view_query = "SELECT * FROM `view` WHERE user_id = ? LIMIT 1";
        $stmt = $db->prepare($view_query);
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $view = $result->fetch_assoc();
        $internal_style .= $view['the_style'] ?? '';
    }
    
    $internal_style .= '</style>';
}

include($add . 'a-html.php');
include($add . 'head.php');
include($add . 'a-body.php');
include($add . 'a-container.php');
include($add . 'navigation.php');
include($add . 'a-10.php');

if (isset($_SESSION['user_id'])) {?>
<form method="GET" action="/">
    <label class="vh" for="search_query">Search:</label>
    <div style="display:flex;overflow:auto">
        <input style="flex:1 1 95%" type="text" id="search_query" name="search_query" value="<?php echo htmlspecialchars($search_query ?? ''); ?>" placeholder="<?php echo htmlspecialchars_decode($randomString ?? ''); ?>" class="wrap">
        <input style="flex:0 1 5%" type="submit" name="search" value="GO" class="button search">
    </div>
</form>
<div class="search-results">
    <?php if (isset($_GET['search_query']) && !empty($_GET['search_query'])): ?>
        <?php if (count($search_results) > 0): ?>
            <?php foreach ($search_results as $user): ?>
                <div class="user-profile">
                    <h1><?php echo htmlspecialchars_decode($user['title']); ?></h1>
                    <div><?php echo htmlspecialchars_decode($user['body']); ?></div>
                    <p><?php echo htmlspecialchars_decode($user['data']); ?></p>
                    <p><?php echo htmlspecialchars_decode($user['tags']); ?></p>
                </div>
                <?php /* $pages = searchPages($db, $user['user_id'], $search_query); ?>
                <?php if (count($pages) > 0): ?>
                    <div class="views">
                        <?php foreach ($pages as $page): ?>
                            <div class="view-item">
                                <div><!-- Body --><?php echo htmlspecialchars_decode($page['the_body']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php $users = searchUsers($db, $user['user_id'], $search_query); ?>
                <?php if (count($users) > 0): ?>
                    <div class="users">
                        <?php foreach ($users as $user): ?>
                            <div class="view-item">
                                <div><!-- Body --><?php echo htmlspecialchars_decode($page['the_body']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php $views = searchViews($db, $user['user_id'], $search_query); ?>
                <?php if (count($views) > 0): ?>
                    <div class="views">
                        <?php foreach ($views as $view): ?>
                            <div class="view-item">
                                <div><!-- Body --><?php echo htmlspecialchars_decode($view['the_body']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; */?>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No results found for "<?php echo htmlspecialchars($search_query); ?>"</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php } else {
?>
<form method="GET" action="/">
    <label class="vh" for="search_query">Search:</label>
    <div style="display:flex;overflow:auto">
        <input style="flex:1 1 95%" type="text" id="search_query" name="search_query" value="<?php echo htmlspecialchars($search_query ?? ''); ?>" placeholder="<?php echo htmlspecialchars_decode($randomString ?? ''); ?>" class="wrap">
        <input style="flex:0 1 5%" type="submit" name="search" value="GO" class="button search">
    </div>
</form>
<div class="search-results">
    <?php if (isset($_GET['search_query']) && !empty($_GET['search_query'])): ?>
        <?php if (count($search_results) > 0): ?>
            <?php foreach ($search_results as $user): ?>
                <div class="user-profile">
                    <h1><?php echo htmlspecialchars_decode($user['title']); ?></h1>
                    <div><?php echo htmlspecialchars_decode($user['body']); ?></div>
                    <p><?php echo htmlspecialchars_decode($user['data']); ?></p>
                    <p><?php echo htmlspecialchars_decode($user['tags']); ?></p>
                </div>
                <?php /* $pages = searchPages($db, $user['user_id'], $search_query); ?>
                <?php if (count($pages) > 0): ?>
                    <div class="views">
                        <?php foreach ($pages as $page): ?>
                            <div class="view-item">
                                <div><!-- Body --><?php echo htmlspecialchars_decode($page['the_body']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php $users = searchUsers($db, $user['user_id'], $search_query); ?>
                <?php if (count($users) > 0): ?>
                    <div class="users">
                        <?php foreach ($users as $user): ?>
                            <div class="view-item">
                                <div><!-- Body --><?php echo htmlspecialchars_decode($page['the_body']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php $views = searchViews($db, $user['user_id'], $search_query); ?>
                <?php if (count($views) > 0): ?>
                    <div class="views">
                        <?php foreach ($views as $view): ?>
                            <div class="view-item">
                                <div><!-- Body --><?php echo htmlspecialchars_decode($view['the_body']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; */?>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No results found for "<?php echo htmlspecialchars($search_query); ?>"</p>
        <?php endif; ?>
    <?php endif; ?>
</div>



<?php
}
include($add . 'c-div.php');
include($add . 'c-div.php');
include($add . 'script.php');
include($add . 'c-body_html.php');
?>