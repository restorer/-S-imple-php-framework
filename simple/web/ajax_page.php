<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
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
	}
}
##
# .end
##
