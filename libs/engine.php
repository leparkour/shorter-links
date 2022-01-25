<?php
class engine {
    private $args = [];
    private $disabled = [
		'Autoloader',
	];
    private static $objects = [];

    public function __construct() {
		;
	}

    public function __destruct() {
		;
	}

    public function __set($name, $val) {
		$this->args[$name] = $val;
	}

    public function __get($name) {
		if( !class_exists($name) ) return;
		
		if( isset(self::$objects[$name]) ) {
			return(self::$objects[$name]);
		}
		
		if( in_array($name, $this->disabled) ) return null;
		
		if( isset($this->args[$name]) && is_array($this->args[$name]) ) self::$objects[$name] = new $name(...$this->args[$name]);
		else self::$objects[$name] = new $name($this->args[$name]??null);
		
		return self::$objects[$name];
	}
}