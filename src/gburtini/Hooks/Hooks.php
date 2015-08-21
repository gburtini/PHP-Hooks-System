<?php
	namespace gburtini\Hooks;
	class Hooks {
		const DEBUG_NONE = 0;		// outputs nothing!
		const DEBUG_EVENTS = 1;		// outputs a list of every run or filter call, so that you can see when they occur.
		const DEBUG_CALLS = 2; 		// outputs a list of every callback
		const DEBUG_BINDS = 4;		// outputs a list of every bind
		const DEBUG_INTERACTION = 8;	// outputs every function call within the class
		const DEBUG_ALL = 15; // DEBUG_INTERACTION + DEBUG_BINDS + DEBUG_CALLS + DEBUG_EVENTS;	

		protected static $hooks = [];
		protected static $debugLevel = self::DEBUG_NONE;
		protected static $debugMethod = "print";

		protected static function debug($string) {
			try { 
				if(self::$debugMethod == "print") {
					echo $string . "\n";
				} else { $dm = self::$debugMethod; $dm($string); }
			} catch(Exception $e) {
				self::debug("Tried to debug output, but ran in to an unprintable.");
			}
		}
		protected static function export($string) {
			try {
				return var_export($string, true);
			} catch(Exception $e) {
				return "...";
			}

		}

		public static function setDebugLevel($debug_level = self::DEBUG_NONE) { 
			if(self::$debugLevel & self::DEBUG_INTERACTION)
				self::debug("Hooks::setDebugLevel(debug_level=$debug_level);");

			self::$debugLevel = $debug_level;
		}

		/**
		 * clear(string $hook) - clears all callbacks associated with a given hook
		 */
		public static function clear($hook = null) {
			if(self::$debugLevel & self::DEBUG_INTERACTION)
				self::debug("Hooks::clear(hook=$hook);");

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
			if(self::$debugLevel & self::DEBUG_INTERACTION)
				self::debug("Hooks::bind(hook=$hook, callback=" . self::export($callback) . ", priority=$priority);");

			if(!is_callable($callback)) {
				throw new \InvalidArgumentException("Callback is not callable on attempt to bind to $hook.");
			}

			if(self::$debugLevel & self::DEBUG_BINDS) {
				self::debug("Binding " . self::export($callback) . " to $hook at priority $priority.");
			}

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
			if(self::$debugLevel & self::DEBUG_INTERACTION)
				self::debug("Hooks::run(hook=$hook, parameters=" . self::export($parameters) . ")");

			if(self::$debugLevel & self::DEBUG_EVENTS)
				self::debug("Running hook $hook.");
			$hooks = self::processKeys($hook);
			foreach($hooks as $hook) {
				$count = 0;
				self::sortHooks($hook);
				if(isset(self::$hooks[$hook])) {
					if(!is_array($parameters)) {
						$parameters = func_get_args();
						array_shift($parameters);
					}
					foreach(self::$hooks[$hook] as $priority=>$hooks) {	
						// hooks arrays will come in priority-sorted order here
						foreach($hooks as $cb) {
							if(self::$debugLevel & self::DEBUG_CALLS)
								self::debug("Calling " . self::export($cb) . " with priority $priority on hook $hook.");
							if(@call_user_func_array($cb, $parameters) !== false) {
								$count++;
							}
						}
					}
				}
				if(self::$debugLevel & self::DEBUG_EVENTS)
					self::debug("Ran $count callbacks for $hook.");
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
			if(self::$debugLevel & self::DEBUG_INTERACTION)
				self::debug("Hooks::filter(hook=$hook, value=" . self::export($value) . ", parameters=" . self::export($parameters) . ")");
			
			if(self::$debugLevel & self::DEBUG_EVENTS)
				self::debug("Running filter $hook on " . self::export($value) . ".");
			
			$hooks = self::processKeys($hook);
			foreach($hooks as $hook) {
				$count = 0;
				self::sortHooks($hook);
				if(isset(self::$hooks[$hook])) {
					if(!is_array($parameters)) {
						$parameters = func_get_args();
						array_shift($parameters);
						array_shift($parameters);
					}
					foreach(self::$hooks[$hook] as $priority=>$hooks) {
						foreach($hooks as $cb) {
							if(self::$debugLevel & self::DEBUG_CALLS)
								self::debug("Calling " . self::export($cb) . " with priority $priority on filter $hook.");

							array_unshift($parameters, $value);
							// this (possibly, depending on $parameters) makes an assumption about the /order/ of a map, a definite bad idea.
							$value = @call_user_func_array($cb, array_values($parameters));
							$count++;
						}
					}
				}

				if(self::$debugLevel & self::DEBUG_EVENTS)
					self::debug("Ran $count filters for $hook. Result is " . self::export($value) . ".");
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
