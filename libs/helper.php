<?php
class helper extends engine {

	public function json($v) {
        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
	
	
	public function cleanSlash($v) {
		return substr($v, -1, 1) == '/' ? substr($v, 0, -1) : $v;
    }
	
	function array_filter_empty(&$array) {
		foreach( $array as $key => $item ) {
			is_array($item) && $array[$key] = $this->array_filter_empty($item);
			if( empty($array[$key]) ) unset($array[$key]);
		}
		return $array;
	}
	
	public function remove_double_space($str) {
		return preg_replace('/[\s]{2,}/', ' ', $str);
	}
	
	public function remove_double_br($str) {
		return preg_replace('/[<br(.*?)>]{2,}/', '<br\1>', $str);
	}
	
	public function gen_code($length = 6, $char = false) {
		$chars = $char ? $char : 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789';
		$code = '';
		$clen = strlen($chars) - 1;
		while( $i++ < $length ) $code .= $chars[mt_rand(0, $clen)];
		return $code;
	}
	
	// get_class_methods(self::class)
}