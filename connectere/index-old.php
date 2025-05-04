
<?php
// connectere/index.php
$view = isset($_GET['view']) && $_GET['view'] === '1';

$query   = isset($_GET['query']) ? trim($_GET['query']) : '';
$queryCI = strtolower($query);
    if (!empty($queryCI)) {
        $logDir = __DIR__ . '/search-logs';
        $year = date('Y');
        $logFileQueries = "$logDir/$year.log";
        $logFileResults = "$logDir/{$year}r.log";
    
        if (!is_dir($logDir)) mkdir($logDir, 0777, true);
    
        // QUERY LOGGING
        $queryCounts = [];
        if (file_exists($logFileQueries)) {
            foreach (file($logFileQueries, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                [$term, $count] = explode('|', $line, 2);
                $queryCounts[$term] = (int)$count;
            }
        }
        $queryCounts[$queryCI] = ($queryCounts[$queryCI] ?? 0) + 1;
        arsort($queryCounts);
        file_put_contents($logFileQueries, implode("\n", array_map(fn($k, $v) => "$k|$v", array_keys($queryCounts), $queryCounts)), LOCK_EX);
    }
$domainParam = isset($_GET['domain']) ? strtolower(trim($_GET['domain'])) : '';
$baseDir = __DIR__;
$htmlFiles = glob($baseDir . '/*.html');
$results = [];
$perPage = 47;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$domainWhitelist = ['witha'];

// Full domain validation with punycode support
function is_valid_domain($domain) {
    return preg_match(
        '/^(?!-)(?:[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9]{1,59})$/i',
        $domain
    );
}

// Meta tag extraction helper
function extract_meta($html, $name) {
    $pattern = '/<meta\s+name=["\']' . preg_quote($name, '/') . '["\']\s+content=["\'](.*?)["\']\s*\/?>/i';
    preg_match($pattern, $html, $match);
    return isset($match[1]) ? trim($match[1]) : null;
}

function extract_http_equiv($html, $name) {
    $pattern = '/<meta\s+http-equiv=["\']' . preg_quote($name, '/') . '["\']\s+content=["\'](.*?)["\']\s*\/?>/i';
    preg_match($pattern, $html, $match);
    return isset($match[1]) ? trim($match[1]) : null;
}

// Canonical URL extraction helper
function extract_canonical($html) {
    $pattern = '/<link\s+rel=["\']canonical["\']\s+href=["\'](.*?)["\']\s*\/?>/i';
    preg_match($pattern, $html, $match);
    return isset($match[1]) ? trim($match[1]) : null;
}

// Checks if <body> is effectively empty (only whitespace/comments)
function is_body_empty($html) {
    if (!preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
        // If there's no <body> at all, treat it as empty
        return true;
    }
    $bodyContent = $matches[1];
    // Remove all HTML comments
    $bodyContent = preg_replace('/<!--.*?-->/s', '', $bodyContent);
    // Strip tags and trim whitespace
    $bodyContent = trim(strip_tags($bodyContent));
    return strlen($bodyContent) === 0;
}

foreach ($htmlFiles as $filePath) {
    $filename = basename($filePath, '.html');
    $contents = file_get_contents($filePath);

    // Extract head tags
    preg_match('/<title>(.*?)<\/title>/is', $contents, $titleMatch);
    $title = isset($titleMatch[1]) ? trim($titleMatch[1]) : null;

    $meta = [
        'application-name' => extract_meta($contents, 'application-name'),
        'author' => extract_meta($contents, 'author'),
        'description' => extract_meta($contents, 'description'),
        'generator' => extract_meta($contents, 'generator'),
        'keywords' => extract_meta($contents, 'keywords'),
        'viewport' => extract_meta($contents, 'viewport'),
        'refresh' => extract_http_equiv($contents, 'refresh')
    ];

    // Extract canonical URL
    $canonical = extract_canonical($contents);

    // Extract inline <svg data-image="logo">
    preg_match('/<svg[^>]*data-image=["\']logo["\'][^>]*>.*?<\/svg>/is', $contents, $svgMatch);
    $svgTag = !empty($svgMatch[0]) ? $svgMatch[0] : null;

    // Check if <body> is empty
    $emptyBody = is_body_empty($contents);

    // Searchable content
    $searchableText = strtolower($filename . ' ' . $title . ' ' . implode(' ', $meta));

    // Priority: exact filename match, domain checks (multiple checks)
    if (($query === $filename || $domainParam === $filename) &&
        (is_valid_domain($query) || in_array($query, $domainWhitelist) || $view)) {
        $exactPath = $baseDir . '/' . $filename . '.html';
        if (file_exists($exactPath)) {
            header('Content-Type: text/html; charset=UTF-8');
            echo file_get_contents($exactPath);
            exit;
        }
    }

    // Only add a result when the user actually typed a query
    if ($query !== '' && strpos($searchableText, $queryCI) !== false) {
        // Basic count of how many times $query appears in $searchableText
        $score = 0;
        if ($query !== '') {
            $score = ($query === '') ? 0 : substr_count($searchableText, $queryCI);
        }

        $results[] = [
            'filename' => $filename,
            'canonical' => $canonical,
            'title' => $title ?? $filename,
            'image' => $svgTag,
            'meta' => $meta,
            'score' => $score,
            'bodyEmpty' => $emptyBody
        ];
    }
}

// Sort by relevance descending
usort($results, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

$totalResults = count($results);
$totalPages = ceil($totalResults / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedResults = array_slice($results, $offset, $perPage);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
<title>W / <?= htmlspecialchars($query) ?></title>
<link rel="stylesheet" type="text/css" href="../style.css">
<meta name="theme-color" content="#000000">
</head>
<body>
<div class="context"><a href="https://witha.com"><span class="title"><h1>W/A</h1></span></a><a href="../raw/index.html"><span class="title t2"><h1>&#174;</h1></span></a></div>
<a href="#"></a>
<div class="contained">
<form class="id" action="/connectere" method="get">
  <input type="search" name="query" placeholder="Find a connection to start." value="<?= htmlspecialchars($query) ?>" />
  <input style="position:absolute;opacity:0;z-index:-1;left:0px" type="submit" value="Search" spellcheck="false" />
</form>

<div class="search">
<?php if (count($results) === 0): ?>
  <p>No connections found.</p>
<main class="z1">
<?php
$logDir = 'search-logs';
$currentYear = date('Y');
$blacklist = ['test', 'example', 'ignoreme', 'local.dev']; // blacklist for both searches + results

// --- POPULAR SEARCHES ---
$logFileQueries = "$logDir/$currentYear.log";
$topSearches = [];

if (file_exists($logFileQueries)) {
    foreach (file($logFileQueries, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        [$term, $count] = explode('|', $line, 2);
        $term = strtolower($term);
        if (in_array($term, $blacklist)) continue;
        $topSearches[$term] = (int)$count;
    }
    arsort($topSearches);
//to update with count, add the following line and be sure to close it with ? > by removing the extra space:
// (<?= $count ? >) 
//
//how to edit counts results limits searches totals
//to edit topSearches count, edit: $array_slice($topSearches, 0, 15)
//to edit common results count, edit: if (count($results) >= 20) break;
}
?>
<ul class="clist">
<?php foreach (array_slice($topSearches, 0, 15) as $term => $count): ?>
  <li><a href="/?query=<?= urlencode($term) ?>"><?= htmlspecialchars($term) ?></a></li>
<?php endforeach; ?>
</ul>

<?php
// --- COMMON RESULTS ---
$logFileResults = "$logDir/{$currentYear}r.log";
$topFiles = [];

if (file_exists($logFileResults)) {
    foreach (file($logFileResults, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        [$filename, $count] = explode('|', $line, 2);
        $filename = strtolower($filename);
        if (in_array($filename, $blacklist)) continue;
        $topFiles[$filename] = (int)$count;
    }
    arsort($topFiles);
}

// Load and parse HTML pages for rendering
$htmlFiles = glob(__DIR__ . '*.html');
$htmlIndex = [];
foreach ($htmlFiles as $filePath) {
    $key = strtolower(basename($filePath, '.html'));
    $htmlIndex[$key] = $filePath;
}

$results = [];
foreach ($topFiles as $filename => $count) {
    if (!isset($htmlIndex[$filename])) continue;

    $contents = file_get_contents($htmlIndex[$filename]);

    preg_match('/<title>(.*?)<\/title>/is', $contents, $titleMatch);
    $title = trim($titleMatch[1] ?? $filename);

    $meta = [
        'application-name' => extract_meta($contents, 'application-name'),
        'author' => extract_meta($contents, 'author'),
        'description' => extract_meta($contents, 'description'),
        'generator' => extract_meta($contents, 'generator'),
        'keywords' => extract_meta($contents, 'keywords'),
        'refresh' => extract_http_equiv($contents, 'refresh')
    ];

    preg_match('/<link\s+rel=["\']canonical["\']\s+href=["\'](.*?)["\']\s*\/?>/i', $contents, $canonMatch);
    $canonical = trim($canonMatch[1] ?? '');

    preg_match('/<svg[^>]*data-image=["\']logo["\'][^>]*>.*?<\/svg>/is', $contents, $svgMatch);
    $svgTag = $svgMatch[0] ?? null;

    // Single result check
    $emptyBody = !preg_match('/<body[^>]*>(.*?)<\/body>/is', $contents, $m) ||
                 !trim(strip_tags(preg_replace('/<!--.*?-->/s', '', $m[1])));

    $results[] = [
        'filename' => $filename,
        'canonical' => $canonical,
        'title' => $title,
        'image' => $svgTag,
        'meta' => $meta,
        'score' => $count,
        'bodyEmpty' => $emptyBody
    ];

    if (count($results) >= 20) break;
}
?>

<div class="search">
<?php foreach ($results as $result): ?>
  <?php
$meta = $result['meta'];
$isDomain = is_valid_domain($result['filename']);
$isWhitelisted = in_array($result['filename'], $domainWhitelist);

$finalLink = '/connectere?query=' . urlencode($result['filename']);
if ($result['bodyEmpty'] && !empty($result['canonical'])) {
    $finalLink = $result['canonical'];
} else {
    if (!$isDomain && !$isWhitelisted) {
        $finalLink .= '&view=1';
    }
}

$targetAttr = (str_starts_with($finalLink, 'http://') || str_starts_with($finalLink, 'https://')) ? ' target="_blank" rel="noopener"' : '';

  ?>
  <div class="search-result">
    <a class="link" href="<?= htmlspecialchars($finalLink) ?>"<?= $targetAttr ?>></a>

    <div class="linked">
      <a class="z2 pr block w100" href="<?= htmlspecialchars($finalLink) ?>"<?= $targetAttr ?>>

        <?php if (!empty($result['image'])): ?>
          <span class='imagery' aria-label="logo">
            <?= $result['image'] ?>
          </span>
        <?php endif; ?>

        <span class='material'>
          <p title="title" aria-label="title">
            <?= htmlspecialchars($result['title']) ?>
            <span class="rlink" title="universal resource link" aria-label="universal resource link">
              <?= htmlspecialchars($result['canonical'] ?? $result['filename']) ?>
            </span>
          </p>
          <p title="description" aria-label="description">
            <?= htmlspecialchars($meta['description'] ?? '') ?>
          </p>
          <p>
            <?php $hasPrevious = false; ?>
            <?php if (!empty($meta['keywords'])): ?>
              <span title="keywords" aria-label="keywords">
                <?= htmlspecialchars($meta['keywords']) ?>
              </span>
              <?php $hasPrevious = true; ?>
            <?php endif; ?>

            <?php if (!empty($meta['refresh'])): ?>
              <?php if ($hasPrevious): ?> &#183; <?php endif; ?>
              <span title="refresh rate" aria-label="refresh rate">
                &#9415; <?= htmlspecialchars($meta['refresh']) ?>seconds
              </span>
              <?php $hasPrevious = true; ?>
            <?php endif; ?>

            <?php if (!empty($meta['author'])): ?>
              <?php if ($hasPrevious): ?> &#183; <?php endif; ?>
              <span title="author" aria-label="author">
                <?= htmlspecialchars($meta['author']) ?>
              </span>
              <?php $hasPrevious = true; ?>
            <?php endif; ?>

            <?php if (!empty($meta['application-name'])): ?>
              <?php if ($hasPrevious): ?> &#183; <?php endif; ?>
              <span title="application name" aria-label="application name">
                <?= htmlspecialchars($meta['application-name']) ?>
              </span>
              <?php $hasPrevious = true; ?>
            <?php endif; ?>

            <?php if (!empty($meta['generator'])): ?>
              <?php if ($hasPrevious): ?> &#183; <?php endif; ?>
              <span title="generator" aria-label="generator">
                <?= htmlspecialchars($meta['generator']) ?>
              </span>
            <?php endif; ?>
          </p>
          <p style="font-size: 0.8em; color: #666;">
            Body Empty: <?= $result['bodyEmpty'] ? 'true' : 'false' ?>
          </p>
        </span>
      </a>
    </div>
  </div>
<?php endforeach; ?>
   <div class="search-result">
    <a class="link" href="#"></a>
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
</svg>
</span>
         <div>
           <p title="title">WithA &nbsp;<span class="rlink" title="universal resource link">https://witha.com</span></p>
           <p title="description">With information that helps all: the anti-censorship platform.</p>
           <p>
         <span title="keywords">Write, Imagine, The, How</span>
         &#183;
         <span title="author">Free</span>
         &#183;
         <span title="application name">And</span>
         &#183;
         <span title="generator">Custom Trace</span>
         </p></div>
       </a>
     </div>
   </div>
</div>
</main>
<?php else: ?>
 
 
 
<?php
$logDir = 'search-logs';
$year = date('Y');
$logFileResults = "$logDir/{$year}r.log";
if (!is_dir($logDir)) mkdir($logDir, 0777, true);
?>
 
 
  <?php foreach ($paginatedResults as $result): ?>
   
   
   
    <?php
    // Log actual result view
    $resultCounts = [];
    if (file_exists($logFileResults)) {
        foreach (file($logFileResults, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            [$fileKey, $count] = explode('|', $line, 2);
            $resultCounts[$fileKey] = (int)$count;
        }
    }
    $resultCounts[$result['filename']] = ($resultCounts[$result['filename']] ?? 0) + 1;
    arsort($resultCounts);
    file_put_contents($logFileResults, implode("\n", array_map(fn($k, $v) => "$k|$v", array_keys($resultCounts), $resultCounts)), LOCK_EX);
    ?>
   
   
   
    <?php
      $isDomain = is_valid_domain($result['filename']);
      $isWhitelisted = in_array($result['filename'], $domainWhitelist);

      // Decide final link based on empty body + canonical
      $finalLink = '/connectere?query=' . urlencode($result['filename']);
      if ($result['bodyEmpty'] && !empty($result['canonical'])) {
          // If body is empty and there's a canonical, link directly to the canonical
          $finalLink = $result['canonical'];
      } else {
          // Otherwise link to our local viewer if not whitelisted or not a valid domain
          if (!$isDomain && !$isWhitelisted) {
              $finalLink .= '&view=1';
          }
      }
      $meta = $result['meta'];
    ?>
    <div class="search-result">
      <a class="link" href="<?= htmlspecialchars($finalLink) ?>"></a>
      <div class="linked">
        <a class="z2 pr block w100" href="<?= htmlspecialchars($finalLink) ?>">
          <?php if (!empty($result['image'])): ?>
          <span class='imagery' aria-label="logo"><?= $result['image'] ?></span>
          <?php endif; ?>
          <span class='material'>
            <p title="title" aria-label="title">
              <?= htmlspecialchars($result['title']) ?>
              <span class="rlink" title="universal resource link" aria-label="universal resource link">
                <?= htmlspecialchars($result['canonical'] ?? $result['filename']) ?>
              </span>
            </p>
            <p title="description" aria-label="description"><?= htmlspecialchars($meta['description'] ?? '') ?></p>
            <p>
            <?php $hasPrevious = false; ?>
            <?php if (!empty($meta['keywords'])): ?>
              <span title="keywords" aria-label="keywords"><?= htmlspecialchars($meta['keywords']) ?></span>
              <?php $hasPrevious = true; ?>
            <?php endif; ?>
            <?php if (!empty($meta['refresh'])): ?>
              <?php if ($hasPrevious): ?> &#183; <?php endif; ?>
              <span title="refresh rate" aria-label="refresh rate">&#9415; <?= htmlspecialchars($meta['refresh']) ?>seconds</span>
              <?php $hasPrevious = true; ?>
            <?php endif; ?>
            <?php if (!empty($meta['author'])): ?>
              <?php if ($hasPrevious): ?> &#183; <?php endif; ?>
              <span title="author" aria-label="author"><?= htmlspecialchars($meta['author']) ?></span>
              <?php $hasPrevious = true; ?>
            <?php endif; ?>
            <?php if (!empty($meta['application-name'])): ?>
              <?php if ($hasPrevious): ?> &#183; <?php endif; ?>
              <span title="application name" aria-label="application name"><?= htmlspecialchars($meta['application-name']) ?></span>
              <?php $hasPrevious = true; ?>
            <?php endif; ?>
            <?php if (!empty($meta['generator'])): ?>
              <?php if ($hasPrevious): ?> &#183; <?php endif; ?>
              <span title="generator" aria-label="generator"><?= htmlspecialchars($meta['generator']) ?></span>
              <?php $hasPrevious = true; ?>
            <?php endif; ?>
            </p>
          </span>
        </a>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php // pagination
if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <?php
        $isActive = $i === $page;
        $link = '/connectere?query=' . urlencode($query) . '&page=' . $i;
      ?>
      <a href="<?= htmlspecialchars($link) ?>"<?= $isActive ? ' style="font-weight:bold;text-decoration:underline;"' : '' ?>>
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
</div>
</div>
</body>
</html>