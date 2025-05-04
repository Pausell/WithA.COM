<?php
// Set the path to the current page
$current_path = $_SERVER['PHP_SELF'];

// Remove any trailing slash from the path
$current_path = rtrim($current_path, '/');

// Get the number of subdirectories from the root directory to the current page
$subdir_count = substr_count($current_path, '/') - 1;

// Construct the relative path from the root directory to the current page
$path = str_repeat('../', $subdir_count);

// Resources Directory
$add = "add/";

// Build the canonical URL using the protocol, hostname, and current path
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$hostname = $_SERVER['HTTP_HOST'];
$canonical = $protocol . "://" . $hostname . $current_path;
$canonical = str_replace(".php", "", $canonical);
?>
<head>
 <title><?php echo $title; ?></title>
 <meta name="description" content="<?php echo $description; ?>">
 <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=yes"">
 <link rel="shortcut icon" type="image/png" href="<?php echo $favicon; ?>">
 <link rel="shortcut icon" type="image/png" href="<?php echo $favicon16; ?>">
  <link rel="apple-touch-icon" href="<?php echo $iphone; ?>touch-icon-iphone.png">
  <link rel="apple-touch-icon" sizes="152x152" href="<?php echo $ipad; ?>touch-icon-ipad.png">
  <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $iphoner; ?>touch-icon-iphone-retina.png">
  <link rel="apple-touch-icon" sizes="167x167" href="<?php echo $ipadr; ?>touch-icon-ipad-retina.png">
 <meta name="msapplication-config" content="<?php echo $browserconfig; ?>browserconfig.xml"/>
 <link rel="canonical" href="<?php echo $canonical; ?>"/>
 <link rel="stylesheet" href="<?php echo $style; ?>">
<?php echo $internal_style ?>
<?php echo $internal_head ?>
</head>