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
    $search_query = '%' . $search_query . '%';

    $sql = "SELECT a.user_data, a.user_style, a.user_body, a.user_tags
            FROM users u
            JOIN access a ON u.id = a.user_id
            WHERE a.user_data LIKE ? OR a.user_tags LIKE ? OR a.user_body LIKE ?";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        echo "Error preparing statement: " . $db->error . "<br>";
        return [];
    }
    
    $stmt->bind_param("sss", $search_query, $search_query, $search_query);
    if (!$stmt->execute()) {
        echo "Error executing statement: " . $stmt->error . "<br>";
        return [];
    }

    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);

    return $users;
}

$search_results = [];
if (isset($_GET['search_query'])) {
    $search_query = $_GET['search_query'];
    $search_results = searchUsers($db, $search_query);
}

$strings = array(
    "&#34;And in nothing terrified by your adversaries: which is to them an evident token of perdition, but to you of salvation, and that of God.&#34;, Philippians 1:28.",
    "&#34;A merry heart doeth good like a medicine: but a broken spirit drieth the bones.&#34;, Proverbs 17:22.",
    "&#34;To every thing there is a season, and a time to every purpose under the heaven:&#34;, Ecclesiastes 3:1.",
    "&#34;And this is the condemnation, that light is come into the world, and men loved darkness rather than light, because their deeds were evil. For every one that doeth evil hateth the light, neither cometh to the light, lest his deeds should be reproved.&#34;, John 3:19-20.",
    "&#34;All scripture is given by inspiration of God, and is profitable for doctrine, for reproof, for correction, for instruction in righteousness.&#34;, 2 Timothy 3:16.",
    "No texting and driving!",
    "Give a try everyday!",
    "Covfefe!",
    "Basic Instructions Before Leaving Earth",
    "13 0_0",
    "Everything needs instruction manuals :)",
    "Limited edition!",
    "What is W/A?",
    "888 <3",
    "There are no limits!",
    "Welcome to the Lake",
    "I'm stronger than ever before!",
    "Man, nobody preachin' hell anymore...",
    "The wages of sin is death!",
    "Repentance is not what you do, it's what you don't do.",
    "Trust is integrity.",
    "Trust in Jesus like you trust in a parachute.",
    "There is a way that seems right to man, but the end thereof is death.",
    "Jesus paid the ultimate price to grant you everlasting life if you would repent from your sins and trust in Him.",
    "NOW!!! THIS IS YOUR LIFE!",
    "Salvation is more precious than anything on this earth.",
    "Am I a good person?",
    "Stupidity is a pitiful contest.",
    "Are you sure that's what you want?",
    "Religion dulls your conscience, Jesus revives your conscience.",
    "Infanticide is murder.",
    "Abortion is murder.",
    "Pro-choice for abortion is still pro-murder.",
    "To all those who said landing a rocket was (near) impossible... :p",
    "Owning digital currency is like owning a company: you're just going to have to find out who owns the most.",
    "Fear of the Lord is the beginning of wisdom.",
    "WALL",
    "True Free Speech Platform",
    "Less government: get off my back, get out of my pocket!",
    "You actually don't need an account to use W/A",
    ""
);
$randomIndex = array_rand($strings);
$randomString = $strings[$randomIndex];

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
    $internal_style = '<style>' . ($user['user_style'] ?? '') . '</style>';
}
include $add.'a-html.php';
include $add.'head.php';
include $add.'a-body.php';
include $add.'a-container.php';
include $add.'navigation.php';
include $add.('a-10.php');
echo $user_c_container;

if (isset($_SESSION['user_id'])) {
?>
    <form method="GET" action="/">
        <label class="vh" for="search_query">Search:</label>
        <div style="display:flex;overflow:auto">
        <input style="flex:1 1 95%" type="text" id="search_query" name="search_query" value="<?php echo $search_query ?? ''; ?>" placeholder="<?php echo $randomString ?? ''; ?>" class="wrap">
        <input style="flex:0 1 5%" type="submit" name="search" value="GO" class="button search">
        </div>
    </form>
    <div class="search-results">
        <?php if (isset($_GET['search_query']) && !empty($_GET['search_query'])): ?>
            <?php if (count($search_results) == 1 && $search_results[0]['access']) {
              $user_c_container = "</div>";
              $user_a_container = "<div id=\"container\">";
            } else {
              $user_c_container = "";
              $user_a_container = "";
            } ?>
            <?php if (count($search_results) > 0): ?>
                <?php foreach ($search_results as $user): ?>
                    <div class="user-profile">
                        <p><!-- Tags --><?php echo htmlspecialchars($user['user_tags'] ?? ''); ?></p>
                        <br/><br/>
                        <?php echo $user['user_body'] ?? ''; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No results found for "<?php echo htmlspecialchars($search_query); ?>"</p>
            <?php endif; ?>
        <?php else: ?>
            
        <?php endif; ?>
    </div>
    <!--<br/><br/><a href="jett" class="button bye">Logout</a>-->
<?php
} else {
?>
    <form method="GET" action="/">
        <label class="vh" for="search_query">Search:</label>
        <div style="display:flex;overflow:auto">
        <input style="flex:1 1 95%" type="text" id="search_query" name="search_query" value="<?php echo $search_query ?? ''; ?>" placeholder="<?php echo $randomString ?? ''; ?>" class="wrap">
        <input style="flex:0 1 5%" type="submit" name="search" value="GO" class="button search">
        </div>
    </form>
    <div class="search-results">
        <?php if (isset($_GET['search_query']) && !empty($_GET['search_query'])): ?>
            <?php if (count($search_results) == 1 && $search_results[0]['access']) {
              $user_c_container = "</div>";
              $user_a_container = "<div id=\"container\">";
            } else {
              $user_c_container = "";
              $user_a_container = "";
            } ?>
            <?php if (count($search_results) > 0): ?>
                <?php foreach ($search_results as $user): ?>
                    <div class="user-profile">
                        <p><!-- Data --><?php echo htmlspecialchars($user['user_data'] ?? ''); ?></p>
                        <p><!-- Tags --><?php echo htmlspecialchars($user['user_tags'] ?? ''); ?></p>
                        <br/><br/>
                        <?php echo $user['user_body'] ?? ''; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No results found for "<?php echo htmlspecialchars($search_query); ?>"</p>
            <?php endif; ?>
        <?php else: ?>
            
        <?php endif; ?>
       </div>
<?php
}
echo $user_a_container;
include $add.'c-div.php';
include $add.'c-div.php';
include $add.'script.php';
include $add.'c-body_html.php';
?>