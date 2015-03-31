<?php
	class Hooks {
		protected static $hooks = array();

		/**
		 * clear(string $hook) - clears all callbacks associated with a given hook
		 */
		public static function clear($hook = null) {
			if($hook === null) {
                                foreach(self::$hooks as $key=>$value) {
                                	self::clear($key);
				}
			} else {
				unset(self::$hooks[$hook]);
			}
		}

		/**
	 	 * bind(string $hook, callback $callback, int $priority) - binds a callback to a hook
		 * 
		 * Associates a function with a given hook. Hooks are just strings as unique identifiers.
		 */
		public static function bind($hook, $callback, $priority = 10) {
			if(!is_callable($callback))
				return false;

			if(!isset(self::$hooks[$hook]))
				self::$hooks[$hook] = array();

			if(!isset(self::$hooks[$hook][$priority]))
				self::$hooks[$hook][$priority] = array();

			self::$hooks[$hook][$priority][] = $callback;			
			return true;
		}
	
		/**
	 	 * run(string $hook, array $parameters) - executes all the functions bound to a given hook
		 * 
		 * The parameters array can define things to be passed in to the hook. This allows hooks to take something like
		 * an ID. Returns the number of hooks executed -- return false from a hook to have it not counted.
		 */
		public static function run($hook, $parameters=array()) {
			self::sortHooks($hook);
			$count = 0;
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

			return $count;
		}

		
		/**
		 * filter(string $hook, object $value, array $parameters) - executes all the functions bound to a given hook, passing in $value each time
		 *
		 * This iteratively processes a value $value by passing it to callback functions.
		 */
		public static function filter($hook, $value, $parameters=array()) {
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
			return $value;
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
