<?php
class cfg extends engine {
    private $vars = [];
    private $def = [];

    function __construct() {
		$this->def = $this->_default();
	}

    public function __get($name) {
		return $this->vars[$name] ?? null;
	}

    public function __set($name, $value) {
		if( !in_array($name, $this->def) )
			$this->vars[$name] = $value;
	}

    public function __unset($name) {
		if( isset($this->vars[$name]) && !in_array($name, $this->def) ) {
			unset($this->vars[$name]);
		}
	}

    private function get($name) {
		return parent::__get($name);
	}

    private function _default() {
		$requests = $this->get('requests');
		$device = $this->get('isdevice');

		$this->vars['route'] = $this->get('route')->routes;
		$this->vars['server_name'] = $requests->cleanUrl( $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] );
		$this->vars['host'] = [
			'server' => $this->vars['server_name'],
			'url' => $requests->siteUrl(),
			'uri' => $_SERVER['REQUEST_URI'],
			'route' => $this->vars['route'],
			'str' => '/'.implode('/', $this->vars['route']),
			'array' => explode('.', $this->vars['server_name']),
			'allow_count' => -2,
		];
		
		$this->vars['host']['count'] = count($this->vars['host']['array']);
		
		if( $this->vars['host']['count'] > 2 && ( in_array($this->vars['host']['array'][$this->vars['host']['count']-2], ['com', 'net', 'org']) || $this->vars['host']['array'][$this->vars['host']['count']-1] == 'ua' ) ) {
			$this->vars['host']['allow_count'] = -3;
		}
		
		$this->vars['host']['get'] = array_slice($this->vars['host']['array'], 0, $this->vars['host']['allow_count']);
		$this->vars['host']['main'] = implode('.', ( is_array($this->vars['host']['get']) && count($this->vars['host']['get']) > 0 ? $this->vars['host']['get'] : ['www'] ));
		$this->vars['host']['home'] = implode('.', array_slice($this->vars['host']['array'], $this->vars['host']['allow_count']));
		
		$this->vars['referer'] = $_SERVER['HTTP_REFERER'] ?? '';

		$this->vars['ip'] = $requests->getIp();
		
		$this->vars['time'] = $_SERVER['REQUEST_TIME'] ?? time();
        $this->vars['date']['start'] = $this->vars['time'] - ( $this->vars['time'] % 86400 );
        if( $this->vars['date']['start'] !== ( $this->vars['times']['start'] = strtotime('now 00:00:00') ) );
        $this->vars['date']['end'] = $this->vars['time'] + 86400;
        $this->vars['date']['month'] = date('Y-m');
        $this->vars['date']['days'] = date('t');
        $this->vars['date']['month_start'] = strtotime($this->vars['date']['month'].'-1 00:00');
        $this->vars['date']['month_end'] = strtotime($this->vars['date']['month'].'-'.$this->vars['date']['days']);

		return array_keys($this->vars);
	}
}