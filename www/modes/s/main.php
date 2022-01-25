<?php
defined('TheEnd') || die('Oops, has error!');

$select = $this->short->get($this->cfg->route[1]);
if( $select ) {
	header("HTTP/1.0 301 Moved Permanently");
	header('Location: '.$select);
	die('Redirect');
} else $this->setTemplate('404');