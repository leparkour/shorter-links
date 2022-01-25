<?php
class tpl extends engine {
    private $ajax = [
        'status' => 'error',
        'msg' => 'Not found application.',
        'response' => []
    ];
    private $content = '';
    private $html = '';
    private $js = [];
    private $css = [];
    private $sbar = [];
    private $breadcrumb = '';
    private $head = [
        'sep' => ' &raquo; ',
        'title' => null,
        'keywords' => null,
        'description' => null,
    ];
    private $mode = 'main';
    private $dir_mode;
    private $load_mode = false;
    private $file_mode;
    private $theme = '/public/main';
    private $dir_theme = '';
    private $class = [];
    private $addons = [];
    public $dbg = false;
    private $lang = [];

    public function __construct() {
        parent::__construct();
        $this->dir_theme = ROOT_DIR.$this->theme;
	}

    private function genJsCss($data = []) {
		$values = [];
		foreach( $data as $val ) {
			$id = $class = '';
			if( is_array($val) ) {
				$id = empty($val['id']) ? '' : ' id="'.$val['id'].'"';
				$class = empty($val['class']) ? '' : ' class="'.$val['class'].'"';
				
				$val = $val['url'];
			}

			$type = pathinfo($val, PATHINFO_EXTENSION);
            if( strpos($val, 'http') !== false ) $file = $val;
			elseif( file_exists($this->dir_theme.'/'.$val) ) $file = $this->theme.'/'.$val.'?t='.filemtime($this->dir_theme.'/'.$val);
			else continue;

			if( 'js' == $type ) $values[] = '<script src="'.$file.'"'.$id.$class.'></script>';
			elseif( 'css' == $type ) $values[] = '<link rel="stylesheet" href="'.$file.'"'.$id.$class.'>';
		}

		return implode(PHP_EOL, $values);
	}
	
	private function replace(&$content, $data = [], $duply = 0) {
		$replaceTpl = $this->tplKeys($data);
		
		foreach( $replaceTpl['values'] as $key => $val ) {
			$replaceTpl['values'][$key] = str_replace($replaceTpl['keys'], $replaceTpl['values'], $val);
		}
		
		$content = str_replace($replaceTpl['keys'], $replaceTpl['values'], $content);
	}
	
	private function tplKeys($data, $duply = false) {
		$replaceTpl = [
			'keys' => [],
			'values' => [],
		];
		
		if( isset($data) && ( is_array($data) || is_object($data) ) ) {
			foreach( $data as $key => $val ) {
				if( $duply !== false ) {
					$key = $duply.'-'.$key;
				}
				if( is_array($val) ) {
					$getLoop = $this->tplKeys($val, $key);
					
					$replaceTpl['keys'] = array_merge($replaceTpl['keys'], $getLoop['keys']);
					$replaceTpl['values'] = array_merge($replaceTpl['values'], $getLoop['values']);
				} else {
					$replaceTpl['keys'][] = '{{'.$key.'}}';
					$replaceTpl['values'][] = $val;
				}
			}
		}
		
		return $replaceTpl;
	}

	private function deprecated($content) {
		$content = preg_replace('#{{\*.*?\*}}#is', '<!--!>deprecated<--!>', $content);
        $content = preg_replace('#{{.*?}}#is', '', $content);
		return $content;
	}
	
	private function load($file) {
		if( file_exists($this->dir_theme.$file[1]) ) {
            ob_start();
            include $this->dir_theme.$file[1];
            $data = ob_get_clean();
            $this->replace($data, $this->replaceTag());
            return $data;
        }
	}

    private function _ajax() {
        $this->requests->initAjax();

        $this->mode = !empty($_REQUEST['action']) ? $_REQUEST['action'] : false;
        $do = !empty($_REQUEST['do']) ? $_REQUEST['do'] : false;

        $this->dir_mode = MODE_DIR.'/'.$this->mode;

        if( $do && is_dir($this->dir_mode) ) {
            $this->file_mode = $this->dir_mode.'/ajax/'.$do.'.php';
            if( file_exists($this->dir_mode.'/config.php') ) {
                include_once $this->dir_mode.'/config.php';
            }
            if( file_exists($this->dir_mode.'/ajax-config.php') ) {
                include_once $this->dir_mode.'/ajax-config.php';
            }
        } else {
            $this->file_mode = AJAX_DIR.'/'.$this->mode.'.php';
        }

        if( file_exists($this->file_mode) ) {
            include_once $this->file_mode;
        } else $this->ajax['msg'] = 'Not found action.';

        if( isset($_REQUEST['_fc']) && !empty($_REQUEST['_fc']) ) $this->ajax['_fc'] = $_REQUEST['_fc'];
        if( isset($_REQUEST['fc']) && !empty($_REQUEST['fc']) ) $this->ajax['fc'] = $_REQUEST['fc'];

        return $this->helper->json($this->ajax);
    }

    private function _compile() {
        $this->mode = $this->route->routes[0] ?? $this->mode;
        $this->dir_mode = MODE_DIR.'/'.$this->mode;

        foreach( ['functions', 'config',] as $val ) {
            if( file_exists($this->dir_theme.'/'.$val.'.php') ) {
                include_once $this->dir_theme.'/'.$val.'.php';
            }
        }

        $this->_content();
        $this->_breadcrumb();
        $this->_index();

        $this->replace($this->html, $this->replaceTag());

        $this->html = preg_replace_callback('#{{load=["|\']?(.*?)["|\']?}}#is', [&$this, 'load'], $this->html);

        $this->html = $this->deprecated($this->html);

        return trim($this->html);
    }

    private function replaceTag() {
        $ret = [
            'content' => $this->content,
            'breadcrumb' => $this->breadcrumb,
            'title' => !is_array($this->head['title']) ? $this->head['title'] : implode($this->head['sep'], $this->head['title']),
            'keywords' => $this->head['keywords'],
            'description' => $this->head['description'],
            'main' => $this->theme,
            'css' => $this->genJsCss($this->css),
            'js' => $this->genJsCss($this->js),
            'class' => $this->class,
        ];

        $ret = array_merge($this->addons, $ret);
        return $ret;
    }

    private function _content() {
        if( $this->load_mode ) return;

        if( is_dir($this->dir_mode) ) {
            $this->file_mode = $this->dir_mode.'/main.php';
            if( file_exists($this->dir_mode.'/config.php') ) include_once $this->dir_mode.'/config.php';
            if( file_exists($this->dir_mode.'/functions.php') ) include_once $this->dir_mode.'/functions.php';
        } else {
            $this->file_mode = MODE_DIR.'/'.$this->mode.'.php';
        }

        if( !file_exists($this->file_mode) ) {
            $this->mode = '404';
            $this->file_mode = MODE_DIR.'/error/'.$this->mode.'.php';
        }

        include_once $this->file_mode;
        $this->load_mode = true;
    }

    private function _breadcrumb() {
        if( empty($this->breadcrumb) && file_exists($this->dir_theme.'/breadcrumb.php') && $this->sbar ) {
            extract(['breadcrumb' => $this->sbar, 'title' => end($this->sbar)['title']]);
            ob_start();
            include_once $this->dir_theme.'/breadcrumb.php';
            $this->breadcrumb = ob_get_clean();
        }
    }

    private function _index() {
        if( !empty($this->html) ) return;
        if( file_exists($this->dir_theme.'/index.php') ) {
            ob_start();
            include $this->dir_theme.'/index.php';
            $this->html = ob_get_clean();
        } else throw new \Exception('Not found index file for template.');
    }

    public function compile() {
        try {
            if( $this->requests->isAjax() ) {
                echo $this->_ajax();
            } else {
                echo $this->_compile();
            }
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    public function addAddons($data = []): static
    {
        $this->addons = array_merge($this->addons, $data);
        return $this;
    }

    public function setTitle($title = '', $full = false): static
    {
        if( $full ) $this->head['title'] = $title;
            else $this->head['title'][] = $title;
        return $this;
    }

    public function setKeywords($text = ''): static
    {
        $this->head['keywords'] = $text;
        return $this;
    }

    public function setDescription($text = ''): static
    {
        $this->head['description'] = $text;
        return $this;
    }

    public function setClass($name = '', $value = ''): static
    {
        $this->class[$name] = $value;
        return $this;
    }

    public function addCss($data): static
    {
        $this->_addAssets($data);
        return $this;
    }

    public function addJs($data): static
    {
        $this->_addAssets($data, 'js');
        return $this;
    }

    private function _addAssets($data, $name = 'css') {
        if( !$data ) return;
        if( is_array($data) ) {
            foreach( $data as $val ) {
                $this->{$name}[] = $val;
            }
        } else {
            $this->{$name}[] = $data;
        }
    }

    public function addSbar($data, $url = false): static
    {
        $this->sbar[] = ['url' => $url, 'title' => $data];
        return $this;
    }

    public function setTemplate($name, $param = []): static
    {
        if( empty($this->content) && file_exists($this->dir_theme.'/view/'.$name.'.php') ) {
            extract($param);
            ob_start();
            include_once $this->dir_theme.'/view/'.$name.'.php';
            $this->content = ob_get_clean();
        }

        return $this;
    }

}