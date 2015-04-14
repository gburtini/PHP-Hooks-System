PHP Hooks and Filters System
============================

_Event-driven programming, a plugin architecture, hooks, filters, all wrapped in to a simple, efficient and clean Composer-ready interface._

This is an elegant way of coding plugin architectures or other decoupled software solutions. The class provides three major methods: bind, run and filter; combined, these allow you to integrate in to existing systems without interfering with their existing code.

There's also a plugin loader in ``Plugins.php`` which can be used to load a directory of optional code. Use is simple: ``Plugins::load("directory")`` will traverse the directory and load all non-disabled (~, . prefix) folders containing a ``init.php`` file.

Installation
------------

Everything you need is contained within the single file ``Hooks.php``, but the most convenient way to install is with composer:

    composer require gburtini/hooks
    
    
Usage
-----
* ``bind(string $hook, callback $callback, int $priority)`` - binds a callback to a hook
* ``run(string $hook, array $parameters)`` - executes all the functions bound to a given hook
* ``filter(string $hook, object $value, array $parameters)`` - executes all the functions bound to a given hook, passing in $value each time, and finally return the value

The designed use case is that throughout your existing software, you will call ``Hooks::filter`` and ``Hooks::run`` where you want to allow users to hook with a specified ``$hook`` value. Then users can call ``Hooks::bind()`` anytime before your code runs to associate code with the various locations -- filters will return the value (so that a function can change it) and run calls will simply run functions with no in-code side effects.

Debugging
---------
The Hooks class has a debug level setting which produces some output indicating which hooks are running and when. Call ``Hooks::setDebugLevel($debug_level)`` at runtime to set the debug output. ``$debug_level`` can take on the following values:

* ``Hooks::DEBUG_NONE`` - the default, no debug output.
* ``Hooks::DEBUG_EVENTS`` - a list of the ``::run`` and ``::filter`` calls as they happen.
* ``Hooks::DEBUG_CALLS`` - a list of every callback executed in each hoo/filter.
* ``Hooks::DEBUG_BINDS`` - a list of each time a bind is setup for a hook or filter.
* ``Hooks::DEBUG_INTERACTION`` - outputs every call to a class method (bind, run, filter, and the private methods)
* ``Hooks::DEBUG_ALL`` - all of the above combined.

You can also combine these with the bitwise OR, for example ``Hooks::DEBUG_EVENTS | Hooks::DEBUG_BINDS`` outputs all the run/filter/bind calls as they happen.

Future Work
-----------

A nice future feature would be to enforce some sort of constraints on what these binds can do -- allowing "untrusted" (in the sense of the data) code to run in some sort of quasi-sandbox; for example, the filters could check to ensure the value was constrained to a particular type, range or callback validation.

Timing functionality for the debug code to see which hooks and associated callbacks are slow is an interesting angle here too.

Sorting can be called less frequently. If nothing has changed, it is unnecessary to call the sort function again. That said, the used quicksort variant should be rapid on already sorted data.


License
-------
*Copyright (C) 2012-2015 Giuseppe Burtini*

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
