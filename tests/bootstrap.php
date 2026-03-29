<?php

$compiled = sys_get_temp_dir().'/eclick-demo-view-cache';
if (! is_dir($compiled)) {
    mkdir($compiled, 0777, true);
}

putenv('VIEW_COMPILED_PATH='.$compiled);
$_ENV['VIEW_COMPILED_PATH'] = $compiled;
$_SERVER['VIEW_COMPILED_PATH'] = $compiled;

require dirname(__DIR__).'/vendor/autoload.php';
