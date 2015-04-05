<?php
	namespace gburtini;
	class Hooks {
		protected static $hooks = [];

		/**
		 * clear(string $hook) - clears all callbacks associated with a given hook
		 */
		public static function clear($hook = null) {
			self::run("hooks-clear");
			if($hook === null) {
				self::run("hooks-clear-all");
                                foreach(self::$hooks as $key=>$value) {
                                	self::clear($key);
				}
			} else {
				$hooks = self::processKeys($hook);
				foreach($hooks as $h) {
					self::run(["hooks-clear-one", "hooks-clear-$h"], ["hook"=>$h]);
					unset(self::$hooks[$h]);
					self::run(["hooks-clear-one-done", "hooks-clear-$h-done"], ["hook"=>$h]);
				}
			}
			self::run("hooks-clear-done");
		}

		/**
	 	 * bind(string $hook, callback $callback, int $priority) - binds a callback to a hook
		 * 
		 * Associates a function with a given hook. Hooks are just strings as unique identifiers.
		 */
		public static function bind($hook, $callback, $priority = 10) {
			if(!is_callable($callback))
				return false;

			$hooks = self::processKeys($hook);
			self::run("hooks-bind");
			foreach($hooks as $hook) {
				if(!isset(self::$hooks[$hook]))
					self::$hooks[$hook] = [];

				if(!isset(self::$hooks[$hook][$priority]))
					self::$hooks[$hook][$priority] = [];

				self::$hooks[$hook][$priority][] = $callback;			
			}
			self::run("hooks-bind-done");
			

			return true;
		}
	
		/**
	 	 * run(string $hook, array $parameters) - executes all the functions bound to a given hook
		 * 
		 * The parameters array can define things to be passed in to the hook. This allows hooks to take something like
		 * an ID. Returns the number of hooks executed -- return false from a hook to have it not counted.
		 */
		public static function run($hook, $parameters=array()) {
			$hooks = self::processKeys($hook);
			$count = 0;
			foreach($hooks as $hook) {
				self::sortHooks($hook);
				if(isset(self::$hooks[$hook])) {
					if(!is_array($parameters)) {
						$parameters = func_get_args();
						array_shift($parameters);
					}
					foreach(self::$hooks[$hook] as $hooks) {	
						// hooks arrays will come in priority-sorted order here
						foreach($hooks as $cb) {
							if(@call_user_func_array($cb, $parameters) !== false) {
								$count++;
							}
						}
					}
				}
			}
			return $count;
		}

		
		/**
		 * filter(string $hook, object $value, array $parameters) - executes all the functions bound to a given hook, passing in $value each time
		 *
		 * This iteratively processes a value $value by passing it to callback functions. Warning: passing an array as hook will pass the value 
		 * through all the hooks.
		 */
		public static function filter($hook, $value, $parameters=array()) {
			$hooks = self::processKeys($hook);
			foreach($hooks as $hook) {
				self::sortHooks($hook);
        	                if(isset(self::$hooks[$hook])) {
					if(!is_array($parameters)) {
						$parameters = func_get_args();
						array_shift($parameters);
						array_shift($parameters);
					}
					foreach(self::$hooks[$hook] as $hooks) {
						foreach($hooks as $cb) {
							array_unshift($parameters, $value);
							$value = @call_user_func_array($cb, $parameters);
						}
					}
				}
			}
			return $value;
		}

		/**
	 	 * array processKeys(string, integer or array $hook) - turns a user specified hook in to a ready to use array of array keys.
		 * 
		 * This is used internally to allow users to pass hooks as any array indexable type
		 * or an array of indexable types. In the future, it could be used to further preprocess
		 * hook keys if necessary.
	 	 *
		 * This function also throws an exception which bubbles up iff a hook key is specified
		 * that is not a valid array key (that is, it is an object or a nested array). We 
		 * currently do not validate the type of nested objects if the hook is already passed
		 * as an array.
		 * 
		 * Warning: note the properties of the PHP array for weird types http://php.net/manual/en/language.types.array.php
		 * in particular, floats are treated as truncated and bools are cast to possibly override other keys.
		 */
		protected static function processKeys($hook) {
			if(is_object($hook) || $hook === null) 
				throw new Exception("Invalid hook index type.");
			if(is_array($hook)) 
				return $hook;
			// TODO: possibly validate internal members.
			return array($hook);
		}

		/**
		 * sortHooks(string $hook) - sorts the given hook according to priority
		 *
		 * This is used internally right before run to make sure hooks run in their
		 * appropriate priority. A lower priority number means run earlier. If this is
		 * called with no $hook value, all hooks are sorted.
		 *
		 * It's worth noting that sorting here is O(n^2) worst case. It'd be nice to not
		 * have to do this in every run call. PHP's ksort function uses an implementation of
		 * quicksort which has good performance on already sorted data -- but "good" performance
		 * is still worse than not having to do it. The alternative is to sort inline when
		 * binding, but PHP arrays make this non-trivial. The assumption here is that bind
		 * is called more frequently than run (most hooks will only run once).
		 * 
		 * http://svn.php.net/viewvc/php/php-src/trunk/Zend/zend_qsort.c?view=markup
		 */
		protected static function sortHooks($hook = null) {
			if($hook === null) {
				foreach(self::$hooks as $key=>$value) {
					self::sortHooks($key);
				}

				return self::$hooks;
			} else {
				if(isset(self::$hooks[$hook])) 
					ksort(self::$hooks[$hook]);
				else
					return array();

				return self::$hooks[$hook];
			}
		}
	}
?>
