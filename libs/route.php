<?php
class route extends engine {
	
	var $routes = [];

    public function __construct() {
        parent::__construct();
        $this->get();
    }

    public function get($patch = false) {
		$getRoute = trim($patch ?: parse_url(urldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH), '/');
        $getRoute = array_filter(explode('/', $getRoute));

        foreach( $getRoute as $key => $val ) {
            if( trim($val) == '' ) continue;
            $this->routes[$key] = htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'utf-8');
        }

		return $this->routes;
	}
	
	function __destruct() {
		return;
	}

}