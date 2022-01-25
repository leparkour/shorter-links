<?php
/*
	$exe = $this->execute('SELECT id FROM users WHERE id = ?', 1);
	$this->query($exe);
	
	define('CONSTANTS', 1);
	$exe = $this->execute('SELECT id FROM users WHERE id = ?#CONSTANTS', 1);
	$exe = $this->execute('SELECT id FROM users WHERE ?%', ['id' => 1]);
	
	$engine->db->execute('UPDATE users SET ?% WHERE id IN(?@)', ['name' => 'name'], [1,2,3]);
	
	$query = 'WHERE id = 11';
	$engine->db->execute('SELECT id FROM users ?:', $query);
*/

class db extends engine {
	var $db_id = false;
	var $query_num = 0;
	var $query_list = array();
	var $query_errors_list = array();
	var $mysql_error = '';
	var $mysql_version = '';
	var $mysql_error_num = 0;
	var $mysql_extend = '';
	var $MySQL_time_taken = 0;
	var $query_id = false;
	var $show_error = true;
	
	function __destruct() {
		$this->close();
	}

	function connect($db_user, $db_pass, $db_name, $db_location = 'localhost') {
		$db_location = explode(":", $db_location);
		
		$time_before = $this->get_real_time();

		if( isset($db_location[1]) ) {
			$this->db_id = @mysqli_connect($db_location[0], $db_user, $db_pass, $db_name, $db_location[1]);
		} else {
			$this->db_id = @mysqli_connect($db_location[0], $db_user, $db_pass, $db_name);
		}
		
		$this->query_list[] = [
			'time' => ($this->get_real_time() - $time_before),
			'query' => 'Connection with MySQL Server',
			'num' => 0
		];
		
		if( !$this->db_id ) {
			if( $this->show_error ) {
				$this->display_error(mysqli_connect_error(), '1');
			}
			$this->query_errors_list[] = ['error' => mysqli_connect_error()];
			return false;
		}

		$this->mysql_version = mysqli_get_server_info($this->db_id);
		if( version_compare($this->mysql_version, '5.5.3', '<') ) {
			throw new Exception('Required MySQL version 5.5.3 or greater. You need upgrade MySQL version on your server.');
		}

		mysqli_set_charset($this->db_id , db_coll);
		
		mysqli_query($this->db_id, 'SET NAMES '.db_coll);
		
		$this->sql_mode();

		return true;
	}
	
	public function execute() {
		$args = func_get_args();
		$tmpl = array_shift($args);
		if( !empty($args) ) {
			$result = $this->sql_execute($tmpl, $args, $error);
			if( $result === false ) {
				throw new ErrorException('Execute sql error: "'.$error.'"');
				return false;
			}
			return $result;
		} else return $tmpl;
	}
	
	private function sql_compile($tmpl) {
		$compiled = [];
		$p = $i = 0;
		$has_named = false;
		while( false !== ( $start = $p = strpos($tmpl, '?', $p) ) ){
			switch( $c = substr($tmpl, ++$p, 1) ) {
				case '%':
				case '@':
				case '#':
				case ':':
					$type = $c;
					++$p;
				break;
				default:
					$type = '';
				break;
			}
			if( preg_match('/^((?:[^\s[:punct:]]|_)+)/', substr($tmpl, $p), $pock) ) {
				$key = $pock[1];
				if( $type != '#' ) $has_named = true;
				$p += strlen($key);
			} else {
				$key = $i;
				if( $type != '#' ) $i++;
			}
			$compiled[] = array($key, $type, $start, $p - $start);
		}
		return array($compiled, $tmpl, $has_named);
	}
	
	private function sql_execute($tmpl, $args, &$errormsg) {
		if( is_array($tmpl) ) $compiled = $tmpl;
			else $compiled = $this->sql_compile($tmpl);

		list($compiled, $tmpl, $has_named) = $compiled;

		if( $has_named ) $args = @$args[0];

		$p = 0;
		$out = '';
		$error = false;

		foreach( $compiled as $num => $e ) {
			list($key, $type, $start, $length) = $e;
			$out .= substr($tmpl, $p, $start - $p);
			$p = $start + $length;

			$repl = '';
			$errmsg = '';
			do {
				if( $type === '#' ) {
					$repl = @constant($key);
					if( NULL === $repl ) $error = $errmsg = 'UNKNOWN_CONSTANT_'.$key;
					break;
				}
				if( !isset($args[$key]) ) {
					$error = $errmsg = 'UNKNOWN_KEY_'.$key;
					break;
				}
				$a = $args[$key];
				if( $type === '' ) {
					if( is_array($a) ) {
						$error = $errmsg = 'NOT_A_SCALAR_KEY_'.$key;
						break;
					}
					$repl = is_int($a) || is_float($a) ? str_replace(',', '.', $a) : "'".addslashes($a)."'";
					break;
				} elseif( ':' == $type ) {
					if( is_array($a) ) {
						$error = $errmsg = 'NOT_A_SCALAR_KEY_'.$key;
						break;
					}
					$repl = $a;
					break;
				}
				
				if( is_object($a) ) $a = get_object_vars($a);
				
				if( !is_array($a) ) {
					$error = $errmsg = 'NOT_AN_ARRAY_KEY_'.$key;
					break;
				}
				if( $type === '@' ) {
					foreach( $a as $v ) {
						if( is_null($v) ) $r = 'NULL';
							else $r = "'".@addslashes($v)."'";

						$repl .= ( $repl === '' ? '' : ',').$r;
					}
				} elseif( $type === '%' ) {
					$lerror = array();
					foreach( $a as $k => $v ) {
						if( !is_string($k) ) $lerror[$k] = 'NOT_A_STRING_KEY_'.$k.'_FOR_KEY_'.$key;
							else $k = preg_replace('/[^a-zA-Z0-9_]/', '_', $k);

						if( is_null($v) ) $r = '=NULL';
							else $r = "='".@addslashes($v)."'";

						$repl .= ( $repl === '' ? '' : ', ').$k.$r;
					}
					if( count($lerror) ) {
						$repl = '';
						foreach( $a as $k => $v ) {
							if( isset($lerror[$k]) ) {
								$repl .= ( $repl === '' ? '' : ', ').$lerror[$k];
							} else {
								$k = preg_replace('/[^a-zA-Z0-9_-]/', '_', $k);
								$repl .= ( $repl === '' ? '' : ', ').$k.'=?';
							}
						}
						$error = $errmsg = $repl;
					}
				}
			} while (false);
			if( $errmsg ) $compiled[$num]['error'] = $errmsg;
			if( !$error ) $out .= $repl;
		}
		$out .= substr($tmpl, $p);

		if( $error ) {
			$out = '';
			$p = 0;
			foreach( $compiled as $num => $e ) {
				list($key, $type, $start, $length) = $e;
				$out .= substr($tmpl, $p, $start - $p);
				$p = $start + $length;
				if( isset($e['error']) ) {
					$out .= $e['error'];
				} else {
					$out .= substr($tmpl, $start, $length);
				}
			}
			$out .= substr($tmpl, $p);
			$errormsg = $out;
			return false;
		} else {
			$errormsg = false;
			return $out;
		}
	}
	
	function query($query) {
		$time_before = $this->get_real_time();

		if( !$this->db_id ) $this->connect(db_user, db_pass, db_name, db_host);

		if( !($this->query_id = mysqli_query($this->db_id, $query)) ) {

			$this->mysql_error = mysqli_error($this->db_id);
			$this->mysql_error_num = mysqli_errno($this->db_id);
			
			if( $this->show_error ) {
				$this->display_error($this->mysql_error, $this->mysql_error_num, $query);
			}
			$this->query_errors_list[] = ['query' => $query, 'error' => $this->mysql_error];
		}
			
		$this->MySQL_time_taken += $this->get_real_time() - $time_before;
		
		$this->query_list[] = [
			'time' => ($this->get_real_time() - $time_before), 
			'query' => $query,
			'num' => count($this->query_list)
		];
		
		$this->query_num++;

		return $this->query_id;
	}
	
	function multi_query($query) {
		$time_before = $this->get_real_time();

		if(!$this->db_id) $this->connect(db_user, db_pass, db_name, db_host);
		
		if( mysqli_multi_query($this->db_id, $query) ) {
			while(mysqli_more_results($this->db_id) && mysqli_next_result($this->db_id)) {
				;
			}
		}
		
		if( mysqli_error($this->db_id) ) {
			$this->mysql_error = mysqli_error($this->db_id);
			$this->mysql_error_num = mysqli_errno($this->db_id);
			
			if( $this->show_error ) {
				$this->display_error($this->mysql_error, $this->mysql_error_num, $query);
			}
			$this->query_errors_list[] = array('query' => $query, 'error' => $this->mysql_error);
		}
		
		$this->query_list[] = array(
			'time' => ($this->get_real_time() - $time_before),
			'query' => $query,
			'num' => count($this->query_list)
		);
		
		$this->MySQL_time_taken += $this->get_real_time() - $time_before;
		
		$this->query_num++;
	}
	
	function get_row($query_id = '') {
		if( $query_id == '' ) $query_id = $this->query_id;

		return mysqli_fetch_assoc($query_id);
	}

	function get_affected_rows() {
		return mysqli_affected_rows($this->db_id);
	}

	function get_array($query_id = '') {
		if( $query_id == '' ) $query_id = $this->query_id;

		return mysqli_fetch_array($query_id);
	}
	
	function super_query($query, $multi = false) {
		if( !$multi ) {

			$this->query($query);
			$data = $this->get_row();
			$this->free();			
			return $data;

		} else {
			$this->query($query);
			
			$rows = [];
			while($row = $this->get_row()) {
				$rows[] = $row;
			}

			$this->free();			

			return $rows;
		}
	}
	
	function num_rows($query_id = '') {
		if( $query_id == '' ) $query_id = $this->query_id;

		return mysqli_num_rows($query_id);
	}
	
	function insert_id() {
		return mysqli_insert_id($this->db_id);
	}

	function get_result_fields($query_id = '') {

		if( $query_id == '' ) $query_id = $this->query_id;

		while($field = mysqli_fetch_field($query_id)) {
            $fields[] = $field;
		}
		
		return $fields;
   	}

	function safesql($source) {
		if( !$this->db_id ) $this->connect(db_user, db_pass, db_name, db_host);

		if( $this->db_id ) return mysqli_real_escape_string($this->db_id, $source);
		else return addslashes($source);
	}

	function free($query_id = '') {
		if( $query_id == '' ) $query_id = $this->query_id;

		if( $query_id ) {
			mysqli_free_result($query_id);
			$this->query_id = false;
		}
	}

	function close() {
		if( $this->db_id ) mysqli_close($this->db_id);
		$this->db_id = false;
	}

	function get_real_time() {
		list($seconds, $microSeconds) = explode(' ', microtime());
		return ((float)$seconds + (float)$microSeconds);
	}
	
	function sql_mode() {
		$remove_modes = array('STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'ONLY_FULL_GROUP_BY', 'NO_ZERO_DATE', 'NO_ZERO_IN_DATE', 'TRADITIONAL');
		
		$res = $this->query("SELECT @@SESSION.sql_mode");
		$this->query_num--;
		$row = $this->get_array();
		
		if( !$row[0] ) return;
		
		$modes_array = explode(',', $row[0]);
		$modes_array = array_change_key_case($modes_array, CASE_UPPER);

		foreach($modes_array as $key => $value) {
			if( in_array($value, $remove_modes) ) unset($modes_array[$key]);
		}
		
		$mode_list = implode(',', $modes_array);

		if( $row[0] != $mode_list ) {
			$this->query("SET SESSION sql_mode='{$mode_list}'");
			$this->query_num--;
		}
	}
	
	function display_error($error, $error_num, $query = '') {
		$query = htmlspecialchars($query, ENT_QUOTES, 'ISO-8859-1');

		$error = preg_replace('#\'(.*?)\'#i', '\'<strong>\1</strong>\'', $error);
		$error = htmlspecialchars($error, ENT_QUOTES, 'ISO-8859-1');
		
		$error = str_replace(['&lt;/strong&gt;', '&lt;strong&gt;'], ['</strong>', '<strong style="color: red;">'], $error);

		$trace = debug_backtrace();

		$level = 0;
		if($trace[1]['function'] == 'query') $level = 1;
		if($trace[2]['function'] == 'super_query') $level = 2;

		$trace[$level]['file'] = str_replace(ROOT_DIR, '', $trace[$level]['file']);
		
		if( ob_get_contents() ) ob_end_clean();
		// ob_get_clean();
		header('Content-Type: text/html; charset=utf-8');
		echo <<<HTML
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<meta name="robots" content="noindex, nofollow" />
		<meta name="viewport" content="width=device-width,initial-scale=1" />
		<title>MySQL Error!</title>
		<style>html{font-family:sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}body{margin:0}article,aside,details,figcaption,figure,footer,header,hgroup,main,menu,nav,section,summary{display:block}audio,canvas,progress,video{display:inline-block;vertical-align:baseline}audio:not([controls]){display:none;height:0}[hidden],template{display:none}a{background-color:transparent}a:active,a:hover{outline:0}abbr[title]{border-bottom:1px dotted}b,strong{font-weight:700}dfn{font-style:italic}h1{margin:.67em 0;font-size:2em}mark{color:#000;background:#ff0}small{font-size:80%}sub,sup{position:relative;font-size:75%;line-height:0;vertical-align:baseline}sup{top:-.5em}sub{bottom:-.25em}img{border:0}svg:not(:root){overflow:hidden}figure{margin:1em 40px}hr{height:0;-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box}pre{overflow:auto}code,kbd,pre,samp{font-family:monospace,monospace;font-size:1em}button,input,optgroup,select,textarea{margin:0;font:inherit;color:inherit}button{overflow:visible}button,select{text-transform:none}button,html input[type="button"],input[type="reset"],input[type="submit"]{-webkit-appearance:button;cursor:pointer}button[disabled],html input[disabled]{cursor:default}button::-moz-focus-inner,input::-moz-focus-inner{padding:0;border:0}input{line-height:normal}input[type="checkbox"],input[type="radio"]{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;padding:0}input[type="number"]::-webkit-inner-spin-button,input[type="number"]::-webkit-outer-spin-button{height:auto}input[type="search"]{-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box;-webkit-appearance:textfield}input[type="search"]::-webkit-search-cancel-button,input[type="search"]::-webkit-search-decoration{-webkit-appearance:none}fieldset{padding:.35em .625em .75em;margin:0 2px;border:1px solid silver}legend{padding:0;border:0}textarea{overflow:auto}optgroup{font-weight:700}table{border-spacing:0;border-collapse:collapse}td,th{padding:0}html {overflow-y: scroll;}body { background-color: #F9F9F9; color: #222; font: 14px/1.4 Helvetica, Arial, sans-serif; padding-bottom: 45px; }a { cursor: pointer; text-decoration: none; }a:hover { text-decoration: underline; }abbr[title] { border-bottom: none; cursor: help; text-decoration: none; }code, pre { font: 13px/1.5 Consolas, Monaco, Menlo, "Ubuntu Mono", "Liberation Mono", monospace; }table, tr, th, td { background: #FFF; border-collapse: collapse; vertical-align: top; }table { background: #FFF; border: 1px solid #E0E0E0; box-shadow: 0px 0px 1px rgba(128, 128, 128, .2); margin: 1em 0; width: 100%; }table th, table td { border: solid #E0E0E0; border-width: 1px 0; padding: 8px 10px; }table th { background-color: #E0E0E0; font-weight: bold; text-align: left; }.hidden-xs-down { display: none; }.block { display: block; }.hidden { display: none; }.nowrap { white-space: nowrap; }.newline { display: block; }.break-long-words { word-wrap: break-word; overflow-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; min-width: 0; }.text-small { font-size: 12px !important; }.text-muted { color: #999; }.text-bold { font-weight: bold; }.empty { border: 4px dashed #E0E0E0; color: #999; margin: 1em 0; padding: .5em 2em; }.status-success { background: rgba(94, 151, 110, 0.3); }.status-warning { background: rgba(240, 181, 24, 0.3); }.status-error { background: rgba(176, 65, 62, 0.2); }.status-success td, .status-warning td, .status-error td { background: transparent; }tr.status-error td, tr.status-warning td { border-bottom: 1px solid #FAFAFA; border-top: 1px solid #FAFAFA; }.status-warning .colored { color: #A46A1F; }.status-error .colored  { color: #B0413E; }.container { max-width: 1024px; margin: 0 auto; padding: 0 15px; }.container::after { content: ""; display: table; clear: both; }header { background-color: #222; color: rgba(255, 255, 255, 0.75); font-size: 13px; height: 33px; line-height: 33px; padding: 0; }header .container { display: flex; justify-content: space-between; }.logo { flex: 1; font-size: 13px; font-weight: normal; margin: 0; padding: 0; }.logo svg { height: 18px; width: 18px; opacity: .8; vertical-align: -5px; }.help-link { margin-left: 15px; }.help-link a { color: inherit; }.help-link .icon svg { height: 15px; width: 15px; opacity: .7; vertical-align: -2px; }.help-link a:hover { color: #EEE; text-decoration: none; }.help-link a:hover svg { opacity: .9; }.exception-summary { background: #B0413E; border-bottom: 2px solid rgba(0, 0, 0, 0.1); border-top: 1px solid rgba(0, 0, 0, .3); flex: 0 0 auto; margin-bottom: 15px; }.exception-metadata { background: rgba(0, 0, 0, 0.1); padding: 7px 0; }.exception-metadata .container { display: flex; flex-direction: row; justify-content: space-between; }.exception-metadata h2 { color: rgba(255, 255, 255, 0.8); font-size: 13px; font-weight: 400; margin: 0; }.exception-http small { font-size: 13px; opacity: .7; }.exception-hierarchy { flex: 1; }.exception-hierarchy .icon { margin: 0 3px; opacity: .7; }.exception-hierarchy .icon svg { height: 13px; width: 13px; vertical-align: -2px; }.exception-without-message .exception-message-wrapper { display: none; }.exception-message-wrapper .container { display: flex; align-items: flex-start; min-height: 70px; padding: 10px 15px 8px; }.exception-message { flex-grow: 1; }.exception-message, .exception-message a { color: #FFF; font-size: 21px; font-weight: 400; margin: 0; }.exception-message.long { font-size: 18px; }.exception-message a { border-bottom: 1px solid rgba(255, 255, 255, 0.5); font-size: inherit; text-decoration: none; }.exception-message a:hover { border-bottom-color: #ffffff; }.exception-illustration { flex-basis: 111px; flex-shrink: 0; height: 66px; margin-left: 15px; opacity: .7; }.trace + .trace { margin-top: 30px; }.trace-head { background-color: #e0e0e0; padding: 10px; }.trace-head .trace-class { color: #222; font-size: 18px; font-weight: bold; line-height: 1.3; margin: 0; position: relative; }.trace-head .trace-namespace { color: #999; display: block; font-size: 13px; }.trace-head .icon { position: absolute; right: 0; top: 0; }.trace-head .icon svg { height: 24px; width: 24px; }.trace-details { background: #FFF; border: 1px solid #E0E0E0; box-shadow: 0px 0px 1px rgba(128, 128, 128, .2); margin: 1em 0; }.trace-message { font-size: 14px; font-weight: normal; margin: .5em 0 0; }.trace-details { table-layout: fixed; }.trace-line { position: relative; padding-top: 8px; padding-bottom: 8px; }.trace-line:hover { background: #F5F5F5; }.trace-line a { color: #222; }.trace-line .icon { opacity: .4; position: absolute; left: 10px; top: 11px; }.trace-line .icon svg { height: 16px; width: 16px; }.trace-line-header { padding-left: 15px; padding-right: 10px; }.trace-file-path, .trace-file-path a { color: #222; font-size: 13px; }.trace-class { color: #B0413E; }.trace-type { padding: 0 2px; }.trace-method { color: #B0413E; font-weight: bold; }.trace-arguments { color: #777; font-weight: normal; padding-left: 2px; }.trace-code { background: #FFF; font-size: 12px; margin: 10px 10px 2px 10px; padding: 10px; overflow-x: auto; white-space: nowrap; }.trace-code ol { margin: 0; float: left; }.trace-code li { color: #969896; margin: 0; padding-left: 10px; float: left; width: 100%; }.trace-code li + li { margin-top: 5px; }.trace-code li.selected { background: #F7E5A1; margin-top: 2px; }.trace-code li code { color: #222; white-space: normal; }.trace-as-text .stacktrace { line-height: 1.8; margin: 0 0 15px; white-space: pre-wrap; }@media (min-width: 575px) {.hidden-xs-down { display: initial; }.help-link { margin-left: 30px; }}.mb-10 {margin-bottom: 10px;}.mb-15 {margin-bottom: 15px;}.mb-20 {margin-bottom: 20px;}.mb-35 {margin-bottom: 35px;}.clearfix, .clfa:after {content: ".";display: block;height: 0;clear: both;visibility: hidden;}
		</style>
	</head>
	<body>
		<div class="container">
			<div class="trace trace-as-html">
				<div class="trace-details">
					<div class="trace-head">
						<h3 class="trace-class">
							<span class="trace-namespace">Error Number: {$error_num}</span>
							MySQL Error!
						</h3>
					</div>
					<div class="trace-line">
						<div class="trace-line-header break-long-words">
						   <span class="block trace-file-path"> MySQL error in file: <a href="#">{$trace[$level]['file']}</a> at line {$trace[$level]['line']}</span>
						</div>
				
						<div class="trace-code">
							<code>The Error returned was:</code>
							<ul>
								<li>
									<code>{$error}</code>
								</li>
							</ul>
							
							<div class="clearfix mb-15"></div>
							
							<code>SQL query:</code>
							<ul>
								<li>
									<code>{$query}</code>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
HTML;
		exit();
	}
}
?>