<?php
/**
 * =========================================================================
 * witha.com
 * /index.php
 * =========================================================================
 */



/* =_SESSION_=============================================================*/
session_start(); 
require_once __DIR__.'/auth/db.php';
require_once __DIR__.'/auth/schema.php';
global $pdo, $pdoSites;

$account   = $_SESSION['user'] ?? 0;

// retrieve Profile Image (optional)
$img = null;
if (isset($_SESSION['user'])) {
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']]);
    $img = $stmt->fetchColumn() ?: null;
}

// 2) Start caching
include 'cache-start.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
<title>W/A</title>
<meta name="description" content="Written Is The Holy Army's Diadem Of Time's Coaliscing Object Model">
<link rel="canonical" href="https://witha.com">
<link rel="shortcut icon" type="image/png" href="aw.png">
<link rel="apple-touch-icon" sizes="180x180" href="aw.png">
<link rel="icon" type="image/png" sizes="32x32" href="aw32.png">
<link rel="icon" type="image/png" sizes="16x16" href="aw16.png">
<meta name="theme-color" content="#000000">
<meta name="msapplication-TileColor" content="#003700">
<meta name="referrer" content="origin">
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav id="navigate">
 <a href="/"            title="who we are: WithA"><h1>W/A</h1></a>
 <a href="/instantiate" title="" style="text-align:center"></a>
 <a href="/truth"       title="" style="text-align:center"></a>
 <a href="/raw"         title="where to get started: Here" style="text-align:right"><?= !empty($img) ? '<img src="'.htmlspecialchars($img).'"style="width:27.5px;height:27.5px;border-radius:50%;object-fit:cover" alt="record">' : '&#174;' ?></a>
</nav>
<section id="beginning" class="contained">
<header>
 <form id="search" class="id" action="connectere" method="get">
  <input type="search" name="query" placeholder="Find a connection to start." value="">
  <input type="submit" value="&nbsp;&nbsp;" spellcheck="false">
 </form>
</header>
<main>
<?php
// 4) Popular searches
$logDir     = 'connectere/search-logs';
$currentYear= date('Y');
$blacklist  = ['test','example','ignoreme','local.dev']; // skip these

$logFileQueries = "$logDir/$currentYear.log";
$topSearches    = [];

if (file_exists($logFileQueries)) {
    foreach (file($logFileQueries, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        [$term, $count] = explode('|', $line, 2);
        $term = strtolower(trim($term));
        if (in_array($term, $blacklist)) continue;
        $topSearches[$term] = (int)$count;
    }
    // random shuffle to avoid guessable order
    shuffleAssoc($topSearches);
}

// show them
?>
<ul class="clist"><?php if (!isset($_SESSION['user'])) { ?>
  <li><a href="raw">publish my search result</a></li> <?php } ?>
  <?php foreach (array_slice($topSearches, 0, 10) as $term => $count): //limit common searches to 10 ?>
    <li><a href="/connectere?query=<?= urlencode($term) ?>"><?= htmlspecialchars($term) ?></a></li>
  <?php endforeach; ?>
</ul>

<?php
// 5) COMMON RESULTS (no ".html" in the logs => my-page|2 => matches connectere/my-page.html)
function shuffleAssoc(&$array) {
    $keys = array_keys($array);
    shuffle($keys);
    $new=[];
    foreach($keys as $k) {
      $new[$k] = $array[$k];
    }
    $array = $new;
}

// read r.log => store e.g. "my-page" => 2
$logFileResults = "$logDir/{$currentYear}r.log";
$topFiles = [];
if (file_exists($logFileResults)) {
    foreach (file($logFileResults, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        [$filename, $count] = explode('|', $line, 2);
        $filename = strtolower(trim($filename));
        if ($filename === '' || in_array($filename, $blacklist)) continue;
        $topFiles[$filename] = (int)$count;
    }
    shuffleAssoc($topFiles);
}

// load local .html => strip .html => "my-page"
$htmlFiles = glob(__DIR__ . '/connectere/*.html');
$htmlIndex=[];
foreach($htmlFiles as $fp){
    $key = strtolower(basename($fp, '.html')); // e.g. "my-page"
    $htmlIndex[$key] = $fp; 
}

// gather results
$results=[];
foreach($topFiles as $filename=>$count){
    if(!isset($htmlIndex[$filename])) continue; 
    $contents=file_get_contents($htmlIndex[$filename]);

    // parse title
    preg_match('/<title>(.*?)<\/title>/is',$contents,$titleM);
    $title = trim($titleM[1]??$filename);

    // parse canonical
    preg_match('/<link\s+rel=["\']canonical["\']\s+href=["\'](.*?)["\']\s*\/?>/i',$contents,$canonM);
    $canonical=trim($canonM[1]??'');

    // parse <svg data-image="logo"
    preg_match('/<svg[^>]*data-image=["\']logo["\'][^>]*>.*?<\/svg>/is',$contents,$svgM);
    $svgTag = $svgM[0]??null;

    // check empty body
    $emptyBody = !preg_match('/<body[^>]*>(.*?)<\/body>/is',$contents,$mBody)
                 || !trim(strip_tags(preg_replace('/<!--.*?-->/s','',$mBody[1]??'')));

    // meta
    $meta=[
      'application-name'=>extract_meta($contents,'application-name'),
      'author'         =>extract_meta($contents,'author'),
      'description'    =>extract_meta($contents,'description'),
      'generator'      =>extract_meta($contents,'generator'),
      'keywords'       =>extract_meta($contents,'keywords'),
      'refresh'        =>extract_http_equiv($contents,'refresh'),
    ];

    $results[]=[
      'filename'=>$filename,
      'canonical'=>$canonical,
      'title'=>$title,
      'image'=>$svgTag,
      'meta'=>$meta,
      'score'=>$count,
      'bodyEmpty'=>$emptyBody
    ];
    if(count($results)>=20) break;
}

// display them
?>
<div class="search">
<?php foreach ($results as $r): ?>
  <?php
    $isDomain = preg_match('/^(?!-)(?:[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9]{1,59})$/i',$r['filename']);
    // e.g. "my-page" => link /connectere?query=my-page
    $finalLink='/connectere?query='.urlencode($r['filename']);
    if($r['bodyEmpty'] && $r['canonical']){
       $finalLink=$r['canonical'];
    } elseif(!$isDomain){
       $finalLink.='&view=1';
    }
    $meta=$r['meta'];
  ?>
  <div class="search-result">
    <div class="linked">
      <a class="z2 pr block w100" href="<?= htmlspecialchars($finalLink) ?>">
        <?php if($r['image']): ?>
          <span class="imagery" aria-label="logo"><?= $r['image'] ?></span>
        <?php endif; ?>
        <span class="material">
          <p><?= htmlspecialchars($r['title']) ?>
            <span class="rlink">
              <?= htmlspecialchars($r['canonical'] ?: $r['filename']) ?>
            </span>
          </p>
          <p title="description"><?= htmlspecialchars($meta['description'] ?? '') ?></p>
          <p>
            <?php
            $pieces=[];
            if(!empty($meta['keywords']))  $pieces[]='Keywords: '.htmlspecialchars($meta['keywords']);
            if(!empty($meta['refresh']))   $pieces[]='Refresh: '.htmlspecialchars($meta['refresh']).'s';
            if(!empty($meta['author']))    $pieces[]='Author: '.htmlspecialchars($meta['author']);
            if(!empty($meta['application-name'])) $pieces[]='App: '.htmlspecialchars($meta['application-name']);
            if(!empty($meta['generator'])) $pieces[]='Gen: '.htmlspecialchars($meta['generator']);
            echo implode(' &middot; ',$pieces);
            ?>
          </p>
        </span>
      </a>
    </div>
  </div>
<?php endforeach; ?>
</div>

   <div class="search-result">
     <div class="linked">
       <a href="#" title="logo" class="z2 pr block w100">
           <span class='imagery'><svg aria-label="logo" width="100%" height="261" viewBox="0 0 260 261" fill="none" xmlns="http://www.w3.org/2000/svg" data-created-by="witha">
<g clip-path="url(#icon)">
<rect width="260" height="260" transform="translate(0 0.710938)" fill="var(--background-witha)"/>
<path d="M232.888 141.28V156.791H197.093V141.28H232.888Z" fill="url(#grade)"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M196.719 172.75L215 104.832L233.282 172.75H249.236L226.736 89.1592H219.219H210.782H203.265L180.765 172.75H196.719Z" fill="var(--color-witha)"/>
<path d="M179.219 89.1601L156.719 172.751H140.764L163.264 89.1601H179.219Z" fill="var(--color-witha)"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M33.2812 172.751L10.7812 89.1602H26.7358L45 157.014L63.2642 89.1602H70.7812H79.2188H86.7358L105 157.014L123.264 89.1602H139.219L116.719 172.751H109.236H100.764H93.2812L75 104.833L56.7188 172.751H49.2358H40.7642H33.2812Z" fill="var(--color-witha)"/>
</g>
<defs>
<linearGradient id="grade" x1="215" y1="148.806" x2="215" y2="149.306" gradientUnits="userSpaceOnUse">
<stop offset="0.9999" stop-color="var(--color-witha"/>
<stop offset="1" stop-color="var(--color-witha)" stop-opacity="0.5"/>
</linearGradient>
<clipPath id="icon">
<rect y="0.710938" width="260" height="260" rx="57.54" fill="var(--background-witha)"/>
</clipPath>
</defs>
</svg></span>
         <div>
           <p title="title">WithA &nbsp;
             <span class="rlink" title="universal resource link">https://witha.com</span>
           </p>
           <p title="description">With information that helps all: the anti-censorship platform.</p>
           <p>
             <span title="keywords">Write, Imagine, The, How</span>
             &#183;
             <span title="author">Free</span>
             &#183;
             <span title="application name">And</span>
             &#183;
             <span title="generator">Custom Trace</span>
           </p>
         </div>
       </a>
     </div>
   </div>
</div> <!-- end of .search -->

<?php
// 7) parse meta helpers
function extract_meta($html, $name) {
    $pattern='/<meta\s+name=["\']'.preg_quote($name,'/').'["\']\s+content=["\'](.*?)["\']\s*\/?>/i';
    if(preg_match($pattern,$html,$m)) return trim($m[1]);
    return null;
}
function extract_http_equiv($html, $name) {
    $pattern='/<meta\s+http-equiv=["\']'.preg_quote($name,'/').'["\']\s+content=["\'](.*?)["\']\s*\/?>/i';
    if(preg_match($pattern,$html,$m)) return trim($m[1]);
    return null;
}
?>
</main>
</section>
<footer title="With All Respect">
 
</footer>
</body>
</html>
<?php
// 8) End caching
$final = ob_get_clean();
$mc->set($cacheKey, $final, 86400); // 24-hour
echo $final;
include 'cache-end.php';

/* Done! */
