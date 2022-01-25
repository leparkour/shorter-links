<?php

class short extends engine {
	public function __construct() {
		parent::__construct();
	}
	
	private function gen_key($length = 4) {
		$token = substr(md5(uniqid(rand(), true)), 0, $length);
		return $token;
	}
	
	public function create($link) {
		$exe = $this->db->execute('SELECT id, uniq_id FROM links WHERE link = ? LIMIT 1', $link);
		$select = $this->db->super_query($exe);
		if( !isset($select['id']) ) {
			$id = $this->_create($link);
		} else {
			$id = $select['uniq_id'];
		}
		return $this->cfg->host['url'].'/s/'.$id;
	}
	
	public function get($id) {
		if( empty($id) ) return!1;
		$exe = $this->db->execute('SELECT id, link FROM links WHERE uniq_id = ?', $id);
		$select = $this->db->super_query($exe);
		if( isset($select['id']) ) {
			$this->db->query('UPDATE links SET open = open+1 WHERE id = '.$select['id']);
			return $select['link'];
		}
		return!1;
	}
	
	private function _create($link) {
		$id = $this->_gen_id();
		$exe = $this->db->execute('INSERT INTO links (uniq_id, link, time) VALUE (?,?,?)', $id, $link, $this->cfg->time);
		$this->db->query($exe);
		return $id;
	}
	
	private function _gen_id() {
		$id = $this->gen_key();
		$exe = $this->db->execute('SELECT id FROM links WHERE uniq_id = ? LIMIT 1', $id);
		$select = $this->db->super_query($exe);
		if( isset($select['id']) ) {
			return $this->_gen_id();
		} else {
			return $id;
		}
	}
}