( function ($) {

  const { createFilters } = Groundhogg.filters.functions
  const {
    searchOptionsWidget,
    regexp,
    specialChars,
    modal,
    input,
    loadingDots,
    copyObject,
    objectEquals,
    toggle,
    moreMenu,
    select,
    dangerConfirmationModal,
    confirmationModal,
    clickInsideElement,
    progressBar,
    dialog,
    bold,
    tooltip,
    adminPageURL,
  } = Groundhogg.element
  const {
    quickEditContactModal,
    addContactModal,
  } = Groundhogg.components
  const { ajax } = Groundhogg.api
  const { base64_json_encode } = Groundhogg.functions
  const {
    searches: SearchesStore,
    contacts: ContactsStore,
    tags: TagsStore,
    funnels: FunnelsStore,
  } = Groundhogg.stores
  const { tagPicker, funnelPicker } = Groundhogg.pickers
  const { userHasCap } = Groundhogg.user
  const {
    formatNumber,
    formatTime,
    formatDate,
    formatDateTime,
  } = Groundhogg.formatting
  const { sprintf, __, _x, _n } = wp.i18n

  SearchesStore.itemsFetched(ContactSearch.searches)

  const loadFilters = (query) => {
    window.location.href = ContactSearch.url + '&' + query
  }

  const loadSearch = (search) => {
    window.location.href = ContactSearch.url + '&saved_search=' + search
  }

  let abortHandler

  const SearchApp = {

    filtersEnabled: false,
    excludeEnabled: false,
    savedSearchEnabled: false,
    query: {
      filters: [],
      exclude_filters: [],
    },
    filtersApp: null,
    excludeFiltersApp: null,
    searchesApp: null,
    currentSearch: null,

    getContacts () {

      if (abortHandler) {
        abortHandler.abort()
      }

      abortHandler = new AbortController()
      const { signal } = abortHandler

      ContactsStore.count({
        filters: base64_json_encode(this.query.filters),
        exclude_filters: base64_json_encode(
          this.excludeEnabled ? this.query.exclude_filters : []),
      }, {
        signal,
      }).then(total => {
        $('#search-contacts').
          html(sprintf(
            _n('Show %s contact', 'Show %s contacts', total, 'groundhogg'),
            formatNumber(total)))
      })
    },

    init () {

      const onUpdate = () => {
        this.getContacts()

        if (this.currentSearch) {
          if (objectEquals(this.query, this.currentSearch.query)) {
            $('#update-search').prop('disabled', true)
          }
          else {
            $('#update-search').prop('disabled', false)
          }
        }
      }

      const handleUpdateExcludeFilters = (filters) => {

        this.query.exclude_filters = filters

        onUpdate()
      }

      const handleUpdateFilters = (filters) => {
        this.query.filters = filters

        onUpdate()
      }

      this.query = ContactSearch.filter_query || []

      if (this.query.filters.length || this.query.exclude_filters.length) {

        this.filtersEnabled = true

        if (this.query.exclude_filters.length) {
          this.excludeEnabled = true
        }

        $('.contact-quick-search').hide()

      }

      if (ContactSearch.currentSearch) {

        this.currentSearch = copyObject(ContactSearch.currentSearch)

        if (this.currentSearch.query) {
          this.query = copyObject(this.currentSearch.query, {})

          if ('exclude_filters' in this.query &&
            this.query.exclude_filters.length) {
            this.excludeEnabled = true
          }
        }
      }

      this.filtersApp = createFilters('#search-filters', this.query.filters,
        handleUpdateFilters)
      this.excludeFiltersApp = createFilters('#exclude-filters',
        this.query.exclude_filters, handleUpdateExcludeFilters)
      this.mount()
    },

    initSavedSearches () {

      searchOptionsWidget({
        selector: '#searches-picker',
        options: SearchesStore.getItems(),
        filterOption: (option, search) => {
          return option.name.match(regexp(search))
        },
        renderOption: (option) => option.name,
        onClose: () => {
          this.savedSearchEnabled = false
          this.mount()
        },
        onSelect: (option) => {
          this.loadingSearch = true
          loadSearch(option.id)
        },
        noOptions: __('No matching searches...', 'groundhogg'),
      }).mount()

    },

    render () {

      if (this.filtersEnabled) {
        //language=HTML
        return `
            <div class="enable-filters-wrap">
                <button class="enable-filters white"><span
                        class="dashicons dashicons-filter"></button>
            </div>
            <div class="search-filters-wrap">
                ${ this.excludeEnabled
                        ? `<div class="include-filters-wrap"><div class="include-block">${ __(
                                'Include') }</div>`
                        : '' }
                <div id="search-filters"></div>
                ${ this.excludeEnabled
                        ? `</div><div class="exclude-filters-wrap"><div class="exclude-block">${ __(
                                'Exclude') }</div><div id="exclude-filters"></div></div>`
                        : '' }
                <div id="below-filters" class="space-between">
                    <div class="align-left-space-between">
                        <span>${ __('Show exclude filters',
                                'groundhogg') }</span>
                        ${ toggle({
                            id: 'enable-exclude',
                            name: 'enable_exclude',
                            checked: this.excludeEnabled,
                        }) }
                    </div>
                    <div class="align-right-space-between">
                        <button id="search-contacts"
                                class="gh-button primary">
                            ${ __('Search', 'groundhogg') }
                        </button>
                        ${ !this.currentSearch
                                ? `<button id="save-search" class="gh-button secondary">${ __(
                                        'Save this search',
                                        'groundhogg') }</button>`
                                : `<button id="update-search" class="gh-button secondary" ${ objectEquals(
                                        this.query.filters,
                                        this.currentSearch.query.filters) &&
                                ( !this.excludeEnabled ||
                                        objectEquals(this.query.exclude_filters,
                                                this.currentSearch.query.exclude_filters) )
                                        ? 'disabled'
                                        : '' }>${ sprintf(
                                        __('Update "%s"', 'groundhogg'),
                                        this.currentSearch.name) }</button><a class="gh-text danger delete-search">${ __(
                                        'Delete') }</a>` }
                    </div>

                </div>
            </div>
        `
      }

      //language=HTML
      return `
          <button class="enable-filters white" style="padding-right: 10px"><span
                  class="dashicons dashicons-filter"></span>
              ${ this.currentSearch ? __('Edit Filters', 'groundhogg') : __(
                      'Filter Contacts', 'groundhogg') }
          </button>
          ${ this.savedSearchEnabled
                  ? `<div id="searches-picker"></div>`
                  : ( ContactSearch.searches.length
                                  ? `<button id="load-saved-search" class="has-dashicon button button-secondary"><span class="dashicons dashicons-search"></span> <span class="text">${ this.loadingSearch
                                          ? __('Loading search', 'groundhogg')
                                          : __('Load saved search',
                                                  'groundhogg') }</span></button>`
                                  : ''
                  ) }`
    },

    mount () {
      $('#search-panel .filters').html(this.render())
      this.addListeners()
    },

    addListeners () {

      var self = this

      const remount = () => {
        this.mount()
      }

      const enableFilters = () => {
        this.filtersEnabled = !this.filtersEnabled
        if (this.filtersEnabled) {
          $('.contact-quick-search').hide()
        }
        else {
          $('.contact-quick-search').show()
        }
        remount()
      }

      const enableSavedSearch = () => {
        this.savedSearchEnabled = !this.savedSearchEnabled
        remount()
      }

      if (this.filtersEnabled) {

        this.filtersApp.init()

        tooltip('.enable-filters', {
          content: __('Turn off filters', 'groundhogg'),
          position: 'top',
        })

        if (this.excludeEnabled) {
          this.excludeFiltersApp.init()
        }
      }

      if (this.savedSearchEnabled) {
        this.initSavedSearches()
      }

      $('.enable-filters').on('click', function () {
        enableFilters()
      })

      $('#load-saved-search').on('click', function () {
        enableSavedSearch()
      })

      if (this.loadingSearch) {
        $('#load-saved-search').prop('disabled', true)
        loadingDots('#load-saved-search span.text')
      }

      $('#enable-exclude').on('change', (e) => {
        this.excludeEnabled = e.target.checked
        remount()
        this.getContacts()
      })

      $('#search-contacts').on('click', (e) => {
        $(e.target).html('Searching').prop('disabled', true)
        loadingDots('#search-contacts')
        if (this.currentSearch
          && objectEquals(this.query.filters, this.currentSearch.query.filters)
          && objectEquals(this.currentSearch.query.exclude_filters,
            this.excludeEnabled ? this.query.exclude_filters : [])) {
          loadSearch(this.currentSearch.id)
        }
        else {

          let query = `filters=${ base64_json_encode(this.query.filters) }`

          if (this.excludeEnabled) {
            query += `&exclude_filters=${ base64_json_encode(
              this.query.exclude_filters) }`
          }

          loadFilters(query)
        }
      })

      $('.delete-search').on('click', (e) => {

        e.preventDefault()

        dangerConfirmationModal({
          alert: `<p>${ __(
            'Are you sure you want to delete this search') }</p>`,
          onConfirm: () => {
            SearchesStore.delete(this.currentSearch.id).then(() => {
              this.currentSearch = null
              $('#current-search').remove()
              this.mount()
            })
          },
        })
      })

      $('#update-search').on('click', (e) => {

        const $button = $(e.target)
        $button.prop('disabled', true)
        $button.html(__('Updating', 'groundhogg'))
        const { stop } = loadingDots('#update-search')

        SearchesStore.patch(this.currentSearch.id, {
          query: {
            filters: this.query.filters,
            exclude_filters: this.excludeEnabled
              ? this.query.exclude_filters
              : [],
          },
        }).then(search => {

          stop()
          this.currentSearch = search
          $button.html(__('Updated!', 'groundhogg'))
          dialog({
            message: __('Search updated!', 'groundhogg'),
          })

          setTimeout(() => {
            this.mount()
          }, 1000)
        })
      })

      $('span#search-name').on('click', (e) => {

        const $span = $(e.target)

        $span.html(input({
          name: 'search_name',
          id: 'saved-search-name-edit',
          value: this.currentSearch.name,
        }))

        $('#saved-search-name-edit').focus().on('change blur keydown', (e) => {

          if (e.type === 'keydown' && e.key !== 'Enter') {
            return
          }

          const newName = e.target.value

          $span.html(specialChars(newName))

          if (newName !== this.currentSearch.name) {
            SearchesStore.patch(this.currentSearch.id, {
              name: newName,
            }).
              then(s => this.currentSearch = s).
              then(() => $span.html(specialChars(this.currentSearch.name)))
          }
        })
      })

      $('#save-search').on('click', () => {
        const {
          $modal,
          close,
        } = modal({
          //language=html
          content: `
              <h2>${ __('Name your search...', 'groundhogg') }</h2>
              <p>${ input({
                  id: 'search-name',
                  placeholder: __('My saved search...', 'groundhogg'),
              }) }</p>
              <button id="save" disabled class="gh-button primary">
                  ${ __('Save', 'groundhogg') }
              </button>`,
        })

        $('input#search-name').on('change input', (e) => {
          this.newSearchName = e.target.value
          if (!this.newSearchName) {
            $('#save').prop('disabled', true)
          }
          else {
            $('#save').prop('disabled', false)
          }

        }).focus()

        $('#save.gh-button').on('click', (e) => {

          if (!this.newSearchName) {
            return
          }

          const $button = $(e.target)
          $button.prop('disabled', true)
          $button.html(__('Saving', 'groundhogg'))
          const { stop } = loadingDots('#save.gh-button')

          SearchesStore.post({
            name: this.newSearchName,
            query: {
              filters: this.query.filters,
              exclude_filters: this.excludeEnabled
                ? this.query.exclude_filters
                : [],
            },
          }).then(search => {

            stop()
            $button.html(__('Saved!', 'groundhogg'))

            this.currentSearch = search
            this.mount()

            setTimeout(close, 1000)
          })
        })
      })
    },
  }

  $(function () {
    SearchApp.init()
  })

  // More Actions
  $(() => {

    $('.gh-actions').
      append(
        `<button type="button" class="more-actions button button-secondary">${ __(
          'More Actions', 'groundhogg') }</button>`)
    $('.more-actions').on('click', (e) => {

      const {
        total_items: totalContacts,
        total_items_formatted: totalContactsFormatted,
        query: ContactQuery,
      } = ContactsTable

      // console.log(e.currentTarget)

      const items = [
        {
          key: 'edit',
          cap: 'edit_contacts',
          text: sprintf(__('Edit %s contacts', 'groundhogg'),
            totalContactsFormatted),
        },
        {
          key: 'export',
          cap: 'export_contacts',
          text: sprintf(__('Export %s contacts', 'groundhogg'),
            totalContactsFormatted),
        },
        {
          key: 'broadcast',
          cap: 'schedule_broadcasts',
          text: sprintf(__('Send a broadcast to %s contacts', 'groundhogg'),
            totalContactsFormatted),
        },
        {
          key: 'funnel',
          cap: 'view_funnels',
          text: sprintf(__('Add %s contacts to a funnel', 'groundhogg'),
            totalContactsFormatted),
        },
        {
          key: 'delete',
          cap: 'delete_contacts',
          text: `<span class="gh-text danger">${ sprintf(
            __('Delete %s contacts', 'groundhogg'),
            totalContactsFormatted) }</span>`,
        },
      ]

      moreMenu(e.currentTarget, {
        items: items.filter(i => userHasCap(i.cap)),
        onSelect: (key) => {

          let { number, offset, paged, ...query } = ContactQuery

          switch (key) {
            case 'edit':
              window.location.href = adminPageURL('gh_contacts', {
                ...query,
                action: 'bulk_edit',
                filters: base64_json_encode(query.filters),
                exclude_filters: base64_json_encode(
                  query.exclude_filters),
              })
              break
            case 'export':
              window.location.href = adminPageURL('gh_tools', {
                tab: 'export',
                action: 'choose_columns',
                query: {
                  ...query,
                  filters: base64_json_encode(query.filters),
                  exclude_filters: base64_json_encode(
                    query.exclude_filters),
                },
              })
              break
            case 'funnel':

              modal({
                //language=HTML
                content: `<h2>${ __('Add contacts to a funnel', 'groundhogg') }</h2>
                <div id="gh-add-to-funnel" style="width: 500px"></div>`,
                onOpen: () => {
                  document.getElementById('gh-add-to-funnel').append(Groundhogg.FunnelScheduler({
                    totalContacts,
                    searchMethod: 'selection',
                    searchMethods: [
                      {
                        id: 'selection',
                        text: sprintf(__('Selected %s contacts', 'groundhogg'), formatNumber(totalContacts)),
                        query: () => ( {
                          ...query,
                        } ),
                      },
                    ],
                  }))
                },
              })

              break
            case 'broadcast':

              modal({
                //language=HTML
                content: `<h2>${ __('Send a broadcast', 'groundhogg') }</h2>
                <div id="gh-broadcast-form"></div>`,
                onOpen: () => {
                  document.getElementById('gh-broadcast-form').append(Groundhogg.BroadcastScheduler({
                    totalContacts,
                    searchMethod: 'selection',
                    searchMethods: [
                      {
                        id: 'selection',
                        text: sprintf(__('Selected %s contacts', 'groundhogg'), formatNumber(totalContacts)),
                        query: () => ( {
                          ...query,
                        } ),
                      },
                    ],
                  }))
                },
              })

              break
            case 'delete':

              dangerConfirmationModal({
                width: 600,
                alert: `<p>${ sprintf(__(
                  'Are you sure you want to delete %s contacts? This cannot be undone. Consider <i>exporting</i> first!',
                  'groundhogg'), `<b>${ totalContactsFormatted }</b>`) }</p>`,
                onConfirm: () => {

                  ContactsStore.deleteMany({
                    ...query,
                    bg: true,
                  }).then(r => {

                    confirmationModal({
                      width: 600,
                      alert: `<p>${ sprintf(__(
                          '🗑️ %s contacts are being deleted in the background. <i>It may take a while.</i> We\'ll let you know when it\'s done!', 'groundhogg'),
                        `<b>${ totalContactsFormatted }</b>`) }</p>`,
                      cancelButtonType: 'hidden',
                      confirmText: __('Sounds good!', 'groundhogg'),
                    })

                  }).catch(err => {
                    dialog({
                      message: err.message,
                      type: 'error',
                    })
                  })
                },
              })
              break
          }
        },
      })
    })
  })

  $(() => {

    if (!userHasCap('delete_contacts')) {
      return
    }

    $(document).on('click', 'table .delete-contact', (e) => {
      e.preventDefault()

      const ID = parseInt(e.currentTarget.dataset.id)

      const contact = ContactsStore.get(ID)

      dangerConfirmationModal({
        confirmText: __('Delete'),
        alert: `<p>${ sprintf(
          __('Are you sure you want to delete %s?', 'groundhogg'), bold(
            `${ contact.data.first_name } ${ contact.data.last_name }`)) }</p>`,
        onConfirm: () => {
          ContactsStore.delete(contact.ID).then(() => {
            $(`#contact-${ contact.ID }`).remove()
            dialog({
              message: sprintf(__('%s was deleted!', 'groundhogg'),
                `${ contact.data.first_name } ${ contact.data.last_name }`),
            })
          })
        },
      })
    })

  })

  // QuickEdit
  $(() => {

    if (userHasCap('add_contacts')) {
      $('#quick-add').on('click', (e) => {
        e.preventDefault()

        addContactModal({
          onCreate: (c) => {
            ajax({
              action: 'groundhogg_contact_table_row',
              contact: c.ID,
            }).then((r) => {
              dialog({
                message: __('Contact created!', 'groundhogg'),
              })
              $('.wp-list-table.contacts tbody').prepend(r.data.row)
            })
          },
        })
      })
    }

    if (userHasCap('edit_contacts')) {
      ContactsStore.itemsFetched(ContactsTable.items)

      $(document).on('click', '.editinline', (e) => {

        e.preventDefault()

        const ID = parseInt(e.currentTarget.dataset.id)

        const contact = ContactsStore.get(ID)

        quickEditContactModal({

          contact,
          onEdit: (contact) => {

            ajax({
              action: 'groundhogg_contact_table_row',
              contact: contact.ID,
            }).then((r) => {
              dialog({
                message: __('Contact updated!', 'groundhogg'),
              })
              $(`#contact-${ contact.ID }`).replaceWith(r.data.row)
            })

          },
        })

      })
    }

  })

  // Column presets
  $(()=>{

  })

} )(jQuery)
