<?php

/*
 * MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * Copyright (c) 2007, Slava Tretyak (aka restorer)
 * Zame Software Development (http://zame-dev.org)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * [S]imple framework
 * AjaxPage class
 */

require_once(S_BASE . 'web/page.php');
require_once(S_BASE . 'data/json.php');

##
# [AJ_INIT] when ajax call found, AjaxPageInit event called. If you will call break_flow in event handler, aj function will not be called (this is useful for authentication)
##
define('AJ_INIT', 'aj_init');

##
# .begin
# = class SAjaxPage
##
class SAjaxPage extends SPage
{
	function __construct()
	{
		parent::__construct();
		$this->add_event(PAGE_INIT, 's_ajax_page_on_init');
	}

	function s_ajax_page_save_log()
	{
		if (LOG_DEBUG_INFO)
		{
			$debuglog_str = dflush_str();
			_log("[[ Ajax info ]]\n\n$debuglog_str\n\n");
		}
	}

	function s_ajax_page_on_init()
	{
		if (!InPOST('__s_ajax_method')) return;

		if (array_key_exists(AJ_INIT, $this->_events))
		{
			foreach ($this->_events[AJ_INIT] as $method)
			{
				$res = call_user_func(array($this, $method));

				if ($this->_flow != PAGE_FLOW_NORMAL)
				{
					if (isset($res)) {
						echo "fail:$res";
					}

					$this->s_ajax_page_save_log();
					return;
				}
			}
		}

		$this->_flow = PAGE_FLOW_BREAK;
		$method = 'aj_' . _POST('__s_ajax_method');

		if (!method_exists($this, $method))
		{
			echo "fail:method $method not found";
			$this->s_ajax_page_save_log();
			return;
		}

		$args_array = SJson::deserialize(_POST('__s_ajax_args'));

		if (!is_array($args_array))
		{
			echo "fail:fail to deserialize arguments";
			$this->s_ajax_page_save_log();
			return;
		}

		try
		{
			$res = call_user_func_array(array($this, $method), $args_array);
		}
		catch (Exception $ex)
		{
			echo 'fail:', $ex->getMessage();
			$this->s_ajax_page_save_log();
			return;
		}

		echo 'succ:';
		if (isset($res)) echo SJson::serialize($res);

		$this->s_ajax_page_save_log();
		exit();
	}
}
##
# .end
##
