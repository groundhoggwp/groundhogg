(function ($) {

  const defaultField = {
    id: '',
    className: '',
    type: '',
    name: '',
    value: '',
    label: '',
    required: false,
  }

  const defaultForm = {
    fields: [
      {
        ...defaultField,
        type: 'first',
        name: 'first_name',
        label: 'First Name',
        required: true,
      },
      {
        ...defaultField,
        type: 'last',
        name: 'last_name',
        label: 'Last Name',
        required: true,
      },
      {
        ...defaultField,
        type: 'email',
        name: 'email',
        label: 'Email',
        required: true,
      }
    ]
  }

  const Elements = {
    switch ({ id, name, className, value, onLabel = 'on', offLabel = 'off', checked }) {
      //language=HTML
      return `
		  <label class="gh-switch ${className}">
			  <input id="${id}" name="${name}" value="${value}" type="checkbox" ${checked ? 'checked' : ''}>
			  <span class="slider round"></span>
			  <span class="on">${onLabel}</span>
			  <span class="off">${offLabel}</span>
		  </label>`
    },
    input ({ id, name, className, type = 'text', value, placeholder }) {
      return `<input type="${type}" id="${id}" name="${name}" class="${className}" value="${value}" placeholder="${placeholder}"/>`
    }
  }

  const Settings = {
    basic (label, atts) {
      const { id } = atts
      return `<label for="${id}">${label}</label> ${Elements.input(atts)}`
    },
    required (required) {
      //language=HTML
      return `<label for="required">Required</label> ${Elements.switch({
		  id: 'required',
		  name: 'required',
		  className: 'required',
		  onLabel: 'Yes',
		  offLabel: 'No',
		  checked: required
	  })}`
    },
    label (label) {
      return this.basic('Label', {
        id: 'label',
        name: 'label',
        className: 'label',
        value: label,
        placeholder: ''
      })
    },
    value (value) {
      return this.basic('Value', {
        id: 'value',
        name: 'value',
        className: 'value',
        value: value,
        placeholder: ''
      })
    },
    placeholder (placeholder) {
      return this.basic('Placeholder', {
        id: 'placeholder',
        name: 'Placeholder',
        className: 'placeholder',
        value: placeholder,
        placeholder: ''
      })
    },
    id (id) {
      return this.basic('CSS Id', {
        id: 'css-id',
        name: 'id',
        className: 'css-id',
        value: id,
        placeholder: 'css-id'
      })
    },
    className (className) {
      return this.basic('CSS Class', {
        id: 'className',
        name: 'className',
        className: 'css-class-name',
        value: className,
        placeholder: 'css-class-name'
      })
    },
  }

  const Fields = {
    first: {
      name: 'First Name',
      content (field) {
        return [
          Settings.required(field.required),
          Settings.label(field.label),
          Settings.placeholder(field.placeholder),
        ]
      },
      advanced (field) {
        return [
          Settings.value(field.value),
          Settings.id(field.id),
          Settings.className(field.className),
        ]
      }
    },
    last: {
      name: 'Last Name',
      content (field) {
        return [
          Settings.required(field.required),
          Settings.label(field.label),
          Settings.placeholder(field.placeholder),
        ]
      },
      advanced (field) {
        return [
          Settings.value(field.value),
          Settings.id(field.id),
          Settings.className(field.className),
        ]
      }
    },
    email: {
      name: 'Email',
      content (field) {
        return [
          Settings.required(field.required),
          Settings.label(field.label),
          Settings.placeholder(field.placeholder),
        ]
      },
      advanced (field) {
        return [
          Settings.value(field.value),
          Settings.id(field.id),
          Settings.className(field.className),
        ]
      }
    },
    phone: {},
    gdpr: {},
    terms: {},
    recaptcha: {},
    submit: {},
    text: {},
    textarea: {},
    number: {},
    dropdown: {},
    radio: {},
    checkbox: {},
    address: {},
    birthday: {},
    // row: {},
    // col: {},
    date: {},
    time: {},
    file: {},
  }

  const Templates = {

    settings (field, settingsTab) {
      const settings = settingsTab === 'advanced' ? Fields[field.type].advanced(field) : Fields[field.type].content(field)

      // language=HTML
      return `
		  <div class="settings-tabs">
			  <a class="settings-tab ${settingsTab === 'content' ? 'active' : ''}" data-tab="content">Content</a>
			  <a class="settings-tab ${settingsTab === 'advanced' ? 'active' : ''}" data-tab="advanced">Advanced</a>
		  </div>
		  <div class="settings">${settings.map(setting => `<div class="row">${setting}</div>`).join('')}</div>`
    },

    field (key, field, isEditing, settingsTab) {

      const { type, label } = field

      //language=HTML
      return `
		  <div class="form-field" data-key="${key}">
			  <div class="field-header">
				  <div class="details">
					  <div class="field-label">${label}</div>
					  <div class="field-type">${Fields[type]?.name}</div>
				  </div>
				  <div class="actions">
					  <!-- Duplicate/Delete -->
					  <button class="duplicate"><span class="dashicons dashicons-admin-page"></span></button>
					  <button class="delete"><span class="dashicons dashicons-no"></span></button>
				  </div>
			  </div>
			  ${isEditing ?
				  //language=HTML
				  Templates.settings(field, settingsTab) : ''}
		  </div>`
    },

    builder (form, activeField, settingsTab) {

      //language=HTML
      return `
		  <div id="form-builder" data-id="${form.id}">
			  <div id="editor" class="editor">
				  <div id="form-fields">
					  ${form.fields.map((field, index) => Templates.field(index, field, activeField === index, settingsTab)).join('')}
				  </div>
				  <button>Add Field</button>
			  </div>
			  <div id="form-preview" class="panel">
				  <!-- Preview -->
			  </div>
		  </div>`
    },

  }

  window.FormBuilder = (el, form, onChange) => ({

    form,
    builder: null,
    activeField: false,
    activeFieldTab: 'content',

    init () {
      this.builder = $(el)
      this.render()
      this.fieldClickListener()
    },

    fieldClickListener () {
      var self = this

      this.builder.on('click', '.form-field', function (e) {
        const fieldKey = parseInt($(this).data('key'))
        if (fieldKey !== self.activeField) {
          self.activeField = fieldKey
          self.activeFieldTab = 'content'
          self.render()
        } else if (e.target.classList.contains('settings-tab')) {
          self.activeFieldTab = e.target.dataset.tab
          self.render()
        }
      })
    },

    render () {
      this.builder.html(Templates.builder(defaultForm, this.activeField, this.activeFieldTab))
    },

    deMount () {},

    renderSettings () {},
    renderPreview () {},

  })

})(jQuery)