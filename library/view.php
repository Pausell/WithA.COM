<?php
function viewPage(string $file, array $context = []): void
{
    extract($ctx, EXTR_SKIP);               // $context['key'] → $key
    require __DIR__.'/../view/page/'.$file;
}

function part(string $file, array $context = []): void
{
    extract($ctx, EXTR_SKIP);
    require __DIR__.'/../view/part/'.$file;
}
