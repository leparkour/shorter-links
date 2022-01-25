<?php
class requests extends engine {
	
	public function getIp() {
		$ip = $_SERVER['REMOTE_ADDR'];

		$tmpIp = explode(',', $ip);
		if( 1 < count($tmpIp) ) $ip = trim($tmpIp[0]);
		
		if( $ip = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ) {
			return $ip;
		} elseif( $ip = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ) {
			return $ip;
		}
		return 'localhost';
    }
	
	public function siteUrl() {
        $protocol = ( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ) ? 'https://' : 'http://';
        $domainName = $_SERVER['HTTP_HOST'];
        return $protocol.$domainName;
    }
	public function cleanUrl($url, $no_d = false) {
        if( $url == '' ) return;

        $url = str_replace(['http://', 'https://'], '', mb_strtolower($url));
        if( substr($url, 0, 2) == '//' ) $url = str_replace('//', '', $url);
        if( substr($url, 0, 4) == 'www.' ) $url = substr($url, 4);
        $url = explode('/', $url);

        if( $no_d ) {
            unset($url[0]);
            return implode('/', $url);
        }

        $url = reset($url);
        $url = explode(':', $url);
        $url = reset($url);

        return $url;
    }
	
	function initAjax() {
        header('Content-Type: application/json; charset=UTF-8');
    }

    function isAjax() {
		return ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' );
    }
	
	function isHttps() {
       return ( ( !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ) || ( !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) || ( !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) == 'on' ) || ( isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443 ) || ( isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443 ) || ( isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https' ) || ( isset($_SERVER['CF_VISITOR']) && $_SERVER['CF_VISITOR'] == '{"scheme":"https"}' ) || ( isset($_SERVER['HTTP_CF_VISITOR']) && $_SERVER['HTTP_CF_VISITOR'] == '{"scheme":"https"}' ) );
    }

    public function redirectHttps() {
        $hasRedirect = false;
        if( isset($_SERVER['SCRIPT_URI']) && preg_match('#^(https?:\/\/)(www)\.(.*?)\/?$#Uis', $_SERVER['SCRIPT_URI'], $matchUri) ) {
            $hasRedirect = ( !$this->isHttps() ? 'https://' : $matchUri[1] ).$matchUri[3];
        } elseif( !$this->isHttps() ) {
            $_SERVER['REQUEST_URI'] = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'utf-8');
            $hasRedirect = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        if($hasRedirect) {
            header('HTTP/1.0 301 Moved Permanently');
            header('Location: '.$hasRedirect);
            die('Redirect');
        }
    }
	
	function getContent($file, $post_params = false, $curlclose = true) {
        $data = false;
        if( stripos($file, 'http://') !== 0 AND stripos($file, 'https://') !== 0 ) {
            return false;
        }
        if( function_exists('curl_init') ) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $file);
            if( is_array($post_params) ) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_params));
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $data = curl_exec($ch);

            if( $curlclose ) curl_close($ch);
            if( $data !== false ) return $data;
        }
        if( preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen')) ) {
            if( is_array($post_params) ) {
                $file .= '?' . http_build_query($post_params);
            }
            $data = @file_get_contents($file);
            if ($data !== false) return $data;
        }
        return false;
    }

	function getHeader($url) {
        $headers = @get_headers($url);
        return $headers;
    }
	
	function isValidURL($url) {
        return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
    }
	
}