SValidators = function()
{
	return {
		required: function(value)
		{
			return (value == '' ? 'This field is required' : '');
		},

		compare: function(value, from, field)
		{
			var row = form.get_row(field);
			return (row.get_value() == value ? '' : 'Must match to ' + row.get_title());
		},

		is_number: function(value)
		{
			return (isNaN(Number(value)) ? 'Must be number' : '');
		},

		number_range: function(value, form, min_value, max_value)
		{
			var val = Number(value);
			return ( (val>=min_value && val<=max_value) ? '' : 'Number must be between ' + min_value + ' and ' + max_value );
		}
	};
}();

SFormRow = function()
{
	$extend(this, SClass);

	this.id = '';
	this.type = '';

	this.title = { element: null, value: '' };
	this.input = { element: null, value: '' };
	this.info =  { element: null, value: '' };
	this.error = { element: null, value: '' };

	this.params = {};
	this.validators = [];

	this.row_tr = null;
	this.parent = null;

	this.init = function(parent, id, type, title, info, validators, params)
	{
		this.parent = parent;

		if (typeof(id)!=$undef && id!=null) this.id = id;
		if (typeof(type)!=$undef && type!=null) this.type = type;
		if (typeof(title)!=$undef && title!=null) this.title.value = title;
		if (typeof(info)!=$undef && info!=null) this.info.value = info;
		if (typeof(params)!=$undef && params!=null) this.params = params;

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
		if (this.title.element != null) this.title.element.innerHTML = this.parent.encode(title) + ':';
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
		if (this.input.element != null) this.input.value = this.input.element.get_value();
		return this.input.value;
	}

	this.set_value = function(value)
	{
		this.input.value = value;
		if (this.input.element != null) this.input.element.set_value(value);
	}

	this.render = function(container)
	{
		this.row_tr = S.create('TR', { vAlign: 'top' });
		container.appendChild(this.row_tr);

		this.title.element = S.create('TH', { innerHTML: this.parent.encode(this.title.value) + ':' });
		this.row_tr.appendChild(this.title.element);

		var input_td = S.create('TD');
		this.row_tr.appendChild(input_td);

		switch (this.type)
		{
			default:
				this.input.element = $new(SInput, this.type, '', this.input.value, this.params);
				break;
		}

		this.input.element.render(input_td);

		this.info.element = S.create('SPAN',
			{ className:'s-form-info', innerHTML:this.parent.encode(this.info.value) },
			{ display:(this.info.value=='' ? 'none' : '') }
		);

		input_td.appendChild(this.info.element);

		this.error.element = S.create('DIV',
			{ className:'s-form-error-info', innerHTML:this.parent.encode(this.error.value) },
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
		if (this.input.element != null) S.add_class(this.input.element.dom(), 's-form-error-el');

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
		if (this.input.element != null) S.rm_class(this.input.element.dom(), 's-form-error-el');
		this.error.value = '';
		if (this.error.element != null) this.error.element.style.display = 'none';
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

	this.click_handler = function()
	{
		if (this.validate && !this.parent.validate()) return;
		if (this.handler != null) this.handler();
	}

	this.render = function(container)
	{
		this.element = $new(SButton, this.title, this.delegate(this.click_handler));
		this.element.render(container);
	}
}

SForm = function()
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
		var row = $new(SFormRow, this, row_data.id, row_data.type, row_data.title,
						(typeof(row_data.info)==$undef ? null : row_data.info),
						(typeof(row_data.validate)==$undef ? null : row_data.validate),
						(typeof(row_data.params)==$undef ? {} : row_data.params)
					);

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

		for (var i = 0; i < form_data.rows.length; i++) this.add_row(form_data.rows[i]);
		for (var i = 0; i < form_data.buttons.length; i++) this.add_button(form_data.buttons[i]);
	}

	this.render = function(container)
	{
		if (this.form_table != null) throw $new(SException, 'Already rendered');

		this.form_table = S.create('TABLE', { cellSpacing:0, cellPadding:0, className:'s-form' });
		container.appendChild(this.form_table);

		this.title.element = S.create('CAPTION', { innerHTML:this.encode(this.title.value) });
		this.form_table.appendChild(this.title.element);

		var tbody = S.create('TBODY');
		this.form_table.appendChild(tbody);

		this.error_tr = S.create('TR', null, { display:'none' });
		tbody.appendChild(this.error_tr);

		this.error_td = S.create('TD', { colSpan:2, className:'s-form-error' });
		this.error_tr.appendChild(this.error_td);

		for (var i = 0; i < this.rows.length; i++) this.rows[i].render(tbody);

		this.buttons_row = S.create('TR');
		tbody.appendChild(this.buttons_row);

		var buttons_td = S.create('TD', { colSpan:2, align:'center' });
		this.buttons_row.appendChild(buttons_td);

		for (var i = 0; i < this.buttons.length; i++)
		{
			this.buttons[i].render(buttons_td);
			if (i != this.buttons.length-1) buttons_td.appendChild(S.create('SPAN', { innerHTML:'&nbsp;' }));
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
			err_msg += '<strong>';

			for (var j = 0; j < err.length; j++)
			{
				err_msg += this.encode(err[j]);
				if (j != err.length-1) errMsg += ', ';
			}

			err_msg += '</strong><br />';
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
		for (var i = 0; i < this.rows.length; i++) this.rows[i].set_value('');
		this.clear_errors();
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
}
