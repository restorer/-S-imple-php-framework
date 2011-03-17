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

SL.set('interface/select_file', 'Select file ...', 'en');
SL.set('interface/navigator/empty-text', 'No items to display', 'en');

SL.set('interface/select_file', 'Выбрать файл ...', 'ru');
SL.set('interface/navigator/empty-text', 'Пусто', 'ru');

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
		if (container === null) throw $new(SException, 'container is null');

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

	this.init = function(text, click_handler, inner_cls, enabled)
	{
		this._text = ((typeof(text)==$undef || text===null) ? '' : String(text));
		this._click_handler = (typeof(click_handler)==$undef ? null : click_handler);
		this._inner_cls = ((typeof(inner_cls)==$undef || inner_cls===null) ? '' : String(inner_cls));
		this._enabled = ((typeof(enabled)==$undef || enabled===null) ? true : enabled);
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
				'<div class="s-inp-wrap">',
				'<textarea class="s-inp s-inp-textarea" name="{0}" rows="{1}" cols="{2}"'.format(this._name, rows, cols),
					' onfocus="S.add_class(this,\'s-inp-focus\')"',
					' onblur="S.rm_class(this,\'s-inp-focus\')">',
				'</textarea>',
				'</div>'
			].join(''))[0];
		}
		else
		{
			this._dom = S.build([
				'<div class="s-inp-wrap">',
				'<input class="s-inp" type="{0}" name="{1}"'.format(this._type, this._name),
					' onfocus="S.add_class(this,\'s-inp-focus\')"',
					' onblur="S.rm_class(this,\'s-inp-focus\')" />',
				'</div>'
			].join(''))[0];
		}
	}

	this.dom_input = function()
	{
		return this._dom.childNodes[0];
	}

	this._update_dom = function()
	{
		this.dom_input().value = this._value;
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
		return (this._dom === null ? this._value : this.dom_input().value);
	}

	this.set_value = function(value)
	{
		if (this._dom === null) {
			this._value = value;
		} else {
			this.dom_input().value = value;
		}
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
					'<label class="s-inp-file-label">',
						'<input size="1" class="s-inp-file-input" type="file" name="{0}" />'.format(this._name),
					'</label>',
				'</div>',
				'<span class="s-inp-file-text"></span>',
			'</span>'
		].join(''))[0];

		this._dom_el = this._dom.childNodes[0].childNodes[0].childNodes[0];
		this._dom_text = this._dom.childNodes[1];
	}

	this._update_dom = function()
	{
		this._dom_el.onchange = this.delegate(this._on_change);

		this._el_button = $new(SButton, SL.get('interface/select_file'));
		this._el_button.render(this._dom.childNodes[0]);
	}

	this._on_change = function(ev)
	{
		var name = this._dom_el.value.replace(/\\/g, '/');

		var ind = name.lastIndexOf('/');
		if (ind >= 0) name = name.substr(ind + 1);

		this._dom_text.innerHTML = S.html(name);
	}

	this.dom_input = function()
	{
		return this._dom_el;
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
			'<input type="hidden" value="{0}" name="{1}" />'.format(this._params.unchecked_value, this._name),
			'<input type="checkbox" id="{0}" value="{1}" name="{2}"{3} />'.format(
				id,
				this._params.checked_value,
				this._name,
				(this._value == this._params.checked_value ? ' checked="checked"' : '')
			)
		];

		if (this._params.title != '') {
			res.push('<label for="{0}">{1}</label>'.format(id, this._params.title));
		}

		res.push('<span>');

		this._dom = S.build(res.join(''))[0];
		this._dom_el = this._dom.childNodes[1];
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

	this.dom_el = function()
	{
		return this._dom_el;
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

	this._id_field = '';
	this._header = [];
	this._rows = [];
	this._active_id = '';
	this._click_handler = null;
	this._max_height = 0;
	this._fixed_height = 0;
	this._use_pager = false;
	this._show_pages = 5;
	this._pages_count = 1;
	this._curr_page = 0;
	this._multiple_select = false;

	this._sortable = {};
	this._sort_field = { field:'', dir:true };
	this._rows_hash = {};
	this._row_header = null;
	this._row_empty = null;
	this._div_header = null;
	this._tbody = null;
	this._div_pager = null;
	this._pager = null;
	this._sort_handler = null;
	this._page_handler = null;

	/*
	 * (max_height==0 && fixed_height==true) has no sense
	 * (max_height>0 && fixed_height==false) may incorretly work under IE
	 *
	 * header example:
	 * [{ title: 'Title', sortable: false, field: 'field', format: function(field, row) {} }, post_render: function(domElement, field, row)]
	 */
	this.init = function(header, click_handler, max_height, fixed_height, use_pager, multiple_select, id_field)
	{
		this._header = header;
		this._click_handler = (typeof(click_handler)==$undef ? null : click_handler);
		this._max_height = ((typeof(max_height)==$undef || max_height==null) ? 0 : max_height);
		this._fixed_height = ((typeof(fixed_height)==$undef || fixed_height==null) ? false : fixed_height);
		this._use_pager = ((typeof(use_pager)==$undef || use_pager==null) ? false : use_pager);
		this._multiple_select = ((typeof(multiple_select)==$undef || multiple_select==null) ? false : multiple_select);
		this._id_field = ((typeof(id_field)==$undef || id_field==null) ? 'id' : id_field);
	}

	this._get_sort_arr = function(dir)
	{
		return (dir ? '&uarr;' : '&darr;');
	}

	this._render_dom = function()
	{
		var st = '';

		if (this._max_height)
		{
			if (this._fixed_height) {
				st = ' style="height:{0}px"'.format(this._max_height);
			} else {
				st = ' style="max-height:{0}px;_height:{0}px"'.format(this._max_height);
			}
		}

		var res = [
			'<div class="s-nav">',
			'<div class="s-nav-wrap"{0}>'.format(st),
			'<table cellspacing="0" cellpadding="0" class="s-nav-tbl">',
			'<tr>'
		];

		if (this._multiple_select)
		{
			res.push('<th class="s-nav-first s-nav-chb">');
			res.push('&nbsp;');
			res.push('</th>');
		}

		for (var i = 0; i < this._header.length; i++)
		{
			var th_cls = '';

			if (i == 0 && !this._multiple_select) th_cls = (th_cls + ' s-nav-first').trim();
			if (i == this._header.length-1) th_cls = (th_cls + ' s-nav-last').trim();

			if (typeof(this._header[i].cls)!=$undef && this._header[i].cls) {
			    th_cls = (th_cls + ' ' + this._header[i].cls).trim();
			}

			res.push(th_cls=='' ? '<th>' : '<th class="{0}">'.format(th_cls));
			var hdr_title = (this._header[i].title.length ? this._header[i].title : '&nbsp;');

			if (typeof(this._header[i].sortable)!=$undef && this._header[i].sortable)
			{
				res.push('<span>{0}</span> <strong>{1}</strong>'.format(hdr_title, this._get_sort_arr(true)));
				this._sortable[this._header[i].field] = true;

				if (!this._sort_field.field) {
					this._sort_field.field = this._header[i].field;
				}
			}
			else
			{
				res.push(hdr_title);
			}

			res.push('</th>');
		}

		res.push('</tr>');

		res.push(this._rows.length ? '<tr class="s-nav-empty" style="display:none">' : '<tr class="s-nav-empty">');
		res.push('<td colspan="{0}">{1}</td>'.format(this._header.length + (this._multiple_select ? 1 : 0), SL.get('interface/navigator/empty-text')));
		res.push('</tr>');

		for (var i = 0; i < this._rows.length; i++)
		{
			var data = this._rows[i].data;
			res.push('<tr{0} __s_id="{1}">'.format((i%2==0 ? '' : ' class="s-nav-alt"'), S.html(String(data[this._id_field]))));

			if (this._multiple_select)
			{
				res.push('<td class="s-nav-first s-nav-chb">');
				res.push('<input type="checkbox" value="1" />');
				res.push('</td>');
			}

			for (var j = 0; j < this._header.length; j++)
			{
				var td_cls = '';

				if (j == 0 && !this._multiple_select) td_cls = (td_cls + ' s-nav-first').trim();
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
		// res.push('</div>');	// s-nav-wrap

		/*
		if (this._use_pager)
		{
			res.push('<div class="s-nav-pager"></div>');

			this._pager = $new(SPager)
			this._pager.set_show_pages(this._show_pages);
			this._pager.set_pages_count(this._pages_count);
			this._pager.set_curr_page(this._curr_page);
			this._pager.on_page_changed = this._page_handler;
		}
		*/

		res.push('<div class="s-nav-hdr">');

		if (this._multiple_select)
		{
			res.push('<div class="s-nav-first s-nav-chb">');
			res.push('<input type="checkbox" value="1" />');
			res.push('</div>');
		}

		for (var i = 0; i < this._header.length; i++)
		{
			var h_cls = '';

			if (i == 0 && !this._multiple_select) h_cls = (h_cls + ' s-nav-first').trim();
			if (i == this._header.length-1) h_cls = (h_cls + ' s-nav-last').trim();

			if (typeof(this._header[i].cls)!=$undef && this._header[i].cls) {
			    h_cls = (h_cls + ' ' + this._header[i].cls).trim();
			}

			res.push(h_cls=='' ? '<div>' : '<div class="{0}">'.format(h_cls));
			var hdr_title = (this._header[i].title.length ? this._header[i].title : '&nbsp;');

			if (typeof(this._header[i].sortable)!=$undef && this._header[i].sortable) {
				res.push('<span __s_fld="{0}">{1}</span> <strong>{2}</strong>'.format(this._header[i].field, hdr_title));
			} else {
				res.push(hdr_title);
			}

			res.push('</div>');
		}

		res.push('</div>');		// s-nav-hdr
		res.push('</div>');		// s-nav-wrap

		if (this._use_pager)
		{
			res.push('<div class="s-nav-pager"></div>');

			this._pager = $new(SPager)
			this._pager.set_show_pages(this._show_pages);
			this._pager.set_pages_count(this._pages_count);
			this._pager.set_curr_page(this._curr_page);
			this._pager.on_page_changed = this._page_handler;
		}

		res.push('</div>');		// s-nav

		this._dom = S.build(res.join(''))[0];
	}

	this._set_row_handlers = function(row)
	{
		row.onmouseover = SNavigator.on_mouse_over;
		row.onmouseout = SNavigator.on_mouse_out;
		row.onclick = SNavigator.on_click;

		if (this._multiple_select)
		{
			row.childNodes[0].onclick = SNavigator.on_chb_td_click;
			row.childNodes[0].getElementsByTagName('INPUT')[0].onclick = SNavigator.on_chb_click;
		}
	}

	this._update_sort_arrows = function()
	{
		if (this._row_header == null) return;
		var off = (this._multiple_select ? 1 : 0);

		for (var i= 0; i < this._header.length; i++)
		{
			var hdr = this._div_header.childNodes[i + off];

			if (typeof(this._sortable[this._header[i].field]) != $undef) {
				hdr.getElementsByTagName('strong')[0].innerHTML = (this._sort_field.field == this._header[i].field ? this._get_sort_arr(this._sort_field.dir) : '&nbsp;');
			}
		}
	}

	this._update_header = function()
	{
		if (this._row_header == null) return;
		var off = (this._multiple_select ? 1 : 0);

		for (var i = 0; i < this._header.length + off; i++)
		{
			var th = this._row_header.childNodes[i];
			var hdr = this._div_header.childNodes[i];

			var wdt = th.offsetWidth;
			wdt -= 12;	// paddings and borders

			hdr.style.left = th.offsetLeft + 'px';
			hdr.style.width = wdt + 'px';

			if (off != 0 && i == 0) {
				hdr.onclick = SNavigator.on_chb_td_click;
				hdr.getElementsByTagName('input')[0].onclick = SNavigator.on_chb_click;
			} else if (off == 0 || i > 0) {
				if (typeof(this._sortable[this._header[i - off].field]) != $undef) {
					hdr.getElementsByTagName('span')[0].onclick = SNavigator.on_sort_click;
				}
			}
		}

		this._update_sort_arrows();
	}

	this._update_dom = function()
	{
		this._dom.__s_el = this;

		this._tbody = this._dom.childNodes[0].childNodes[0].childNodes[0];
		this._row_header = this._tbody.childNodes[0];
		this._row_empty = this._tbody.childNodes[1];

		this._div_header = this._dom.childNodes[0].childNodes[1];
		this._div_pager = (this._use_pager ? this._dom.childNodes[1] : null);

		if (this._use_pager) {
			this._pager.render(this._div_pager);
		}

		var off = (this._multiple_select ? 1 : 0);
		var table_rows = this._tbody.childNodes;

		for (var i = 2; i < table_rows.length; i++)
		{
			var row = this._rows[i - 2];

			row.dom = table_rows[i];
			this._rows_hash[row.data[this._id_field]] = row;
			this._set_row_handlers(table_rows[i]);

			for (var j = 0; j < this._header.length; j++)
			{
				var hdr = this._header[j];
				if (typeof(hdr.post_render) != $undef) hdr.post_render(table_rows[i].childNodes[j + off], row.data[hdr.field], row.data);
			}
		}

		if (this._active_id != '') this.set_active_id(this._active_id);
		this._update_header();
	}

	this._update_empty_info = function()
	{
		if (this._row_empty != null) {
			this._row_empty.style.display = (this._rows.length ? 'none' : '');
		}
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

			if (this._multiple_select)
			{
				var td = S.create('TD');
				td.className = 's-nav-first s-nav-chb';
				td.innerHTML = '<input type="checkbox" value="1" />';
				tr.appendChild(td);
			}

			for (var i = 0; i < this._header.length; i++)
			{
				var td = S.create('TD');

				var td_cls = '';

				if (i == 0 && !this._multiple_select) S.add_class(td, 's-nav-first');
				if (i == this._header.length-1) S.add_class(td, 's-nav-last');

				var hdr = this._header[i];
				var fld = hdr.field;

				var str = String(typeof(data[fld])==$undef ? '&nbsp;' : (typeof(hdr.format)==$undef ? data[fld] : hdr.format(data[fld], data)));
				td.innerHTML = (str=='' ? '&nbsp;' : str);

				tr.appendChild(td);

				if (typeof(hdr.post_render) != $undef) hdr.post_render(td, data[fld], data);
			}

			this._tbody.appendChild(tr);

			item.dom = tr;
			this._set_row_handlers(tr);
		}

		this._rows.push(item);
		this._rows_hash[data[this._id_field]] = item;

		this._update_empty_info();
		this._update_header();
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

	this.get_data_by_ind = function(ind)
	{
		return (typeof(this._rows[ind])==$undef ? null : this._rows[ind].data);
	}

	this.get_rows = function()
	{
		return this._rows.map(function(item){ return item.data });
	}

	this.set_row_data = function(id, data)
	{
		if (typeof(this._rows_hash[id]) == $undef) return;

		var row = this._rows_hash[id];
		row.data = data;

		var off = (this._multiple_select ? 1 : 0);

		for (var i = 0; i < this._header.length; i++)
		{
			var hdr = this._header[i];
			var fld = hdr.field;

			var str = String(typeof(data[fld])==$undef ? '&nbsp;' : (typeof(hdr.format)==$undef ? data[fld] : hdr.format(data[fld], data)));
			row.dom.childNodes[i + off].innerHTML = (str=='' ? '&nbsp;' : str);

			if (typeof(hdr.post_render) != $undef) hdr.post_render(row.dom.childNodes[i + off], data[fld], data);
		}
	}

	this.get_active_id = function()
	{
		return this._active_id;
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
				this._tbody.removeChild(this._rows[i].dom);
			}

			if (this._multiple_select) {
				this._div_header.childNodes[0].childNodes[0].checked = false;
			}
		}

		this._rows = [];
		this._rows_hash = {};

		this._update_empty_info();
		this._update_header();
	}

	this.get_rows_count = function()
	{
		return this._rows.length;
	}

	this.get_curr_page = function()
	{
		return (this._pager == null ? this._curr_page : this._pager.get_curr_page());
	}

	this.set_curr_page = function(page)
	{
		if (this._pager == null) {
			this._curr_page = page;
		} else {
			this._pager.set_curr_page(page);
		}
	}

	this.get_pages_count = function()
	{
		return (this._pager == null ? this._pages_count : this._pager.get_pages_count());
	}

	this.set_pages_count = function(pages)
	{
		if (this._pager == null) {
			this._pages_count = pages;
		} else {
			this._pager.set_pages_count(pages);
		}
	}

	this.get_show_pages = function()
	{
		return (this._pager == null ? this._show_pages : this._pager.get_show_pages());
	}

	this.set_show_pages = function(show_pages)
	{
		if (this._pager == null) {
			this._show_pages = show_pages;
		} else {
			this._pager.set_show_pages(show_pages);
		}
	}

	this.set_sort_handler = function(func)
	{
		this._sort_handler = func;
	}

	this.set_page_handler = function(func)
	{
		if (this._pager == null) {
			this._page_handler = func;
		} else {
			this._pager.on_page_changed = func;
		}
	}

	this.set_sort_field = function(sort_fld)
	{
		if (typeof(sort_fld.field) != $undef) this._sort_field.field = sort_fld.field;
		if (typeof(sort_fld.dir) != $undef) this._sort_field.dir = sort_fld.dir;
	}

	this.get_sort_field = function()
	{
		return this._sort_field;
	}

	this.get_selected_ids = function()
	{
		var res = [];

		for (var i = 0; i < this._rows.length; i++)
		{
			var row = this._rows[i];

			if (row.dom.childNodes[0].childNodes[0].checked) {
				res.push(String(row.data[this._id_field]));
			}
		}

		return res;
	}

	// selected == true by default
	this.select_by_id = function(id, selected)
	{
		if (typeof(this._rows_hash[id]) == $undef) return;

		this._rows_hash[id].dom.childNodes[0].childNodes[0].checked = (typeof(selected)==$undef || selected);
		SNavigator._fix_chb(this._rows_hash[id].dom.childNodes[0]);
	}

	this.set_selected_ids = function(ids)
	{
		var ids_hash = {};
		for (var i = 0; i < ids.length; i++) ids_hash[ids[i]] = true;

		for (var i = 0; i < this._rows.length; i++)
		{
			var row = this._rows[i];
			row.dom.childNodes[0].childNodes[0].checked = (typeof(ids_hash[row.data[this._id_field]]) != $undef);
		}

		SNavigator._fix_chb(this._rows[0].dom.childNodes[0]);
	}
}

SNavigator._fix_chb = function(el)
{
	if (el.tagName.toLowerCase() == 'div')
	{
		var checked = el.getElementsByTagName('input')[0].checked;
		var rows = el.parentNode.parentNode.getElementsByTagName('tr');

		for (var i = 2; i < rows.length; i++) {
			rows[i].childNodes[0].childNodes[0].checked = checked;
		}
	}
	else
	{
		var rows = el.parentNode.parentNode.getElementsByTagName('tr');
		var checkedCount = 0;

		for (var i = 2; i < rows.length; i++) {
			if (rows[i].childNodes[0].childNodes[0].checked) {
				checkedCount++;
			}
		}

		var headerChb = el.parentNode.parentNode.parentNode.parentNode.getElementsByTagName('div')[0].childNodes[0].childNodes[0];
		headerChb.checked = (checkedCount == rows.length - 2);
	}
}

SNavigator.on_chb_td_click = function(ev)
{
	if (!ev) ev = event;

	var chb = this.getElementsByTagName('input')[0];
	chb.checked = !chb.checked;
	SNavigator._fix_chb(this);

	if (ev.stopPropagation) ev.stopPropagation();
	if (ev.preventDefault) ev.preventDefault();
	ev.cancelBubble = true;

	return false;
}

SNavigator.on_chb_click = function(ev)
{
	if (!ev) ev = event;

	var element = this;
	var checked = this.checked;

	setTimeout(function() {
		element.checked = checked;
		SNavigator._fix_chb(element.parentNode);
	}, 1);

	if (ev.stopPropagation) ev.stopPropagation();
	if (ev.preventDefault) ev.preventDefault();
	ev.cancelBubble = true;

	return false;
}

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
	var el = this.parentNode.parentNode.parentNode.parentNode.__s_el;

	if (el._click_handler !== null)
	{
		var id = this.getAttribute('__s_id');
		el._click_handler(id);
	}
}

SNavigator.on_sort_click = function()
{
	var el = this.parentNode.parentNode.parentNode.parentNode.__s_el;
	var fld = this.getAttribute('__s_fld');

	if (el._sort_field.field == fld) {
		el._sort_field.dir = !el._sort_field.dir;
	} else {
		el._sort_field = { field:fld, dir:true }
	}

	el._update_sort_arrows();
	if (el._sort_handler) el._sort_handler(el._sort_field);
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
				'<input type="hidden" name="_s_{0}_action" value="{1}" />'.format(this._name, S.html(this._page_action)),
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

SDropDown = function()
{
	$extend(this, SInputElement);

	this._name = '';
	this._value = '';
	this._options = [];

	this.init = function(name, value, options)
	{
		this._name = ((typeof(name)==$undef || name===null) ? '' : String(name));
		this._value = ((typeof(value)==$undef || value===null) ? '' : String(value));
		this._options = ((typeof(options)==$undef || options===null) ? [] : options);
	}

	this._render_dom = function()
	{
		var res = '<select name="{0}">'.format(this._name);

		for (var i = 0; i < this._options.length; i++) {
			res += '<option value="{0}">{1}</option>'.format(S.html(this._options[i][0]), S.html(this._options[i][1]));
		}

		res += '</select>';

		this._dom = S.build(res)[0];
		this._dom.value = this._value;
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

	this.clear_options = function()
	{
		while (this._dom.options.length > 0) {
			this._dom.remove(0);
		}
	}

	this.append_option = function(id, name)
	{
		this._dom.options[this._dom.options.length] = new Option(name, id);
	}

	this.set_options = function(options)
	{
		this.clear_options();

		for (var i = 0; i < options.length; i++) {
			this.append_option(options[i][0], options[i][1]);
		}
	}
}

SPager = function()
{
	$extend(this, SElement);

	this._show_pages = 5;
	this._curr_page = 0;
	this._pages_count = 1;

	this._thumb_bar = null;
	this._thumb = null;
	this._pos = 0;
	this._thumb_wdt = 0;
	this._max_pos = 0;
	this._ev_added = false;

	this.on_page_changed = null;

	this.init = function()
	{
	}

	this._render_dom = function()
	{
		this._dom = S.build('<div class="s-pager"></div>')[0];
		this._dom.__s_el = this;
	}

	this._update_dom = function()
	{
		this._thumb_wdt = Math.floor(Math.max(1, Math.min(100, this._show_pages * 100 / this._pages_count)));
		this._max_pos = 100 - this._thumb_wdt;
		if (this._pos > this._max_pos) { this._pos = this._max_pos; }

		var cells_count = Math.max(1, Math.min(this._show_pages, this._pages_count));
		var res = '<table><tr>';
		var perc = Math.floor(100 / cells_count);

		for (var i = 0; i < cells_count; i++) {
			res += '<td width="{0}%"></td>'.format(perc);
		}

		res += '</tr><th colspan="{0}"><div class="s-pager-bar"><div class="s-pager-thumb">&nbsp;</div></div></th></tr></table>'.format(cells_count);
		this._dom.innerHTML = res;

		this._thumb_bar = this._dom.childNodes[0].childNodes[0].childNodes[1].childNodes[0].childNodes[0];
		this._thumb_bar.onmousedown = SPager.on_mouse_down;

		this._thumb = this._dom.childNodes[0].childNodes[0].childNodes[1].childNodes[0].childNodes[0].childNodes[0];
		this._thumb.style.width = this._thumb_wdt + '%';
		this._thumb.style.left = this._pos + '%';

		var cells = this._dom.childNodes[0].childNodes[0].childNodes[0].childNodes;
		for (var i = 0; i < cells_count; i++) { cells[i].onclick = SPager.on_click; }

		this._update_page();
	}

	this._update_page = function()
	{
		var page = Math.max(0, Math.min(this._curr_page, this._pages_count-1));
		var cells_count = Math.max(1, Math.min(this._show_pages, this._pages_count));
		var thumb_pages = Math.max(1, this._pages_count - cells_count);
		var min_page = Math.floor(this._pos * thumb_pages / Math.max(1, this._max_pos));
		var cells = this._dom.childNodes[0].childNodes[0].childNodes[0].childNodes;

		for (var i = 0; i < cells_count; i++)
		{
			cells[i].innerHTML = (min_page + i + 1);
			cells[i].setAttribute('__s_page', min_page + i);
			cells[i].className = (min_page+i == page ? 's-pager-sel' : '');
		}
	}

	this.get_show_pages = function()
	{
		return this._show_pages;
	}

	this.set_show_pages = function(show_pages)
	{
		this._show_pages = Math.max(1, show_pages);
		if (this._dom != null) this._update_dom();
	}

	this.get_curr_page = function()
	{
		return Math.max(0, Math.min(this._curr_page, this._pages_count-1));
	}

	this.set_curr_page = function(page)
	{
		var page = Math.max(0, Math.min(page, this._pages_count-1));
		this._curr_page = page;

		var cells_count = Math.max(1, Math.min(this._show_pages, this._pages_count));
		var min_page = Math.max(0, page - Math.floor(cells_count / 2));
		if (min_page + cells_count > this._pages_count) min_page = this._pages_count - cells_count;

		var thumb_pages = Math.max(1, this._pages_count - cells_count);
		this._pos = (min_page * this._max_pos / thumb_pages);

		if (this._dom != null)
		{
			this._thumb.style.left = this._pos + '%';
			this._update_page();
		}
	}

	this.get_pages_count = function()
	{
		return this._pages_count;
	}

	this.set_pages_count = function(pages)
	{
		this._pages_count = Math.max(1, pages);
		if (this._dom != null) this._update_dom();
	}
}

SPager.on_click = function()
{
	var el = this.parentNode.parentNode.parentNode.parentNode.__s_el;
	el.set_curr_page(this.getAttribute('__s_page'));
	if (el.on_page_changed) el.on_page_changed(el.get_curr_page());
}

SPager.on_mouse_down = function(ev)
{
	if (!ev) ev = event;
	var el = this.parentNode.parentNode.parentNode.parentNode.parentNode.__s_el;

	S.stop_event(ev);
	var el_bar = this;

	function update_pos(ev)
	{
		var bar_pos = S.pos(el_bar);
		var mouse_pos = S.mouse_pos(ev);

		var xpos = mouse_pos.left - bar_pos.left;

		var min_xpos = Math.floor(el_bar.offsetWidth * el._thumb_wdt / 200);
		var max_xpos = el_bar.offsetWidth - min_xpos;
		var act_wdt = Math.max(1, max_xpos - min_xpos);

		if (xpos > max_xpos) xpos = max_xpos;
		if (xpos < min_xpos) xpos = min_xpos;

		el._pos = Math.floor(Math.max(0, xpos - min_xpos) * el._max_pos / act_wdt);
		el._thumb.style.left = el._pos + '%';
		el._update_page();
	}

	var on_select_start = function()
	{
		return false;
	};

	var on_mouse_move = function(ev)
	{
		S.stop_event(ev);
		update_pos(ev);
	};

	var on_mouse_up = function(ev)
	{
		S.rm_handler(document, 'selectstart', on_select_start);
		S.rm_handler(document, 'mousemove', on_mouse_move);
		S.rm_handler(document, 'mouseup', on_mouse_up);
		el._ev_added = false;
	};

	if (!el._ev_added)
	{
		S.add_handler(document, 'selectstart', on_select_start);
		S.add_handler(document, 'mousemove', on_mouse_move);
		S.add_handler(document, 'mouseup', on_mouse_up);
		el._ev_added = true;
	}

	update_pos(ev);
}
