$undef = 'undefined';
$func = 'function';

Array.prototype.map = function(func)
{
	var res = [];
	for (var i = 0; i < this.length; i++) res.push(func(this[i]));
	return res;
}

Array.prototype.to_hash = function()
{
	var res = {};
	for (var i = 0; i < this.length; i++) res[this[i]] = true;
	return res;
}

//
// collect is NOT same as collect in ruby (which is equal to map)
// this collect is opposite to reject from fuby
//
Array.prototype.collect = function(func)
{
	var res = [];

	for (var i = 0; i < this.length; i++) {
		if (func(this[i])) {
			res.push(this[i]);
		}
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

	// thanks goes to http://www.json.org/json2.js, but here is more correct version (original version don't escape russian characters)
	var json_escapable = /[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0100-\uffff]/g;
	var json_meta = { '\b':'\\b', '\t':'\\t', '\n':'\\n', '\f':'\\f', '\r':'\\r', '"' :'\\"', '\\':'\\\\' };

	return {
		error: function(msg)
		{
			alert('[Error occurred]\n\n' + msg);
		},

		dump: function(obj)
		{
			var str = '';
			for (var k in obj) str += k + ': ' + obj[k] + '\n';
			alert(str);
		},

		debug: function(msg)
		{
			if (debug_el === null)
			{
				setTimeout(function()
				{
					var cnt = S.get('__s_debug__');

					if (cnt === null)
					{
						cnt = S.create('DIV', { id:'__s_debug__', innerHTML: '<pre></pre>' }, { position:'absolute', top:'0', left:'0', backgroundColor:'#FFF' });
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

			debug_el.innerHTML += "\n" + S.html(msg)
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
				S.error('Unable find XMLHttpRequest or it ActiveX alalog.');
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

			if (!send_is_active)
			{
				for (var i = 0; i < begin_request.length; i++) begin_request[i]();
				send_is_active = true;
			}

			if (callback_func !== null)
			{
				req.onreadystatechange = function()
				{
					if (req.readyState == 4)
					{
						callback_func((req.status==200 || req.status==304) ? req.responseText : null)

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
					}
				};
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
					req.setRequestHeader('Content-length', data.length);
					req.setRequestHeader('Connection', 'close');
					req.send(data);
				}
				catch (ex) {}
			}

			if (callback_func !== null) return null;

			try
			{
				// sometimes error occurred here
				var status = req.status;

				if (req.status!=200 && req.status!=304)
				{
					if (_try_num < _MAX_TRIES) {
						S.send(url, data, callback_func, _try_num+1);
					} else {
						S.error('Error sending request to "{0}": {1}\n{2}'.format(url, req.status, req.responseText));
					}
				}
				else
				{
					return req.responseText;
				}
			}
			catch (ex)
			{
				S.error('Internal error while sending request to "{0}"'.format(url));
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
					S.error('Script "{0}" not found'.format(require_path + script_url));

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
						S.error('Can\'t include script "{0}". Probably error in script or HEAD tag is missing.'.format(script_url));

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
			var res = { top:0, left: 0 };

			while (el)
			{
				res.top += el.offsetTop;
				res.left += el.offsetLeft;
				el = el.offsetParent;
			}

			return res;
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

		html: function(str)
		{
			return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
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
						height: function() {
							return ((window.innerHeight && window.scrollMaxY) ? (window.innerHeight + window.scrollMaxY) : ((document.body.scrollHeight > document.body.offsetHeight) ? document.body.scrollHeight : document.body.offsetHeight));
						}
					}
				}()
			};
		}(),

		add_class: function(element, className)
		{
			var cls = String(element.className).split(' ').collect(function(s){ return !s.empty(); });
			var ncls = String(className).split(' ').collect(function(s){ return !s.empty(); });

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
			var cls = String(element.className).split(' ').collect(function(s){ return !s.empty(); });
			var rcls = String(className).split(' ').collect(function(s){ return !s.empty(); }).to_hash();

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
			var cls = String(element.className).split(' ').collect(function(s){ return !s.empty(); }).to_hash();
			var hcls = String(className).split(' ').collect(function(s){ return !s.empty(); });

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

			mask_el.style.height = S.info.page.height() + 'px';
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
					for (var k in obj) res.push("'" + S.js_encode(k) + "':" + S.serialize(obj[k]));
					return ('{' + res.join(',') + '}');
			}

			return '';
		},

		escape: function(str)
		{
			return escape(str).replace(/\+/g, '%2B');
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
					else S.alert('Error occurred' + (typeof(res)==null ? '' : '\n\n'+res));
				}

				if (typeof(params.last) != $undef) params.last();
			});
		},

		alert: function(msg, callback)
		{
			alert(msg);
			if (typeof(callback) != $undef) callback();
		},

		_new_element_id: function()
		{
			element_cnt++;
			return ('s-' + element_cnt);
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
			if (typeof(event) == 'undefined') event = _event;
			return _func.apply(_this, _args);
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
		if (typeof(window['__global_guid_counter__']) == 'undefined') window['__global_guid_counter__'] = 0;
		window['__global_guid_counter__']++;
		return String(window['__global_guid_counter__']) + (new Date()).valueOf();
	}
}

function SException()
{
	$extend(this, SClass);

	this.message = '';

	this.init = function(message)
	{
		if (typeof(message) != $undef) this.message = message;
	}
}
