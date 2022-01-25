<?php
namespace autoLoader {
	class Autoloader {
		const debug = 0;
		
		public function __construct() {
			;
		}
		
		public static function autoload($file, $ext = FALSE, $dir = FALSE) {
			$file = str_replace('\\', '/', $file);
			
			if( $ext === FALSE ) {
				$path = LIB_DIR;
				$filepath = LIB_DIR . '/' . $file . '.php';
			} else {
				$path = LIB_DIR . (($dir) ? '/' . $dir : '');
				$filepath = $path . '/' . $file . '.' . $ext;
			}
			
			if( file_exists($filepath) ) {
				if($ext === FALSE) {
					if( Autoloader::debug ) Autoloader::saveLog('autoload', ['method' => 'include', 'file' => $filepath]);
					require_once($filepath);
				} else {
					if(Autoloader::debug) Autoloader::saveLog('autoload', ['method' => 'find_file', 'file' => $filepath]);
					return $filepath;
				}
			} else {
				$flag = true;
				if(Autoloader::debug) Autoloader::saveLog('autoload', ['method' => 'recursive_find', 'in_dir' => $path, 'file' => $file]);
				return Autoloader::recursive_autoload($file, $path, $ext, $flag);
			}
		}

		public static function recursive_autoload($file, $path, $ext, &$flag) {
			if( FALSE !== ($handle = opendir($path)) && $flag ) {
				while( FAlSE !== ($dir = readdir($handle)) && $flag ) {
					if( strpos($dir, '.') === FALSE ) {
						$path2 = $path .'/' . $dir;
						$filepath = $path2 . '/' . $file .(($ext === FALSE) ? '.php' : '.' . $ext);
						if(Autoloader::debug) Autoloader::saveLog('autoload', ['method' => 'find_file', 'in_dir' => $filepath, 'file' => $file]);
						
						if( file_exists($filepath) ) {
							$flag = FALSE;
							if( $ext === FALSE ) {
								if(Autoloader::debug) Autoloader::saveLog('autoload', ['method' => 'include', 'file' => $filepath]);
								require_once($filepath);
								break;
							} else {
								if(Autoloader::debug) Autoloader::saveLog('autoload', ['method' => 'find_file', 'file' => $filepath]);
								return $filepath;
							}
						}
						Autoloader::recursive_autoload($file, $path2, $ext, $flag); 
					}
				}
				closedir($handle);
			}
		}
	}
	\spl_autoload_register('autoLoader\Autoloader::autoload');
}