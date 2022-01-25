<?php
defined('TheEnd') || die('Oops, has error!');

if( !empty($_POST['link']) ) {
	if( !$this->requests->isValidURL($_POST['link']) ) return $this->ajax['msg'] = 'Ссылка указана не верно!';
	
	$link = $this->short->create($_POST['link']);
	
	$response = [
		'link' => $link,
	];
	
	$this->ajax = ['status' => 'success', 'msg' => 'Успешно создано', 'response' => $response];
} else $this->ajax['msg'] = 'Укажите линк.';