<?php
defined('TheEnd') || die('Oops, has error!');

ini_set('memory_limit', '2048M');
header('Content-type: text/html; charset=utf-8');

include_once 'db.php';
include_once LIB_DIR.'/autoLoader.php';

$engine = new engine;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('html_errors', 0);

ini_set('log_errors', 'On');
ini_set('error_log', ROOT_DIR.'/error.log');

ini_set('error_reporting', E_ALL);
error_reporting(E_ALL);

$engine->Sessions;