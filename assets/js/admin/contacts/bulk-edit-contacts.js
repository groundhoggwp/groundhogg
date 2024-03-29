( ($) => {

  const { propertiesEditor } = Groundhogg
  const { createFilters } = Groundhogg.filters.functions
  const {
    input,
    progressModal,
    select,
    confirmationModal,
    dialog,
    bold,
    loadingModal,
    inputRepeaterWidget,
  } = Groundhogg.element

  const {
    betterTagPicker,
  } = Groundhogg.components

  const {
    contacts: ContactsStore,
  } = Groundhogg.stores

  const {
    formatNumber,
  } = Groundhogg.formatting

  const { sprintf, __, _x, _n } = wp.i18n

  const fieldSection = ({
    title = '',
    fields = '',
  }) => {

    // language=HTML
    return `
        <div class="gh-panel">
            <div class="gh-panel-header">
                <h2>${ title }</h2>
                <button type="button" class="toggle-indicator"
                        aria-expanded="true"></button>
            </div>
            <div class="inside">
                ${ fields }
            </div>
        </div>`

  }

  let sections = [

    {
      title: __('General', 'groundhogg'),
      // language=HTML
      fields: `
          <div class="gh-rows-and-columns">
              <div class="gh-row">
                  <div class="gh-col">
                      <label for="email">${ __('Opt-in Status',
                              'groundhogg') }</label>
                      ${ select({
                          id: `optin-status`,
                          name: 'optin_status',
                      }, {
                          0: __('No change', 'groundhogg'),
                          ...Groundhogg.filters.optin_status,
                      }) }
                  </div>
                  <div class="gh-col">
                      <label for="owner">${ __('Owner',
                              'noun the contact owner', 'groundhogg') }</label>
                      ${ select({
                          id: `owner`,
                          name: 'owner_id',
                      }, [
                          { value: 0, text: __('No change', 'groundhogg') },
                          ...Groundhogg.filters.owners.map(u => ( {
                              text: `${ u.data.display_name } (${ u.data.user_email })`,
                              value: u.ID,
                          } )),
                      ]) }
                  </div>
              </div>
          </div>`,
      onMount: ({ updateData }) => {

        $('#owner, #optin-status').on('change', e => {
          updateData({
            [e.target.name]: e.target.value,
          })
        })

      },
    },
    {
      title: __('Location', 'groundhogg'),
      // language=HTML
      fields: `
          <div class="gh-rows-and-columns">
              <div class="gh-row">
                  <div class="gh-col">
                      <label for="line1">${ __('Line 1', 'groundhogg') }</label>
                      ${ input({
                          id: 'line1',
                          name: 'street_address_1',
                          className: 'location-setting',

                      }) }
                  </div>
                  <div class="gh-col">
                      <label for="line2">${ __('Line 2', 'groundhogg') }</label>
                      ${ input({
                          id: 'line2',
                          name: 'street_address_2',
                          className: 'location-setting',

                      }) }
                  </div>
              </div>
              <div class="gh-row">
                  <div class="gh-col">
                      <label for="city">${ __('City', 'groundhogg') }</label>
                      ${ input({
                          id: 'city',
                          name: 'city',
                          className: 'location-setting',
                      }) }
                  </div>
                  <div class="gh-col">
                      <label for="postal_zip">${ __('Postal/Zip Code',
                              'groundhogg') }</label>
                      ${ input({
                          id: 'postal_zip',
                          name: 'postal_zip',
                          className: 'location-setting',
                      }) }
                  </div>
              </div>
              <div class="gh-row">
                  <div class="gh-col">
                      <label for="region">${ __('State', 'groundhogg') }</label>
                      ${ input({
                          id: 'region',
                          name: 'region',
                          className: 'location-setting',
                      }) }
                  </div>
                  <div class="gh-col">
                      <label for="country">${ __('Country',
                              'groundhogg') }</label>
                      ${ select({
                          id: 'country',
                          name: 'country',
                          className: 'location-setting',
                      }, {
                          0: __('Select a country', 'groundhogg'),
                          ...BulkEdit.countries,
                      }) }
                  </div>
              </div>
              <div class="gh-row">
                  <div class="gh-col">
                      <label for="time-zone">${ __('Time Zone',
                              'groundhogg') }</label>
                      ${ select({
                          id: 'time-zone',
                          name: 'time_zone',
                          className: 'location-setting',
                      }, {
                          0: __('Select a time zone', 'groundhogg'),
                          ...BulkEdit.time_zones,
                      }) }
                  </div>
                  <div class="gh-col">
                      <label for="locale">${ __('Locale',
                              'groundhogg') }</label>
                      ${ BulkEdit.language_dropdown }
                  </div>
              </div>
          </div>`,
      onMount: ({ updateData, updateMeta }) => {

        $('#locale,.location-setting').on('change', e => {
          updateMeta({
            [e.target.name]: e.target.value,
          })
        })

        $('#locale, #time-zone, #country').select2()

      },
    },
    {
      title: '<span class=" dashicons dashicons-tag"></span>' +
        __('Apply Tags', 'groundhogg'),
      // language=HTML
      fields: `
          <div id="apply-tags"></div>`,
      onMount: ({ setInPayload }) => {
        betterTagPicker('#apply-tags', {
          onChange: ({ addTags }) => {
            setInPayload({
              add_tags: addTags,
            })
          },
        })
      },
    },
    {
      title: '<span class=" dashicons dashicons-tag"></span>' +
        __('Remove Tags', 'groundhogg'),
      // language=HTML
      fields: `
          <div id="remove-tags"></div>`,
      onMount: ({ setInPayload }) => {
        betterTagPicker('#remove-tags', {
          onChange: ({ addTags }) => {
            setInPayload({
              remove_tags: addTags,
            })
          },
        })
      },
    },

  ]

  if (BulkEdit.gh_contact_custom_properties) {
    BulkEdit.gh_contact_custom_properties.tabs.forEach(t => {

      // Groups belonging to this tab
      let groups = BulkEdit.gh_contact_custom_properties.groups.filter(
        g => g.tab === t.id)
      // Fields belonging to the groups of this tab
      let fields = BulkEdit.gh_contact_custom_properties.fields.filter(
        f => groups.find(g => g.id === f.group))

      sections.push({
        title: t.name,
        fields: `<div id="${ t.id }"></div>`,
        onMount: ({ updateMeta }) => {
          propertiesEditor(`#${ t.id }`, {
            values: {},
            properties: {
              groups,
              fields,
            },
            onChange: (meta) => {
              updateMeta(meta)
            },
            canEdit: () => false,

          })
        },
      })

    })
  }

  const sanitizeKey = (label) => {
    return label.toLowerCase().replace(/[^a-z0-9]/g, '_')
  }

  sections.push({
    title: __('Custom Meta', 'groundhogg'),
    // language=HTML
    fields: `
        <div id="meta-list"></div>`,
    onMount: ({ updateMeta, deleteMeta }) => {
      inputRepeaterWidget({
        selector: '#meta-list',
        rows: [],
        cellProps: [
          {
            className: 'meta-key',
          }, {},
        ],
        cellCallbacks: [input, input],
        onMount: () => {

          $('.meta-key').on('input', (e) => {
            let key = sanitizeKey(e.target.value)
            $(e.target).val(key)
          })
        },
        onChange: (rows) => {

          rows.forEach(([key, value]) => {

            if (!key) {
              return
            }

            updateMeta({
              [key]: value,
            })
          })
        },
        onRemove: ([key, value]) => {

          if (!key) {
            return
          }

          deleteMeta(key)
        },
      }).mount()
    },
  })

  const template = () => {

    //language=HTML
    return `
        <div id="bulk-edit-inside">
            <div class="include-filters-wrap">
                <div class="include-block">${ __(
                        'Include') }
                </div>
                <div id="filters"></div>
            </div>
            <div class="exclude-filters-wrap">
                <div class="exclude-block">${ __(
                        'Exclude') }
                </div>
                <div id="exclude-filters"></div>
            </div>
            <div id="edit-fields">
                ${ sections.map(
                        ({ title, fields }) => fieldSection({ title, fields })).
                        join('') }
            </div>
            <p>
                <button id="commit" class="gh-button primary">${ __('Commit') }
                </button>
            </p>
        </div>`
  }

  const { query } = BulkEdit
  let totalContacts = 0

  let data = {}, meta = {}

  let payload = {
    add_tags: [],
    remove_tags: [],
  }

  const State = Groundhogg.createState({
    hasChanges: false
  })

  const updateData = (_data) => {
    data = {
      ...data,
      ..._data,
    }

    State.set({
      hasChanges: true
    })
  }

  const updateMeta = (_meta) => {
    meta = {
      ...meta,
      ..._meta,
    }

    State.set({
      hasChanges: true
    })
  }

  const deleteMeta = (key) => {
    delete meta[key]
  }

  const setInPayload = (_p) => {
    payload = {
      ...payload,
      ..._p,
    }

    State.set({
      hasChanges: true
    })
  }

  const fetchContactCount = () => {
    return ContactsStore.count({
      ...query,
    }).then((t) => {
      totalContacts = t
    })
  }

  const setCommitText = () => {
    $('#commit').text(sprintf(
      _n('Edit %s contact', 'Edit %s contacts', totalContacts,
        'groundhogg'), formatNumber(totalContacts)))
  }

  const mount = () => {
    $('#bulk-edit').html(template())

    createFilters('#filters', query.filters, (filters) => {
      query.filters = filters
      fetchContactCount().then(setCommitText)
    }).init()

    createFilters('#exclude-filters', query.exclude_filters,
      (filters) => {
        query.exclude_filters = filters
        fetchContactCount().then(setCommitText)
      }).init()

    // Used the INCLUDE from the multi select
    if (query.include) {
      $('.include-filters-wrap, .exclude-filters-wrap').addClass('hidden')
    }

    sections.forEach(
      s => s.onMount({ updateData, updateMeta, deleteMeta, setInPayload }))

    $('.toggle-indicator').on('click', e => {
      $(e.target).closest('.gh-panel').toggleClass('closed')
    })

    $('#commit').on('click', () => {

      if ( ! State.hasChanges ){
        dialog({
          message: __( 'Make some changes first!' ),
          type: 'error'
        })
        return
      }

      confirmationModal({
        width: 600,
        alert: `<p>${ sprintf(__(
          'Are you sure you want to edit %s contacts? This action cannot be undone.',
          'groundhogg'), bold(formatNumber(totalContacts))) }</p>`,
        onConfirm: () => {

          ContactsStore.patchMany({
            bg: true,
            query,
            data,
            meta,
            ...payload,
          }).then(r => {

            confirmationModal({
              width: 600,
              alert: `<p>${ __(
                'Your contacts are being updated in the background. <i>It may take a while.</i> We\'ll let you know what it\'s done!', 'groundhogg')}</p>`,
              onConfirm: () => {
                let url = new URL(window.location.href)

                url.searchParams.delete('action')
                url.searchParams.delete('number')
                url.searchParams.delete('offset')
                url.searchParams.delete('search')

                window.open(url, '_self')
              }
            })

          }).catch(err => {
            dialog({
              message: err.message,
              type: 'error',
            })
          })
        },
      })
    })
  }

  $(() => {

    const { close } = loadingModal()

    fetchContactCount().then(() => {
      mount()
      setCommitText()
      close()
    })

  })

} )(jQuery)
