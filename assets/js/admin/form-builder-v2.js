(function ($) {

  const { toggle, input, select, inputWithReplacements } = Groundhogg.element

  const defaultField = {
    className: 'text',
    type: 'text',
    name: 'text',
    value: '',
    label: 'New field',
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

  const Settings = {
    type (type) {

      // language=html
      return `<label for="type">Type</label>
	  <div class="setting">
		  ${select({
			  id: 'type',
			  name: 'type',
		  }, getFieldTypeOptions(), type)}
	  </div>`
    },
    basic (label, atts) {
      const { id } = atts
      return `<label for="${id}">${label}</label>
	  <div class="setting">${input(atts)}</div>`
    },
    basicWithReplacements (label, atts) {
      const { id } = atts
      return `<label for="${id}">${label}</label> ${inputWithReplacements(atts)}`
    },
    required (required) {
      //language=HTML
      return `<label for="required">Required</label>
	  <div class="setting">${toggle({
		  id: 'required',
		  name: 'required',
		  className: 'required',
		  onLabel: 'Yes',
		  offLabel: 'No',
		  checked: required
	  })}
	  </div>`
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
      return this.basicWithReplacements('Value', {
        id: 'value',
        name: 'value',
        className: 'value regular-text',
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

  /**
   *
   * @param type
   * @returns {(*&{advanced(*): [], contentOnMount(*), advancedOnMount(*), name: string, content(*): []})|boolean}
   */
  const getFieldType = (type) => {
    if (!FieldTypes.hasOwnProperty(type)) {
      return false
    }

    return {
      ...FieldTypes.default,
      ...FieldTypes[type]
    }
  }

  const getFieldTypeOptions = () => {

    const options = []

    for (const type in FieldTypes) {
      if (FieldTypes.hasOwnProperty(type) && FieldTypes[type].hasOwnProperty('name') && !FieldTypes[type].hasOwnProperty('hide')) {
        options.push({
          value: type,
          text: FieldTypes[type].name
        })
      }
    }

    return options
  }

  const FieldTypes = {
    default: {
      name: 'default',
      content (field) { return []},
      advanced (field) { return []},
      contentOnMount (field) {},
      advancedOnMount (field) {},
      hide: true
    },
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
      contentOnMount (field, updateField) {

      },
      advanced (field) {
        return [
          Settings.value(field.value),
          Settings.id(field.id),
          Settings.className(field.className),
        ]
      },
      advancedOnMount (field, updateField) {

      }
    },
    phone: {},
    gdpr: {},
    terms: {},
    recaptcha: {},
    submit: {},
    text: {
      name: 'Text',
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

      const fieldType = getFieldType(field.type)

      const settings = settingsTab === 'advanced' ? fieldType.advanced(field) : fieldType.content(field)

      // language=HTML
      return `
		  <div class="settings-tabs">
			  <a class="settings-tab ${settingsTab === 'content' ? 'active' : ''}" data-tab="content">Content</a>
			  <a class="settings-tab ${settingsTab === 'advanced' ? 'active' : ''}" data-tab="advanced">Advanced</a>
		  </div>
		  <div class="settings">
			  ${settingsTab === 'content' ? `<div class="row">${Settings.type(field.type)}</div>` : ''}
			  ${settings.map(setting => `<div class="row">${setting}</div>`).join('')}
		  </div>`
    },

    field (key, field, isEditing, settingsTab) {

      const { type, label } = field

      const fieldType = getFieldType(type)

      //language=HTML
      return `
		  <div class="form-field" data-key="${key}">
			  <div class="field-header">
				  <div class="details">
					  <div class="field-label">${label}</div>
					  <div class="field-type">${fieldType.name}</div>
				  </div>
				  <div class="actions">
					  <!-- Duplicate/Delete -->
					  <button class="duplicate" data-key="${key}"><span class="dashicons dashicons-admin-page"></span>
					  </button>
					  <button class="delete" data-key="${key}"><span class="dashicons dashicons-no"></span></button>
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
			  <div id="fields-editor" class="fields-editor">
				  <div id="form-fields">
					  ${form.fields.map((field, index) => Templates.field(index, field, activeField === index, settingsTab)).join('')}
				  </div>
				  <button class="add-field gh-button secondary">Add Field</button>
			  </div>
			  <div id="form-preview" class="panel">
				  <!-- Preview -->
			  </div>
		  </div>`
    },

  }

  const FormBuilder = (
    selector,
    form = defaultForm,
    onChange = (form) => {
      console.log(form)
    }) => ({

    form,
    el: null,
    activeField: false,
    activeFieldTab: 'content',

    init () {
      this.el = $(selector)
      this.mount()
    },

    mount () {
      this.render()
      this.onMount()
    },

    onMount () {
      var self = this

      const render = () => {
        this.mount()
      }

      const currentField = () => {
        return this.form.fields[this.activeField]
      }

      const setActiveField = (id) => {
        self.activeField = id
        self.activeFieldTab = 'content'
        render()
      }

      const addField = () => {
        this.form.fields.push(defaultField)
        onChange(this.form)
        setActiveField(this.form.fields.length - 1)
      }

      const deleteField = (id) => {
        this.form.fields.splice(id, 1)

        if (this.activeField === id) {
          this.activeField = false
          self.activeFieldTab = 'content'
        }

        onChange(this.form)
        render()
      }

      const duplicateField = (id) => {

        const field = this.form.fields[id]

        this.form.fields.splice(id, 0, field)

        onChange(this.form)
        setActiveField(id + 1)
      }

      const updateField = (atts) => {
        this.form.fields[this.activeField] = {
          ...this.form.fields[this.activeField],
          ...atts
        }

        onChange(this.form)
        render()
      }

      const $builder = $('#form-builder')

      $builder.on('click', '.add-field', addField)

      $builder.on('click', '.form-field', (e) => {

        const $field = $(e.currentTarget)
        const $target = $(e.target)

        const fieldKey = parseInt($field.data('key'))

        if ($target.is('button.delete, button.delete .dashicons')) {
          deleteField(fieldKey)
        } else if ($target.is('button.duplicate, button.duplicate .dashicons')) {
          duplicateField(fieldKey)
        } else {
          if (fieldKey !== self.activeField) {
            setActiveField(fieldKey)
          } else if (e.target.classList.contains('settings-tab')) {
            self.activeFieldTab = e.target.dataset.tab
            render()
          }
        }

        if (self.activeField) {
          if (self.activeFieldTab === 'content') {

            $('#type').on('change', (e) => {
              updateField({
                type: e.target.value
              })
            })

            getFieldType(currentField().type).contentOnMount(currentField(), updateField)
          } else {
            getFieldType(currentField().type).advancedOnMount(currentField(), updateField)
          }
        }
      })
    },

    render () {
      this.el.html(Templates.builder(this.form, this.activeField, this.activeFieldTab))
    },

    deMount () {},

    renderSettings () {},
    renderPreview () {},

  })

  Groundhogg.formBuilder = FormBuilder

})(jQuery)