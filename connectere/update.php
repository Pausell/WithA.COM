<?php
// run:  php update.php     — or hit it in the browser once after new uploads
$baseDir   = __DIR__;
$htmlFiles = glob($baseDir . '/*.html');
$index     = [];

foreach ($htmlFiles as $filePath) {
    $filename = basename($filePath, '.html');
    $html     = file_get_contents($filePath);

    // title
    preg_match('/<title>(.*?)<\/title>/is', $html, $m);
    $title = isset($m[1]) ? trim($m[1]) : $filename;

    // canonical
    preg_match('/<link\s+rel=["\']canonical["\']\s+href=["\'](.*?)["\']\s*\/?>/i', $html, $m);
    $canonical = $m[1] ?? null;

    // simple meta (keywords & description only; add more if you need)
    preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\'](.*?)["\']\s*\/?>/i', $html, $km);
    preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']\s*\/?>/i', $html, $dm);
    $keywords    = $km[1] ?? '';
    $description = $dm[1] ?? '';

    // body‑empty flag
    preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $bm);
    $bodyEmpty = !isset($bm[1]) || strlen(trim(strip_tags(preg_replace('/<!--.*?-->/s','',$bm[1])))) === 0;

    // store a lean record
    $index[] = [
        'filename'     => $filename,
        'title'        => $title,
        'canonical'    => $canonical,
        'keywords'     => $keywords,
        'description'  => $description,
        'bodyEmpty'    => $bodyEmpty,
        // one lowercase blob used for matching
        'searchable'   => strtolower("$filename $title $keywords $description")
    ];
}

file_put_contents($baseDir.'/search-index.json', json_encode($index, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo "Indexed ".count($index)." HTML files\n";