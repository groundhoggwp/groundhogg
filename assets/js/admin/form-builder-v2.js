( function ($) {

  const {
    toggle,
    textarea,
    input,
    select,
    inputWithReplacements,
    uuid,
    inputRepeaterWidget,
    icons,
    miniModal,
    tooltip,
    copyObject,
    tinymceElement,
    sanitizeKey,
  } = Groundhogg.element
  const { sprintf, __, _x } = wp.i18n
  const { tags: TagsStore } = Groundhogg.stores
  const {
    metaPicker,
    tagPicker,
  } = Groundhogg.pickers

  const columnClasses = {
    '1/1': 'col-1-of-1',
    '1/2': 'col-1-of-2',
    '1/3': 'col-1-of-3',
    '1/4': 'col-1-of-4',
    '2/3': 'col-2-of-3',
    '3/4': 'col-3-of-4',
  }

  const defaultField = {
    className: '',
    id: '',
    type: 'text',
    name: '',
    value: '',
    label: 'New field',
    hide_label: false,
    required: false,
    column_width: '1/1',
  }

  const fieldGroups = {
    contact: __('Contact'),
    address: __('Contact Address'),
    custom: __('Custom'),
    compliance: __('Compliance'),
  }

  const defaultForm = {
    button: {
      type: 'button',
      text: 'Submit',
      label: 'Submit',
      column_width: '1/1',
    },
    recaptcha: {
      type: 'recaptcha',
      label: 'reCAPTCHA',
      text: 'reCAPTCHA',
      column_width: '1/1',
      enabled: false,
      required: true,
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
      },
    ],
  }

  const Settings = {

    basic (label, atts) {
      const { id } = atts
      // language=html
      return `<label for="${ id }">${ label }</label>
      <div class="setting">${ input(atts) }</div>`
    },
    basicWithReplacements (label, atts) {
      const { id } = atts
      return `<label for="${ id }">${ label }</label> ${ inputWithReplacements(atts) }`
    },

    html: {
      type: 'html',
      edit ({ html = '' }) {
        //language=HTML
        return `${ textarea({ id: 'html-content', value: html }) }`
      },
      onMount (field, updateField) {
        wp.editor.remove('html-content')
        tinymceElement('html-content', {
          quicktags: false,
          tinymce: {
            height: 100,
          },
        }, (content) => {
          updateField({
            html: content,
          })
        })

      },
    },
    type: {
      type: 'type',
      edit ({ type = 'text' }) {
        //language=HTML
        return `<label for="type">Type</label>
        <div class="setting">
            ${ select({
                id: 'type',
                name: 'type',
            }, getFieldTypeOptions(), type) }
        </div>`
      },
      onMount (field, updateField) {
        $('#type').on('change', (e) => {
          updateField({
            type: e.target.value,
          }, true)
        })
      },
    },
    property: {
      type: 'property',
      edit ({ property = false }) {
        //language=HTML
        return `<label for="type">${ __('Custom Field') }</label>
        <div class="setting">
            ${ select({
                id: 'property',
                name: 'property',
            }, Funnel.contact_custom_fields.map(field => ( { value: field.id, text: field.label } )), property) }
        </div>`
      },
      onMount (field, updateField) {
        $('#property').on('change', (e) => {

          let property = e.target.value
          let label = Funnel.contact_custom_fields.find(f => f.id === property).label

          updateField({
            property,
            label,
          }, true)
        })
      },
    },
    tags: {
      type: 'tags',
      edit () {
        //language=HTML
        return `<label for="type">${ __('Apply Tags') }</label>
        <div class="setting">
            ${ select({
                id: 'apply-tags',
                name: 'apply-tags',
            }) }
        </div>`
      },
      onMount ({ tags = [] }, updateField) {

        const renderTagPicker = () => {
          tagPicker('#apply-tags', true, (items) => TagsStore.itemsFetched(items), {
            data: tags.map(id => ( { id, text: TagsStore.get(id).data.tag_name, selected: true } )),
          }).on('change', e => {
            let tags = $(e.target).val().map(id => parseInt(id))
            updateField({
              tags,
            })
          })
        }

        if (tags && !TagsStore.hasItems(tags)) {
          TagsStore.fetchItems({
            id: tags,
          }).then(() => {
            renderTagPicker()
          })
        }
        else {
          renderTagPicker()
        }

      },
    },
    name: {
      type: 'name',
      edit ({ name = '' }) {
        //language=HTML
        return `<label for="type">${ __('Internal Name', 'groundhogg') }</label>
        <div class="setting">
            ${ input({
                id: 'name',
                name: 'name',
                value: name,
            }) }
        </div>`
      },
      onMount (field, updateField) {
        metaPicker('#name').on('change input', (e) => {
          updateField({
            name: e.target.value,
          })
        })
      },
    },
    required: {
      type: 'required',
      edit ({ required = false }) {
        //language=HTML
        return `<label for="required">${ __('Required', 'groundhogg') }</label>
        <div class="setting">${ toggle({
            id: 'required',
            name: 'required',
            className: 'required',
            onLabel: 'Yes',
            offLabel: 'No',
            checked: required,
        }) }
        </div>`
      },
      onMount (field, updateField) {
        $('#required').on('change', (e) => {
          updateField({
            required: e.target.checked,
          })
        })
      },
    },
    enabled: {
      type: 'enabled',
      edit ({ enabled = false }) {
        //language=HTML
        return `<label for="enabled">${ __('Enabled', 'groundhogg') }</label>
        <div class="setting">${ toggle({
            id: 'enabled',
            name: 'enabled',
            className: 'enabled',
            onLabel: 'Yes',
            offLabel: 'No',
            checked: enabled,
        }) }
        </div>`
      },
      onMount (field, updateField) {
        $('#enabled').on('change', (e) => {
          updateField({
            enabled: e.target.checked,
          })
        })
      },
    },
    checked: {
      type: 'checked',
      edit ({ checked = false }) {
        //language=HTML
        return `<label for="required">${ __('Checked by default', 'groundhogg') }</label>
        <div class="setting">${ toggle({
            id: 'checked',
            name: 'checked',
            className: 'checked',
            onLabel: 'Yes',
            offLabel: 'No',
            checked,
        }) }
        </div>`
      },
      onMount (field, updateField) {
        $('#checked').on('change', (e) => {
          updateField({
            checked: e.target.checked,
          })
        })
      },
    },
    label: {
      type: 'label',
      edit ({ label = '' }) {
        return Settings.basic('Label', {
          id: 'label',
          name: 'label',
          className: 'label',
          value: label,
          placeholder: '',
        })
      },
      onMount (field, updateField) {

        $('#label').on('change input', (e) => {

          let label = e.target.value

          updateField({
            label,
          })

          if (!field.name) {
            $('#name').val(sanitizeKey(label)).trigger('change')
          }
        })
      },
    },
    hideLabel: {
      type: 'hideLabel',
      edit ({ hide_label = false }) {
        //language=HTML
        return `<label for="hide-label">Hide label</label>
        <div class="setting">${ toggle({
            id: 'hide-label',
            name: 'hide_label',
            className: 'hide-label',
            onLabel: 'Yes',
            offLabel: 'No',
            checked: hide_label,
        }) }
        </div>`
      },
      onMount (field, updateField) {
        $('#hide-label').on('change', (e) => {
          updateField({
            hide_label: e.target.checked,
          })
        })
      },
    },
    text: {
      type: 'text',
      edit ({ text = '' }) {
        return Settings.basic('Button Text', {
          id: 'text',
          name: 'text',
          className: 'text regular-text',
          value: text,
          placeholder: '',
        })
      },
      onMount (field, updateField) {
        $('#text').on('change input', (e) => {
          updateField({
            text: e.target.value,
          })
        })
      },
    },
    value: {
      type: 'value',
      edit ({ value = '' }) {
        return Settings.basicWithReplacements('Value', {
          id: 'value',
          name: 'value',
          className: 'value regular-text',
          value: value,
          placeholder: '',
        })
      },
      onMount (field, updateField) {
        $('#value').on('change input', (e) => {
          updateField({
            value: e.target.value,
          })
        })
      },
    },
    placeholder: {
      type: 'placeholder',
      edit ({ placeholder = '' }) {
        return Settings.basic('Placeholder', {
          id: 'placeholder',
          name: 'Placeholder',
          className: 'placeholder',
          value: placeholder,
          placeholder: '',
        })
      },
      onMount (field, updateField) {
        $('#placeholder').on('change input', (e) => {
          updateField({
            placeholder: e.target.value,
          })
        })
      },
    },
    id: {
      type: 'id',
      edit ({ id = '' }) {
        return Settings.basic('CSS Id', {
          id: 'css-id',
          name: 'id',
          className: 'css-id',
          value: id,
          placeholder: 'css-id',
        })
      },
      onMount (field, updateField) {
        $('#css-id').on('change input', (e) => {
          updateField({
            id: e.target.value,
          })
        })
      },
    },
    className: {
      type: 'className',
      edit ({ className = '' }) {
        return Settings.basic('CSS Class', {
          id: 'className',
          name: 'className',
          className: 'css-class-name',
          value: className,
          placeholder: 'css-class-name',
        })
      },
      onMount (field, updateField) {
        $('#className').on('change input', (e) => {
          updateField({
            className: e.target.value,
          })
        })
      },
    },
    phoneType: {
      type: 'phoneType',
      edit ({ phone_type = 'primary' }) {
        //language=HTML
        return `<label for="phone-type">${ _x('Phone Type', 'form field setting', 'groundhogg') }</label>
        <div class="setting">${ select({
            id: 'phone-type',
            name: 'phone_type',
            className: 'phone-type',
        }, {
            primary: 'Primary Phone',
            mobile: 'Mobile Phone',
            company: 'Company Phone',
        }, phone_type) }
        </div>`
      },
      onMount (field, updateField) {
        $('#phone-type').on('change', (e) => {
          updateField({
            phone_type: e.target.value,
          })
        })
      },
    },
    columnWidth: {
      type: 'columnWidth',
      edit ({ column_width }) {
        //language=HTML
        return `<label for="column-width">Column Width</label>
        <div class="setting">${ select({
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
        }, column_width) }
        </div>`
      },
      onMount (field, updateField) {
        $('#column-width').on('change', (e) => {
          updateField({
            column_width: e.target.value,
          })
        })
      },
    },
    fileTypes: {
      type: 'fileTypes',
      edit: ({ file_types }) => {
        // language=HTML
        return `
            <div class="setting">
                <label>${ _x('Restrict file types', 'groundhogg') }</label>
                ${ select({
                    name: 'file-types',
                    id: 'file-types',
                    multiple: true,
                }, [
                    { text: 'jpeg', value: 'jpeg' },
                    { text: 'png', value: 'png' },
                    { text: 'pdf', value: 'pdf' },
                    { text: 'doc', value: 'doc' },
                    { text: 'docx', value: 'docx' },
                ], file_types) }
            </div>`
      },
      onMount: (field, updateField) => {
        $('#file-types').select2().on('change', (e) => {
          updateField({
            file_types: $(e.target).val(),
          })
        })
      },
    },
    options: {
      type: 'options',
      edit ({ options = [''] }) {

        const selectOption = (option, i) => {
          // language=HTML
          return `
              <div class="select-option-wrap">
                  ${ input({
                      id: `select-option-${ i }`,
                      className: 'select-option',
                      value: option,
                      dataKey: i,
                  }) }
                  <button class="dashicon-button remove-option" data-key="${ i }"><span
                          class="dashicons dashicons-no-alt"></span></button>
              </div>`
        }

        // language=HTML
        return `
            <div class="options full-width">
                <label>${ _x('Options', 'label for dropdown options', 'groundhogg') }</label>
                <div class="select-options"></div>
            </div>`
      },
      onMount ({ options = [['', []]] }, updateField, currentField) {

        let allTags = options.map(opt => opt[1]).reduce((a, i) => [...a, i], [])

        if (!TagsStore.hasItems(allTags)) {
          TagsStore.fetchItems({
            id: allTags,
          })
        }

        inputRepeaterWidget({
          selector: '.select-options',
          rows: options,
          sortable: true,
          cellCallbacks: [
            input, (field) => {
              // language=HTML
              return `
                  <div class="inline-tag-picker">
                      ${ icons.tag }
                      ${ input({
                          className: 'input hidden tags-input',
                          ...field,
                      }) }
                  </div>`
            },
          ],
          onMount: () => {

            let modal = false

            const openModal = (el) => {

              if (modal) {
                modal.close()
              }

              modal = miniModal(el, {
                content: select({
                  id: 'tags',
                }),
                onOpen: () => {

                  let $input = $($(el).find('input'))
                  let selected = $input.val().split(',').map(t => parseInt(t)).filter(id => TagsStore.has(id))

                  tagPicker('#tags', true, (items) => TagsStore.itemsFetched(items), {
                    data: selected.map(id => ( { id, text: TagsStore.get(id).data.tag_name, selected: true } )),
                  }).on('change', e => {
                    let tagIds = $(e.target).val().map(id => parseInt(id))
                    $input.val(tagIds.join(',')).trigger('change')
                  })
                },
                closeOnFocusout: false,
              })

            }

            $('.inline-tag-picker').on('click', e => {
              let el = e.currentTarget
              openModal(el)
            })

            tooltip('.inline-tag-picker', {
              content: __('Apply a tag'),
            })
          },
          cellProps: [{ placeholder: _x('Value...', 'input placeholder', 'groundhogg') }, {}],
          onChange: (rows) => {
            updateField({
              options: rows,
            })
          },
        }).mount()
      },
    },

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
      ...FieldTypes[type],
    }
  }

  const getFieldTypeOptions = () => {

    const options = []

    for (const type in FieldTypes) {
      if (FieldTypes.hasOwnProperty(type) && FieldTypes[type].hasOwnProperty('name') &&
        !FieldTypes[type].hasOwnProperty('hide')) {
        options.push({
          value: type,
          text: FieldTypes[type].name,
          group: fieldGroups[FieldTypes[type].group],
        })
      }
    }

    return options
  }

  const standardContentSettings = [
    Settings.type.type,
    Settings.label.type,
    Settings.placeholder.type,
    Settings.hideLabel.type,
    Settings.required.type,
    Settings.columnWidth.type,
  ]

  const standardMetaContentSettings = [
    Settings.type.type,
    Settings.label.type,
    Settings.name.type,
    Settings.placeholder.type,
    Settings.hideLabel.type,
    Settings.required.type,
    Settings.columnWidth.type,
  ]

  const standardAdvancedSettings = [
    Settings.value.type,
    Settings.id.type,
    Settings.className.type,
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
    className = '',
  }) => {

    const inputField = input({
      id: id,
      type: type,
      name: name,
      placeholder: placeholder,
      value: value,
      className: `gh-input ${ className }`,
    })

    if (hide_label) {
      return inputField
    }

    if (required) {
      label += ' <span class="required">*</span>'
    }

    return `<label class="gh-input-label" for="${ id }">${ label }</label><div class="gh-form-field-input">${ inputField }</div>`
  }

  const FieldTypes = {
    default: {
      name: 'default',
      content: [],
      advanced: [],
      hide: true,
      preview: (field) => fieldPreview({
        ...field,
        type: 'text',
      }),
    },
    recaptcha: {
      name: 'reCAPTCHA',
      hide: true,
      content: [
        Settings.enabled.type,
        Settings.columnWidth.type,
      ],
      advanced: [
        Settings.id.type,
        Settings.className.type,
      ],
      preview ({ id = '', className = '' }) {
        return `<div id="${ id }" class="${ className }"><div id="recaptcha-here" class="gh-panel outlined" style="width: fit-content"><div class="inside">${ __(
          'reCAPTCHA: <i>Only displayed on the front-end.</i>', 'groundhogg') }</div></div></div>`
      },
    },
    button: {
      name: 'Button',
      hide: true,
      content: [
        Settings.text.type,
        Settings.columnWidth.type,
      ],
      advanced: [
        Settings.id.type,
        Settings.className.type,
      ],
      preview ({ text = 'Submit', id = '', className = '' }) {
        return `<button id="${ id }" class="gh-button primary ${ className } full-width">${ text }</button>`
      },
    },
    first: {
      group: 'contact',
      name: 'First Name',
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        name: 'first_name',
        type: 'text',
      }),
    },
    last: {
      group: 'contact',
      name: 'Last Name',
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        name: 'last_name',
        type: 'text',
      }),
    },
    email: {
      group: 'contact',
      name: 'Email',
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        type: 'email',
        name: 'email',
      }),
    },
    phone: {
      group: 'contact',
      name: 'Phone Number',
      content: [
        Settings.type.type,
        Settings.label.type,
        Settings.phoneType.type,
        Settings.placeholder.type,
        Settings.hideLabel.type,
        Settings.required.type,
        Settings.columnWidth.type,
      ],
      advanced: standardAdvancedSettings,
      preview: ({ phone_type = 'primary',...field }) => fieldPreview({
        ...field,
        type: 'tel',
        name: phone_type + '_phone',
      }),
    },
    line1: {
      group: 'address',
      name: __('Line 1', 'groundhogg'),
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        name: 'line1',
        type: 'text',
      }),
    },
    line2: {
      group: 'address',
      name: __('Line 2', 'groundhogg'),
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        name: 'line2',
        type: 'text',
      }),
    },
    city: {
      group: 'address',
      name: __('City', 'groundhogg'),
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        name: 'city',
        type: 'text',
      }),
    },
    state: {
      group: 'address',
      name: __('State', 'groundhogg'),
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        name: 'state',
        type: 'text',
      }),
    },
    zip_code: {
      group: 'address',
      name: __('Zip Code', 'groundhogg'),
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        name: 'zip_code',
        type: 'text',
      }),
    },
    country: {
      group: 'address',
      name: __('Country', 'groundhogg'),
      content: standardContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        name: 'country',
        type: 'text',
      }),
    },
    gdpr: {
      group: 'compliance',
      name: __('GDPR Consent', 'groundhogg'),
      content: [
        Settings.type.type,
        Settings.columnWidth.type,
      ],
      advanced: [
        Settings.id.type,
        Settings.className.type,
      ],
      preview: ({
        className = '',
        checked = false,
      }) => {

        const dataField = input({
          id: 'data-processing-consent',
          type: 'checkbox',
          className: `gh-checkbox-input ${ className }`,
          name: 'data_processing_consent',
          required: true,
          value: 'yes',
          checked,
        })

        let dataLabel = sprintf(__('I agree to %s\'s storage and processing of my personal data.', 'groundhogg'),
          Groundhogg.name)

        const marketingField = input({
          id: 'marketing-consent',
          type: 'checkbox',
          className: `gh-checkbox-input ${ className }`,
          name: 'marketing_consent',
          required: true,
          value: 'yes',
          checked,
        })

        let marketingLabel = sprintf(__('I agree to receive marketing offers and updates from %s.', 'groundhogg'),
          Groundhogg.name)

        //language=HTML
        return `
            <div><label class="gh-input-label">${ dataField } ${ dataLabel } <span class="required">*</span></label>
            </div>
            <div><label class="gh-input-label">${ marketingField } ${ marketingLabel } <span
                    class="required">*</span></label></div>
        `
      },
    },
    terms: {
      group: 'compliance',
      name: __('Terms & Conditions', 'groundhogg'),
      content: [
        Settings.type.type,
        Settings.columnWidth.type,
      ],
      advanced: [
        Settings.className.type,
      ],
      preview: ({
        className = '',
        checked = false,
      }) => {

        const field = input({
          id: 'groundhogg-terms',
          type: 'checkbox',
          className: `gh-checkbox-input ${ className }`,
          name: 'groundhogg_terms',
          required: true,
          value: 'yes',
          checked,
        })

        label = __('I agree to the terms & conditions.', 'groundhogg')

        //language=HTML
        return `<label class="gh-input-label">${ field } ${ label } <span class="required">*</span></label>`
      },
    },
    text: {
      group: 'custom',
      name: 'Text',
      content: standardMetaContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview(field),
    },
    url: {
      group: 'custom',
      name: 'URL',
      content: standardMetaContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        type: 'url',
      }),
    },
    textarea: {
      group: 'custom',
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
        className = '',
      }) => {

        const inputField = textarea({
          id: id,
          type: type,
          name: name,
          placeholder: placeholder,
          value: value,
          className: `gh-input ${ className }`,
        })

        if (hide_label) {
          return inputField
        }

        if (required) {
          label += ' <span class="required">*</span>'
        }

        return `<label class="gh-input-label" for="${ id }">${ label }</label><div class="gh-form-field-input">${ inputField }</div>`
      },
    },
    number: {
      group: 'custom',
      name: 'Number',
      content: standardMetaContentSettings,
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        type: 'number',
      }),
    },
    dropdown: {
      group: 'custom',
      name: 'Dropdown',
      content: [
        Settings.type.type,
        Settings.label.type,
        Settings.name.type,
        Settings.placeholder.type,
        Settings.hideLabel.type,
        Settings.required.type,
        Settings.options.type,
        Settings.columnWidth.type,
      ],
      advanced: [
        Settings.id.type,
        Settings.className.type,
      ],
      preview: ({
        id = uuid(),
        name = 'name',
        options = [],
        placeholder = '',
        label = '',
        hide_label = false,
        required = false,
        className = '',
      }) => {

        options = options.map(opt => ( {
          text: Array.isArray(opt) ? opt[0] : opt,
          value: Array.isArray(opt) ? opt[0] : opt,
        } ))

        if (placeholder) {
          options.unshift({
            text: placeholder,
            value: '',
          })
        }

        const inputField = select({
          id: id,
          name: name,
          className: `gh-input ${ className }`,
        }, options)

        if (hide_label) {
          return inputField
        }

        if (required) {
          label += ' <span class="required">*</span>'
        }

        return `<label class="gh-input-label" for="${ id }">${ label }</label><div class="gh-form-field-input">${ inputField }</div>`
      },
    },
    radio: {
      group: 'custom',
      name: 'Radio',
      content: [
        Settings.type.type,
        Settings.label.type,
        Settings.name.type,
        // Settings.hideLabel.type,
        Settings.required.type,
        Settings.options.type,
        Settings.columnWidth.type,
      ],
      advanced: [
        Settings.id.type,
        Settings.className.type,
      ],
      preview: ({
        id = uuid(),
        name = 'name',
        options = [],
        label = '',
        // hide_label = false,
        required = false,
        className = '',
      }) => {

        const inputField = options.map(opt => {
          // language=HTML
          return `
              <div class="gh-radio-wrapper">
                  <label class="gh-radio-label">
                      ${ input({
                          type: 'radio',
                          id,
                          className,
                          name,
                          value: Array.isArray(opt) ? opt[0] : opt,
                      }) } ${ Array.isArray(opt) ? opt[0] : opt }
                  </label>
              </div>`
        }).join('')

        if (required) {
          label += ' <span class="required">*</span>'
        }

        return `<label class="gh-input-label" for="${ id }">${ label }</label><div class="gh-form-field-input">${ inputField }</div>`
      },
    },
    checkboxes: {
      group: 'custom',
      name: __('Checkbox List'),
      content: [
        Settings.type.type,
        Settings.label.type,
        Settings.name.type,
        // Settings.hideLabel.type,
        Settings.required.type,
        Settings.options.type,
        Settings.columnWidth.type,
      ],
      advanced: [
        Settings.id.type,
        Settings.className.type,
      ],
      preview: ({
        id = uuid(),
        name = 'name',
        options = [],
        label = '',
        required = false,
        className = '',
      }) => {

        const inputField = options.map(opt => {
          // language=HTML
          return `
              <div class="gh-radio-wrapper">
                  <label class="gh-radio-label">
                      ${ input({
                          type: 'checkbox',
                          id,
                          // required,
                          className,
                          name: name + '[]',
                          value: Array.isArray(opt) ? opt[0] : opt,
                      }) } ${ Array.isArray(opt) ? opt[0] : opt }
                  </label>
              </div>`
        }).join('')

        if (required) {
          label += ' <span class="required">*</span>'
        }

        return `<label class="gh-input-label" for="${ id }">${ label }</label><div class="gh-form-field-input">${ inputField }</div>`
      },
    },
    checkbox: {
      group: 'custom',
      name: __('Checkbox', 'groundhogg'),
      content: [
        Settings.type.type,
        Settings.label.type,
        Settings.name.type,
        Settings.tags.type,
        Settings.required.type,
        Settings.checked.type,
        Settings.columnWidth.type,
      ],
      advanced: standardAdvancedSettings,
      preview: ({
        id = uuid(),
        name = 'name',
        value = '1',
        label = '',
        required = false,
        className = '',
        checked = false,
      }) => {

        if (!value) {
          value = '1'
        }

        const inputField = input({
          id: id,
          type: 'checkbox',
          className: `gh-checkbox-input ${ className }`,
          name,
          value,
          checked,
        })

        if (required) {
          label += ' <span class="required">*</span>'
        }

        return `<label class="gh-input-label">${ inputField } ${ label }</label>`
      },
    },
    birthday: {},
    date: {
      group: 'custom',
      name: 'Date',
      content: [
        Settings.type.type,
        Settings.label.type,
        Settings.name.type,
        Settings.hideLabel.type,
        Settings.required.type,
        Settings.columnWidth.type,
      ],
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        type: 'date',
      }),
    },
    time: {
      group: 'custom',
      name: _x('Time', 'form field', 'groundhogg'),
      content: [
        Settings.type.type,
        Settings.label.type,
        Settings.name.type,
        Settings.hideLabel.type,
        Settings.required.type,
        Settings.columnWidth.type,
      ],
      advanced: standardAdvancedSettings,
      preview: (field) => fieldPreview({
        ...field,
        type: 'time',
      }),
    },
    file: {
      group: 'custom',
      name: _x('File', 'form field', 'groundhogg'),
      content: [
        Settings.type.type,
        Settings.name.type,
        Settings.required.type,
        Settings.hideLabel.type,
        Settings.label.type,
        Settings.fileTypes.type,
        Settings.columnWidth.type,
      ],
      advanced: [
        Settings.id.type,
        Settings.className.type,
      ],
      preview: (field) => fieldPreview({
        ...field,
        type: 'file',
      }),
    },
    custom_field: {
      group: 'contact',
      name: _x('Custom Field', 'form field', 'groundhogg'),
      content: [
        Settings.type.type,
        Settings.property.type,
        Settings.required.type,
        Settings.columnWidth.type,
      ],
      advanced: [
        Settings.value.type,
        Settings.id.type,
        Settings.className.type,
      ],
      preview: ({
        id = uuid(),
        property = false,
        value = '',
        required = false,
        className = '',
      }) => {

        property = Funnel.contact_custom_fields.find(f => f.id === property)

        if (!property) {
          return ''
        }

        property = copyObject(property)

        return FieldTypes[property.type].preview({
          ...property,
          value,
          required,
          className,
        })
      },
    },
    html: {
      group: 'custom',
      name: _x('HTML', 'form field', 'groundhogg'),
      content: [
        Settings.type.type,
        Settings.html.type,
        Settings.columnWidth.type,
      ],
      advanced: [
        Settings.id.type,
        Settings.className.type,
      ],
      preview: ({
        id = uuid(),
        html = '',
        className = '',
      }) => {
        return `<div id="${ id }" class="${ className }">${ html }</div>`
      },
    },
  }

  const Templates = {

    settings (field, settingsTab) {

      const fieldType = getFieldType(field.type)

      const settings = settingsTab === 'advanced' ? fieldType.advanced : fieldType.content

      // language=HTML
      return `
          <div class="settings-tabs">
              <a class="settings-tab ${ settingsTab === 'content' ? 'active' : '' }" data-tab="content">Content</a>
              <a class="settings-tab ${ settingsTab === 'advanced' ? 'active' : '' }" data-tab="advanced">Advanced</a>
          </div>
          <div class="settings">
              ${ settings.map(setting => `<div class="row">${ Settings[setting].edit(field) }</div>`).join('') }
          </div>`
    },

    field (key, field, isEditing, settingsTab, isSpecial = false) {

      const { type, label } = field

      const fieldType = getFieldType(type)

      //language=HTML
      return `
          <div class="form-field" data-key="${ key }">
              <div class="field-header">
                  <div class="details">
                      <div class="field-label">${ label }</div>
                      <div class="field-type">${ fieldType.name }</div>
                  </div>
                  <div class="actions">
                      ${ !isSpecial ? `
					  <!-- Duplicate/Delete -->
					  <button class="duplicate" data-key="${ key }"><span class="dashicons dashicons-admin-page"></span>
					  </button>
					  <button class="delete" data-key="${ key }"><span class="dashicons dashicons-no"></span></button>`
                              // language=html
                              : `<button class="open" data-key="${ key }"><span class="dashicons ${ isEditing
                                      ? 'dashicons-arrow-up'
                                      : 'dashicons-arrow-down' }"></span></button>` }
                  </div>
              </div>
              ${ isEditing ?
                      //language=HTML
                      Templates.settings(field, settingsTab) : '' }
          </div>`
    },

    builder (form, activeField, settingsTab) {

      //language=HTML
      return `
          <div id="form-builder" data-id="${ form.id }">
              <div id="fields-editor" class="fields-editor">
                  <div id="form-fields">
                      ${ form.fields.map(
                              (field, index) => Templates.field(index, field, activeField === index, settingsTab)).
                              join('') }
                  </div>
                  <button class="add-field gh-button secondary">${ __('Add Field', 'groundhogg') }</button>
                  <div id="special-fields">
                      ${ this.field('recaptcha', form.recaptcha, activeField === 'recaptcha', settingsTab, true) }
                      ${ this.field('button', form.button, activeField === 'button', settingsTab, true) }
                  </div>
              </div>
              <div id="form-preview-wrap" class="panel">
                  <label class="row-label">Preview...</label>
                  <div id="form-preview">
                      ${ this.preview(form) }
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

      let { button, recaptcha, fields } = form

      let tmpFields = [...fields]

      if (recaptcha.enabled) {
        tmpFields.push(recaptcha)
      }

      tmpFields.push(button)

      const formHTML = tmpFields.map(field => {

        const { column_width } = field

        // language=HTML
        return `
            <div class="gh-form-column ${ columnClasses[column_width] }">
                ${ previewField(field) }
            </div>`

      }).join('')

      //language=HTML
      return `
          <div class="gh-form-wrapper">
              ${ formHTML }
          </div>`
    },

  }

  const FormBuilder = (
    selector,
    form = defaultForm,
    onChange = (form) => {
      console.log(form)
    }) => ( {

    form: {
      ...defaultForm,
      ...form,
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
          case 'recaptcha':
            return this.form.recaptcha
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
              ...atts,
            }
            break
          case 'recaptcha' :
            this.form.recaptcha = {
              ...this.form.recaptcha,
              ...atts,
            }
            break
          default:
            this.form.fields[this.activeField] = {
              ...this.form.fields[this.activeField],
              ...atts,
            }
            break
        }

        if (reRenderSettings) {

          render()
        }
        else if (reRenderPreview) {
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

        if (fieldKey !== 'button' && fieldKey !== 'recaptcha') {
          fieldKey = parseInt(fieldKey)
        }

        if ($target.is('button.delete, button.delete .dashicons')) {
          deleteField(fieldKey)
        }
        else if ($target.is('button.duplicate, button.duplicate .dashicons')) {
          duplicateField(fieldKey)
        }
        else {
          if (fieldKey !== self.activeField) {
            setActiveField(fieldKey)
          }
          else if (e.target.classList.contains('settings-tab')) {
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
        }
        else {
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

          this.activeField = false
          this.activeFieldTab = 'content'

          const newFields = []

          $('#form-fields .form-field').each(function (i) {
            const fieldId = parseInt($(this).data('key'))
            newFields.push(self.form.fields[fieldId])
          })

          self.form.fields = newFields

          render()
          onChange(self.form)
        },
      })
    },

    renderPreview () {
      $('#form-preview').html(Templates.preview(this.form))
    },

    render () {
      this.el.html(Templates.builder(this.form, this.activeField, this.activeFieldTab))
    },

  } )

  Groundhogg.formBuilder = FormBuilder

} )
(jQuery)