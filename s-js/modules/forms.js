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

SL.set('forms/validators/required', 'This field is required', 'en');
SL.set('forms/validators/compare', 'Must match to {0}', 'en');
SL.set('forms/validators/is_number', 'Must be number', 'en');
SL.set('forms/validators/number_range', 'Number must be between {0} and {1}', 'en');

SL.set('forms/validators/required', 'Это поле необходимо', 'ru');
SL.set('forms/validators/compare', 'Должно совпадать с полем "{0}"', 'ru');
SL.set('forms/validators/is_number', 'Должно быть числом', 'ru');
SL.set('forms/validators/number_range', 'Должно быть числом от {0} до {1}', 'ru');

SValidators = function()
{
	return {
		required: function(value)
		{
			return (value == '' ? SL.get('forms/validators/required') : '');
		},

		compare: function(value, form, field)
		{
			var row = form.get_row(field);
			return (row.get_value() == value ? '' : SL.get('forms/validators/compare').format(row.get_title()));
		},

		is_number: function(value)
		{
			return (isNaN(Number(value)) ? SL.get('forms/validators/is_number') : '');
		},

		number_range: function(value, form, min_value, max_value)
		{
			var val = Number(value);
			return ( (val>=min_value && val<=max_value) ? '' : SL.get('forms/validators/number_range').format(min_value, max_value) );
		},

		regex: function(value, form, re, msg)
		{
			return (re.test(value) ? '' : msg);
		}
	};
}();

SFormRow = function()
{
	var avail_types = {};
	avail_types['html'] = function(){ return $new(SHtmlElement, this.input.value); };
	avail_types['text'] = function(){ return $new(SInput, this.type, this.id, this.input.value, this.params); };
	avail_types['password'] = avail_types['text'];
	avail_types['textarea'] = avail_types['text'];
	avail_types['checkbox'] = function(){ return $new(SCheckBox, this.id, this.input.value, this.params); };
	avail_types['date'] = function(){ return $new(SDateSelector, this.id, this.input.value, this.params); };
	avail_types['file'] = function(){ return $new(SInputFile, this.id, this.input.value, this.params); };
	avail_types['dropdown'] = function(){ return $new(SDropDown, this.id, this.input.value, this.options); };

	$extend(this, SClass);

	this.id = '';
	this.type = '';
	this.width = 0;		// 0 - no width override
	this.render_title = true;
	this.params = {};
	this.options = [];
	this.title_cls = '';
	this.custom_func = null;
	this.validate_when_hidden = false;
	this.validation_enabled = true;

	this.title = { element: null, value: '' };
	this.input = { element: null, value: '' };
	this.info =  { element: null, value: '' };
	this.error = { element: null, value: '' };

	this.validators = [];
	this.row_tr = null;
	this.parent = null;

	this.init = function(parent, validators)
	{
		this.parent = parent;

		if (typeof(validators)!=$undef && validators!=null) {
			for (var i = 0; i < validators.length; i++) this.add_validator(validators[i]);
		}
	}

	this.get_title = function()
	{
		return this.title.value;
	}

	this.set_title = function(title)
	{
		this.title.value = title;
		if (this.title.element != null) this.title.element.innerHTML = (title=='' ? '&nbsp;' : this.parent.encode(title) + ':');
	}

	this.get_info = function()
	{
		return this.info.value;
	}

	this.set_info = function(info)
	{
		this.info.value = info;

		if (this.info.element != null)
		{
			this.info.element.innerHTML = this.parent.encode(info);
			this.info.element.style.display = (info == '' ? 'none' : '');
		}
	}

	this.get_value = function()
	{
		if (this.input.element != null) {
			this.input.value = this.input.element.get_value();
		}

		return this.input.value;
	}

	this.set_value = function(value)
	{
		this.input.value = value;

		if (this.input.element != null) {
			this.input.element.set_value(value);
		}
	}

	this.set_options = function(options)
	{
		this.options = options;

		if (this.input.element != null) {
			this.input.element.set_options(options);
		}
	}

	this.render = function(container, row_cls)
	{
		this.row_tr = S.create('TR', { vAlign: 'top', className: 's-form-e ' + row_cls });
		container.appendChild(this.row_tr);

		if (this.render_title)
		{
			this.title.element = S.create('TH', { className: 's-form-e ' + this.title_cls, innerHTML: (this.title.value=='' ? '&nbsp;' : this.parent.encode(this.title.value) + ':') });
			this.row_tr.appendChild(this.title.element);
		}

		var input_td = S.create('TD', { className: 's-form-e' });
		this.row_tr.appendChild(input_td);

		if (!this.render_title) input_td.colSpan = 2;

		if (this.type == 'custom')
		{
			if (this.custom_func == null) {
				throw $new(SException, 'custom_func is null');
			}

			this.input.element = this.custom_func.call(this);
		}
		else
		{
			if (typeof(avail_types[this.type]) == $undef) {
				throw $new(SException, 'Unknown row type "{0}"'.format(this.type));
			}

			this.input.element = avail_types[this.type].call(this);
		}

		this.input.element.render(input_td);

		if (this.width && this.render_title) {
			this.input.element.set_width(this.width);
		}

		this.info.element = S.create('SPAN',
			{ className:'s-form-e s-form-info', innerHTML:this.parent.encode(this.info.value) },
			{ display:(this.info.value=='' ? 'none' : '') }
		);

		input_td.appendChild(this.info.element);

		this.error.element = S.create('DIV',
			{ className:'s-form-e s-form-error-info', innerHTML:this.parent.encode(this.error.value) },
			{ display:(this.error.value=='' ? 'none' : '') }
		);

		input_td.appendChild(this.error.element);
	}

	this.add_validator = function(validator)
	{
		if (typeof(validator)==$func) this.validators.push([validator]);
		else this.validators.push(validator);
	}

	this.validate = function()
	{
		if (this.row_tr.style.display == 'none' && !this.validate_when_hidden) return '';
		if (!this.validation_enabled) return '';

		var val = this.get_value();

		for (var i = 0; i < this.validators.length; i++)
		{
			var args = [val, this.parent];
			for (var j = 1; j < this.validators[i].length; j++) args.push(this.validators[i][j]);

			var res = this.validators[i][0].apply(this, args);
			if (res != '') return res;
		}

		return '';
	}

	this.set_error = function(msg)
	{
		if (this.input.element != null)
		{
			var dom = this.input.element.dom_input();
			if (dom != null) S.add_class(dom, 's-form-error-el');
		}

		if (typeof(msg)==$undef || msg===null) msg = '';
		this.error.value = msg;

		if (this.error.element != null)
		{
			this.error.element.innerHTML = this.parent.encode(msg);
			this.error.element.style.display = (msg == '' ? 'none' : '');
		}
	}

	this.clear_error = function()
	{
		if (this.input.element != null)
		{
			var dom = this.input.element.dom_input();
			if (dom != null) S.rm_class(dom, 's-form-error-el');
		}

		this.error.value = '';
		if (this.error.element != null) this.error.element.style.display = 'none';
	}

	this.set_visibility = function(visible)
	{
		this.row_tr.style.display = (visible ? '' : 'none');
	}

	this.get_visibility = function()
	{
		return (this.row_tr.style.display != 'none');
	}

	this.show = function()
	{
		this.set_visibility(true);
	}

	this.hide = function()
	{
		this.set_visibility(false);
	}

	this.set_width = function(width)
	{
		this.width = width;

		if (this.render_title) {
			this.input.element.set_width(width ? width : null);
		}
	}

	this.can_clear = function()
	{
		return (this.type != 'html');
	}

	this.set_custom_func = function(func)
	{
		this.custom_func = func;
	}

	this.get_element = function()
	{
		return this.input.element;
	}

	this.enable_validation = function()
	{
		this.validation_enabled = true;
	}

	this.disable_validation = function()
	{
		this.validation_enabled = false;
	}

	this.set_title_class = function(cls)
	{
		this.title.element.className = cls;
	}
}

SFormButton = function()
{
	$extend(this, SClass);

	this.id = '';
	this.title = '';
	this.validate = false;

	this.handler = null;
	this.parent = null;
	this.element = null;

	this.init = function(parent, id, title, validate)
	{
		this.parent = parent;
		this.id = id;
		this.title = title;

		if (typeof(validate)!=$undef && validate!=null) this.validate = validate;
	}

	this.get_title = function()
	{
		return this.title;
	}

	this.set_title = function(title)
	{
		this.title = title;
		if (this.element != null) this.element.set_value(title);
	}

	this.set_handler = function(handler_func)
	{
		this.handler = handler_func;
	}

	this.press = function()
	{
		if (this.validate && !this.parent.validate()) return;
		if (this.handler != null) this.handler();
	}

	this.render = function(container)
	{
		this.element = $new(SButton, this.title, this.delegate_ne(this.press));
		this.element.render(container);
	}

	this.set_visibility = function(visible)
	{
		this.element.dom().style.display = (visible ? '' : 'none');
	}

	this.get_visibility = function()
	{
		return (this.element.dom().style.display != 'none');
	}

	this.show = function()
	{
		this.set_visibility(true);
	}

	this.hide = function()
	{
		this.set_visibility(false);
	}
}

SFormView = function()
{
	$extend(this, SClass);

	this.form_table = null;
	this.title = { element: null, value: '' };
	this.rows = [];
	this.rows_hash = {};
	this.buttons = [];
	this.buttons_hash = {};
	this.buttons_row = null;
	this.error_tr = null;
	this.error_td = null;
	this.errors = {};
	this.info_tr = null;
	this.info_td = null;
	this.fields_width = 0;	// 0 - no width override

	this.init = function(form_data)
	{
		if (typeof(form_data) != $undef) this.create(form_data)
	}

	this.encode = function(str)
	{
		return str;
	}

	this.dom = function()
	{
		return this.form_table;
	}

	this.update = function()
	{
		if (!this.instantiated) return;
	}

	this.get_title = function()
	{
		return this.title.value;
	}

	this.set_title = function(title)
	{
		this.title.value = title;

		if (this.title.element != null)
		{
			this.title.element.innerHTML = this.encode(title);
			this.title.element.style.display = (title == '' ? 'none' : '');
		}
	}

	this.add_row = function(row_data)
	{
		var row = $new(SFormRow, this, row_data.validate);
		row.width = this.fields_width;

		for (var k in row_data)
		{
			switch (k)
			{
				case 'validate':
					break;

				case 'title':
					row.set_title(row_data[k]);
					break;

				case 'info':
					row.set_info(row_data[k]);
					break;

				default:
					row[k] = row_data[k];
			}
		}

		if (typeof(row_data.def) != $undef) row.set_value(row_data.def);

		this.rows.push(row);
		this.rows_hash['z' + row_data.id] = row;
	}

	this.add_button = function(button_data)
	{
		var btn = $new(SFormButton, this, button_data.id, button_data.title, (typeof(button_data.validate)==$undef ? null : button_data.validate ));
		this.buttons.push(btn);
		this.buttons_hash['z' + button_data.id] = btn;
	}

	this.get_row = function(id)
	{
		return this.rows_hash['z' + id];
	}

	this.get_button = function(id)
	{
		return this.buttons_hash['z' + id];
	}

	this.create = function(form_data)
	{
		this.title.value = (typeof(form_data.title)==$undef ? '' : form_data.title);
		this.fields_width = (typeof(form_data.fields_width)==$undef ? 0 : form_data.fields_width);

		for (var i = 0; i < form_data.rows.length; i++) this.add_row(form_data.rows[i]);
		for (var i = 0; i < form_data.buttons.length; i++) this.add_button(form_data.buttons[i]);
	}

	this.render = function(container)
	{
		if (this.form_table != null) throw $new(SException, 'Already rendered');

		this.form_table = S.create('TABLE', { cellSpacing:0, className:'s-form' });
		container.appendChild(this.form_table);

		this.title.element = S.create('CAPTION', { className:'s-form-e', innerHTML:this.encode(this.title.value) }, { display:(this.title.value == '' ? 'none' : '') });
		this.form_table.appendChild(this.title.element);

		var tbody = S.create('TBODY', { className:'s-form-e' });
		this.form_table.appendChild(tbody);

		this.error_tr = S.create('TR', { className:'s-form-e' }, { display:'none' });
		tbody.appendChild(this.error_tr);

		this.error_td = S.create('TD', { colSpan:2, className:'s-form-e s-form-error' });
		this.error_tr.appendChild(this.error_td);

		this.info_tr = S.create('TR', { className:'s-form-e' }, { display:'none' });
		tbody.appendChild(this.info_tr);

		this.info_td = S.create('TD', { colSpan:2, className:'s-form-e s-form-info-msg' });
		this.info_tr.appendChild(this.info_td);

		for (var i = 0; i < this.rows.length; i++) this.rows[i].render(tbody, (i==0 ? 's-form-first' : ''));

		this.buttons_row = S.create('TR', { className:'s-form-e s-form-buttons' });
		tbody.appendChild(this.buttons_row);

		var buttons_td = S.create('TD', { className:'s-form-e', colSpan:2, align:'center' });
		this.buttons_row.appendChild(buttons_td);

		for (var i = 0; i < this.buttons.length; i++)
		{
			this.buttons[i].render(buttons_td);
			if (i != this.buttons.length-1) buttons_td.appendChild(S.create('SPAN', { className:'s-form-e', innerHTML:'&nbsp;' }));
		}
	}

	this.add_error = function(id, message)
	{
		if (typeof(id)==$undef || id==null) id = '';
		if (typeof(this.errors['z' + id]) == $undef) this.errors['z' + id] = [];
		this.errors['z' + id].push(message);
	}

	this.has_errors = function()
	{
		for (var k in this.errors) return true;
		return false;
	}

	this.show_errors = function()
	{
		if (!this.has_errors()) return;

		var err_msg = '';

		if (typeof(this.errors['z']) != $undef)
		{
			var err = this.errors['z'];

			for (var j = 0; j < err.length; j++)
			{
				err_msg += this.encode(err[j]);
				if (j != err.length-1) errMsg += ', ';
			}
		}

		for (var i = 0; i < this.rows.length; i++)
		{
			if (typeof(this.errors['z' + this.rows[i].id]) != $undef)
			{
				var err = this.errors['z' + this.rows[i].id];
				row_err = '';

				for (var j = 0; j < err.length; j++)
				{
					row_err += this.encode(err[j]);
					if (j != err.length-1) row_err += ', ';
				}

				this.rows[i].set_error(row_err);
			}
		}

		this.error_td.innerHTML = err_msg;
		this.error_tr.style.display = (err_msg == '' ? 'none' : '');
	}

	this.clear = function()
	{
		for (var i = 0; i < this.rows.length; i++) {
			if (this.rows[i].can_clear()) {
				this.rows[i].set_value('');
			}
		}

		this.clear_errors();
		this.clear_info();
	}

	this.set_error = function(message)
	{
		this.add_error(null, message);
		this.show_errors();
	}

	this.clear_errors = function()
	{
		this.errors = {};
		this.error_tr.style.display = 'none';
		for (var i = 0; i < this.rows.length; i++) this.rows[i].clear_error();
	}

	this.validate = function()
	{
		this.clear_errors();

		for (var i = 0; i < this.rows.length; i++)
		{
			var res = this.rows[i].validate();
			if (res != '') this.add_error(this.rows[i].id, res);
		}

		this.show_errors();
		return (!this.has_errors());
	}

	this.set_info = function(info)
	{
		this.info_td.innerHTML = info;
		this.info_tr.style.display = (info == '' ? 'none' : '');
	}

	this.clear_info = function()
	{
		this.set_info('');
	}

	this.set_fields_width = function(width)
	{
		this.fields_width = width;
		for (var i = 0; i < this.rows.length; i++) rows[i].set_width(width);
	}

	this.set_visibility = function(visible)
	{
		this.form_table.style.display = (visible ? '' : 'none');
	}

	this.get_visibility = function()
	{
		return (this.form_table.style.display != 'none');
	}

	this.show = function()
	{
		this.set_visibility(true);
	}

	this.hide = function()
	{
		this.set_visibility(false);
	}
}
