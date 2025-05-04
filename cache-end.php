<?php
$final = ob_get_clean();
$mc->set($cacheKey, $final, 86400);
echo $final;
?>