(($) => {

  const { notes: NotesStore } = Groundhogg.stores
  const {
    uuid,
    specialChars,
    icons,
    modal,
    copyObject,
    input,
    select,
    tinymceElement,
    moreMenu,
    tooltip,
    inputRepeaterWidget,
    dangerConfirmationModal,
    toggle,
  } = Groundhogg.element
  const { post, get, patch, routes, ajax } = Groundhogg.api
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { sprintf, __, _x, _n } = wp.i18n

  const optionsRepeater = ({
    selector,
    options,
    onChange,
  }) => {

    inputRepeaterWidget({
      selector,
      rows: options.map(o => ([o])),
      cellCallbacks: [input],
      cellProps: [{ placeholder: __('Option') }],
      onChange: (r) => {
        onChange(r.map(r => r[0]))
      }
    }).mount()

  }

  const fieldTypes = {

    text: {
      name: __('Text', 'groundhogg'),
      view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${input({
			...props,
			type: 'text'
		})}`
      },
      onMount: ({ id }, onChange) => {
        $(`#${id}`).on('change', (e) => {
          onChange({
            [props.name]: e.target.value
          })
        })
      },
      edit: () => {
        return ''
      },
      onEditMount: () => {}
    },
    number: {
      name: __('Number', 'groundhogg'),
      view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${input({
			...props,
			type: 'number',
		})}`
      },
      onMount: ({ id, name }, onChange) => {
        $(`#${id}`).on('change', (e) => {
          onChange({
            [name]: e.target.value
          })
        })
      },
      edit: () => {
        return ''
      },
      onEditMount: () => {}
    },
    date: {
      name: __('Date', 'groundhogg'),
      view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${input({
			...props,
			type: 'date',
		})}`
      },
      onMount: ({ id, name }, onChange) => {
        $(`#${id}`).on('change', (e) => {
          onChange({
            [name]: e.target.value
          })
        })
      },
      edit: () => {
        return ''
      },
      onEditMount: () => {}
    },
    checkboxes: {
      name: __('Checkboxes', 'groundhogg'),
      view: () => {},
      onMount: () => {},
      edit: () => {},
      onEditMount: () => {}
    },

    radio: {
      name: __('Radio Buttons', 'groundhogg'),
      view: () => {},
      onMount: ({ id }, onChange) => {

      },
      edit: () => {},
      onEditMount: () => {}
    },
    dropdown: {
      name: __('Dropdown', 'groundhogg'),
      view: ({ label, value, options, blankOption, ...props }) => {

        options = options.map(o => ({ text: o, value: o }))

        if (blankOption) {
          options.unshift({ text: __('Select...'), value: '' })
        }

        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${select({
			...props,
		}, options, value)}`
      },
      onMount: ({ id, multiple, name, ...props }, onChange) => {
        $(`#${id}`).on('change', (e) => {

          if (multiple) {
            onChange({
              [name]: e.target.selectedOptions.map(o => o.value)
            })
          } else {
            onChange({
              [name]: e.target.value
            })
          }
        })
      },
      edit: (field) => {

        const { multiple, blankOption } = field

        //language=HTML
        return `
			<div class="gh-rows-and-columns">
				<div class="gh-row">
					<div class="gh-col">
						<label>${__('Options', 'groundhogg')}</label>
						<div id="property-dropdown-options"></div>
					</div>
				</div>
				<div class="gh-row">
					<div class="gh-col">
						<label class="space-between">${__('Allow multiple selections?', 'groundhogg')} ${toggle({
							id: 'allow-multiple',
							checked: multiple
						})} </label>
					</div>
				</div>
				<div class="gh-row">
					<div class="gh-col">
						<label class="space-between">${__('Insert blank option?', 'groundhogg')} ${toggle({
							id: 'blank-option',
							checked: blankOption
						})} </label>
					</div>
				</div>
			</div>
        `
      },
      onEditMount: (field, updateField) => {
        optionsRepeater({
          selector: '#property-dropdown-options',
          options: field.options || [''],
          onChange: (options) => updateField({ options })
        })

        $('#allow-multiple').on('change', (e) => {
          updateField({
            multiple: e.target.checked
          })
        })

        $('#blank-option').on('change', (e) => {
          updateField({
            blankOption: e.target.checked
          })
        })
      }

    }

  }

  const Templates = {

    noProperties: () => {
      //language=HTML
      return `
		  <button id="add-custom-property" class="gh-button secondary">
			  ${__('Add custom properties', 'groundhogg')}
		  </button>`
    },

    groups: ({ groups, fields = [] }) => {
      //language=HTML
      return `
		  <div class="property-groups">
			  ${groups.map(g => Templates.group(g, fields.filter(f => f.group == g.id)))}
		  </div>`
    },

    group: (group, fields = []) => {
      //language=HTML
      return `
		  <div class="property-group">
			  <div class="property-group-header">
				  <h3>${specialChars(group.name)}</h3>
				  <button class="gh-button text icon secondary property-group-more" data-id="${group.id}">
					  ${icons.verticalDots}
				  </button>
			  </div>
			  <div class="property-group-fields">
				  ${fields.map(f => Templates.field(f)).join('')}
			  </div>
		  </div>`
    },

    addPropertyGroup: () => {

      //language=HTML
      return `
		  <div class="property-group">
			  <h3 class="no-margin-top">${__('Add property group')}</h3>
			  <div class="gh-input-group">
				  ${input({
					  id: 'property-group-name',
					  name: 'property_group_name',
					  placeholder: __('New property group name', 'groundhogg')
				  })}
				  <button class="gh-button primary" id="create-property-group">${__('Create Group', 'groundhogg')}
				  </button>
			  </div>
		  </div>`
    },

    addField: (field) => {

      const { type, label, name, id } = field

      //language=HTML
      return `
		  <div class="property-field">
			  <h3 class="no-margin-top">${id ? __('Edit field', 'groundhogg') : __('Add field', 'groundhogg')}</h3>
			  <div class="gh-rows-and-columns">
				  <div class="gh-row">
					  <div class="gh-col">
						  <label class="">${__('Field Label', 'groundhogg')}</label>
						  ${input({
							  id: 'property-field-label',
							  name: 'property_field_label',
							  placeholder: __('New field label', 'groundhogg'),
							  value: label
						  })}
					  </div>
				  </div>
				  <div class="gh-row">
					  <div class="gh-col">
						  <label class="">${__('Internal Name', 'groundhogg')}</label>
						  ${input({
							  id: 'property-field-name',
							  name: 'property_field_name',
							  placeholder: __('internal_field_name', 'groundhogg'),
							  value: name
						  })}
					  </div>
				  </div>
				  <div class="gh-row">
					  <div class="gh-col">
						  <label class="">${__('Field Type', 'groundhogg')}</label>
						  ${select({
							  id: 'property-field-type'
						  }, Object.keys(fieldTypes).map(type => ({ value: type, text: fieldTypes[type].name })), type)}
					  </div>
				  </div>
				  <div class="gh-row">
					  <div class="gh-col">
						  ${fieldTypes[type].edit(field)}
					  </div>
				  </div>
				  <div class="gh-row">
					  <div class="gh-col">
						  <button class="gh-button primary" id="create-property-field">
							  ${id ? __('Update Field') : __('Create Field', 'groundhogg')}
						  </button>
					  </div>
				  </div>
			  </div>
		  </div>`
    },

    field: ({group, ...field}) => {
      //language=HTML
      return `
		  <div class="property-field" data-group="${group}" data-id="${field.id}">
			  ${fieldTypes[field.type].view(field)}
		  </div>`
    }

  }

  const Properties = (selector, {
    properties = {
      groups: [],
      fields: [],
    },
    values = {}, // usually just the object meta
    onPropertiesUpdated = (properties) => {},
    onChange = (properties) => {},
  }) => {

    properties = copyObject(properties)

    const removeGroup = (id) => {
      properties = {
        ...properties,
        groups: [
          ...properties.groups.filter(g => g.id != id)
        ],
        fields: [
          ...properties.fields.filter(f => f.group != id)
        ]
      }

      onPropertiesUpdated(properties)
      mount()
    }

    const removeField = (id) => {
      properties = {
        ...properties,
        fields: [
          ...properties.fields.filter(f => f.id != id)
        ]
      }

      onPropertiesUpdated(properties)
      mount()
    }

    const addField = (field) => {
      if (!properties.fields) {
        properties.fields = []
      }

      properties.fields.push({
        ...field,
        id: uuid()
      })

      onPropertiesUpdated(properties)

      mount()
    }

    const editField = (fieldId, field) => {

      properties.fields = [
        ...properties.fields.map(f => f.id === fieldId ? field : f)
      ]

      onPropertiesUpdated(properties)

      mount()
    }

    const addGroup = (name) => {

      if (!properties.groups) {
        properties.groups = []
      }

      properties.groups.push({
        id: uuid(),
        name
      })

      onPropertiesUpdated(properties)

      mount()
    }
    
    const addPropertyGroupModal = () => {
      const { close } = modal({
        content: Templates.addPropertyGroup()
      })

      let groupName

      $('#property-group-name').on('change input', (e) => {
        groupName = e.target.value
      })

      $('#create-property-group').on('click', (e) => {
        if (groupName.length) {
          close()
          addGroup(groupName)
        }
      })
    }

    const mount = () => {

      if (!properties || !properties.groups || !properties.groups.length) {
        $(selector).html(Templates.noProperties())
        $('#add-custom-property').on('click', (e) => {
          addPropertyGroupModal()
        })
        return
      }

      $(selector).html(Templates.groups({
        ...properties,
        fields: properties.fields.map(f => ({ ...f, value: values[f.name] || '' }))
      }))
      onMount()
    }

    const onMount = () => {

      properties.fields.forEach(f => {
        fieldTypes[f.type].onMount(f, (props) => {
          onChange(props)
        })
      })

      $('.property-field').on('dblclick', (e) => {
        moreMenu(e.currentTarget, {
          items: [
            {
              key: 'edit',
              text: __('Edit field', 'groundhogg')
            },
            {
              key: 'delete',
              text: `<span class="gh-text danger">${__('Delete')}</span>`
            }
          ],
          onSelect: k => {

            const fieldId = e.currentTarget.dataset.id

            let field = properties.fields.find(f => f.id == fieldId)

            switch (k) {
              case 'edit':

                let newField = {
                  ...field
                }

                const onAddFieldMount = () => {

                  const updateField = (props, r = false) => {
                    newField = {
                      ...newField,
                      ...props
                    }

                    if (r) {
                      setContent(Templates.addField(newField))
                      onAddFieldMount()
                    }
                  }

                  $('#property-field-label').on('input change', (e) => {
                    newField.label = e.target.value
                  })

                  $('#property-field-name').on('input change', (e) => {
                    newField.name = e.target.value
                  })

                  $('#property-field-type').on('change', (e) => {
                    newField.type = e.target.value
                    setContent(Templates.addField(newField))
                    onAddFieldMount()
                    $('#property-field-type').focus()
                  })

                  $('#create-property-field').on('click', (e) => {
                    editField(fieldId, newField)
                    close()
                  })

                  fieldTypes[newField.type].onEditMount(newField, updateField)
                }

                const { close, setContent } = modal({
                  content: Templates.addField(newField)
                })

                onAddFieldMount()
                break
              case 'delete':
                dangerConfirmationModal({
                  alert: `<p>${__('Are you sure you want to delete this property?', 'groundhogg')}</p>`,
                  onConfirm: () => {
                    removeField(fieldId)
                  }
                })
                break
            }
          }
        })
      })

      $('.property-group-more').on('click', (e) => {

        const groupId = e.currentTarget.dataset.id

        moreMenu(e.currentTarget, {
          items: [
            {
              key: 'add-field',
              text: __('Add Field')
            },
            {
              key: 'add-group',
              text: __('Add Group')
            },
            {
              key: 'rename',
              text: __('Rename')
            },
            {
              key: 'delete',
              text: `<span class="gh-text danger">${__('Delete')}</span>`
            }
          ],
          onSelect: (k) => {
            switch (k) {
              case 'add-group':
                addPropertyGroupModal()
                break;
              case 'add-field':

                let newField = {
                  type: 'text',
                  name: '',
                  label: '',
                  group: groupId,
                }

                const onAddFieldMount = () => {

                  const updateField = (props, r = false) => {
                    newField = {
                      ...newField,
                      ...props
                    }

                    if (r) {
                      setContent(Templates.addField(newField))
                      onAddFieldMount()
                    }
                  }

                  $('#property-field-label').on('input change', (e) => {
                    newField.label = e.target.value
                  })

                  $('#property-field-name').on('input change', (e) => {
                    newField.name = e.target.value
                  })

                  $('#property-field-type').on('change', (e) => {
                    newField.type = e.target.value
                    setContent(Templates.addField(newField))
                    onAddFieldMount()
                    $('#property-field-type').focus()
                  })

                  $('#create-property-field').on('click', (e) => {
                    addField(newField)
                    close()
                  })

                  fieldTypes[newField.type].onEditMount(newField, updateField)
                }

                const { close, setContent } = modal({
                  content: Templates.addField(newField)
                })

                onAddFieldMount()

                break
              case 'rename':

                break
              case 'delete':

                dangerConfirmationModal({
                  alert: `<p>${__('Are you sure you want to delete this property group?', 'groundhogg')}</p>`,
                  onConfirm: () => {
                    removeGroup(groupId)
                  }
                })

                break
            }
          }
        })
      })
    }

    mount()
  }

  Groundhogg.propertiesEditor = Properties

})(jQuery)