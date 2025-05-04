<?php
// connectere/index.php
session_start();
require_once __DIR__.'/../auth/db.php';
require_once __DIR__.'/../auth/schema.php';
global $pdo, $pdoSites;

/* ---------------- PROFILE IMAGE ---------------- */
$img = null;
if (isset($_SESSION['user'])) {
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']]);
    $img = $stmt->fetchColumn() ?: null;
}

/* ---------------- LOG RESULT VIEWS ------------- */
function incrementPageCount(string $filename) {
    $year = date('Y');
    $logDir = __DIR__.'/search-logs';
    $logFile = "$logDir/{$year}r.log";
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);

    $counts = [];
    if (file_exists($logFile)) {
        foreach (file($logFile, FILE_IGNORE_NEW_LINES) as $line) {
            [$key,$cnt] = explode('|',$line,2);
            $counts[$key] = (int)$cnt;
        }
    }
    $filename = strtolower($filename);
    $counts[$filename] = ($counts[$filename] ?? 0) + 1;
    arsort($counts);
    file_put_contents($logFile, implode("\n", array_map(fn($k,$v)=>"$k|$v", array_keys($counts), $counts)), LOCK_EX);
}

/* ---------------- HELPERS ---------------------- */
function is_valid_domain($d){
    return preg_match('/^(?!-)(?:[a-z0-9](?:[a-z0-9\-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9]{1,59})$/i',$d);
}
function extract_meta($html,$name){
    return preg_match('/<meta\s+name=["\']'.preg_quote($name,'/').'["\']\s+content=["\'](.*?)["\']\s*\/?>/i',$html,$m)?trim($m[1]):null;
}
function extract_http_equiv($html,$name){
    return preg_match('/<meta\s+http-equiv=["\']'.preg_quote($name,'/').'["\']\s+content=["\'](.*?)["\']\s*\/?>/i',$html,$m)?trim($m[1]):null;
}
function extract_canonical($html){
    return preg_match('/<link\s+rel=["\']canonical["\']\s+href=["\'](.*?)["\']\s*\/?>/i',$html,$m)?trim($m[1]):null;
}
function extract_relevant($html):array{
    $raw = extract_meta($html,'relevant');
    if(!$raw) return [];
    $items = array_map('trim', explode(',',$raw));
    $parsed=[];
    foreach($items as $item){
        if($item==='') continue;
        [$domain,$rank] = array_pad(explode('?',$item,2),2,null);
        if(!is_valid_domain($domain)) continue;
        $parsed[]=['domain'=>$domain,'rank'=>is_numeric($rank)?(int)$rank:999];
    }
    usort($parsed,fn($a,$b)=>$a['rank']<=>$b['rank']);
    return array_column($parsed,'domain');
}
function is_body_empty(string $html):bool{
    $doc=new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    libxml_clear_errors();
    $body=$doc->getElementsByTagName('body')->item(0);
    if(!$body) return true;
    foreach($body->childNodes as $n){
        if($n->nodeType===XML_TEXT_NODE && trim($n->textContent)!=='') return false;
        if($n->nodeType===XML_ELEMENT_NODE) return false;
    }
    return true;
}

/* ---------------- INPUT ------------------------ */
$query   = isset($_GET['query']) ? trim($_GET['query']) : '';
$queryCI = strtolower($query);
$isRel   = isset($_GET['search']) && $_GET['search']==='rel';

/* ---------------- LOG QUERIES ------------------ */
if($queryCI!==''){
    $year=date('Y');
    $logDir=__DIR__.'/search-logs';
    $logFile="$logDir/$year.log";
    if(!is_dir($logDir)) mkdir($logDir,0777,true);
    $counts=[];
    if(file_exists($logFile)){
        foreach(file($logFile,FILE_IGNORE_NEW_LINES) as $l){
            [$term,$cnt]=explode('|',$l,2);
            $counts[$term]=(int)$cnt;
        }
    }
    $counts[$queryCI]=($counts[$queryCI]??0)+1;
    arsort($counts);
    file_put_contents($logFile,implode("\n",array_map(fn($k,$v)=>"$k|$v",array_keys($counts),$counts)),LOCK_EX);
}

/* ---------------- FILE SYSTEM ------------------ */
$baseDir = __DIR__;
$htmlFiles = glob("$baseDir/*.html");
$perPage = 47;
$page = max(1, (int)($_GET['page'] ?? 1));

/* =================================================
   DIRECT DOMAIN HANDLING
   =================================================*/
$exactPath = $query!=='' ? "$baseDir/$query.html" : null;
if($query!=='' && file_exists($exactPath)){
    $contents = file_get_contents($exactPath);
    $relevant = extract_relevant($contents);

    /* redirect to rel-search listing if relevant meta exists and not already in rel mode */
    if(!$isRel && $relevant){
        header('Location: /connectere?query='.urlencode($query).'&search=rel'); exit;
    }

    /* render page when: no relevant OR user clicked through */
    if(!$relevant || !$isRel){
        incrementPageCount($queryCI);
        if(!is_body_empty($contents)){
            header('Content-Type: text/html; charset=UTF-8');
            echo $contents; exit;
        }
        if(($canon=extract_canonical($contents))){
            header("Location: $canon"); exit;
        }
        http_response_code(404); exit('No valid page found.');
    }
}

/* =================================================
   BUILD RESULTS
   =================================================*/
$results=[];

/* -- 1. Relevant-mode results --------------------*/
if($isRel && $query!=='' && file_exists($exactPath)){
    /* self result first */
    $selfContents=file_get_contents($exactPath);
    $selfTitle   = preg_match('/<title>(.*?)<\/title>/is',$selfContents,$m)?trim($m[1]):$query;
    $selfCanon   = extract_canonical($selfContents) ?: "https://$query";
    $selfSvg     = preg_match('/<svg[^>]*data-image=["\']logo["\'][^>]*>.*?<\/svg>/is',$selfContents,$sm)?$sm[0]:null;
    $selfMeta    = [
        'application-name'=>extract_meta($selfContents,'application-name'),
        'author'          =>extract_meta($selfContents,'author'),
        'description'     =>extract_meta($selfContents,'description'),
        'generator'       =>extract_meta($selfContents,'generator'),
        'keywords'        =>extract_meta($selfContents,'keywords'),
        'viewport'        =>extract_meta($selfContents,'viewport'),
        'refresh'         =>extract_http_equiv($selfContents,'refresh')
    ];
    $results[]=[
        'filename'=>$query,
        'canonical'=>$selfCanon,
        'title'=>$selfTitle,
        'image'=>$selfSvg,
        'meta'=>$selfMeta,
        'score'=>PHP_INT_MAX /* pin to top */
    ];

    /* relevant domains thereafter */
    foreach($relevant as $dom){
        $path="$baseDir/$dom.html";
        if(file_exists($path)){
            $c=file_get_contents($path);
            $title=preg_match('/<title>(.*?)<\/title>/is',$c,$m)?trim($m[1]):$dom;
            $canon=extract_canonical($c) ?: "https://$dom";
            $svg  =preg_match('/<svg[^>]*data-image=["\']logo["\'][^>]*>.*?<\/svg>/is',$c,$sm)?$sm[0]:null;
            $meta=[
                'application-name'=>extract_meta($c,'application-name'),
                'author'          =>extract_meta($c,'author'),
                'description'     =>extract_meta($c,'description'),
                'generator'       =>extract_meta($c,'generator'),
                'keywords'        =>extract_meta($c,'keywords'),
                'viewport'        =>extract_meta($c,'viewport'),
                'refresh'         =>extract_http_equiv($c,'refresh')
            ];
            $results[]=[
                'filename'=>$dom,
                'canonical'=>$canon,
                'title'=>$title,
                'image'=>$svg,
                'meta'=>$meta,
                'score'=>0
            ];
        }else{
            $results[]=[
                'filename'=>$dom,
                'canonical'=>"https://$dom",
                'title'=>$dom,
                'image'=>null,
                'meta'=>[],
                'score'=>0
            ];
        }
    }
}
/* -- 2. Standard search --------------------------*/
if(!$isRel){
    foreach($htmlFiles as $filePath){
        $filename = basename($filePath,'.html');
        $contents = file_get_contents($filePath);

        $title = preg_match('/<title>(.*?)<\/title>/is',$contents,$m)?trim($m[1]):'';
        $searchable = strtolower($filename.' '.$title.' '.implode(' ',[
            extract_meta($contents,'application-name'),
            extract_meta($contents,'author'),
            extract_meta($contents,'description'),
            extract_meta($contents,'generator'),
            extract_meta($contents,'keywords')
        ]));

        if($query==='' || strpos($searchable,$queryCI)!==false){
            $results[]=[
                'filename'=>$filename,
                'canonical'=>extract_canonical($contents),
                'title'=>$title ?: $filename,
                'image'=>preg_match('/<svg[^>]*data-image=["\']logo["\'][^>]*>.*?<\/svg>/is',$contents,$sm)?$sm[0]:null,
                'meta'=>[
                    'application-name'=>extract_meta($contents,'application-name'),
                    'author'          =>extract_meta($contents,'author'),
                    'description'     =>extract_meta($contents,'description'),
                    'generator'       =>extract_meta($contents,'generator'),
                    'keywords'        =>extract_meta($contents,'keywords'),
                    'viewport'        =>extract_meta($contents,'viewport'),
                    'refresh'         =>extract_http_equiv($contents,'refresh')
                ],
                'score'=>substr_count($searchable,$queryCI)
            ];
        }
    }
}

/* sort unless scores are pinned (self result) */
usort($results,fn($a,$b)=>$b['score']<=>$a['score']);

$totalResults=count($results);
$totalPages = max(1, ceil($totalResults/$perPage));
$offset     = ($page-1)*$perPage;
$paginatedResults=array_slice($results,$offset,$perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>W / <?=htmlspecialchars($query)?></title>
<link rel="stylesheet" href="/style.css">
<meta name="theme-color" content="#000000">
</head>
<body>
<nav id="navigate">
 <a href="/" title="who we are: WithA"><h1>W/A</h1></a>
 <a href="/instantiate"></a>
 <a href="/truth"></a>
 <a href="/raw" title="where to get started: Here" style="text-align:right">
  <?= $img ? '<img src="'.htmlspecialchars($img).'" style="width:27.5px;height:27.5px;border-radius:50%;object-fit:cover" alt="record">' : '&#174;' ?>
 </a>
</nav>

<section id="beginning" class="contained">
<header>
 <form id="search" action="/connectere" method="get">
  <input type="search" name="query" placeholder="Find a connection to start." value="<?=htmlspecialchars($query)?>">
  <input type="submit" value="&nbsp;&nbsp;">
 </form>
</header>

<main class="z1">
<div class="search">
<?php if($totalResults===0): ?>
    <p>No connections found.</p>
<?php else: ?>
    <?php foreach($paginatedResults as $r):
        $href = $r['canonical'] ?? null;
        $file = "$baseDir/{$r['filename']}.html";
        if(file_exists($file) && !is_body_empty(file_get_contents($file))){
            $href = '/connectere?query='.urlencode($r['filename']);
        }
        $m = $r['meta'];
    ?>
    <?php if($href): ?>
    <div class="search-result">
     <div class="linked">
      <a class="z2 pr block w100" href="<?=htmlspecialchars($href)?>">
        <?php if($r['image']): ?><span class="imagery"><?=$r['image']?></span><?php endif; ?>
        <span class="material">
          <p><?=htmlspecialchars($r['title'])?> <span class="rlink"><?=htmlspecialchars($r['canonical'] ?? $r['filename'])?></span></p>
          <p><?=htmlspecialchars($m['description'] ?? '')?></p>
          <p>
            <?php $sep=false;
              foreach(['keywords','refresh','author','application-name','generator'] as $k):
                 if(empty($m[$k])) continue;
                 if($sep) echo ' &#183; ';
                 echo $k==='refresh' ? '&#9415; '.htmlspecialchars($m[$k]).'seconds' : htmlspecialchars($m[$k]);
                 $sep=true;
              endforeach;
            ?>
          </p>
        </span>
      </a>
     </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <?php if($totalPages>1): ?>
    <div class="pagination">
        <?php for($i=1;$i<=$totalPages;$i++):
            $link='/connectere?query='.urlencode($query).'&page='.$i.($isRel?'&search=rel':''); ?>
            <a href="<?=$link?>"<?= $i===$page?' style="font-weight:bold;text-decoration:underline;"':'' ?>><?=$i?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
</div>
</main>
</section>
</body>
</html>
