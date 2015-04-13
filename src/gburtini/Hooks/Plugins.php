<?php
	namespace gburtini\Hooks;
	require_once dirname(__FILE__) . "/Hooks.php";
	use gburtini\Hooks\Hooks;

	/**
	 * The bare minimum plugin loader. It would be valuable for some cases to extend this to support WordPress style configuration (structured headers),
	 * secure disable, sandboxing, etc. At the end of the day, this code is used predominantly to build "optional" code for internal use in conjunction
	 * with the Hooks class.
	 */
	class Plugins {
		// NOTE: disabling plugins that start with '.' should be considered an essential security feature.

		protected static $disabled = ["~", "."];	// plugins whose folders start with these characters will be treated as disabled. 
		// Not Implemented: protected static $recursive = false;		// whether we should recursively enumerate the paths
		protected static $file = "init.php";		// the file to load from each plugin, a folder is considered "not a plugin" if it doesn't contain this.

		/**
		 * Load all plugins in the path $path.
		 */
		public static function load($path) {
			if ($handle = opendir($path)) {
				self::hook("load-plugins");
				while (false !== ($entry = readdir($handle))) {
					if ($entry == "." || $entry == "..") 
						continue;
					$entry = self::filter("plugin-name", $entry);
					if($entry === false) 
						continue;
			
					$fc = substr($entry, 0, 1);
					if(in_array($fc, self::$disabled)) 
						continue;

					$fp = $path . "/" . $entry . "/" . self::$file;
					$fp = self::filter("plugin-path", $fp);
					if(!file_exists($fp))
						continue;

					self::hook(["load-plugin", "load-plugin-$entry"], ['name' => $entry, 'path' => $fp]);
						require_once $fp;
					self::hook(["load-plugin-done", "load-plugin-$entry-done"], ['name' => $entry, 'path' => $fp]);
				}

				closedir($handle);
				self::hook("load-plugins-done");
			} else {
				return false;
			}
		}

		// wrapper functions that allow this to be used without the hooks code.
		protected static function hook($name, $data=array()) {
			if(class_exists("Hooks"))
				Hooks::run($name, $data);
			return false;
		}
		protected static function filter($name, $value, $data=array()) {
			if(class_exists("Hooks"))
				Hooks::filter($name, $value, $data);
			return $value;
		}
	}
