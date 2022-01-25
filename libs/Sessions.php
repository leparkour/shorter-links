<?php
defined('TheEnd') || die('Oops, has error!');

class Sessions extends engine {
	protected $lifetime = 30;
	protected $cookieName = 'uid';
	public $id;
	public $user_agent;
	protected $is_bot;
	protected $bots = [
		'yandex', 'rank', 'google', 'bot', 'PHP', 'hosted', 'validator', 'facebook', 'http-client', 'FBAN', 'vkShare', 'Skype', 'WhatsApp', 'Accoona', 'ia_archiver', 'Ask Jeeves',
		'Yahoo!', 'Ezooms', 'SiteStatus', 'Nigma.ru', 'Baiduspider', 'SISTRIX', 'findlinks', 'proximic', 'OpenindexSpider', 'statdom.ru', 'Spider', 'Snoopy', 'heritrix', 'Yeti',
		'DomainVader', 'StackRambler', 'Lighthouse', 'WebAlta', 'YahooFeedSeeker', 'GTmetrix', 'Structured-Data-Linter', 'PR-CY.RU', 'crawler'
	];
	
	function __construct() {
		$this->setCookie('is_bot', 1, 30);
		if( !empty($_COOKIE[$this->cookieName]) ) {
			if( !preg_match('/^[a-zA-Z0-9,-]{22,40}$/', $_COOKIE[$this->cookieName]) ) unset($_COOKIE[$this->cookieName]);
			else $this->id = $_COOKIE[$this->cookieName];
		}
		$params = session_get_cookie_params();
		
		if( $this->requests->isHttps() ) $params['secure'] = true;
        session_set_cookie_params($params['lifetime'], '/', $params['domain'], $params['secure'], true);
		session_name($this->cookieName);
		session_start();

		$this->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

		$this->id = $this->id ?? session_id();

		$this->is_bot = $this->check_bot();
		
		if( !$this->is_bot ) {
			$this->setCookie($this->cookieName, $this->id, 30);
		}
	}
	
	public function set($name, $value) {
		if( is_array($value) && !empty($value) ) {
			foreach( $value AS $key => $v ) $_SESSION[$name][$key] = $v;
		} else {
			$_SESSION[$name] = $value;
		}
	}
	
	public function get($name = false, $key = false) {
		if( $name && $key ) {
			return $_SESSION[$name][$key] ?? false;
		} elseif( $name && !$key ) {
			return $_SESSION[$name] ?? false;
		} else {
			return $_SESSION;
		}
	}
	
	public function del($name, $key = false) {
		if( $key ) unset($_SESSION[$name][$key]);
		else unset($_SESSION[$name]);
	}
	
	protected function check_bot() {
		if( empty($this->user_agent) ) return !0;
		
		foreach( $this->bots as $bot )
			if( stripos($this->user_agent, $bot) !== false ) return !0;

		if( !empty($_COOKIE['is_bot']) ) return !1;
		
		$user_agents = ['Mozilla', 'Firefox', 'Chrome', 'Opera', 'Safari', 'Macintosh', 'Linux', 'Unix', 'FreeBSD'];
		foreach( $user_agents as $ua )
			if( stripos($this->user_agent, $ua) !== false ) return !1;

		return !0;
	}
	
	public function setCookie($name, $value = '', $expires = false, $httponly = true) {
		$expires = $expires ? time() + ($expires * 86400) : false;
        if( $this->requests->isHttps() ) setcookie($name, $value, $expires, '/', '', true, $httponly);
			else setcookie($name, $value, $expires, '/', '', null, $httponly);
	}
	
	/* function setCookie($name, $value = '', $expires = false, $patch = '/', $domain = false, $httponly = true) {
        if( $expires ) $expires = time() + ($expires * 86400);
			else $expires = FALSE;
			
        if( $domain === false ) $domain = domain_site;
		
		setcookie($name, $value, $expires, $patch, $domain, NULL, $httponly);
    } */
	
}