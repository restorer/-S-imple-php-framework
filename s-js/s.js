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
 * [S]imple
 * http://sourceforge.net/projects/zame-simple
 */

$undef = 'undefined';
$func = 'function';

Number.prototype.format = function(format)
{
	format = String(format).trim();
	if (format == '') return this.toString();

	var type = (format.match(/[a-zA-Z]+/) || '');
	if (type == '') return this.toString();

	var disp_sign = (format.charAt(0) == '+');
	var int_pad = (/^[\+]?[0]/.test(format) ? '0' : ' ');
	var int_sz = Number((format.match(/^[\+]?[0]*([0-9]+)/) || [0, -1])[1]);
	var frac_pad = (format.match(/^[^\.]*\.([0 ])/) || ['', ''])[1];
	var frac_sz = Number((format.match(/^[^\.]*\.[0 ]*([0-9]+)/) || [0, -1])[1]);

	var res;

	switch (format)
	{
		case 'b':
			res = this.toString(2);
			break;

		case 'o':
			res = this.toString(8);
			break;

		case 'x':
			res = this.toString(16).toLowerCase();
			break;

		case 'X':
			res = this.toString(16).toUpperCase();
			break;

		default:
			res = this.toString();
	}

	var sign_str = (res.match(/^[\-]/) || '+');
	var int_str = (res.match(/^[\-]?([^\.]+)/) || ['', '0'])[1];
	var frac_str = (res.match(/[^\.]*\.(.+)/) || ['', ''])[2];

	var res = ((disp_sign || sign_str=='-') ? sign_str : '');

	if (int_sz>0 && int_str.length<int_sz) {
		while (int_str.length < int_sz) {
			int_str = int_pad + int_str;
		}
	}

	res += int_str;

	if (frac_str!='' && frac_sz>0)
	{
		res += '.';

		if (frac_str.length > frac_sz)
		{
			frac_str = frac_str.substring(0, frac_sz);
		}
		else if (frac_str.length<frac_sz && frac_pad!='')
		{
			while (frac_str.length < frac_sz) {
				frac_str += frac_pad;
			}
		}

		res += frac_str;
	}

	return res;
}

String.prototype.empty = function()
{
	return (this.length == 0);
}

String.prototype.format = function()
{
	var res = this;

	for (var i = 0; i < arguments.length; i++) {
		res = res.replace(new RegExp('\\{' + i + '\\}', 'g'), String(arguments[i]));
	}

	return res;
}

String.prototype.trim = function(str)
{
	return this.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
}

String.prototype.ltrim = function(str)
{
	return this.replace(/^\s\s*/, '');
}

String.prototype.rtrim = function(str)
{
	return this.replace(/\s\s*$/, '');
}

Array.prototype.first = function() {
	return this[0];
}

Array.prototype.last = function() {
	return this[this.length - 1];
}

Array.prototype.map_apply = function(block)
{
	for (var i = 0; i < this.length; i++) {
		this[i] = block(this[i]);
	}

	return this;
}

Array.prototype.map = function(block)
{
	var res = [];

	for (var i = 0; i < this.length; i++) {
		res[i] = block(this[i]);
	}

	return res;
}

Array.prototype.reject_apply = function(block)
{
	var ind = 0;

	while (ind < this.length) {
		if (block(this[ind])) {
			this.splice(ind, 1);
		} else {
			ind++;
		}
	}

	return this;
}

Array.prototype.select = function(block)
{
	var res = [];

	for (var i = 0; i < this.length; i++) {
		if (block(this[i])) {
			res.push(this[i]);
		}
	}

	return res;
}

Array.prototype.empty = function() {
	return (this.length == 0);
}

Array.prototype.to_hash = function()
{
	var res = {};
	for (var i = 0; i < this.length; i++) res[this[i]] = true;
	return res;
}

Array.prototype.append = function(list)
{
	for (var i = 0; i < list.length; i++) {
		this.push(list[i]);
	}

	return this;
}

if (typeof([].indexOf) == $undef)
{
	Array.prototype.indexOf = function(item)
	{
		for (var i = 0; i < this.length; i++) {
			if (this[i] == item) {
				return i;
			}
		}

		return -1;
	}
}

Date.getNow = function() {
	return (new Date());
}

Date.prototype.format = function(fmt) {
	var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

	fmt = fmt.replace(/\{M\}/g, months[this.getMonth()]);
	fmt = fmt.replace(/\{dd\}/g, (this.getDate() < 10 ? '0' : '') + this.getDate());
	fmt = fmt.replace(/\{yyyy\}/g, this.getFullYear());

	return fmt;
}

window.location.getHost = function() {
	var l = window.location;
	var res = l.protocol + '//' + l.hostname;

	if ( l.port && !((l.protocol=='http' && l.port=='80') || (l.protocol=='https' && l.port=='443')) ) {
		res += ':' + l.port;
	}

	return res;
}

window.location.getUrl = function() {
	return (window.location.getHost() + window.location.pathname);
}

SL = function()
{
	var current_locale = 'en';
	var strings = {};

	return {
		get: function(key)
		{
			if (typeof(strings[current_locale]) == $undef) return key;
			if (typeof(strings[current_locale][key]) == $undef) return key;
			return strings[current_locale][key];
		},

		set: function(key, value, locale)
		{
			if (typeof(locale) == $undef) locale = current_locale;

			if (typeof(strings[locale]) == $undef) strings[locale] = {};
			strings[locale][key] = value;
		},

		set_locale: function(locale)
		{
			current_locale = locale;
		}
	};
}();

S = function()
{
	var MAX_TRIES = 8;

	var loaded_files = [];
	var already_required_files = [];
	var send_is_active = false;
	var send_queue = [];
	var get_xmlhttp_code = null;
	var debug_el = null;
	var mask_el = null;
	var element_cnt = 0;

	var require_path = '';
	var begin_request = [];
	var end_request = [];

	// thanks goes to http://www.json.org/json2.js, but here is more correct version (original version didn't escape russian characters)
	var json_escapable = /[\\\"\x00-\x1f\x7f-\xff\u00ad\u0100-\uffff]/g;	// "
	var json_meta = { '\b':'\\b', '\t':'\\t', '\n':'\\n', '\f':'\\f', '\r':'\\r', '"' :'\\"', '\\':'\\\\' };

	var err_el = null;
	var in_error = false;

	return {
		is_ie: (document.all && !window.opera),

		hash_append: function(hash_a, hash_b)
		{
		    var res = {};

		    for (var k in hash_a) {
		        if (hash_a.hasOwnProperty(k)) {
		            res[k] = hash_a[k];
		        }
		    }

		    for (var k in hash_b) {
		        if (hash_b.hasOwnProperty(k)) {
		            res[k] = hash_b[k];
		        }
		    }

		    return res;
		},

		error: function(msg)
		{
			if (in_error) return;

			in_error = true;
			S.mask();

			if (err_el == null)
			{
				err_el = S.create('DIV', { className:'s-error' });
				document.body.appendChild(err_el);
			}
			else
			{
				err_el.style.display = '';
			}

			err_el.innerHTML = [
				'<span onclick="S._hide_error()" class="s-error-close">&times;</span>&nbsp;<b class="s-error-title">' + SL.get('s/error_occurred') + '</b>',
				'<br /><br />',
				msg.replace(/\n/g, '<br />')
			].join('');

			if (S.is_ie) {
				err_el.style.top = document.body.scrollTop + 'px';
			}
		},

		_hide_error: function()
		{
			if (in_error && err_el)
			{
				err_el.style.display = 'none';
				S.unmask();
				in_error = false;
			}
		},

		dump: function(obj, dump_functions)
		{
			var str = '';

			for (var k in obj)
			{
				str += k + ': ';

				if (typeof(obj[k])!='function' || dump_functions) str += obj[k];
				else str += '{function}';

				str += '\n';
			}

			alert(str);
		},

		debug: function(msg)
		{
			msg = String(msg);

			if (debug_el === null)
			{
				setTimeout(function()
				{
					var cnt = S.get('__s_debug__');

					if (cnt === null)
					{
						document.body.appendChild(S.build([
							'<div style="z-index:99999;position:absolute;top:0;left:0;font-size:10px;font-family:Tahoma;',
								'font-weight:bold;background-color:#000;color:#FFF;cursor:pointer;cursor:hand;"',
								' onclick="var s=document.getElementById(\'__s_debug__\').style;',
								's.display=s.display==\'\'?\'none\':\'\';return false;">#</div>'
						].join(''))[0]);

						cnt = S.build([
							'<div id="__s_debug__" style="z-index:99999;position:absolute;top:15px;left:10px;',
							'border:1px solid #888;background-color:#FFF;overflow:auto;width:800px;height:300px;display:none;">',
							'<pre style="text-align:left;padding:5px;margin:0;"></pre></div>'
						].join(''))[0];

						document.body.appendChild(cnt);
					}

					var arr = cnt.getElementsByTagName('PRE');

					if (arr.length == 0)
					{
						debug_el = S.create('PRE');
						cnt.appendChild(debug_el);
					}
					else
					{
						debug_el = arr[0];
					}

					S.debug(msg);
				}, 1);

				return;
			}

			debug_el.innerHTML += (debug_el.innerHTML ? '<br />' : '') + S.html(msg);
			debug_el.parentNode.style.display = '';
		},

		set_require_path: function(req_path)
		{
			require_path = req_path;
		},

		add_begin_request_handler: function(func)
		{
			begin_request.push(func);
		},

		add_end_request_handler: function(func)
		{
			end_request.push(func);
		},

		get_xmlhttp: function()
		{
			var ex;
			var req = null;

			if (get_xmlhttp_code !== null)
			{
				eval(get_xmlhttp_code);
				return req;
			}

			if (window.XMLHttpRequest)
			{
				get_xmlhttp_code = 'req=new XMLHttpRequest()';
				eval(get_xmlhttp_code);
			}
			else
			if (window.ActiveXObject)
			{
				var msxmls = ['Msxml2.XMLHTTP.5.0', 'Msxml2.XMLHTTP.4.0', 'Msxml2.XMLHTTP.3.0', 'Msxml2.XMLHTTP', 'Microsoft.XMLHTTP'];

				for (var i = 0; i < msxmls.length; i++)
				{
					try
					{
						get_xmlhttp_code = "req=new ActiveXObject('{0}')".format(msxmls[i]);
						eval(get_xmlhttp_code);
						break;
					}
					catch (ex)
					{
						get_xmlhttp_code = null;
					}
				}
			}

			if (req===null || req===false)
			{
				S.error(SL.get('s/no_xmlhttp'));
				return null;
			}

			return req;
		},

		send_in_process: function()
		{
			return send_is_active;
		},

		//
		// if callback_func is undefined or null, non-async request performed
		//
		send: function(url, data, callback_func, _try_num)
		{
			var ex;

			if (typeof(callback_func) == $undef) callback_func = null;
			if (typeof(_try_num) == $undef) _try_num = null;

			if (callback_func!==null && send_is_active && _try_num==null)
			{
				send_queue.push({ url:url, data:data, callback_func:callback_func });
				return;
			}

			var req = S.get_xmlhttp();
			if (req === null) return null;
			if (_try_num == null) _try_num = 1;

			if (!send_is_active && callback_func!==null)
			{
				for (var i = 0; i < begin_request.length; i++) begin_request[i]();
				send_is_active = true;
			}

			req.open((data === false ? 'GET' : 'POST'), url, (callback_func!==null ? true : false));

			if (data === false)
			{
				try {
					req.send(null);
				} catch (ex) {}
			}
			else
			{
				try
				{
					req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
					// req.setRequestHeader('Content-length', data.length); - chrome says that this is unsafe header
					// req.setRequestHeader('Connection', 'close'); - chrome says that this is unsafe header
					req.send(data);
				}
				catch (ex) {}
			}

			if (callback_func !== null)
			{
				// weird bug with FireBug console fixed (thanks to http://www.ghastlyfop.com/blog/2007/01/onreadystate-changes-in-firefox.html)

				req.onreadystatechange = function()
				{
					if (req.readyState == 4)
					{
						if (send_queue.length != 0)
						{
							var msg = send_queue.shift();
							S.send(msg.url, msg.data, msg.callback_func, 1);
						}
						else
						{
							for (var i = 0; i < end_request.length; i++) end_request[i]();
							send_is_active = false;
						}

						try {
							callback_func((req.status==200 || req.status==304) ? req.responseText : null)
						} catch (ex) {
							S.error(SException.get_readable_error(ex));
						}
					}
				}

				return null;
			}

			try
			{
				// sometimes error occurred here
				var status = req.status;

				if (req.status!=200 && req.status!=304)
				{
					if (_try_num < _MAX_TRIES) {
						S.send(url, data, callback_func, _try_num+1);
					} else {
						S.error(SL.get('s/error_sending').format(url, req.status, req.responseText));
					}
				}
				else
				{
					return req.responseText;
				}
			}
			catch (ex)
			{
				S.error(SL.get('s/internal_error_sending').format(url));
			}

			return null;
		},

		get: function(id)
		{
			return document.getElementById(id);
		},

		style: function(id)
		{
			var element = S.get(id);
			return (element ? element.style : null);
		},

		create: function(tag_name, attrs, styles)
		{
			var element = document.createElement(tag_name.toUpperCase());

			if (element)
			{
				if (typeof(attrs)!=$undef && attrs!=null) {
					for (var k in attrs) element[k] = attrs[k];
				}

				if (typeof(styles)!=$undef && styles!=null) {
					for (var k in styles) element.style[k] = styles[k];
				}
			}

			return element;
		},

		append_childs: function(el, childs)
		{
			for (var i = 0; i < childs.length; i++) {
				el.appendChild(childs[i]);
			}
		},

		require: function(script_url, callback_func)
		{
			script_url = String(script_url);

			if (typeof(loaded_files[script_url]) != $undef)
			{
				if (typeof(callback_func) != $undef) callback_func(true);
				return;
			}

			if (typeof(already_required_files[script_url]) != $undef)
			{
				if (typeof(callback_func) != $undef) {
					already_required_files[script_url].push(callback_func);
				}

				return;
			}

			already_required_files[script_url] = [];

			if (typeof(callback_func) != $undef) {
				already_required_files[script_url].push(callback_func);
			}

			S.send(require_path + script_url, false, function(script_text)
			{
				if (script_text == null)
				{
					S.error(SL.get('s/script_not_found').format(require_path + script_url));

					var callbacks = already_required_files[script_url];
					for (var i = 0; i < callbacks.length; i++) callbacks[i](false);

					return;
				}

				if (script_text != '')
				{
					var ex;
					var script_element = S.create('script', { type:'text/javascript', text:script_text });

					if (typeof(script_element.src) != $undef)
					{
						try {
							delete script_element.src;
						} catch (ex) {}
					}

					try
					{
						document.getElementsByTagName('HEAD')[0].appendChild(script_element);
						document.getElementsByTagName('HEAD')[0].removeChild(script_element);

						loaded_files[script_url] = true;
					}
					catch (ex)
					{
						S.error(SL.get('s/cant_include_script').format(script_url));

						var callbacks = already_required_files[script_url];
						for (var i = 0; i < callbacks.length; i++) callbacks[i](false);

						return;
					}

					var callbacks = already_required_files[script_url];
					for (var i = 0; i < callbacks.length; i++) callbacks[i](true);
				}
			});
		},

		depend: function(scripts, callback_func)
		{
			for (var i = 0; i < scripts.length; i++)
			{
				if (typeof(loaded_files[scripts[i]]) == $undef)
				{
					S.require(scripts[i], function(res){
						if (res) S.depend(scripts, callback_func);
					});

					return;
				}
			}

			callback_func();
		},

		rm_childs: function(el)
		{
			while (el.firstChild) el.removeChild(el.firstChild);
		},

		pos: function(el)
		{
			var res = { top:0, left:0 };

			while (el)
			{
				res.top += el.offsetTop;
				res.left += el.offsetLeft;
				el = el.offsetParent;
			}

			return res;
		},

		mouse_pos: function(ev)
		{
			return ((ev.pageX || ev.pageY) ? {
				left: ev.pageX,
				top: ev.pageY
			} : {
				left: (ev.clientX + document.body.scrollLeft + document.documentElement.scrollLeft),
				top: (ev.clientY + document.body.scrollTop + document.documentElement.scrollTop)
			});
		},

		add_handler: function(element, event_name, event_handler)
		{
			if (element.addEventListener) element.addEventListener(event_name, event_handler, false);
			else
			if (element.attachEvent) element.attachEvent('on' + event_name, event_handler);
		},

		rm_handler: function(element, event_name, event_handler)
		{
			if (element.removeEventListener) element.removeEventListener(event_name, event_handler, false);
			else
			if (element.detachEvent) element.detachEvent('on' + event_name, event_handler);
		},

		stop_event: function(ev)
		{
			ev.cancelBubble = true;
			if (ev.stopPropagation) ev.stopPropagation();
		},

		html: function(str)
		{
			if (typeof(str) == $undef) throw $new(SException, 'str is undefined');
			return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');	// "
		},

		unhtml: function(str)
		{
			if (typeof(str) == $undef) throw $new(SException, 'str is undefined');
			return str.replace(/&quot;/g, '"').replace(/&gt;/g, '>').replace(/&lt;/g, '<').replace(/&amp;/g, '&');
		},

		current_style: function(el, prop)
		{
			if (el.currentStyle)
			{
				var spl = prop.split('-');

				prop = spl[0];
				for (var i = 1; i < spl.length; i++) prop += spl[i].substring(0, 1).toUpperCase() + spl[i].substring(1);

				return el.currentStyle[prop];
			}
			else
			if (window.getComputedStyle) {
				return document.defaultView.getComputedStyle(el, null).getPropertyValue(prop);
			}

			return '';
		},

		info: function() {
			return {
				window: function() {
					return {
						width: function() {
							return (window.innerWidth ? window.innerWidth : document.body.offsetWidth);
						},
						height: function() {
							return (window.innerHeight ? window.innerHeight : document.body.offsetHeight + 24);
						}
					};
				}(),
				page: function() {
					return {
						width: function() {
							return ((window.innerWidth && window.scrollMaxX) ? (window.innerWidth + window.scrollMaxX) : ((document.body.scrollWidth > document.body.offsetWidth) ? document.body.scrollWidth : document.body.offsetWidth));
						},
						height: function() {
							return ((window.innerHeight && window.scrollMaxY) ? (window.innerHeight + window.scrollMaxY) : ((document.body.scrollHeight > document.body.offsetHeight) ? document.body.scrollHeight : document.body.offsetHeight));
						}
					}
				}()
			};
		}(),

		add_class: function(element, className)
		{
			var cls = String(element.className).split(' ').select(function(s){ return !s.empty(); });
			var ncls = String(className).split(' ').select(function(s){ return !s.empty(); });

			var already = cls.to_hash();

			for (var i = 0; i < ncls.length; i++)
			{
				var cl = ncls[i];

				if (typeof(already[cl]) == $undef)
				{
					cls.push(cl);
					already[cl] = true;
				}
			}

			element.className = cls.join(' ');
		},

		rm_class: function(element, className)
		{
			var cls = String(element.className).split(' ').select(function(s){ return !s.empty(); });
			var rcls = String(className).split(' ').select(function(s){ return !s.empty(); }).to_hash();

			var res = [];

			for (var i = 0; i < cls.length; i++)
			{
				var cl = cls[i];
				if (typeof(rcls[cl]) == $undef) res.push(cl);
			}

			element.className = res.join(' ');
		},

		has_class: function(element, className)
		{
			var cls = String(element.className).split(' ').select(function(s){ return !s.empty(); }).to_hash();
			var hcls = String(className).split(' ').select(function(s){ return !s.empty(); });

			for (var i = 0; i < hcls.length; i++) {
				if (typeof(cls[hcls[i]]) == $undef) {
					return false;
				}
			}

			return true;
		},

		build: function(html)
		{
			var tmp_el = S.create('DIV', { innerHTML: html });
			document.body.appendChild(tmp_el);

			var res = [];
			for (var i = 0; i < tmp_el.childNodes.length; i++) res.push(tmp_el.childNodes[i]);

			document.body.removeChild(tmp_el);
			tmp_el = null;

			return res;
		},

		mask: function()
		{
			if (mask_el == null)
			{
				mask_el = S.create('DIV', { className:'s-mask' });
				document.body.appendChild(mask_el);
			}
			else
			{
				mask_el.style.display = '';
			}

			if (S.is_ie) {
				mask_el.style.height = S.info.page.height() + 'px';
			}
		},

		unmask: function()
		{
			if (mask_el != null) {
				mask_el.style.display = 'none';
			}
		},

		deserialize: function(str)
		{
			return eval('(' + str + ')');
		},

		// thanks goes to http://www.json.org/json2.js
		json_quote: function(str)
		{
			json_escapable.lastIndex = 0;

			if (json_escapable.test(str))
			{
				return '"' + str.replace(json_escapable, function(val){
					var ch = json_meta[val];
					return (typeof(ch)=== 'string' ? ch : ('\\u' + ('0000' + val.charCodeAt(0).toString(16)).slice(-4)));
				}) + '"';
			}
			else
			{
				return '"' + str + '"';
			}
		},

		serialize: function(obj)
		{
			if (obj === null) return 'null';

			switch (typeof(obj))
			{
				case 'function':
					return 'null';

				case 'string':
					return S.json_quote(obj);

				case 'number':
					return (isFinite(obj) ? String(obj) : 'null');

				case 'boolean':
					return (obj ? 'true' : 'false');

				case 'object':
					if (Object.prototype.toString.apply(obj) === '[object Array]')
					{
						var res = [];
						for (var i = 0; i < obj.length; i++) res.push(S.serialize(obj[i]));
						return ('[' + res.join(',') + ']');
					}

					var res = [];
					for (var k in obj) res.push(S.json_quote(k) + ":" + S.serialize(obj[k]));
					return ('{' + res.join(',') + '}');
			}

			return '';
		},

		escape: function(str)
		{
			return encodeURIComponent(String(str));
		},

		call: function(method_path, params)
		{
			var spl = method_path.split('|');
			var url = spl[0].trim();
			var method = spl[1].trim();

			var args_str = ((typeof(params.args)!=$undef && params.args!==null) ? S.serialize(params.args) : '[]');
			var data = '__s_ajax_method={0}&__s_ajax_args={1}'.format(S.escape(method), S.escape(args_str));

			S.send(url, data, function(res)
			{
				if (res!=null && res.indexOf('succ:')==0)
				{
					res = res.substr(5);
					res = (res.length==0 ? null : S.deserialize(res));

					if (typeof(params.succ) != $undef) params.succ(res);
				}
				else
				{
					if (res!=null && res.indexOf('fail:')==0) res = res.substr(5);

					if (typeof(params.fail) != $undef) params.fail(res);
					else S.alert(SL.get('s/error_occurred') + (typeof(res)==null ? '' : '\n\n'+res));
				}

				if (typeof(params.last) != $undef) params.last();
			});
		},

		alert: function(msg, callback)
		{
			alert(msg);
			if (typeof(callback) != $undef) callback();
		},

		confirm: function(msg, params)
		{
			if (confirm(msg)) {
				if (typeof(params.succ) != $undef) params.succ();
			} else {
				if (typeof(params.fail) != $undef) params.fail();
			}

			if (typeof(params.last) != $undef) params.last();
		},

		_new_element_id: function()
		{
			element_cnt++;
			return ('s-' + element_cnt);
		},

		background_submit: function(form, url, params)
		{
			var frame_id = S._new_element_id();
			var frame_el = S.create('IFRAME', { id:frame_id, name:frame_id }, { display:'none' });
			if (S.is_ie) frame_el.src = 'javascript:false';	// taken from ExtJS
			document.body.appendChild(frame_el);
			if (S.is_ie) document.frames[frame_id].name = frame_id;	// taken from ExtJS

			function on_frame_loaded()
			{
				var res = '';
				var ex;

				try {
					res = (S.is_ie ? frame_el.contentWindow.document : frame_el.contentDocument).body.innerHTML;
				} catch (ex) {}

				if (typeof(params.callback) != $undef)
				{
					params.callback(res);
				}
				else
				{
					if (res.indexOf('succ:') == 0)
					{
						res = res.substr(5);
						res = (res.length==0 ? null : S.deserialize(res));

						if (typeof(params.succ) != $undef) params.succ(res);
					}
					else
					{
						if (res.indexOf('fail:') == 0) res = res.substr(5);

						if (typeof(params.fail) != $undef) params.fail(res);
						else S.alert(SL.get('s/error_occurred') + (typeof(res)==null ? '' : '\n\n'+res));
					}

					if (typeof(params.last) != $undef) params.last();
				}

				S.rm_handler(frame_el, 'load', on_frame_loaded);
				setTimeout(function(){ document.body.removeChild(frame_el); }, 1);
			}

			form.action = url;
			form.target = frame_id;
			S.add_handler(frame_el, 'load', on_frame_loaded);
			form.submit();
		},

		// thanks goes to http://javascript.nwbox.com/cursor_position/
		get_selection_start: function(el)
		{
			if (el.createTextRange)
			{
				var rng = document.selection.createRange().duplicate();
				if (rng.parentElement() != el) return el.value.length;

				rng.moveEnd('character', el.value.length);
				return (rng.text=='' ? el.value.length : el.value.lastIndexOf(rng.text));
			}
			else
			{
				return el.selectionStart;
			}
		},

		// thanks goes to http://javascript.nwbox.com/cursor_position/
		get_selection_end: function(el)
		{
			if (el.createTextRange)
			{
				var rng = document.selection.createRange().duplicate()
				rng.moveStart('character', -el.value.length)
				return rng.text.length;
			}
			else
			{
				return el.selectionEnd;
			}
		},

		set_cookie: function(name, value, expires, path)
		{
			var res = [S.escape(name) + '=' + S.escape(value)];

			if (typeof(expires)!=$undef && expires)
			{
				if (typeof(expires.match) != $undef)
				{
					var mt = expires.match(/(\+|\-)\s*(\d+)\s*([a-z]+)/i);

					if (mt)
					{
						var mult = (mt[1] === '-' ? -1 : 1);
						var num = Number(mt[2]);

						switch (mt[3])
						{
							case 'day':
							case 'days':
								num *= 24;
								// no break here

							case 'hour':
							case 'hours':
								num *= 60;
								// no break here

							case 'min':
							case 'mins':
							case 'minute':
							case 'minutes':
								num *= 60;
								// no break here

							case 'sec':
							case 'secs':
							case 'second':
							case 'seconds':
								num *= 1000;
						}

						var dt = new Date();
						dt.setTime(dt.getTime() + num * mult);

						res.push('expires=' + dt.toGMTString());
					}
				}
				else
				{
					res.push('expires=' + expires.toGMTString());
				}
			}

			if (typeof(path)!=$undef && path) {
				res.push('path=' + S.escape(path));
			} else {
				res.push('path=/');
			}

			document.cookie = res.join('; ');
		},

		rm_cookie: function(name)
		{
			S.set_cookie(name, '', '-1 day');
		},

		get_cookie: function(name)
		{
			var list = document.cookie.split(';');

			for (var i = 0; i < list.length; i++)
			{
				var pair = list[i].trim().split('=');

				if (unescape(pair[0]) == name) {
					return unescape(pair[1]);
				}
			}

			return null;
		}
	};
}();

function $void() {}

//
// some_object.prototype sucks, multiple inheritance - rules
//
function $extend(obj, base)
{
	base.call(obj);
}

function $new(obj_class)
{
	var obj = new obj_class();

	if (typeof(obj.init) != $undef)
	{
		var args = [];
		for (var i = 1; i < arguments.length; i++) args.push(arguments[i]);

		obj.init.apply(obj, args);
	}

	return obj;
}

function SClass()
{
	this.delegate = function(func)
	{
		var _func = func;
		var _this = this;

		var _args = [];
		for (var i = 1; i < arguments.length; i++) _args.push(arguments[i]);

		return function(_event)
		{
			var args = [typeof(_event)==$undef ? event : _event];
			for (var i = 0; i < _args.length; i++) args.push(_args[i]);
			return _func.apply(_this, args);
		};
	}

	this.delegate_ne = function(func)
	{
		var _func = func;
		var _this = this;

		var _args = [];
		for (var i = 1; i < arguments.length; i++) _args.push(arguments[i]);

		return function()
		{
			var args = [];
			for (var i = 0; i < _args.length; i++) args.push(_args[i]);
			for (var i = 0; i < arguments.length; i++) args.push(arguments[i]);
			return _func.apply(_this, args);
		};
	}

	this.new_uid = function()
	{
		if (typeof(window['__global_guid_counter__']) == $undef) window['__global_guid_counter__'] = 0;
		window['__global_guid_counter__']++;
		return String(window['__global_guid_counter__']) + (new Date()).valueOf();
	}
}

function SException()
{
	$extend(this, SClass);

	this.message = '';
	this.stack = [];

	this.init = function(message)
	{
		this.stack = SException.get_stack_trace().slice(4);
		if (typeof(message) != $undef) this.message = message;
	}

	this.toString = function()
	{
		return '"' + this.message + '"\n\n' + this.stack.join('\n') + '\n';
	}
}

SException.get_readable_error = function(ex)
{
    if (ex instanceof SException) {
        return ex.toString();
    } else {
        return SException.get_ex_stack_trace(ex).join("\n");
    }
}

SException.get_ex_stack_trace = function(ex)
{
    if (ex.stack)
	{
		var lines = ex.stack.split('\n').slice(1);
		if (lines.length>0 && lines[lines.length-1].length==0) lines = lines.slice(0, lines.length-1);

		for (var i = 0; i < lines.length; i++)
		{
			var ind = lines[i].lastIndexOf('@');
			if (ind <= 0) continue;

			var args = lines[i].substr(0, ind);
			var where = lines[i].substr(ind + 1);

			if (args.charAt(0) == '(') args = '{anonymous}' + args;

			lines[i] = where + ' at ' + args;
		}

		return lines;
	}
	else if (window.opera)
	{
		var lines = ex.message.split('\n');
		var re = /Line\s+(\d+).*?in\s+(http\S+)(?:.*?in\s+function\s+(\S+))?/i;
		var i, j, len;

		for (i=4, j=0, len=lines.length; i < len; i += 2) {
			if (re.test(lines[i])) {
				lines[j++] = (RegExp.$3 ? (RegExp.$3 + '()@' + RegExp.$2 + RegExp.$1) : ('{anonymous}' + RegExp.$2 + ':' + RegExp.$1)) + ' -- ' + lines[i+1].replace(/^\s+/, '');
			}
		}

		lines.splice(j, lines.length - j);
		return lines;
	}
	else
	{
		var curr = arguments.callee.caller;
		var re = /function\s*([\w\-$]+)?\s*\(/i;
		var stack = [];
		var j = 0;
		var fn, args, i;

		while (curr)
		{
			fn = (re.test(curr.toString()) ? (RegExp.$1 || '{anonymous}') : '{anonymous}');
			args = stack.slice.call(curr.arguments);
			i = args.length;

			while (i--)
			{
				switch (typeof args[i])
				{
					case 'string':
						args[i] = '"' + args[i].replace(/"/g, '\\"') + '"';		// "
						break;

					case 'function':
						args[i] = 'function';
						break;
				}
			}

			stack[j++] = fn + '(' + args.join() + ')';
			curr = curr.caller;
		}

		return stack;
	}
}

// thanks goes to http://eriwen.com/javascript/js-stack-trace/ and http://pastie.org/253058.txt
SException.get_stack_trace = function()
{
	var ex;

	try {
		(0)();
	} catch (ex) {
		return SException.get_ex_stack_trace(ex);
	}

	return ['ERROR'];
}

//
// Locales
//

SL.set('s/error_occurred', 'Error occurred', 'en');
SL.set('s/no_xmlhttp', 'Unable find XMLHttpRequest or it ActiveX alalog.', 'en');
SL.set('s/error_sending', 'Error sending request to "{0}": {1}\n{2}', 'en');
SL.set('s/internal_error_sending', 'Internal error while sending request to "{0}"', 'en');
SL.set('s/script_not_found', 'Script "{0}" not found', 'en');
SL.set('s/cant_include_script', 'Can\'t include script "{0}". Probably error in script or HEAD tag is missing.', 'en');

SL.set('s/error_occurred', 'Произошла ошибка', 'ru');
SL.set('s/no_xmlhttp', 'Ваш браузер не поддерживает технологию XMLHttpRequest и её ActiveX аналог.', 'ru');
SL.set('s/error_sending', 'Произошла ошибка при отправлении данных в "{0}": {1}\n{2}', 'ru');
SL.set('s/internal_error_sending', 'Произошла внутренняя ошибка при отправлении данных в "{0}"', 'ru');
SL.set('s/script_not_found', 'Скрипт "{0}" не найден', 'ru');
SL.set('s/cant_include_script', 'Произошла ошибка при подключении скрипта "{0}". Возможно отсутствует тэг HEAD.', 'ru');
