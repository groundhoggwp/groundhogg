(function ($) {

  const { toggle, textarea, input, select, inputWithReplacements, uuid } = Groundhogg.element
  const {
    metaPicker
  } = Groundhogg.pickers

  const columnWidths = {
    '1/1': 1,
    '1/2': 0.5,
    '1/3': 0.33333,
    '1/4': 0.25,
    '2/3': 0.66666,
    '3/4': 0.75,
  }

  const columnClasses = {
    '1/1': 'col-1-of-1',
    '1/2': 'col-1-of-2',
    '1/3': 'col-1-of-3',
    '1/4': 'col-1-of-4',
    '2/3': 'col-2-of-3',
    '3/4': 'col-3-of-4',
  }

  const fieldWidth = ({ column_width }) => {
    return columnWidths[column_width]
  }
  
  /**
   * Group into rows based on their field width
   *
   * @param fields
   * @param button
   * @returns {*}
   */
  const groupFieldsInRows = ({ fields, button }) => {

    fields = [
      ...fields,
      button
    ]

    return fields.reduce((rows, field) => {

      const rowWidth = rows[rows.length - 1].reduce((width, field) => {
        return width + fieldWidth(field)
      }, 0)

      if (rowWidth + fieldWidth(field) > 1) {
        rows.push([])
      }

      rows[rows.length - 1].push(field)

      return rows
    }, [[]])

  }

  const defaultField = {
    className: '',
    id: '',
    type: 'text',
    name: 'text',
    value: '',
    label: 'New field',
    hide_label: false,
    required: false,
    column_width: '1/1'
  }

  const defaultForm = {
    button: {
      type: 'button',
      text: 'Submit',
      label: 'Submit',
      column_width: '1/1'
    },
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

    basic (label, atts) {
      const { id } = atts
      // language=html
      return `<label for="${id}">${label}</label>
	  <div class="setting">${input(atts)}</div>`
    },
    basicWithReplacements (label, atts) {
      const { id } = atts
      return `<label for="${id}">${label}</label> ${inputWithReplacements(atts)}`
    },

    type: {
      type: 'type',
      edit ({ type = 'text' }) {
        //language=HTML
        return `<label for="type">Type</label>
		<div class="setting">
			${select({
				id: 'type',
				name: 'type',
			}, getFieldTypeOptions(), type)}
		</div>`
      },
      onMount (field, updateField) {
        $('#type').on('change', (e) => {
          updateField({
            type: e.target.value
          }, true)
        })
      }
    },

    name: {
      type: 'name',
      edit ({ name = '' }) {
        //language=HTML
        return `<label for="type">Name</label>
		<div class="setting">
			${input({
				id: 'name',
				name: 'name',
				value: name
			})}
		</div>`
      },
      onMount (field, updateField) {
        metaPicker('#name').on('change', (e) => {
          updateField({
            name: e.target.value
          })
        })
      }
    },

    required: {
      type: 'required',
      edit ({ required = false }) {
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
      onMount (field, updateField) {
        $('#required').on('change', (e) => {
          updateField({
            required: e.target.checked
          })
        })
      }
    },
    label: {
      type: 'label',
      edit ({ label = '' }) {
        return Settings.basic('Label', {
          id: 'label',
          name: 'label',
          className: 'label',
          value: label,
          placeholder: ''
        })
      },
      onMount (field, updateField) {
        $('#label').on('change', (e) => {
          updateField({
            label: e.target.value
          })
        })
      }
    },
    hideLabel: {
      type: 'hideLabel',
      edit ({ hide_label = false }) {
        //language=HTML
        return `<label for="hide-label">Hide label</label>
		<div class="setting">${toggle({
			id: 'hide-label',
			name: 'hide_label',
			className: 'hide-label',
			onLabel: 'Yes',
			offLabel: 'No',
			checked: hide_label
		})}
		</div>`
      },
      onMount (field, updateField) {
        $('#hide-label').on('change', (e) => {
          updateField({
            hide_label: e.target.checked
          })
        })
      }
    },
    text: {
      type: 'text',
      edit ({ text = '' }) {
        return Settings.basic('Button Text', {
          id: 'text',
          name: 'text',
          className: 'text regular-text',
          value: text,
          placeholder: ''
        })
      },
      onMount (field, updateField) {
        $('#text').on('change', (e) => {
          updateField({
            text: e.target.value
          })
        })
      }
    },
    value: {
      type: 'value',
      edit ({ value = '' }) {
        return Settings.basicWithReplacements('Value', {
          id: 'value',
          name: 'value',
          className: 'value regular-text',
          value: value,
          placeholder: ''
        })
      },
      onMount (field, updateField) {
        $('#value').on('change', (e) => {
          updateField({
            value: e.target.value
          })
        })
      }
    },
    placeholder: {
      type: 'placeholder',
      edit ({ placeholder = '' }) {
        return Settings.basic('Placeholder', {
          id: 'placeholder',
          name: 'Placeholder',
          className: 'placeholder',
          value: placeholder,
          placeholder: ''
        })
      },
      onMount (field, updateField) {
        $('#placeholder').on('change', (e) => {
          updateField({
            placeholder: e.target.value
          })
        })
      }
    },
    id: {
      type: 'id',
      edit ({ id = '' }) {
        return Settings.basic('CSS Id', {
          id: 'css-id',
          name: 'id',
          className: 'css-id',
          value: id,
          placeholder: 'css-id'
        })
      },
      onMount (field, updateField) {
        $('#css-id').on('change', (e) => {
          updateField({
            id: e.target.value
          })
        })
      }
    },
    className: {
      type: 'className',
      edit ({ className = '' }) {
        return Settings.basic('CSS Class', {
          id: 'className',
          name: 'className',
          className: 'css-class-name',
          value: className,
          placeholder: 'css-class-name'
        })
      },
      onMount (field, updateField) {
        $('#className').on('change', (e) => {
          updateField({
            className: e.target.value
          })
        })
      }
    },
    phoneType: {
      type: 'phoneType',
      edit ({ phone_type = 'primary' }) {
        //language=HTML
        return `<label for="phone-type">Phone Type</label>
		<div class="setting">${select({
			id: 'phone-type',
			name: 'phone_type',
			className: 'phone-type',
		}, {
			primary: 'Primary Phone',
			mobile: 'Mobile Phone',
			company: 'Company Phone',
		}, phone_type)}
		</div>`
      },
      onMount (field, updateField) {
        $('#phone-type').on('change', (e) => {
          updateField({
            phone_type: e.target.value
          })
        })
      }
    },
    columnWidth: {
      type: 'columnWidth',
      edit ({ column_width }) {
        //language=HTML
        return `<label for="column-width">Column Width</label>
		<div class="setting">${select({
			id: 'column-width',
			name: 'column_width',
			className: 'column-width',
		}, {
			'1/1': '1/1',
			'1/2': '1/2',
			'1/3': '1/3',
			'1/4': '1/4',
			'2/3': '2/3',
			'3/4': '3/4',
		}, column_width)}
		</div>`
      },
      onMount (field, updateField) {
        $('#column-width').on('change', (e) => {
          updateField({
            column_width: e.target.value
          })
        })
      }
    },
    options: {
      type: 'options',
      edit ({ options = [''] }) {

        const selectOption = (option, i) => {
          // language=HTML
          return `
			  <div class="select-option-wrap">
				  ${input({
					  id: `select-option-${i}`,
					  className: 'select-option',
					  value: option,
					  dataKey: i
				  })}
				  <button class="dashicon-button remove-option" data-key="${i}"><span
					  class="dashicons dashicons-no-alt"></span></button>
			  </div>`
        }

        // language=HTML
        return `
			<div class="options">
				<label>Options</label>
				<div class="select-options">
					${options.map((option, i) => selectOption(option, i)).join('')}
					<div class="select-option-add">
						<div class="spacer"></div>
						<button id="add-option" class="dashicon-button">
							<span class="dashicons dashicons-plus-alt2"></span></button>
					</div>
				</div>
			</div>`
      },
      onMount ({ options = [''] }, updateField, currentField) {

        $('#add-option').on('click', (e) => {

          const { options } = currentField()

          updateField({
            options: [
              ...options,
              ''
            ]
          }, true)
        })

        $('.select-option').on('change', (e) => {

          const key = parseInt(e.target.dataset.key)

          const { options } = currentField()

          updateField({
            options: [
              ...options.map((opt, i) => i === key ? e.target.value : opt)
            ]
          })
        })

        $('.remove-option').on('click', (e) => {

          const { options } = currentField()

          updateField({
            options: [
              ...options.filter((option, i) => i !== parseInt(e.currentTarget.dataset.key))
            ]
          }, true)
        })

      }
    },

    optionNone : {
      type: 'optionNone',
      edit( { option_none } ) {

      }
    }

  }

  /**
   * Render a preview of the field
   *
   * @param field
   * @returns {*}
   */
  const previewField = (field) => {
    return getFieldType(field.type).preview(field)
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

  const standardContentSettings = [
    Settings.type.type,
    Settings.required.type,
    Settings.hideLabel.type,
    Settings.label.type,
    Settings.placeholder.type,
    Settings.columnWidth.type
  ]

  const standardMetaContentSettings = [
    Settings.type.type,
    Settings.name.type,
    Settings.required.type,
    Settings.hideLabel.type,
    Settings.label.type,
    Settings.placeholder.type,
    Settings.columnWidth.type
  ]

  const standardAdvancedSettings = [
    Settings.value.type,
    Settings.id.type,
    Settings.className.type
  ]

  const fieldPreview = ({
    type = 'text',
    id = uuid(),
    name = 'name',
    placeholder = '',
    value = '',
    label = '',
    hide_label = false,
    required = false,
    className = ''
  }) => {

    const inputField = input({
      id: id,
      type: type,
      name: name,
      placeholder: placeholder,
      value: value,
      className: `gh-input ${className}`
    })

    if (hide_label) {
      return inputField
    }

    if (required) {
      label += ' <span class="required">*</span>'
    }

    return `<label class="gh-input-label" for="${id}">${label}</label><div class="gh-form-field-input">${inputField}</div>`
  }

  const FieldTypes = {
    default: {
      name: 'default',
      content: [],
      advanced: [],
      hide: true,
      preview: (field) => fieldPreview({
        ...field,
        type: 'text'
      })
    },
    button: {
      name: 'Button',
      hide: true,
      content: [
        Settings.text.type,
        Settings.columnWidth.type
      ],
      advanced: [
        Settings.id.type,
        Settings.className.type,
      ],
      preview ({ text, id, className }) {
        return `<button id="${id}" class="gh-button secondary ${className}">${text}</button>`
      }
    },
    first: {
      name: 'First Name',
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        name: 'first_name',
        type: 'text'
      })
    },
    last: {
      name: 'Last Name',
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        name: 'last_name',
        type: 'text'
      })
    },
    email: {
      name: 'Email',
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        type: 'email',
        name: 'email'
      })
    },
    phone: {
      name: 'Phone Number',
      content: [
        ...standardContentSettings,
        Settings.phoneType.type
      ],
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        type: 'tel',
        name: field.phoneType + '_phone'
      })
    },
    gdpr: {},
    terms: {},
    recaptcha: {},
    submit: {},
    text: {
      name: 'Text',
      content: standardMetaContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview(field)
    },
    textarea: {
      name: 'Textarea',
      content: standardMetaContentSettings,
      advanced: standardAdvancedSettings,
      preview: ({
        type = 'text',
        id = uuid(),
        name = 'name',
        placeholder = '',
        value = '',
        label = '',
        hide_label = false,
        required = false,
        className = ''
      }) => {

        const inputField = textarea({
          id: id,
          type: type,
          name: name,
          placeholder: placeholder,
          value: value,
          className: `gh-input ${className}`
        })

        if (hide_label) {
          return inputField
        }

        if (required) {
          label += ' <span class="required">*</span>'
        }

        return `<label class="gh-input-label" for="${id}">${label}</label><div class="gh-form-field-input">${inputField}</div>`
      }
    },
    number: {
      name: 'Number',
      content: standardMetaContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        type: 'number',
      })
    },
    dropdown: {
      name: 'Dropdown',
      content: [
        Settings.type.type,
        Settings.name.type,
        Settings.required.type,
        Settings.hideLabel.type,
        Settings.label.type,
        Settings.options.type,
        Settings.columnWidth.type
      ],
      advanced: standardAdvancedSettings,
      preview: ({
        id = uuid(),
        name = 'name',
        options = [],
        label = '',
        hide_label = false,
        required = false,
        className = ''
      }) => {

        const inputField = select({
          id: id,
          name: name,
          className: `gh-input ${className}`
        }, options.map(opt => ({
          text: opt,
          value: opt
        })))

        if (hide_label) {
          return inputField
        }

        if (required) {
          label += ' <span class="required">*</span>'
        }

        return `<label class="gh-input-label" for="${id}">${label}</label><div class="gh-form-field-input">${inputField}</div>`
      }
    },
    radio: {
      name: 'Radio',
      content: [
        Settings.type.type,
        Settings.required.type,
        // Settings.hideLabel.type,
        Settings.label.type,
        Settings.options.type,
        Settings.columnWidth.type
      ],
      advanced: standardAdvancedSettings,
      preview: ({
        id = uuid(),
        name = 'name',
        options = [],
        label = '',
        // hide_label = false,
        required = false,
        className = ''
      }) => {

        const inputField = options.map(opt => {
          // language=HTML
          return `
			  <div class="gh-radio-wrapper">
				  <label class="gh-radio-label">
					  ${input({
						  type: 'radio',
						  id,
						  required,
						  className,
						  name,
						  value: opt,
					  })} ${opt}
				  </label>
			  </div>`
        }).join('')

        return `<label class="gh-input-label" for="${id}">${label}</label><div class="gh-form-field-input">${inputField}</div>`
      }
    },
    checkbox: {},
    address: {},
    birthday: {},
    // row: {},
    // col: {},
    date: {
      name: 'Date',
      content: standardMetaContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        type: 'date',
      })
    },
    time: {
      name: 'Time',
      content: standardMetaContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        type: 'time',
      })
    },
    file: {},
  }

  const Templates = {

    settings (field, settingsTab) {

      const fieldType = getFieldType(field.type)

      const settings = settingsTab === 'advanced' ? fieldType.advanced : fieldType.content

      // language=HTML
      return `
		  <div class="settings-tabs">
			  <a class="settings-tab ${settingsTab === 'content' ? 'active' : ''}" data-tab="content">Content</a>
			  <a class="settings-tab ${settingsTab === 'advanced' ? 'active' : ''}" data-tab="advanced">Advanced</a>
		  </div>
		  <div class="settings">
			  ${settings.map(setting => `<div class="row">${Settings[setting].edit(field)}</div>`).join('')}
		  </div>`
    },

    field (key, field, isEditing, settingsTab, isSpecial = false) {

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
					  ${!isSpecial ? `
					  <!-- Duplicate/Delete -->
					  <button class="duplicate" data-key="${key}"><span class="dashicons dashicons-admin-page"></span>
					  </button>
					  <button class="delete" data-key="${key}"><span class="dashicons dashicons-no"></span></button>`
						  // language=html
						  : `<button class="open" data-key="${key}"><span class="dashicons ${isEditing ? 'dashicons-arrow-up' : 'dashicons-arrow-down'}"></span></button>`}
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
				  <div id="button-settings">
					  ${this.field('button', form.button, activeField === 'button', settingsTab, true)}
				  </div>
			  </div>
			  <div id="form-preview-wrap" class="panel">
				  <label class="row-label">Preview...</label>
				  <div id="form-preview">
					  ${this.preview(form)}
				  </div>
			  </div>
		  </div>`
    },

    /**
     *
     * @param form
     * @returns {string}
     */
    preview (form) {

      const rows = groupFieldsInRows(form)

      const formHTML = rows.map(row => {

        const rowHTML = row.map(field => {

          const { column_width } = field

          // language=HTML
          return `
			  <div class="gh-form-column ${columnClasses[column_width]}">
				  <div class="gh-form-field">
					  ${previewField(field)}
				  </div>
			  </div>`

        }).join('')

        // language=HTML
        return `
			<div class="gh-form-row">${rowHTML}</div>`
      }).join('')

      //language=HTML
      return `
		  <div class="gh-form-wrapper">
			  ${formHTML}
		  </div>`
    }

  }

  const FormBuilder = (
    selector,
    form = defaultForm,
    onChange = (form) => {
      console.log(form)
    }) => ({

    form: {
      ...defaultForm,
      ...form
    },
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

      const renderPreview = () => {
        this.renderPreview()
      }

      const currentField = () => {

        switch (this.activeField) {
          case 'button':
            return this.form.button
          default:
            return this.form.fields[this.activeField]
        }
      }

      const setActiveField = (id) => {
        self.activeField = id
        self.activeFieldTab = 'content'
        render()
      }

      const addField = () => {
        this.form.fields.push(defaultField)
        setActiveField(this.form.fields.length - 1)
        onChange(this.form)
      }

      const deleteField = (id) => {
        this.form.fields.splice(id, 1)

        if (this.activeField === id) {
          this.activeField = false
          self.activeFieldTab = 'content'
        }

        render()
        onChange(this.form)
      }

      const duplicateField = (id) => {

        const field = this.form.fields[id]

        this.form.fields.splice(id, 0, field)

        setActiveField(id + 1)
        onChange(this.form)
      }

      const updateField = (atts, reRenderSettings = false, reRenderPreview = true) => {

        switch (this.activeField) {
          case 'button' :
            this.form.button = {
              ...this.form.button,
              ...atts
            }
            break
          default:
            this.form.fields[this.activeField] = {
              ...this.form.fields[this.activeField],
              ...atts
            }
            break
        }

        if (reRenderSettings) {

          render()
        } else if (reRenderPreview) {
          renderPreview()
        }

        onChange(this.form)
      }

      const $builder = $('#form-builder')

      $builder.on('click', '.add-field', addField)

      $builder.on('click', '.form-field', (e) => {

        const $field = $(e.currentTarget)
        const $target = $(e.target)

        let fieldKey = $field.data('key')

        if (fieldKey !== 'button') {
          fieldKey = parseInt(fieldKey)
        }

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
      })

      if (self.activeField !== false) {
        if (self.activeFieldTab === 'content') {
          getFieldType(currentField().type).content.forEach(setting => {
            Settings[setting].onMount(currentField(), updateField, currentField)
          })
        } else {
          getFieldType(currentField().type).advanced.forEach(setting => {
            Settings[setting].onMount(currentField(), updateField, currentField)
          })
        }
      }

      $('#form-fields').sortable({
        placeholder: 'field-placeholder',
        handle: '.field-header',
        start: function (e, ui) {
          ui.placeholder.height(ui.item.height())
          ui.placeholder.width(ui.item.width())
        },
        update: function (e, ui) {
          const newFields = []

          $('#form-fields .form-field').each(function (i) {
            const fieldId = parseInt($(this).data('key'))
            newFields.push(self.form.fields[fieldId])
          })

          self.form.fields = newFields

          render()
          onChange(self.form)
        }
      })
    },

    renderPreview () {
      $('#form-preview').html(Templates.preview(this.form))
    },

    render () {
      this.el.html(Templates.builder(this.form, this.activeField, this.activeFieldTab))
    },

  })

  Groundhogg.formBuilder = FormBuilder

})
(jQuery)
