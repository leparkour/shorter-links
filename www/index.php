<?php
ob_start();
ob_implicit_flush(0);

define('TheEnd', 1);

define('ROOT_DIR', dirname(__FILE__));
define('REAL_PATH', realpath(ROOT_DIR.'/..'));
define('CONFIG_DIR', REAL_PATH.'/config');
define('LIB_DIR', REAL_PATH.'/libs');
define('MODE_DIR', ROOT_DIR.'/modes');
define('AJAX_DIR', ROOT_DIR.'/ajax');

include_once CONFIG_DIR.'/config.php';

$engine->tpl->compile();