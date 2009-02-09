<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Page class
 */

require_once(S_BASE.'web/template.php');
require_once(S_BASE.'web/control.php');

##
# [PAGE_INIT] Page init event, called before form handling
##
define('PAGE_INIT', 'init');

##
# [PAGE_PRE_RENDER] Page pre-render event, called before render
##
define('PAGE_PRE_RENDER', 'prerender');

define('PAGE_FLOW_BREAK', 0);
define('PAGE_FLOW_NORMAL', 1);
define('PAGE_FLOW_ERROR', 2);
define('PAGE_FLOW_REDIRECT', 3);
define('PAGE_FLOW_RENDER', 4);

##
# [CSS] Path to css (for using in templates)
##
define('CSS', ROOT.'css/');

##
# [JS] Path to javascripts (for using in templates)
##
define('JS', ROOT.'js/');

##
# [IMG] Path to images (for using in templates)
##
define('IMG', ROOT.'img/');

##
# .begin
# = class SPage
##
class SPage
{
	##
	# [$vars] Template variables
	##
	public $vars = array();

	public $validators = array();
	public $controls = array();

	##
	# [$template_name] Page template
	##
	public $template_name = '';

	##
	# [$design_page_name] Design template
	##
	public $design_page_name = '';

	##
	# [$error_page_name] Error page template
	##
	public $error_page_name = '';

	##
	# [$content_type] Content-type
	##
	public $content_type = 'text/html';

	public $form_data = array();	// used while render form and form controls

	protected $_start_time = 0;
	protected $_flow = PAGE_FLOW_NORMAL;
	protected $_events = array();
	protected $_error_message = '';
	protected $_redirect = '';
	protected $_headers = array();
	protected $_form_posted = '';
	protected $_form_action = '';
	protected $_uploaded_files = array();

	##
	# = void __construct()
	# Don't forget to call parent constructor in your page
	##
	// ' fix mc highlighter
	function __construct()
	{
		$this->_start_time = get_microtime();

		if (DEBUG)
		{
			dwrite("**[Page processing begin]**");

			if (conf('page.show_vars', false))
			{
				dwrite_msg('GET', dump_str($_GET));
				dwrite_msg('POST', dump_str($_POST));
				dwrite_msg('SESSION', dump_str($_SESSION));
				dwrite_msg('FILES', dump_str($_FILES));
				dwrite_msg('COOKIE', dump_str($_COOKIE));
			}
		}

		$this->template_name = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $this->script_name() . '.tpl';
		$this->design_page_name = BASE.'templates/design.tpl';
		$this->error_page_name = BASE.'templates/error.tpl';
	}

	##
	# = public string script_name()
	# Returns script name (w/o .php extension)
	##
	public function script_name()
	{
		return basename($_SERVER['SCRIPT_NAME'], '.php');
	}

	##
	# = public void cache_set(string $name, mixed $value)
	# Set value in cache (session keys **"page.<script_name>.<name>"** is used as cache)
	##
	public function cache_set($name, $value)
	{
		$arr = _SESSION('page.'.$this->script_name(), array());
		$arr[$name] = $value;
		$_SESSION['page.'.$this->script_name()] = $arr;
	}

	##
	# = public void cache_remove(string $name)
	# Remove value from cache
	##
	public function cache_remove($name)
	{
		if (inSESSION('page.'.$this->script_name())) {
			if (array_key_exists($name, $_SESSION['page.'.$this->script_name()])) {
				unset($_SESSION['page.'.$this->script_name()][$name]);
			}
		}
	}

	##
	# = public mixed cache_get(string $name, mixed $def='')
	# Get value from cache
	##
	public function cache_get($name, $def='')
	{
		$arr = _SESSION('page.'.$this->script_name(), array());
		return (array_key_exists($name, $arr) ? $arr[$name] : $def);
	}

	##
	# = public void add_validator(string $field, &$validator)
	# [$field] Field name
	# [$validator] Instantiated validator class
	##
	public function add_validator($field, $validator)
	{
		if (!array_key_exists($field, $this->validators)) $this->validators[$field] = array();
		$this->validators[$field][] = $validator;
	}

	##
	# = public void add_validators(string $field, array $arr)
	# [$field] Field name
	# [$arr] Array of validators
	##
	public function add_validators($field, $arr)
	{
		foreach ($arr as $k=>$v) {
			$this->add_validator($field, $arr[$k]);
		}
	}

	##
	# = public void add_control($name, $ctl)
	# [$name] Control name
	# [$ctl] Instantiated control class
	##
	public function add_control($name, $ctl)
	{
		if (array_key_exists($name, $this->controls)) {
			if (DEBUG) dwrite("Control '$name' already added");
			return;
		}

		$ctl->page =& $this;
		$this->controls[$name] =& $ctl;
	}

	##
	# = public SControl &get_control(string $name)
	# [$name] Control name
	# Returns added control by name
	##
	public function get_control($name)
	{
		if (!array_key_exists($name, $this->controls)) throw new Exception("Control '$name' not found");
		return $this->controls[$name];
	}

	##
	# = public void add_event(int $type, string $method_name)
	# [$type] Event type (PAGE_INIT or PAGE_PRE_RENDER)
	# [$method_name] Class method, which will be called on event
	##
	public function add_event($type, $method_name)
	{
		if (!array_key_exists($type, $this->_events)) $this->_events[$type] = array();
		$this->_events[$type][] = $method_name;
	}

	##
	# = public mixed get_var(string $name, mixed $def='')
	##
	public function get_var($name, $def='')
	{
		return (array_key_exists($name, $this->vars) ? $this->vars[$name] : $def);
	}

	##
	# = public array validation_errors()
	# Returns assoc array of validation errors
	# **key** - field name
	# **$result[$key]** - validation error
	##
	public function validation_errors()
	{
		$errors = array();

		foreach ($this->validators as $fld=>$arr)
		{
			foreach ($arr as $vl)
			{
				$vl->page = $this;

				if (!$vl->validate($fld, $this->vars))
				{
					$errors[$fld] = $vl->error_message($fld, $this->vars);
					break;
				}
			}
		}

		return $errors;
	}

	##
	# = public void validate()
	# Validate page, and fill **'errors'** template variable with validation errors
	##
	public function validate()
	{
		$this->vars['errors'] = $this->validation_errors();
	}

	##
	# = public bool is_valid()
	# Returns **true** if page is valid, **false** otherwise
	##
	public function is_valid()
	{
		if (!array_key_exists('errors', $this->vars)) $this->validate();
		return (!count($this->get_var('errors', array())));
	}

	##
	# = protected void _process_post()
	# Internal POST parsing. When value **'_s_<form-name>_action'** exists in post, set **posted form name** and **form action**
	##
	protected function _process_post()
	{
		foreach ($_POST as $k=>$v)
		{
			if (substr($k, 0, 3)=='_s_' && substr($k, -7)=='_action') {
				$this->_form_posted = substr($k, 3, -7);
				$this->_form_action = $v;
			} else {
				$this->vars[$k] = $v;
			}
		}

		foreach ($_FILES as $k=>$v)
		{
			if ($v['error'] == UPLOAD_ERR_OK)
			{
				if (is_uploaded_file($v['tmp_name']))
				{
					if ($v['size'] != 0)
					{
						$this->_uploaded_files[$k] = UPLOAD_ERR_OK;
						$this->vars[$k] = '_uploaded_file_';
						$this->vars[$k.':name'] = preg_replace('@[\\\\/\\*]@', '', $v['name']);
						$this->vars[$k.':type'] = $v['type'];
						$this->vars[$k.':size'] = $v['size'];
						$this->vars[$k.':tmp_name'] = $v['tmp_name'];
					}
					else { $this->_uploaded_files[$k] = UPLOAD_ERR_NO_FILE; }
				}
				else { $this->_uploaded_files[$k] = UPLOAD_ERR_PARTIAL; }
			}
			elseif ($v['error'] != UPLOAD_ERR_NO_FILE) {
				$this->_uploaded_files[$k] = $v['error'];
			}
		}
	}

	##
	# = public bool file_is_uploaded(string $name)
	# [$name] Field name
	# Returns **true** is file is uploaded without errors, **false** otherwise
	##
	public function file_is_uploaded($name)
	{
		if (!array_key_exists($name, $this->_uploaded_files)) return false;
		return ($this->_uploaded_files[$name] == UPLOAD_ERR_OK);
	}

	##
	# = public void move_upl_file(string $name, string $destination_path)
	# [$name] Field name
	# [$destination_path] Destination path
	##
	public function move_upl_file($name, $destination_path)
	{
		if (!$this->file_is_uploaded($name)) {
			if (DEBUG) dwrite("Can't move uploaded file for field '$name' because this file not uploaded");
			return;
		}

		move_uploaded_file($this->vars[$name.':tmp_name'], $destination_path);
	}

	##
	# = public bool is_post_back(string $form_name='')
	# Returns **true** is postback occurred (when form specified, also checks submitted form name), **false** otherwise
	##
	public function is_post_back($form_name='')
	{
		if (!strlen($form_name)) return strlen($this->_form_posted);
		return ($this->_form_posted == $form_name);
	}

	##
	# = public void set_select_data(string $name, array $data, string $group='__default__')
	# [$name] Select control id
	# [$data] Select items, key => item id, value => value
	# [$group] Items group
	##
	public function set_select_data($name, $data, $group='__default__')
	{
		$sd = $this->get_var($name.':data', array());
		$sd[$group] = $data;
		$this->vars[$name.':data'] = $sd;
	}

	##
	# = public bool check_select_items(string $name, array $data)
	# [$name] Select control id
	# [$data] Select items
	# Returns **true** when submitted value exists in **$data**, **false** otherwise
	##
	public function check_select_items($name, $data)
	{
		if (!array_key_exists($name, $this->vars)) return false;

		if (is_array($this->vars[$name]))
		{
			foreach ($this->vars[$name] as $val) {
				if (!array_key_exists($val, $data)) return false;
			}

			return true;
		}
		else { return array_key_exists($this->vars[$name], $data); }
	}

	##
	# = public void validate_select_items(string $name, array $data)
	# [$name] Select control id
	# [$data] Select items
	# Throws exception, when submitted value not exists in **$data**
	##
	public function validate_select_items($name, $data) {
		if (!$this->check_select_items($name, $data)) throw new Exception('Please stop hack us, evil haxor.');
	}

	##
	# = protected void add_header(string $name, string $value)
	##
	protected function add_header($name, $value)
	{
		foreach ($this->_headers as $k=>$v) {
			if (strtolower($k) == strtolower($name)) {
				$this->_headers[$k] = $value;
				return;
			}
		}

		$this->_headers[$name] = $value;
	}

	protected function _init()
	{
		$this->_process_post();
		if (!array_key_exists(PAGE_INIT, $this->_events)) return;

		foreach ($this->_events[PAGE_INIT] as $method)
		{
			call_user_func(array($this, $method));
			if ($this->_flow != PAGE_FLOW_NORMAL) return;
		}
	}

	##
	# = protected void _handle_forms()
	# Internal form handling, call **'on_<posted-form-name>_submit'** method (in page or in controls) when form submitted
	##
	protected function _handle_forms()
	{
		if (!strlen($this->_form_posted)) return;
		$method = 'on_'.$this->_form_posted.'_submit';

		if (method_exists($this, $method))
		{
			call_user_func(array($this, $method), $this->_form_action);
			return;
		}

		foreach ($this->controls as $k=>$v)
		{
			$ctl = $this->controls[$k];

			if (method_exists($ctl, $method))
			{
				$ctl->page = $this;
				call_user_func(array($ctl, $method), $this->_form_action);
				return;
			}
		}

		if (DEBUG) dwrite("Method '$method' not defined");
	}

	protected function _pre_render()
	{
		if (!array_key_exists(PAGE_PRE_RENDER, $this->_events)) return;

		foreach ($this->_events[PAGE_PRE_RENDER] as $method)
		{
			call_user_func(array($this, $method));
			if ($this->_flow != PAGE_FLOW_NORMAL) return;
		}
	}

	##
	# = protected void output_headers()
	# Output headers to browser. In most of cases, you don't need to call this method directly
	##
	protected function output_headers()
	{
		$this->add_header('Content-type', $this->content_type);
		foreach ($this->_headers as $k=>$v) header($k.': '.$v);
	}

	##
	# = protected void output_result(string $res)
	# Output result to browser. In most of cases, you don't need to call this method directly
	##
	protected function output_result($res)
	{
		global $s_runconf;
		$nw = get_microtime();

		$this->output_headers();
		echo $res;

		if  ($this->content_type=='text/html' && DEBUG)
		{
			dwrite('**[Page processing end]**');
			dwrite('Page processing takes: ' . number_format(($nw - $this->_start_time), 8));
			dwrite('SQL parsing takes: ' . number_format($s_runconf->get('time.sql.parse'), 8));
			dwrite('SQL queries takes: ' . number_format($s_runconf->get('time.sql.query'), 8));
			dwrite('Templates takes: ' . number_format($s_runconf->get('time.template'), 8) . ' (approx, including template loading)');

			$debuglog_str = dflush_str();

			if (LOG_DEBUG_INFO) {
				_log("[[ Page info ]]\n\n$debuglog_str\n\n");
			}

			echo '<div style="z-index:99999;position:absolute;top:0;left:0;font-size:10px;font-family:Tahoma;font-weight:bold;background-color:#000;color:#FFF;cursor:pointer;cursor:hand;"';
			echo ' onclick="var s=document.getElementById(\'__s_debug__\').style;s.display=s.display==\'\'?\'none\':\'\';return false;">#</div>';
			echo '<div id="__s_debug__" style="z-index:99999;position:absolute;top:15px;left:10px;border:1px solid #888;background-color:#FFF;overflow:auto;width:800px;height:300px;display:none;">';
			echo '<pre style="text-align:left;padding:5px;margin:0;" class="s-debug">';
			echo get_debuglog_html($debuglog_str);
			echo '</pre></div>';
		}
	}

	##
	# = protected string render_result()
	# Render page to string. In most of cases, you don't need to call this method directly
	##
	protected function render_result()	// '
	{
		if (!array_key_exists('this_page', $this->vars)) {
			$this->vars['this_page'] = $this;
		}

		$tpl = new STemplate();
		$tpl->vars =& $this->vars;
		$tpl->controls =& $this->controls;

		$res = $tpl->process($this->template_name);

		if (strlen($this->design_page_name) && @file_exists($this->design_page_name))
		{
			$tpl = new STemplate();
			$tpl->vars =& $this->vars;
			$tpl->controls =& $this->controls;

			$tpl->vars['__content__'] = $res;
			$res = $tpl->process($this->design_page_name);
		}

		return $res;
	}

	protected function render()
	{
		$this->output_result($this->render_result());
		/* ob_flush(); */
	}

	protected function error_handler()
	{
		$tpl =& new STemplate();
		$tpl->vars =& $this->vars;
		$tpl->controls =& $this->controls;

		$tpl->vars['__error__'] = $this->_error_message;

		$res = $tpl->process($this->error_page_name);
		$this->output_result($res);
	}

	protected function redirect_handler()
	{
		header('location: ' . $this->_redirect);
		echo ' ';
	}

	protected function process_flow()
	{
		switch ($this->_flow)
		{
			case PAGE_FLOW_ERROR: $this->error_handler(); break;
			case PAGE_FLOW_REDIRECT: $this->redirect_handler(); break;
			case PAGE_FLOW_RENDER: $this->render(); break;
		}
	}

	##
	# = protected void break_flow()
	##
	protected function break_flow()
	{
		$this->_flow = PAGE_FLOW_BREAK;
	}

	##
	# = protected void error(string $msg)
	# [$msg] Error message
	# Render error page with error message, instead of normal page flow
	##
	protected function error($msg)
	{
		$this->_error_message = $msg;
		$this->_flow = PAGE_FLOW_ERROR;
	}

	##
	# = protected void redirect(string $url)
	# [$url] Redirect location
	# Redirect to new location, instead of normal page flow
	##
	protected function redirect($url)
	{
		$this->_redirect = $url;
		$this->_flow = PAGE_FLOW_REDIRECT;
	}

	##
	# = void process()
	# Call this function to process page
	##
	public function process()
	{
		$this->_init();
		if ($this->_flow != PAGE_FLOW_NORMAL) {$this->process_flow(); return;}

		$this->_handle_forms();
		if ($this->_flow != PAGE_FLOW_NORMAL) {$this->process_flow(); return;}

		$this->_pre_render();
		if ($this->_flow != PAGE_FLOW_NORMAL) {$this->process_flow(); return;}

		$this->render();
	}
}
##
# .end
##
