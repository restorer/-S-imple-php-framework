SElement = function()
{
	$extend(this, SClass);

	this._container = null;
	this._dom = null;
	this._tmp = {};
	this._in_render = false;

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

SInput = function()
{
	$extend(this, SElement);

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
			res.push('<tr{0} __s_id="{1}">'.format((i%2==0 ? '' : ' class="s-nav-alt"'), S.html(String(data.id))));

			for (var j = 0; j < this._header.length; j++)
			{
				var td_cls = '';

				if (j == 0) td_cls = (td_cls + ' s-nav-first').trim();
				if (j == this._header.length-1) td_cls = (td_cls + ' s-nav-last').trim();

				if (td_cls == '') res.push('<td>');
				else res.push('<td class="{0}">'.format(td_cls));

				var id = this._header[j].id;
				res.push(typeof(data[id])==$undef ? '&nbsp;' : (String(data[id])=='' ? '&nbsp;' : String(data[id])));

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
		var rows = this._dom.childNodes[0].childNodes;

		for (var i = 1; i < rows.length; i++)
		{
			this._rows[i-1].row = rows[i];
			this._rows_hash[this._rows[i-1].data.id] = this._rows[i-1];
			this._set_row_handlers(rows[i]);
		}

		if (this._active_id != '') this.set_active_id(this._active_id);
	}

	this.append_row = function(data)
	{
		var item = { data:data, row:null };

		if (this._dom !== null)
		{
			var tr = S.create('TR');
			tr.setAttribute('__s_id', data.id);

			if (this._rows.length % 2 != 0) tr.className = 's-nav-alt';
			if (this._active_id == data.id) tr.className += ' s-nav-act';

			for (var i = 0; i < this._header.length; i++)
			{
				var td = S.create('TD');

				var td_cls = '';

				if (i == 0) S.add_class(td, 's-nav-first');
				if (i == this._header.length-1) S.add_class(td, 's-nav-last');

				var id = this._header[i].id;
				td.innerHTML = (typeof(data[id])==$undef ? '&nbsp;' : (String(data[id])=='' ? '&nbsp;' : String(data[id])));

				tr.appendChild(td);
			}

			this._dom.childNodes[0].appendChild(tr);

			item.row = tr;
			this._set_row_handlers(tr);
		}

		this._rows.push(item);
		this._rows_hash[data.id] = item;
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

	this.set_active_id = function(id)
	{
		this._active_id = id;

		if (this._dom !== null)
		{
			for (var i = 0; i < this._rows.length; i++)
			{
				if (this._rows[i].data.id == id) {
					S.add_class(this._rows[i].row, 's-nav-act');
				} else {
					S.rm_class(this._rows[i].row, 's-nav-act');
				}
			}
		}
	}

	this.set_click_handler = function(click_handler)
	{
		this._click_handler = click_handler;
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
