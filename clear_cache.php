<?php
/**
 * clear_cache.php
 * 
 * A standalone script to send PURGE requests to your server, 
 * attempting to clear the dynamic / Varnish cache for certain URLs.
 * 
 * Usage #1: CLI or cron job => `php /path/to/clear_cache.php`
 * Usage #2: Browser => `https://yourdomain.com/clear_cache.php`
 */

// URLs you want to purge. 
// Could be your homepage, or a specific pattern. 
// Sometimes you have to list out multiple URLs if your cache doesn't allow wildcard purge.
$urlsToPurge = [
    'https://yourdomain.com/',
    'https://yourdomain.com/index.html', 
    'https://yourdomain.com/somepage.html',
    // ... or whatever resources you want to invalidate
];

foreach ($urlsToPurge as $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PURGE'); // The PURGE verb
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        echo "Error purging $url: $error\n";
    } else {
        echo "PURGE $url => HTTP $code\n";
        echo "Response: $response\n\n";
    }
}

// Optionally return an HTTP 200 or a small message if accessed via browser:
echo "Done attempting to purge cache.\n";
