(($) => {

  const {
    uuid,
    specialChars,
    icons,
    modal,
    copyObject,
    input,
    textarea,
    select,
    tinymceElement,
    moreMenu,
    tooltip,
    inputRepeaterWidget,
    confirmationModal,
    dangerConfirmationModal,
    toggle,
  } = Groundhogg.element

  const { sprintf, __, _x, _n } = wp.i18n

  const optionsRepeater = ({
    selector, options, onChange,
  }) => {

    inputRepeaterWidget({
      selector,
      rows: options.map(o => ([o])),
      cellCallbacks: [input],
      cellProps: [{ placeholder: __('Option') }],
      sortable: true,
      onChange: (r) => {
        onChange(r.map(r => r[0]))
      },
    }).mount()

  }

  const standardField = {
    onMount: ({ id, name }, onChange) => {
      $(`#${id}`).on('change', (e) => {
        onChange({
          [name]: e.target.value,
        })
      })
    }, edit: () => {
      return ''
    }, onEditMount: () => {},
  }

  const getFieldType = (type) => {
    return {
      ...standardField, ...fieldTypes[type],
    }
  }

  const fieldTypes = {

    text: {
      name: __('Text', 'groundhogg'),
      view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${input({ 
            className: 'full-width', ...props, type: 'text', 
        })}`
      },
    },
    textarea: {
      name: __('Textarea', 'groundhogg'),
      view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${textarea({ 
            className: 'full-width', ...props, 
        })}`
      },
    },
    number: {
      name: __('Number', 'groundhogg'),
      view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${input({ 
            className: 'full-width', ...props, type: 'number', 
        })}`
      },
    },
    url: {
      name: __('URL', 'groundhogg'), view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>
		<div class="gh-input-group">${input({
			className: 'full-width', ...props, type: 'url',
		})}
			<button id="link-${props.id}" class="gh-button secondary small icon">
				<span class="dashicons dashicons-external"></span>
			</button>
		</div>`
      }, onMount: ({ id, name }, onChange) => {

        const $input = $(`#${id}`)

        $input.on('change', (e) => {
          onChange({
            [name]: e.target.value,
          })
        })

        $(`#link-${id}`).on('click', (e) => {

          let link = $input.val()

          if (link) {
            window.open(link, '_blank')
          }
        })
      },
    }, tel: {
      name: __('Phone Number', 'groundhogg'), view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${input({
			className: 'full-width', ...props, type: 'tel',
		})}`
      },
    }, custom_email: {
      name: __('Email Address', 'groundhogg'), view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${input({
			className: 'full-width', ...props, type: 'email',
		})}`
      },
    }, date: {
      name: __('Date', 'groundhogg'), view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${input({
			className: 'full-width', ...props, type: 'date',
		})}`
      },
    }, time: {
      name: __('Time', 'groundhogg'), view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${input({
			className: 'full-width', ...props, type: 'time',
		})}`
      },
    }, datetime: {
      name: __('Date & Time', 'groundhogg'), view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${input({
			className: 'full-width', ...props, type: 'datetime-local',
		})}`
      },
    },
    checkboxes: {
      name: __('Checkboxes', 'groundhogg'),
      view: ({ label, id, name, options, value, ...props }) => {

        if (!Array.isArray(value)) {
          value = value.split(',').map(v => v.trim())
        }

        //language=HTML
        return `<label class="property-label">${label}</label>
		${options.map(opt => `<label class="checkbox-label">${input({
			type: 'checkbox', value: opt, dataId: id, checked: value.includes(opt),
		})} ${opt}</label>`).join('')}`
      },
      onMount: ({ id, name }, onChange) => {
        $(`input[type=checkbox][data-id=${id}]`).on('change', e => {

          let checked = []

          $(`input[type=checkbox][data-id=${id}]`).each((i, e) => {
            if (e.checked) {
              checked.push(e.value)
            }
          })

          onChange({
            [name]: checked,
          })
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
			</div>
        `
      },
      onEditMount: (field, updateField) => {
        optionsRepeater({
          selector: '#property-dropdown-options',
          options: field.options || [''],
          onChange: (options) => updateField({ options }),
        })
      },
    },
    radio: {
      name: __('Radio Buttons', 'groundhogg'), view: ({ label, id, name, options, value, ...props }) => {
        //language=HTML
        return `<label class="property-label">${label}</label>
		${options.map(opt => `<label class="checkbox-label">${input({
			type: 'radio', value: opt, name, checked: value === opt,
		})} ${opt}</label>`).join('')}`
      }, onMount: ({ id, name }, onChange) => {
        $(`input[type=radio][name=${name}]`).on('change', e => {
          onChange({
            [name]: e.target.value,
          })
        })
      }, edit: (field) => {
        //language=HTML
        return `
			<div class="gh-rows-and-columns">
				<div class="gh-row">
					<div class="gh-col">
						<label>${__('Options', 'groundhogg')}</label>
						<div id="property-dropdown-options"></div>
					</div>
				</div>
			</div>
        `
      }, onEditMount: (field, updateField) => {
        optionsRepeater({
          selector: '#property-dropdown-options',
          options: field.options || [''],
          onChange: (options) => updateField({ options }),
        })
      },
    },
    dropdown: {
      name: __('Dropdown', 'groundhogg'), view: ({ label, value, options = [], ...props }) => {

        options = options.map(o => ({ text: o, value: o }))

        options.unshift({ text: __('Select...'), value: '' })

        if (props.multiple && !Array.isArray(value)) {
          value = value.split(',').map(v => v.trim())
        }

        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${select({
			className: 'full-width', options, selected: value, ...props,
		})}`
      }, onMount: ({ id, multiple, name, ...props }, onChange) => {
        $(`#${id}`).on('change', (e) => {

          if (multiple) {
            onChange({
              [name]: [...e.target.selectedOptions].map(o => o.value),
            })
          } else {
            onChange({
              [name]: e.target.value,
            })
          }
        })
      }, edit: (field) => {

        const { multiple } = field

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
							id: 'allow-multiple', checked: multiple,
						})} </label>
					</div>
				</div>
			</div>
        `
      }, onEditMount: (field, updateField) => {
        optionsRepeater({
          selector: '#property-dropdown-options',
          options: field.options || [''],
          onChange: (options) => updateField({ options }),
          sortable: true,
        })

        $('#allow-multiple').on('change', (e) => {
          updateField({
            multiple: e.target.checked,
          })
        })
      },
    },
    html: {
      name: __('HTML', 'groundhogg'),
      view: ({ label, ...props }) => {
        //language=HTML
        return `<label class="property-label" for="${props.id}">${label}</label>${textarea({
			className: 'full-width', ...props,
		})}`
      },
      onMount: ({ id, name, ...props }, onChange) => {

        wp.editor.remove(id)
        let editor = tinymceElement(id, {
          quicktags: false,
        }, content => {
          onChange({
            [name]: content,
          })
        })
      },
    },
  }

  const Templates = {

    noProperties: () => {
      //language=HTML
      return `
		  <button id="add-custom-property" class="gh-button secondary">
			  ${__('Add custom properties', 'groundhogg')}
		  </button>`
    },

    groups: ({ groups, fields = [] }, editable) => {
      //language=HTML
      return `
		  <div class="property-groups">
			  ${groups.map(g => Templates.group(g, fields.filter(f => f.group == g.id), editable)).join('')}
		  </div>`
    },

    group: (group, fields = [], editable) => {

      fields.sort(({ order: a = 10 }, { order: b = 10 }) => a - b)

      //language=HTML
      return `
		  <div class="property-group">
			  <div class="property-group-header">
				  <h3 data-id="${group.id}">${specialChars(group.name)}</h3>
				  ${editable ? `
				  <button class="gh-button text icon secondary property-group-more" data-id="${group.id}">
					  ${icons.verticalDots}
				  </button>` : ''}
			  </div>
			  <div class="property-group-fields">
				  ${fields && fields.length
					  ? fields.map(f => Templates.field(f)).join('')
					  : `<button data-id="${group.id}" class="gh-button secondary property-group-add-field">${__(
						  'Add field', 'groundhogg')}</button>`}
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
					  placeholder: __('New property group name', 'groundhogg'),
				  })}
				  <button class="gh-button primary" id="create-property-group">${__('Create Group', 'groundhogg')}
				  </button>
			  </div>
		  </div>`
    },

    renamePropertyGroup: (name) => {

      //language=HTML
      return `
		  <div class="property-group">
			  <h3 class="no-margin-top">${__('Rename property group')}</h3>
			  <div class="gh-input-group">
				  ${input({
					  id: 'property-group-name',
					  name: 'property_group_name',
					  value: name,
					  placeholder: __('Property group name', 'groundhogg'),
				  })}
				  <button class="gh-button primary" id="create-property-group">${__('Rename Group', 'groundhogg')}
				  </button>
			  </div>
		  </div>`
    },

    addField: (field) => {

      const { type, label, name, id, order = 10, width = 2 } = field

      let editUI = ''

      try {
        editUI = getFieldType(type).edit(field)
      } catch (e) {
        console.log(e)
      }

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
							  value: label,
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
							  value: name,
						  })}
					  </div>
				  </div>
				  <div class="gh-row">
					  <div class="gh-col">
						  <label class="">${__('Field Type', 'groundhogg')}</label>
						  ${select({
							  id: 'property-field-type', options: Object.keys(fieldTypes).
							  map(type => ({ value: type, text: fieldTypes[type].name })), selected: type,
						  })}
					  </div>
				  </div>
				  <div class="gh-row">
					  <div class="gh-col">
						  ${editUI}
					  </div>
				  </div>
				  <div class="gh-row">
					  <div class="gh-col">
						  <label class="">${__('Order', 'groundhogg')}</label>
						  ${input({
							  id: 'property-field-order',
							  name: 'property_field_order',
							  placeholder: __('10', 'groundhogg'),
							  value: order,
							  type: 'number',
						  })}
					  </div>
					  <div class="gh-col">
						  <label class="">${__('Width', 'groundhogg')}</label>
						  ${select({
							  id: 'property-field-width', name: 'property_field_width',
						  }, {
							  1: __('Full'), 2: __('Half'),
						  }, width)}
					  </div>
				  </div>
          <div class="gh-row">
              <div class="gh-col">
                  <label style="display: block">${__('Group', 'groundhogg')}</label>
                  <select id="property-field-group"></select>
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

    field: ({ group, ...field }) => {

      let fieldUI

      try {
        fieldUI = getFieldType(field.type).view(field)
      } catch (e) {
        console.log(e)
        fieldUI = `<span class="gh-text danger">${__('This field is corrupted', 'groundhogg')}</span>`
      }

      let { width = 2 } = field
      //language=HTML
      return `
		  <div class="property-field col-width-${width}" data-group="${group}" data-id="${field.id}">
			  ${fieldUI}
		  </div>`
    },

  }

  const Properties = (selector, {
    properties = {
      groups: [], fields: [],
    }, values = {}, // usually just the object meta
    onPropertiesUpdated = (properties) => {}, onChange = (properties) => {}, canEdit = () => true,
  }) => {

    properties = copyObject(properties)
    values = copyObject(values)

    /**
     * Ensure that a field name is not being used by another field
     *
     * @param name
     * @param fieldId
     * @returns {boolean}
     */
    function isInternalNameInUse (name, fieldId) {
      return properties.fields.some(field => field.name === name && field.id !== fieldId)
    }

    const removeGroup = (id) => {

      const { fields = [], groups = [] } = properties

      properties = {
        ...properties, groups: [
          ...groups.filter(g => g.id != id),
        ], fields: [
          ...fields.filter(f => f.group != id),
        ],
      }

      onPropertiesUpdated(properties)
      mount()
    }

    const removeField = (id) => {
      properties = {
        ...properties, fields: [
          ...properties.fields.filter(f => f.id != id),
        ],
      }

      onPropertiesUpdated(properties)
      mount()
    }

    const addField = (field) => {
      if (!properties.fields) {
        properties.fields = []
      }

      properties.fields.push({
        ...field, id: uuid(),
      })

      onPropertiesUpdated(properties)

      mount()
    }

    const editField = (fieldId, field) => {

      properties.fields = [
        ...properties.fields.map(f => f.id === fieldId ? field : f),
      ]

      onPropertiesUpdated(properties)

      mount()
    }

    const editGroup = (groupId, group) => {

      properties.groups = [
        ...properties.groups.map(g => g.id === groupId ? { ...g, ...group } : g),
      ]

      onPropertiesUpdated(properties)

      mount()
    }

    const moveGroup = (groupId, direction = 'down') => {
      let group = properties.groups.find(g => g.id == groupId)
      let index = properties.groups.findIndex(g => g.id == groupId)
      properties.groups.splice(index, 1)
      properties.groups.splice(direction === 'down' ? index + 1 : index - 1, 0, group)

      onPropertiesUpdated(properties)
      mount()
    }

    const addGroup = (name) => {

      if (!properties.groups) {
        properties.groups = []
      }

      let groupId = uuid()

      properties.groups.push({
        id: groupId, name,
      })

      onPropertiesUpdated(properties)

      mount()

      addOrEditField({
        type: 'text', name: '', label: '', group: groupId,
      }, addField)
    }

    const addPropertyGroupModal = () => {
      const { close } = modal({
        content: Templates.addPropertyGroup(),
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

    const renamePropertyGroupModal = (groupId) => {

      let groupName = properties.groups.find(g => g.id == groupId).name

      const { close } = modal({
        content: Templates.renamePropertyGroup(groupName),
      })

      $('#property-group-name').on('change input', (e) => {
        groupName = e.target.value
      })

      $('#create-property-group').on('click', (e) => {
        if (groupName.length) {
          close()
          editGroup(groupId, {
            name: groupName,
          })
        }
      })
    }

    const addOrEditField = (newField, onDone) => {

      let origField = Groundhogg.functions.jsonCopy(newField)

      const onAddFieldMount = () => {

        const sanitizeKey = (label) => {
          return label.toLowerCase().replace(/[^a-z0-9]/g, '_')
        }

        const updateField = (props, r = false) => {
          newField = {
            ...newField, ...props,
          }

          if (r) {
            setContent(Templates.addField(newField))
            onAddFieldMount()
          }
        }

        $('#property-field-label').on('input change', (e) => {
          let label = e.target.value

          if (onDone === addField) {
            let name = sanitizeKey(label)

            updateField({
              label,
              name,
            })

            $('#property-field-name').val(name)
          }
          else {
            updateField({
              label,
            })
          }
        })

        $('#property-field-name').on('input change', (e) => {
          updateField({ name: e.target.value })
        })

        $('#property-field-order').on('input change', (e) => {
          updateField({ order: parseInt(e.target.value) })
        })

        $('#property-field-width').on('change', (e) => {
          updateField({ width: parseInt(e.target.value) })
        })

        $('#property-field-group').select2({
          data: properties.groups.map( ({id, name}) => ({id, text:name, selected: newField.group === id }) ),
          multiple: false,
        }).on( 'change', e => newField.group = e.target.value )

        $('#property-field-type').on('change', (e) => {
          newField.type = e.target.value
          setContent(Templates.addField(newField))
          onAddFieldMount()
          $('#property-field-type').focus()
        })

        // save the data
        $('#create-property-field').on('click', (e) => {

          // make sure the name isn't already in use though
          if (isInternalNameInUse(newField.name, newField.id)) {
            Groundhogg.element.errorDialog({
              message: `The internal name <code>${ newField.name }</code> is already in use.`,
            })
            return
          }

          // if the name is changed from a previously created field,
          // we might want to update the keys in the meta table as well
          if (onDone !== addField && newField.name !== origField.name) {

            // warning for now
            dangerConfirmationModal({
              // language=HTML
              alert    : `<p>Changing the internal name of a custom field might break replacement code references and other uses.</p>
              <p>Are you sure you want to continue?</p>`,
              confirmText: 'Continue',
              onConfirm: () => {
                onDone(newField)
                close()
              },
              onCancel: () => {
                updateField({
                  name: origField.name
                })
                $('#property-field-name').val( origField.name )
              }
            })

            return

          }

          onDone(newField)
          close()
        })

        try {
          getFieldType(newField.type).onEditMount(newField, updateField)
        } catch (e) {
          console.log(e)
        }
      }

      const { close, setContent } = modal({
        content: Templates.addField(newField),
      })

      onAddFieldMount()
    }

    const mount = () => {

      if (!properties || !properties.groups || !properties.groups.length) {

        if (!canEdit()) {
          return
        }

        $(selector).html(Templates.noProperties())
        $('#add-custom-property').on('click', (e) => {

          e.preventDefault()

          addPropertyGroupModal()
        })
        return
      }

      const { fields = [] } = properties

      $(selector).html(Templates.groups({
        ...properties, fields: fields.map(f => ({ ...f, value: values[f.name] || '' })),
      }, canEdit()))

      onMount()
    }

    const onMount = () => {

      const { fields = [] } = properties

      fields.forEach(f => {
        try {
          getFieldType(f.type).onMount(f, (props) => {

            values = {
              ...values, ...props,
            }

            onChange(props)
          })
        } catch (e) {
          console.log(e)
        }
      })

      $('.property-field').on('dblclick', (e) => {

        if (!canEdit()) {
          return
        }

        moreMenu(e.currentTarget, {
          items: [
            {
              key: 'edit', text: __('Edit field', 'groundhogg'),
            },
            {
              key: 'delete', text: `<span class="gh-text danger">${__('Delete')}</span>`,
            },
          ], onSelect: k => {

            const fieldId = e.currentTarget.dataset.id

            let field = properties.fields.find(f => f.id == fieldId)

            switch (k) {
              case 'edit':

                addOrEditField({ ...field }, (field) => {
                  editField(fieldId, field)
                })

                break
              case 'delete':
                dangerConfirmationModal({
                  alert: `<p>${__('Are you sure you want to delete this property?', 'groundhogg')}</p>`,
                  onConfirm: () => {
                    removeField(fieldId)
                  },
                })
                break
            }
          },
        })
      })

      $('.property-group-add-field').on('click', (e) => {

        if (!canEdit()) {
          return
        }

        const groupId = e.currentTarget.dataset.id

        let newField = {
          type: 'text', name: '', label: '', group: groupId,
        }

        addOrEditField(newField, addField)

      })

      $('.property-group-more').on('click', (e) => {

        const groupId = e.currentTarget.dataset.id
        let index = properties.groups.findIndex(g => g.id == groupId)

        moreMenu(e.currentTarget, {
          items: [
            {
              key: 'add-field', text: __('Add Field', 'groundhogg'),
            }, {
              key: 'add-group', text: __('Add Group', 'groundhogg'),
            }, {
              key: 'edit-fields', text: __('Edit Fields', 'groundhogg'),
            }, {
              key: 'rename', text: __('Rename'),
            }, index !== 0 ? {
              key: 'move_up', text: __('Move up', 'groundhogg'),
            } : null, index < properties.groups.length - 1 ? {
              key: 'move_down', text: __('Move down', 'groundhogg'),
            } : null, {
              key: 'delete', text: `<span class="gh-text danger">${__('Delete')}</span>`,
            },
          ], onSelect: (k) => {
            switch (k) {
              case 'edit-fields':

                confirmationModal({
                  alert: `<p>${__('Double click a field to edit it!', 'groundhogg')}</p>`,
                  confirmText: __('Got it!', 'groundhogg'),
                  closeText: '',
                })

                break
              case 'move_up':
                moveGroup(groupId, 'up')
                break
              case 'move_down':
                moveGroup(groupId, 'down')
                break
              case 'add-group':
                addPropertyGroupModal()
                break
              case 'add-field':

                let newField = {
                  type: 'text', name: '', label: '', group: groupId,
                }

                addOrEditField(newField, addField)

                break
              case 'rename':
                renamePropertyGroupModal(groupId)
                break
              case 'delete':

                dangerConfirmationModal({
                  alert: `<p>${__('Are you sure you want to delete this property group?', 'groundhogg')}</p>`,
                  onConfirm: () => {
                    removeGroup(groupId)
                  },
                })

                break
            }
          },
        })
      })
    }

    mount()
  }

  Groundhogg.propertiesEditor = Properties

})(jQuery)
