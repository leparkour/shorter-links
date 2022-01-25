<?php
defined('TheEnd') || die('Oops, has error!');

http_response_code(404);
header('HTTP/1.0 404 Not Found');

$this->setTemplate('404')->setTitle('404', 1);