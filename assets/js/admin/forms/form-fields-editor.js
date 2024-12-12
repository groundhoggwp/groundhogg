( ($) => {

  const {
    Div,
    Input,
    Fragment,
    Toggle,
    Label,
    Button,
    makeEl,
    Span,
    Dashicon,
    Modal,
    ToolTip,
  } = MakeEl

  const {
    searchOptionsWidget,
  } = Groundhogg.element

  const {
    sprintf,
    __,
    _x,
  } = wp.i18n

  // More basic version of the form builder

  // Need sortable fields
  // Need basic fields
  // Need custom field selection
  // Need label overrides
  // Need required/not-required

  /**
   * Field setting row
   *
   * @param label
   * @param stacked
   * @param input
   * @returns {*}
   * @constructor
   */
  const FieldSetting = ({
    label = '',
    stacked = false,
  }, input) => Div({
    className: 'space-between',
  }, [
    Label({ for: input.id }, label),
    input,
  ])

  /**
   * Field settings
   *
   * @param label
   * @param required
   * @param updateField
   * @returns {*}
   * @constructor
   */
  const FieldSettings = ({
    label,
    required,
    updateField,
  }) => Fragment([
    FieldSetting({
      label: 'Label',
    }, Input({
      value  : label,
      onInput: e => updateField({ label: e.target.value }),
    })),
    FieldSetting({
      label: 'Is required?',
    }, Toggle({
      checked : required,
      onChange: e => updateField({ required: e.target.checked }),
    })),
  ])

  const DisplayField = () => {}

  /**
   * Field edit box
   *
   * @param id
   * @param label
   * @param required
   * @param onUpdate
   * @param onDelete
   * @returns {*}
   * @constructor
   */
  const Field = ({
    id,
    index,
    label,
    name,
    required,
    onUpdate,
    onDelete,
    isOpen = false,
    onOpen = () => {},
  }) => {

    return Div({
      id       : `field-${ id }`,
      className: `gh-panel outlined ${ isOpen ? 'open' : 'closed' }`,
      dataId   : id,
      dataIndex: index,
      style    : {
        cursor: 'grab',
      },
    }, [
      Div({
        className: 'gh-panel-header',
      }, [
        makeEl('h2', {
          onClick  : onOpen,
          className: 'display-flex gap-10 align-center',
          style    : {
            cursor: 'pointer',
          },
        }, [
          name,
          isOpen ? null : Dashicon('edit'),
          ToolTip('Click to edit', 'left'),
        ]),
        Button({
          type     : 'button',
          className: 'gh-button icon danger text',
          onClick  : onDelete,
          style    : {
            cursor: 'pointer',
            margin: '3px',
          },
        }, [
          Dashicon('no-alt'),
          ToolTip('Delete', 'right'),
        ]),
      ]),
      Div({
        className: 'inside display-flex gap-10 column',
      }, [
        FieldSettings({
          label,
          required,
          updateField: onUpdate,
        }),
      ]),
    ])
  }

  /**
   * The form field editor
   *
   * @param id
   * @param form
   * @param fields
   * @param fieldGroups
   * @param onChange
   * @returns {*}
   * @constructor
   */
  const FormFieldsEditor = ({
    id = 'form-fields-editor',
    form = [],
    fields = [],
    fieldGroups = {},
    onChange = ([form, map]) => {},
  }) => {

    const State = Groundhogg.createState({
      form,
      currField: '',
    })

    /**
     * Open the settings for a field
     *
     * @param id
     */
    const openField = id => {
      State.set({
        currField: id,
      })
      morph()
    }

    const morph = () => morphdom(document.getElementById(id), render())

    const handleOnChange = () => {

      const map = {}

      State.form.forEach(({
        mapFrom,
        mapTo,
        id = '',
      }) => {
        map[mapFrom ?? id] = mapTo ?? id
      })

      onChange([
        State.form,
        map,
      ])
    }

    /**
     * Update a field with new settings
     *
     * @param id
     * @param newSettings
     */
    const updateField = (id, newSettings) => {

      State.set({
        form: State.form.map(field => field.id === id ? { ...field, ...newSettings } : field),
      })

      handleOnChange()
      morph()
    }

    /**
     * Add a field to the form
     *
     * @param settings
     */
    const addField = (settings) => {
      State.form.push(settings)
      handleOnChange()
      morph()
    }

    /**
     * Delete a field from the form
     *
     * @param id
     */
    const deleteField = (id) => {
      State.set({
        form: State.form.filter(field => field.id !== id),
      })
      handleOnChange()
      morph()
    }

    const render = () => {

      return Div({
        id,
      }, [

        // Fields
        Div({
          className: 'display-flex column',
          onCreate : el => {
            $(el).sortable({
              start : (e, ui) => {
                ui.helper.css('cursor', 'grabbing')
              },
              update: (e, ui) => {

                State.set({
                  form: [...el.querySelectorAll('.gh-panel')].map(node => {
                    return State.form.find(field => field.id === node.dataset.id)
                  }),
                })

                handleOnChange()
                morph()
              },
            })
          },
        }, State.form.map((field, index) => Field({
          ...field,
          index,
          isOpen  : field.id === State.currField,
          onUpdate: settings => updateField(field.id, settings),
          onDelete: () => deleteField(field.id),
          onOpen  : () => openField(field.id),
        }))),

        // Add Field
        Button({
          type     : 'button',
          id       : `add-form-field`,
          className: 'gh-button secondary',
          style    : {
            marginTop: '20px',
          },
          onClick  : e => {

            // only show fields that have not been added to the form already
            let options = fields.filter(field => !State.form.some(f => f.id === field.id))

            let groups = fieldGroups

            searchOptionsWidget({
              // selector: '.add-filter-wrap',
              position    : 'fixed',
              target      : e.currentTarget,
              options,
              groups,
              onSelect    : ({
                group,
                ...field
              }) => {
                // Easy, just add the whole field :)
                addField({
                  ...field,
                  label: field.name,
                })
              },
              filterOption: (option, search) => {
                return option.name.match(new RegExp(search, 'i'))
              },
              renderOption: (option) => option.name,
              noOptions   : __('No matching fields...', 'groundhogg'),
            }).mount()

          },
        }, [
          Dashicon('plus-alt2'),
          Span({}, __('Add field', 'groundhogg')),
        ]),
      ])
    }

    return render()
  }

  /**
   * Field editor for contact fields
   *
   * @param id
   * @param form
   * @param onChange
   * @returns {*}
   * @constructor
   */
  const ContactFormFieldsEditor = ({
    id = 'contact-form-fields-editor',
    form,
    onChange = form => {},
  }) => {

    const {
      tabs  : customTabs,
      fields: customFields,
      groups: customGroups,
    } = Groundhogg.filters.gh_contact_custom_properties

    const fieldGroups = {
      contact   : __('Contact Info'),
      address   : __('Address'),
      compliance: __('Compliance'),
      special   : __('Special'),
    }

    Object.values(customTabs).forEach(tab => {
      let groups = Object.values(customGroups).filter(group => group.tab === tab.id)
      groups.forEach(group => {
        fieldGroups[group.id] = sprintf('%s: %s', tab.name, group.name)
      })
    })

    const fields = [

      // Contact Fields
      {
        id      : 'first_name',
        name    : 'First Name',
        group   : 'contact',
        required: true,
      }, // First Name
      {
        id      : 'last_name',
        name    : 'Last Name',
        group   : 'contact',
        required: true,
      },  // Last Name
      {
        id      : 'full_name',
        name    : 'Full Name',
        group   : 'contact',
        required: true,
      },  // Full Name
      {
        id      : 'email',
        name    : 'Email Address',
        group   : 'contact',
        required: true,
      },
      {
        id      : 'primary_phone',
        name    : 'Phone',
        group   : 'contact',
        required: false,
      },
      {
        id      : 'mobile_phone',
        name    : 'Mobile Phone',
        group   : 'contact',
        required: false,
      },
      {
        id      : 'birthday',
        name    : 'Birthday',
        group   : 'contact',
        required: false,
      },

      // Address Fields
      {
        id      : 'street_address_1',
        name    : 'Line 1',
        group   : 'address',
        required: false,
      },
      {
        id      : 'street_address_2',
        name    : 'Line 2',
        group   : 'address',
        required: false,
      },
      {
        id      : 'city',
        name    : 'City',
        group   : 'address',
        required: false,
      },
      {
        id      : 'region',
        name    : 'State',
        group   : 'address',
        required: false,
      },
      {
        id      : 'postal_zip',
        name    : 'Zip Code',
        group   : 'address',
        required: false,
      },
      {
        id      : 'country',
        name    : 'Country',
        group   : 'address',
        required: false,
      },

      // Compliance Fields

      // Special Fields

      // Custom Fields
      ...Object.values(customFields).
        map(({
          id,
          label,
          group,
          name,
        }) => ( {
          id,
          name    : label,
          group,
          required: false,
          mapFrom : name,
          mapTo   : id,
        } )),
    ]

    return FormFieldsEditor({
      id,
      fields,
      fieldGroups,
      form,
      onChange,
    })

  }

  Groundhogg.fields = {
    FormFieldsEditor,
    ContactFormFieldsEditor,
  }

  const doOptionsForm = (option) => {

    const State = Groundhogg.createState({
      form      : CustomFields[option] ?? [],
      submitted : false,
      hasChanges: false,
    })

    window.addEventListener('load', () => {

      const el = document.getElementById(option)

      if (!el) {
        return
      }

      morphdom(el, ContactFormFieldsEditor({
        id      : option,
        form    : State.form[0] ?? [],
        onChange: (form) => {

          State.set({
            form,
            hasChanges: true,
          })
        },
      }))

      const form = document.querySelector(`form[action="options.php"]`)

      form.addEventListener('submit', async e => {

        if (State.submitted || !State.hasChanges) {
          return
        }

        e.preventDefault()

        await Groundhogg.stores.options.patch({
          [option]: State.form,
        })

        State.set({
          submitted: true,
        })

        form.submit()
      })
    })

  }

  if (typeof CustomFields !== 'undefined') {
    doOptionsForm('gh_custom_profile_fields')
    doOptionsForm('gh_custom_preference_fields')
  }

} )(jQuery)
