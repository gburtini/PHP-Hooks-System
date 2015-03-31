<?php
	require_once "../src/hook.php";
	function HookTestHelper_addOne($a) {
		return $a+1;
	}
	function HookTestHelper_timesThree($a) {
		return $a*3;
	}
	function HookTestHelper_output() {
		echo "OUTPUT_SUCCESS";
	}
	function HookTestHelper_addX($a, $x) {
		return $a + $x;
	}

	class HookTest extends PHPUnit_Framework_TestCase {
		public function testLambdaBinding() {
			Hooks::bind("lambda_test", function($a) { return $a+1; });

			$value = 2;
			$value = Hooks::filter("lambda_test", $value);
			$this->assertEquals($value, 3);
			
			Hooks::bind("lambda_test2", function($a, $b) { return $a+$b; });

			$value = 2;
			$value = Hooks::filter("lambda_test2", $value, 4);
			$this->assertEquals($value, 6);
		}

		public function testBind() {	
			$this->assertTrue(Hooks::bind('bind_test', "HookTestHelper_addOne"));
			$this->assertFalse(Hooks::bind('bind_test', "bogus_function"));
		}

		public function testPriorities() {	// tests order and binding by exploiting non-transitivity
			Hooks::bind("priority_test", "HookTestHelper_addOne", 10);
			Hooks::bind("priority_test", "HookTestHelper_timesThree", 1);

			$value = 3;	// times three = 9, plus 1 = 10.
			$value = Hooks::filter("priority_test", $value);
			$this->assertEquals($value, 10, "Not equal to expected value. If it came out as 12, then the priorities are not being honored (3+1)*3 instead of (3*3)+1.");
		}

		public function testParameters() {
			Hooks::bind("parameter_test", "HookTestHelper_addX");
			$value = 1;
			$value = Hooks::filter("parameter_test", $value, array(3));
			$this->assertEquals($value, 4);

			$value = 4;
			$value = Hooks::filter("parameter_test", $value, 6);
			$this->assertEquals($value, 10, "Non-array mode parameters didn't work.");
		}

		public function testMultipleFilter() {
			Hooks::bind("multiplefilter_test", "HookTestHelper_addOne", 1);
			Hooks::bind("multiplefilter_test", "HookTestHelper_addOne", 2);
			Hooks::bind("multiplefilter_test", "HookTestHelper_addOne", 3);
			$value = 1;
			$value = Hooks::filter("multiplefilter_test", $value);
			$this->assertEquals($value, 4, "Different priorities didn't cascade.");

			Hooks::clear();
			Hooks::bind("multiplefilter_test2", "HookTestHelper_addOne");
			Hooks::bind("multiplefilter_test2", "HookTestHelper_addOne");
			Hooks::bind("multiplefilter_test2", "HookTestHelper_addOne");
			Hooks::bind("multiplefilter_test2", "HookTestHelper_addOne");
			$value = -1;
			$value = Hooks::filter("multiplefilter_test2", $value);
			$this->assertEquals($value, 3, "Same priorities didn't cascade.");
		}

		public function testExecution() {
			Hooks::bind("execution_test", "HookTestHelper_output");
			ob_start();
				Hooks::run("execution_test");
			$result = ob_get_clean();
			$this->assertEquals($result, "OUTPUT_SUCCESS");
		}
	
		public function testFilter() {
			Hooks::bind("filter_test", "HookTestHelper_addOne");
			$result = Hooks::filter("filter_test", 14);
			$this->assertEquals(15, $result);
		}

		public function testClear() {
			Hooks::bind("clear_test", "HookTestHelper_addOne");
			Hooks::clear();
			$result = Hooks::filter("clear_test", 10);
			$this->assertEquals($result, 10);

			Hooks::bind("clear_test", "HookTestHelper_addOne");
			Hooks::clear("clear_test");
			$result = Hooks::filter("clear_test", 10);
			$this->assertEquals($result, 10);
		}

		public function testEmptyHook() {
			Hooks::clear();
			Hooks::run("empty_hook");
			$result = Hooks::filter("empty_hook", "abc");
			$this->assertEquals("abc", $result);
		}
	}
