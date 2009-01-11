SL.set('interface/select_file', 'Select file ...', 'en');

SL.set('interface/select_file', 'Выбрать файл ...', 'ru');

SElement = function()
{
	$extend(this, SClass);

	this._container = null;
	this._dom = null;
	this._tmp = {};
	this._in_render = false;

	/*
	 * _render_dom()
	 * optional _update_dom()
	 * get_value
	 * set_value(value)
	 */

	this._tmp_get = function(key, def)
	{
		if (typeof(def) == $undef) def = null;
		return (typeof(this._tmp[key])==$undef ? def : this._tmp[key]);
	}

	this.render = function(container)
	{
		this._in_render = true;

		if (this._dom === null)
		{
			this._render_dom();
		}
		else if (this._container !== null)
		{
			if (typeof(container._remove_element) != $undef) {
				container._remove_element(this);
			} else {
				this._container.removeChild(this._dom);
			}
		}

		this._container = container;

		if (typeof(container._append_element) != $undef) {
			container._append_element(this);
		} else {
			this._container.appendChild(this._dom);
		}

		if (typeof(this._update_dom) != $undef) {
			this._update_dom();
		}

		this._in_render = false;
	}

	this.dom = function()
	{
		return this._dom;
	}

	this.set_width = function(width)
	{
		if (this._dom !== null) {
			this._dom.style.width = (width===null ? 'auto' : (String(width) + 'px'));
		}
	}

	this._do_get = function(property)
	{
		return (this._dom===null ? this['_'+property] : this._dom[property]);
	}

	this._do_set = function(property, value)
	{
		if (this._dom === null) {
			this['_'+property] = value;
		} else {
			this._dom[property] = value;
		}
	}

	this._ro_set = function(property, value)
	{
		if (this._dom !== null) {
			throw $new(SException, 'Property "{0}" can\'t be changed after rendering element'.format(property));
		}

		this['_'+property] = value;
	}
}

SHtmlElement = function()
{
	$extend(this, SElement);

	this.text = '';

	this.init = function(text)
	{
		this._text = ((typeof(text)==$undef || text===null) ? '' : String(text));
	}

	this._render_dom = function()
	{
		this._dom = S.create('DIV', { innerHTML: this._text });
	}

	this.get_value = function()
	{
		if (this._dom !== null) this._text = this._dom.innerHTML;
		return this._text;
	}

	this.set_value = function(text)
	{
		this._text = text;
		if (this._dom !== null) this._dom.innerHTML = text;
	}

	this.dom_input = function()
	{
		return null;
	}
}

SButton = function()
{
	$extend(this, SElement);

	this._dom_text = null;
	this._text = '';
	this._click_handler = null;
	this._inner_cls = '';
	this._enabled = true;

	this.cls_normal = 's-btn';
	this.cls_hover = 's-btn-hover';
	this.cls_pressed = 's-btn-pressed';
	this.cls_disabled = 's-btn-disabled';

	this.init = function(text, click_handler, inner_cls)
	{
		this._text = ((typeof(text)==$undef || text===null) ? '' : String(text));
		this._click_handler = (typeof(click_handler)==$undef ? null : click_handler);
		this._inner_cls = ((typeof(inner_cls)==$undef || inner_cls===null) ? '' : String(inner_cls));
	}

	this._render_dom = function()
	{
		this._dom = S.build([
			'<div class="{0}">'.format(this.cls_normal),
				'<div class="s-btn-before"></div>',
				'<div class="s-btn-inner"><span>{0}</span></div>'.format(this._text),
				'<div class="s-btn-after"></div>',
			'</div>'
		].join(''))[0];
	}

	this._update_dom = function()
	{
		this._dom.__s_el = this;

		this._dom.onmouseover = SButton.on_mouse_over;
		this._dom.onmouseout = SButton.on_mouse_out;
		this._dom.onmousedown = SButton.on_mouse_down;
		this._dom.onmouseup = SButton.on_mouse_up;
		this._dom.onclick = SButton.on_click;

		this._dom_text = this._dom.childNodes[1].childNodes[0];

		S.add_class(this._dom_text, this._inner_cls);
		if (!this._enabled) S.add_class(this._dom, this.cls_disabled);
	}

	this.get_value = function()
	{
		return this._text;
	}

	this.set_value = function(text)
	{
		this._text = text;
		if (this._dom_text !== null) this._dom_text.innerHTML = text;
	}

	this.set_click_handler = function(click_handler)
	{
		this._click_handler = click_handler;
		if (this._dom !== null) this._dom.onclick = (click_handler==null ? $void : click_handler);
	}

	this.get_inner_cls = function()
	{
		return this._inner_cls;
	}

	this.set_inner_cls = function(inner_cls)
	{
		this._inner_cls = inner_cls;
		if (this._dom !== null) S.add_class(this._dom_text, this._inner_cls);
	}

	this.get_enabled = function()
	{
		return this._enabled;
	}

	this.set_enabled = function(enabled)
	{
		this._enabled = enabled;

		if (this._dom !== null)
		{
			if (enabled) {
				S.rm_class(this._dom, this.cls_disabled);
			} else {
				S.add_class(this._dom, this.cls_disabled);
				S.rm_class(this._dom, this.cls_hover + ' ' + this.cls_pressed);
			}
		}
	}
};

SButton.on_mouse_over = function()
{
	if (this.__s_el._enabled) {
		S.add_class(this, this.__s_el.cls_hover);
	}
}

SButton.on_mouse_out = function()
{
	if (this.__s_el._enabled) {
		S.rm_class(this, this.__s_el.cls_hover + ' ' + this.__s_el.cls_pressed);
	}
}

SButton.on_mouse_down = function()
{
	if (this.__s_el._enabled) {
		S.rm_class(this, this.__s_el.cls_hover);
		S.add_class(this, this.__s_el.cls_pressed);
	}
}

SButton.on_mouse_up = function()
{
	if (this.__s_el._enabled) {
		S.rm_class(this, this.__s_el.cls_pressed);
		S.add_class(this, this.__s_el.cls_hover);
	}
}

SButton.on_click = function()
{
	if (this.__s_el._enabled) {
		if (this.__s_el._click_handler != null) {
			this.__s_el._click_handler();
		}
	}
}

SToolbarButton = function()
{
	$extend(this, SButton);

	this.cls_normal = 's-tb-btn';
	this.cls_hover = 's-tb-btn-hover';
	this.cls_pressed = 's-tb-btn-pressed';
}

SInputElement = function()
{
	$extend(this, SElement);

	this.dom_input = function()
	{
		return this._dom;
	}
}

SInput = function()
{
	$extend(this, SInputElement);

	this._type = 'text';
	this._name = '';
	this._value = '';
	this._params = {};

	this.init = function(type, name, value, params)
	{
		this._type = ((typeof(type)==$undef || type===null) ? 'text' : String(type));
		this._name = ((typeof(name)==$undef || name===null) ? '' : String(name));
		this._value = ((typeof(value)==$undef || value===null) ? '' : String(value));
		this._params = ((typeof(params)==$undef || params===null) ? {} : params);
	}

	this._render_dom = function()
	{
		if (this._type == 'textarea')
		{
			var rows = (typeof(this._params.rows)==$undef ? 4 : this._params.rows);
			var cols = (typeof(this._params.cols)==$undef ? 40 : this._params.cols);

			this._dom = S.build([
				'<textarea class="s-inp s-inp-textarea" name="{0}" rows="{1}" cols="{2}"'.format(this._name, rows, cols),
					' onfocus="S.add_class(this,\'s-inp-focus\')"',
					' onblur="S.rm_class(this,\'s-inp-focus\')">',
				'</textarea>'
			].join(''))[0];
		}
		else
		{
			this._dom = S.build([
				'<input class="s-inp" type="{0}" name="{1}"'.format(this._type, this._name),
					' onfocus="S.add_class(this,\'s-inp-focus\')"',
					' onblur="S.rm_class(this,\'s-inp-focus\')" />'
			].join(''))[0];
		}

		this._dom.value = this._value;
	}

	this.set_type = function(type)
	{
		this._ro_set('type', type);
	}

	this.set_name = function(name)
	{
		this._ro_set('name', name);
	}

	this.get_value = function()
	{
		return this._do_get('value');
	}

	this.set_value = function(value)
	{
		this._do_set('value', value);
	}
};

SInputFile = function()
{
	$extend(this, SInputElement);

	this._name = '';
	this._value = '';
	this._params = {};

	this._dom_el = null;
	this._dom_text = null;
	this._el_button = null;

	this.init = function(name, value, params)
	{
		this._name = ((typeof(name)==$undef || name===null) ? '' : String(name));
		this._value = ((typeof(value)==$undef || value===null) ? '' : String(value));
		this._params = ((typeof(params)==$undef || params===null) ? {} : params);
	}

	this._render_dom = function()
	{
		this._dom = S.build([
			'<span class="s-inp-file">',
				'<div class="s-inp-file-cont">',
					'<div></div>',
					'<label class="s-inp-file-label">',
						'<input size="1" class="s-inp-file-input" type="file" name="{0}" />'.format(this._name),
					'</label>',
				'</div>',
				'<span class="s-inp-file-text"></span>',
			'</span>'
		].join(''))[0];

		this._dom_el = this._dom.childNodes[0].childNodes[1].childNodes[0];
		this._dom_text = this._dom.childNodes[1];
	}

	this._update_dom = function()
	{
		this._dom.onchange = this.delegate(this._on_change);

		this._el_button = $new(SButton, SL.get('interface/select_file'));
		this._el_button.render(this._dom.childNodes[0]);
	}

	this._on_change = function(ev)
	{
		var name = this._dom_el.value.replace(/\\/, '/');

		var ind = name.lastIndexOf('/');
		if (ind >= 0) name = name.substr(ind + 1);

		this._dom_text.innerHTML = S.html(name);
	}

	this.dom_input = function()
	{
		return null;
	}

	this.get_value = function()
	{
		return (this._dom_el === null ? '' : this._dom_el.value);
	}

	this.set_value = function(value)
	{
		if (this._dom_el!==null && value=='')
		{
			var ex;

			try {
				this._dom_el.value = '';
			} catch (ex) {}

			this._on_change();
		}
	}

	this.set_width = function(width)
	{
		// do nothing
	}
}

SCheckBox = function()
{
	$extend(this, SInputElement);

	this._name = '';
	this._value = '';
	this._params = {};
	this._dom_el = null;

	this.init = function(name, value, params)
	{
		this._name = ((typeof(name)==$undef || name===null) ? '' : String(name));
		this._value = ((typeof(value)==$undef || value===null) ? '' : String(value));
		this._params = ((typeof(params)==$undef || params===null) ? {} : params);

		if (typeof(this._params.checked_value) == $undef) this._params.checked_value = '1';
		if (typeof(this._params.unchecked_value) == $undef) this._params.unchecked_value = '0';
		if (typeof(this._params.title)==$undef || this._params.title===null) this._params.title = '';
	}

	this._render_dom = function()
	{
		var id = S._new_element_id();

		var res = [
			'<span class="s-chb">',
			'<input type="checkbox" id="{0}" value="1" name="{1}" />'.format(id, this._name)
		];

		if (this._params.title != '') {
			res.push('<label for="{0}>{1}</label>'.format(id, this._params.title));
		}

		res.push('<span>');

		this._dom = S.build(res.join(''))[0];
		this._dom_el = this._dom.childNodes[0];
	}

	this.get_value = function()
	{
		if (this._dom_el != null) this._value = (this._dom_el.checked ? this._params.checked_value : this._params.unchecked_value);
		return this._value;
	}

	this.set_value = function(value)
	{
		this._value = value;
		if (this._dom_el != null) this._dom_el.checked = (value==this._params.checked_value ? true : false);
	}
}

SToolbar = function()
{
	$extend(this, SElement);

	this._elements_left = [];
	this._elements_right = [];

	this._dom_row = null;
	this._dom_sep = null;

	this.init = function(left_elements, right_elements)
	{
		if (typeof(left_elements)!=$undef && left_elements!==null) {
			for (var i = 0; i < left_elements.length; i++) {
				this.append(left_elements[i], false);
			}
		}

		if (typeof(right_elements)!=$undef && right_elements!==null) {
			for (var i = 0; i < right_elements.length; i++) {
				this.append(right_elements[i], true);
			}
		}
	}

	this._render_dom = function()
	{
		this._dom = S.build([
			'<table cellspacing="0" cellpadding="0" class="s-tlb"><tr><td class="s-tbl-sep"></td></tr></table>'
		].join(''))[0];
	}

	this._update_dom = function()
	{
		this._dom_row = this._dom.childNodes[0].childNodes[0];
		this._dom_sep = this._dom_row.childNodes[0];

		for (var i = 0; i < this._elements_left.length; i++) this._append_item_to_dom(this._elements_left[i], false);
		for (var i = 0; i < this._elements_right.length; i++) this._append_item_to_dom(this._elements_right[i], true);
	}

	this._append_element = function(element)
	{
		if (this._dom == null) throw $new(SException, "Don't render elements into SToolbar, when toolbar itself is not rendered");
		this.append(element);
	}

	this._remove_from_elements = function(elements, element)
	{
		var res = [];

		for (var i = 0; i < elements.length; i++)
		{
			if (elements[i].el == element)
			{
				if (this._dom !== null)
				{
					elements[i].dom.removeChild(element.dom());
					this._dom_row.removeChild(elements[i].dom);
				}
			}
			else
			{
				res.push(elements[i]);
			}
		}

		return res;
	}

	this._remove_element = function(element)
	{
		this._elements_left = this._remove_from_elements(this._elements_left, element);
		this._elements_right = this._remove_from_elements(this._elements_right, element);
	}

	this._append_item_to_dom = function(item, append_to_right)
	{
		item.dom = S.create('TD');

		if (append_to_right) {
			this._dom_row.appendChild(item.dom);
		} else {
			this._dom_row.insertBefore(item.dom, this._dom_sep);
		}

		if (item.el._in_render) {
			item.dom.appendChild(item.el._dom);
		} else {
			item.el.render(item.dom);
		}
	}

	this.append = function(element, append_to_right)
	{
		if (typeof(append_to_right) == $undef) append_to_right = false;

		var item = { dom:null, el:element };
		if (this._dom !== null) this._append_item_to_dom(item, append_to_right);

		if (append_to_right) {
			this._elements_right.push(item);
		} else {
			this._elements_left.push(item);
		}
	}

	this.remove = function(element)
	{
		this._remove_element(element);
	}
};

SNavigator = function()
{
	$extend(this, SElement);

	this._id_field = 'id';
	this._header = [];
	this._rows = [];
	this._rows_hash = {};
	this._active_id = '';
	this._click_handler = null;

	this.init = function(header, click_handler)
	{
		this._header = header;
		this._click_handler = (typeof(click_handler)==$undef ? null : click_handler);
	}

	this._render_dom = function()
	{
		var res = ['<table cellspacing="0" cellpadding="0" class="s-nav">','<tr>'];

		for (var i = 0; i < this._header.length; i++)
		{
			var th_cls = '';

			if (i == 0) th_cls = (th_cls + ' s-nav-first').trim();
			if (i == this._header.length-1) th_cls = (th_cls + ' s-nav-last').trim();

			if (th_cls == '') res.push('<th>');
			else res.push('<th class="{0}">'.format(td_cls));

			res.push(this._header[i].title);
			res.push('</th>');
		}

		res.push('</tr>');

		for (var i = 0; i < this._rows.length; i++)
		{
			var data = this._rows[i].data;
			res.push('<tr{0} __s_id="{1}">'.format((i%2==0 ? '' : ' class="s-nav-alt"'), S.html(String(data[this._id_field]))));

			for (var j = 0; j < this._header.length; j++)
			{
				var td_cls = '';

				if (j == 0) td_cls = (td_cls + ' s-nav-first').trim();
				if (j == this._header.length-1) td_cls = (td_cls + ' s-nav-last').trim();

				if (td_cls == '') res.push('<td>');
				else res.push('<td class="{0}">'.format(td_cls));

				var hdr = this._header[j];
				var fld = hdr.field;

				var str = String(typeof(data[fld])==$undef ? '&nbsp;' : (typeof(hdr.format)==$undef ? data[fld] : hdr.format(data[fld], data)));
				res.push(str=='' ? '&nbsp;' : str);

				res.push('</td>');
			}

			res.push('</tr>');
		}

		res.push('</table>');

		this._dom = S.build(res.join(''))[0];
	}

	this._set_row_handlers = function(row)
	{
		row.onmouseover = SNavigator.on_mouse_over;
		row.onmouseout = SNavigator.on_mouse_out;
		row.onclick = SNavigator.on_click;
	}

	this._update_dom = function()
	{
		this._dom.__s_el = this;
		var table_rows = this._dom.childNodes[0].childNodes;

		for (var i = 1; i < table_rows.length; i++)
		{
			var row = this._rows[i - 1];

			row.dom = table_rows[i];
			this._rows_hash[row.data[this._id_field]] = row;
			this._set_row_handlers(table_rows[i]);

			for (var j = 0; j < this._header.length; j++)
			{
				var hdr = this._header[j];
				if (typeof(hdr.post_render) != $undef) hdr.post_render(rows[i].childNodes[j], row.data[hdr.field], row.data);
			}
		}

		if (this._active_id != '') this.set_active_id(this._active_id);
	}

	this.append_row = function(data)
	{
		var item = { data:data, row:null };

		if (this._dom !== null)
		{
			var tr = S.create('TR');
			tr.setAttribute('__s_id', data[this._id_field]);

			if (this._rows.length % 2 != 0) tr.className = 's-nav-alt';
			if (this._active_id == data[this._id_field]) tr.className += ' s-nav-act';

			for (var i = 0; i < this._header.length; i++)
			{
				var td = S.create('TD');

				var td_cls = '';

				if (i == 0) S.add_class(td, 's-nav-first');
				if (i == this._header.length-1) S.add_class(td, 's-nav-last');

				var hdr = this._header[i];
				var fld = hdr.field;

				var str = String(typeof(data[fld])==$undef ? '&nbsp;' : (typeof(hdr.format)==$undef ? data[fld] : hdr.format(data[fld], data)));
				td.innerHTML = (str=='' ? '&nbsp;' : str);

				tr.appendChild(td);

				if (typeof(hdr.post_render) != $undef) hdr.post_render(td, data[fld], data);
			}

			this._dom.childNodes[0].appendChild(tr);

			item.dom = tr;
			this._set_row_handlers(tr);
		}

		this._rows.push(item);
		this._rows_hash[data[this._id_field]] = item;
	}

	this.append_rows = function(arr)
	{
		for (var i = 0; i < arr.length; i++) {
			this.append_row(arr[i]);
		}
	}

	this.get_row_data = function(id)
	{
		return (typeof(this._rows_hash[id])==$undef ? null : this._rows_hash[id].data);
	}

	this.set_row_data = function(id, data)
	{
		if (typeof(this._rows_hash[id]) == $undef) return;

		var row = this._rows_hash[id];
		row.data = data;

		for (var i = 0; i < this._header.length; i++)
		{
			var hdr = this._header[i];
			var fld = hdr.field;

			var str = String(typeof(data[fld])==$undef ? '&nbsp;' : (typeof(hdr.format)==$undef ? data[fld] : hdr.format(data[fld], data)));
			row.dom.childNodes[i].innerHTML = (str=='' ? '&nbsp;' : str);

			if (typeof(hdr.post_render) != $undef) hdr.post_render(row.dom.childNodes[i], data[fld], data);
		}
	}

	this.set_active_id = function(id)
	{
		this._active_id = id;

		if (this._dom !== null)
		{
			for (var i = 0; i < this._rows.length; i++)
			{
				if (this._rows[i].data[this._id_field] == id) {
					S.add_class(this._rows[i].dom, 's-nav-act');
				} else {
					S.rm_class(this._rows[i].dom, 's-nav-act');
				}
			}
		}
	}

	this.set_click_handler = function(click_handler)
	{
		this._click_handler = click_handler;
	}

	this.clear = function()
	{
		if (this._dom != null) {
			for (var i = 0; i < this._rows.length; i++) {
				this._dom.childNodes[0].removeChild(this._rows[i].dom);
			}
		}

		this._rows = [];
		this._rows_hash = {};
	}
};

SNavigator.on_mouse_over = function()
{
	S.add_class(this,'s-nav-hover')
}

SNavigator.on_mouse_out = function()
{
	S.rm_class(this,'s-nav-hover')
}

SNavigator.on_click = function()
{
	var el = this.parentNode.parentNode.__s_el;

	if (el._click_handler !== null)
	{
		var id = this.getAttribute('__s_id');
		el._click_handler(id);
	}
}

SPopupLayer = function()
{
	$extend(this, SElement);

	this._anchor = '';
	this._offset = [0, 0];
	this._hide_on_click_out = true;
	this._hide_timeout = 0;

	this.init = function(anchor, offset, hide_on_click_out)
	{
		this._anchor = ((typeof(anchor)==$undef || anchor===null) ? 'tl-br' : anchor);
		this._offset = ((typeof(offset)==$undef || offset===null) ? [0, 0] : offset);
		this._hide_on_click_out = ((typeof(hide_on_click_out)==$undef || hide_on_click_out===null) ? true : hide_on_click_out);
	}

	this._render_dom = function()
	{
		this._dom = S.create('DIV', { className:'s-pop' }, { display:'none' });
	}

	this._update_dom = function()
	{
		if (S.is_ie) {
			S.add_handler(document.body, 'click', this.delegate(this._on_window_click));
		} else {
			S.add_handler(window, 'click', this.delegate(this._on_window_click));
		}
	}

	this._on_window_click = function(ev)
	{
		if (!this._hide_on_click_out || !this.is_visible()) return true;
		var target = (typeof(ev.target)!=$undef ? ev.target : ev.srcElement);

		while (target)
		{
			if (target == this._dom) return true;
			target = target.parentNode;
		}

		if (this._hide_timeout) clearTimeout(this._hide_timeout);
		this._hide_timeout = setTimeout(this.delegate_ne(this.hide), 10);

		return true;
	}

	this._clear_timeout = function()
	{
		if (this._hide_timeout)
		{
			clearTimeout(this._hide_timeout);
			this._hide_timeout = 0;
		}
	}

	this.is_visible = function()
	{
		return (this._dom == null ? null : (this._dom.style.display != 'none'));
	}

	this.show = function(anchorTo)
	{
		setTimeout(this.delegate_ne(this._clear_timeout), 1);
		if (this._dom == null) return;

		var base = this.offsetParent;
		var pos = { top:0, left:0 };
		var el = anchorTo;

		while (el && el!=base)
		{
			pos.top += el.offsetTop;
			pos.left += el.offsetLeft;
			el = el.offsetParent;
		}

		th_ver = (/^[^\-]*[b]/.test(this._anchor) ? 'b' : 't');
		th_hor = (/^[^\-]*[r]/.test(this._anchor) ? 'r' : 'l');
		el_ver = (/[\-].*[t]/.test(this._anchor) ? 't' : 'b');
		el_hor = (/[\-].*[l]/.test(this._anchor) ? 'l' : 'r');

		if (el_ver == 'b') pos.top += anchorTo.offsetHeight;
		if (el_hor == 'r') pos.left += anchorTo.offsetWidth;

		this._dom.style.display = '';

		if (th_ver == 'b') pos.top -= this._dom.offsetHeight;
		if (th_hor == 'r') pos.left -= this._dom.offsetWidth;

		this._dom.style.top = (pos.top + this._offset[0]) + 'px';
		this._dom.style.left = (pos.left + this._offset[1]) + 'px';
	}

	this.hide = function()
	{
		if (this._dom == null) return;

		this._dom.style.display = 'none';
	}
}

SDateSelector = function()
{
	$extend(this, SInputElement);

	this._name = '';
	this._value = '';
	this._params = {};
	this._el_input = null;
	this._el_button = null;

	this.init = function(name, value, params)
	{
		this._name = ((typeof(name)==$undef || name===null) ? '' : String(name));
		this._value = ((typeof(value)==$undef || value===null) ? '' : String(value));
		this._params = ((typeof(params)==$undef || params===null) ? {} : params);

		if (typeof(this._params.src_parse) == $undef) this._params.src_parse = SDateSelector.sql_date_time_parse;
		if (typeof(this._params.src_format) == $undef) this._params.src_format = SDateSelector.sql_date_time_format;
		if (typeof(this._params.disp_parse) == $undef) this._params.disp_parse = SDateSelector.sql_date_parse;
		if (typeof(this._params.disp_format) == $undef) this._params.disp_format = SDateSelector.sql_date_format;
	}

	this._render_dom = function()
	{
		this._dom = S.build([
			'<table cellspacing="0" class="s-date"><tr>',
				'<td class="s-date-inp"></td>',
				'<td class="s-date-btn"></td>',
			'</tr></table>'
		].join(''))[0];
	}

	this._update_dom = function()
	{
		this._el_input = $new(SInput, 'text', this._name, this._params.disp_format(this._params.src_parse(this._value)));
		this._el_input.render(this._dom.childNodes[0].childNodes[0].childNodes[0]);

		this._el_button = $new(SButton, '...', this.delegate_ne(this._on_click));
		this._el_button.render(this._dom.childNodes[0].childNodes[0].childNodes[1]);
	}

	this._on_click = function()
	{
		var date = this._params.disp_parse(this._el_input.get_value());
		var create = false;

		if (!window.calendar)
		{
			window.calendar = new Calendar(
				null,
				date,
				this.delegate_ne(this._on_date_selected),
				function(cal) { cal.hide(); }
			);

			window.calendar.showsTime = false;
			window.calendar.time24 = true;
			window.calendar.weekNumbers = true;

			create = true;
		}
		else
		{
			if (date) window.calendar.setDate(date);
			window.calendar.hide();
		}

		window.calendar.showsOtherMonths = true;
		window.calendar.yearStep = 2;
		window.calendar.setRange(1900, 2999);
		window.calendar.params = this._params;
		window.calendar.setDateStatusHandler(null);
		window.calendar.getDateText = null;
		window.calendar.setDateFormat('%Y-%m-%d');
		window.calendar.show_offset.y = 2;

		if (create) window.calendar.create();

		window.calendar.refresh();
		window.calendar.showAtElement(this._el_button.dom(), 'Bl');
	}

	this._on_date_selected = function()
	{
		this._el_input.set_value(this._params.disp_format(window.calendar.date));
		window.calendar.callCloseHandler();
	}

	this.dom_input = function()
	{
		return this._el_input.dom();
	}

	this.set_name = function(name)
	{
		this._ro_set('name', name);
	}

	this.get_value = function()
	{
		return this._params.src_format(this._params.disp_parse(this._el_input.get_value()));
	}

	this.set_value = function(value)
	{
		this._el_input.set_value(this._params.disp_format(this._params.src_parse(value)));
	}
}

SDateSelector.sql_date_time_parse = function(str)
{
	var arr = /^\s*([0-9]{1,4})\-([0-9]{1,2})\-([0-9]{1,2})\s+([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/.exec(str);
	var res = new Date();

	if (arr == null) return null;

	res.setFullYear(arr[1]);
	res.setMonth(arr[2] - 1);
	res.setDate(arr[3]);
	res.setHours(arr[4]);
	res.setMinutes(arr[5]);
	res.setSeconds(arr[6]);
	res.setMilliseconds(0);

	return res;
}

SDateSelector.sql_date_time_format = function(date)
{
	if (date == null) return '';

	return '{0}-{1}-{2} {3}:{4}:{5}'.format(
		date.getFullYear().format('04d'),
		(date.getMonth() + 1).format('02d'),
		date.getDate().format('02d'),
		date.getHours().format('02d'),
		date.getMinutes().format('02d'),
		date.getSeconds().format('02d'));
}

SDateSelector.sql_date_parse = function(str)
{
	var arr = /^\s*([0-9]{1,4})\-([0-9]{1,2})\-([0-9]{1,2})/.exec(str);
	var res = new Date();

	if (arr == null) return null;

	res.setFullYear(arr[1]);
	res.setMonth(arr[2] - 1);
	res.setDate(arr[3]);
	res.setHours(0);
	res.setMinutes(0);
	res.setSeconds(0);
	res.setMilliseconds(0);

	return res;
}

SDateSelector.sql_date_format = function(date)
{
	if (date == null) return '';

	return '{0}-{1}-{2}'.format(
		date.getFullYear().format('04d'),
		(date.getMonth() + 1).format('02d'),
		date.getDate().format('02d'));
}

SForm = function()
{
	$extend(this, SElement);

	this._name = '';
	this._url = '';
	this._page_action = '';
	this._dom_action = null;

	this.init = function(name, page_action, url)
	{
		this._name = ((typeof(name)==$undef || name===null) ? '' : name);
		this._page_action = ((typeof(page_action)==$undef || page_action===null) ? 'submit' : page_action);
		this._url = ((typeof(url)==$undef || url===null) ? '?' : url);
	}

	this._render_dom = function()
	{
		this._dom = S.build([
			'<form name="{0}" action="{1}" method="POST" enctype="multipart/form-data" class="s-inp-form">'.format(this._name, S.html(this._url)),
				'<input type="hidden" name="_s_{0}_action" value="{1} />'.format(this._name, S.html(this._page_action)),
			'</form>'
		].join(''))[0];
	}

	this._update_dom = function()
	{
		this._dom_action = this._dom.childNodes[0];
	}

	this.set_name = function(name)
	{
		this._ro_set('name', name);
	}

	this.get_page_action = function()
	{
		return this._page_action;
	}

	this.set_page_action = function(value)
	{
		this._page_action = value;
		if (this._dom_action != null) this._dom_action = value;
	}

	this.get_hidden_value = function(name)
	{
		for (var i = 0; i < this._dom.elements; i++) {
			if (this._dom.elements[i].name == name) {
				return this._dom.elements[i].value;
			}
		}

		return null;
	}

	this.set_hidden_value = function(name, value)
	{
		for (var i = 0; i < this._dom.elements; i++)
		{
			if (this._dom.elements[i].name == name)
			{
				this._dom.elements[i].value = value;
				return;
			}
		}

		var hid = S.create('INPUT', { type:'hidden', name:name, value:value });
		this._dom.appendChild(hid);
	}
}
