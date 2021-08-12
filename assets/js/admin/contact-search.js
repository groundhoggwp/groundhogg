(function ($) {

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
    moreMenu,
    select,
    dangerConfirmationModal,
    confirmationModal,
    clickInsideElement,
    progressBar,
    dialog,
    bold,
    tooltip,
    adminPageURL
  } = Groundhogg.element
  const { post, get, patch, routes, ajax } = Groundhogg.api
  const { searches: SearchesStore, contacts: ContactsStore, tags: TagsStore, funnels: FunnelsStore } = Groundhogg.stores
  const { tagPicker, funnelPicker } = Groundhogg.pickers
  const { userHasCap } = Groundhogg.user
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { sprintf, __, _x, _n } = wp.i18n

  // const { StepTypes } = Groundhogg

  // StepTypes.setup()

  SearchesStore.itemsFetched(ContactSearch.searches)

  const base64_json_encode = (stuff) => {
    return btoa(JSON.stringify(stuff))
  }

  const loadFilters = (filters) => {
    window.location.href = ContactSearch.url + '&filters=' + base64_json_encode(filters)
  }

  const loadSearch = (search) => {
    window.location.href = ContactSearch.url + '&saved_search=' + search
  }

  const SearchApp = {

    filtersEnabled: false,
    savedSearchEnabled: false,
    filters: [],
    filtersApp: null,
    searchesApp: null,
    currentSearch: null,

    init () {

      const handleUpdateFilters = (filters) => {
        this.filters = filters
        getContacts(filters)

        // console.log(this.filters, this.currentSearch.query.filters)

        if (this.currentSearch) {
          if (objectEquals(this.filters, this.currentSearch.query.filters)) {
            $('#update-search').prop('disabled', true)
          } else {
            $('#update-search').prop('disabled', false)
          }
        }

      }

      this.filters = ContactSearch.filters || []
      if (this.filters && this.filters.length > 0) {
        this.filtersEnabled = true
        $('.contact-quick-search').hide()
      } else if (ContactSearch.currentSearch) {
        this.currentSearch = copyObject(ContactSearch.currentSearch)
        if (this.currentSearch.query.filters) {
          this.filters = copyObject(this.currentSearch.query.filters, [])
        }
      }

      this.filtersApp = createFilters('#search-filters', this.filters, handleUpdateFilters)
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
        noOptions: __('No matching searches...', 'groundhogg')
      }).mount()

    },

    render () {

      if (this.filtersEnabled) {
        //language=HTML
        return `
			<div class="enable-filters-wrap">
				<button class="enable-filters white"><span class="dashicons dashicons-filter"></button>
			</div>
			<div class="search-filters-wrap">
				<div id="search-filters"></div>
				<div class="search-contacts-wrap">
					<button id="search-contacts" class="button button-primary">${__('Search', 'groundhogg')}</button>
					${!this.currentSearch
						? `<button id="save-search" class="button button-secondary">${__('Save this search', 'groundhogg')}</button>`
						: `<button id="update-search" class="button button-secondary" ${objectEquals(this.filters, this.currentSearch.query.filters) ? 'disabled' : ''}>${sprintf(__('Update "%s"', 'groundhogg'), this.currentSearch.name)}</button><a class="gh-text danger delete-search">${__('Delete')}</a>`}
				</div>
			</div>
        `
      }

      //language=HTML
      return `
		  <button class="enable-filters white" style="padding-right: 10px"><span
			  class="dashicons dashicons-filter"></span>
			  ${this.currentSearch ? __('Edit Filters', 'groundhogg') : __('Filter Contacts', 'groundhogg')}
		  </button>
		  ${this.savedSearchEnabled
			  ? `<div id="searches-picker"></div>`
			  : (ContactSearch.searches.length
					  ? `<button id="load-saved-search" class="has-dashicon button button-secondary"><span class="dashicons dashicons-search"></span> <span class="text">${this.loadingSearch ? __('Loading search', 'groundhogg') : __('Load saved search', 'groundhogg')}</span></button>`
					  : ''
			  )}`
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
        } else {
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
          position: 'top'
        })
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

      $('#search-contacts').on('click', (e) => {
        $(e.target).html('Searching').prop('disabled', true)
        loadingDots('#search-contacts')
        if (this.currentSearch && objectEquals(this.filters, this.currentSearch.query.filters)) {
          loadSearch(this.currentSearch.id)
        } else {
          loadFilters(this.filters)
        }
      })

      $('.delete-search').on('click', (e) => {

        e.preventDefault()

        dangerConfirmationModal({
          alert: `<p>${__('Are you sure you want to delete this search')}</p>`,
          onConfirm: () => {
            SearchesStore.delete(this.currentSearch.id).then(() => {
              this.currentSearch = null
              $('#current-search').remove()
              this.mount()
            })
          }
        })
      })

      $('#update-search').on('click', (e) => {

        const $button = $(e.target)
        $button.prop('disabled', true)
        $button.html(__('Updating', 'groundhogg'))
        const { stop } = loadingDots('#update-search')

        SearchesStore.patch(this.currentSearch.id, {
          query: {
            filters: this.filters
          }
        }).then(search => {

          stop()
          this.currentSearch = search
          $button.html(__('Updated!', 'groundhogg'))

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
          value: this.currentSearch.name
        }))

        $('#saved-search-name-edit').focus().on('change blur keydown', (e) => {

          if (e.type === 'keydown' && e.key !== 'Enter') {
            return
          }

          const newName = e.target.value

          $span.html(specialChars(newName))

          if (newName !== this.currentSearch.name) {
            SearchesStore.patch(this.currentSearch.id, {
              name: newName
            }).then(s => this.currentSearch = s).then(() => $span.html(specialChars(this.currentSearch.name)))
          }
        })
      })

      $('#save-search').on('click', () => {
        const {
          $modal,
          close
        } = modal({
          //language=html
          content: `
			  <h2>${__('Name your search...', 'groundhogg')}</h2>
			  <p>${input({
				  id: 'search-name',
				  placeholder: __('My saved search...', 'groundhogg')
			  })}</p>
			  <button id="save" disabled class="gh-button primary">${__('Save', 'groundhogg')}</button>`
        })

        $('input#search-name').on('change input', (e) => {
          this.newSearchName = e.target.value
          if (!this.newSearchName) {
            $('#save').prop('disabled', true)
          } else {
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
              filters: this.filters
            }
          }).then(search => {

            stop()
            $button.html(__('Saved!', 'groundhogg'))

            this.currentSearch = search
            this.mount()

            setTimeout(close, 1000)
          })
        })
      })
    }
  }

  let abortHandler

  const getContacts = (filters) => {

    if (abortHandler) {
      abortHandler.abort()
    }

    abortHandler = new AbortController()
    const { signal } = abortHandler

    ContactsStore.count({
      filters: base64_json_encode(filters)
    }, {
      signal
    }).then(total => {
      $('#search-contacts').html(sprintf(_n('Show %s contact', 'Show %s contacts', total, 'groundhogg'), formatNumber(total)))
    })
  }

  $(function () {
    SearchApp.init()
  })

  // More Actions
  $(() => {

    $('.gh-actions').append(`<button type="button" class="more-actions button button-secondary">${__('More Actions', 'groundhogg')}</button>`)
    $('.more-actions').on('click', (e) => {

      const {
        total_items: totalContacts,
        total_items_formatted: totalContactsFormatted,
        query: ContactQuery
      } = ContactsTable

      // console.log(e.currentTarget)

      const items = [
        {
          key: 'edit',
          cap: 'edit_contacts',
          text: sprintf(__('Edit %s contacts', 'groundhogg'), totalContactsFormatted)
        },
        {
          key: 'export',
          cap: 'export_contacts',
          text: sprintf(__('Export %s contacts', 'groundhogg'), totalContactsFormatted)
        },
        {
          key: 'broadcast',
          cap: 'schedule_broadcasts',
          text: sprintf(__('Send a broadcast to %s contacts', 'groundhogg'), totalContactsFormatted)
        },
        {
          key: 'funnel',
          cap: 'edit_contacts',
          text: sprintf(__('Add %s contacts to a funnel', 'groundhogg'), totalContactsFormatted)
        },
        {
          key: 'delete',
          cap: 'delete_contacts',
          text: `<span class="gh-text danger">${sprintf(__('Delete %s contacts', 'groundhogg'), totalContactsFormatted)}</span>`
        }
      ]

      moreMenu(e.currentTarget, {
        items: items.filter(i => userHasCap(i.cap)),
        onSelect: (key) => {
          switch (key) {
            case 'edit':
              window.location.href = adminPageURL('gh_contacts', {
                action: 'bulk_edit',
                query: {
                  ...ContactQuery,
                  number: -1,
                  offset: 0,
                  filters: base64_json_encode(ContactQuery.filters) // base64 json encode it to preserve the filters
                }
              })
              break
            case 'export':
              window.location.href = adminPageURL('gh_tools', {
                tab: 'export',
                action: 'choose_columns',
                query: {
                  ...ContactQuery,
                  number: -1,
                  offset: 0,
                  filters: base64_json_encode(ContactQuery.filters) // base64 json encode it to preserve the filters
                }
              })
              break
            case 'funnel':

              let funnel
              let step

              const addToFunnel = () => {

                const steps = () => {
                  // language=HTML
                  return `
					  <div class="gh-row">
						  <div class="gh-col">
							  <label class="block">${__('Select a step', 'groundhogg')}</label>
							  ${select({
								  id: 'select-step',
								  name: 'step'
							  }, funnel.steps.sort((a, b) => a.data.step_order - b.data.step_order).map(s => ({
								  value: s.ID,
								  text: `${s.data.step_title} (${Groundhogg.rawStepTypes[s.data.step_type].name})`
							  })), step && step.ID)}
						  </div>
					  </div>`
                }

                const submit = () => {
                  //language=HTML
                  return `
					  <div class="gh-row">
						  <div class="gh-col">
							  <button id="add-to-funnel" class="gh-button primary">
								  ${sprintf(__('Add %1$s contacts to %2$s', 'groundhogg'), totalContactsFormatted, bold(funnel.data.title))}
							  </button>
						  </div>
					  </div>`
                }

                // language=HTML
                return `
					<h2>${__('Add contacts to a funnel', 'groundhogg')}</h2>
					<div class="gh-rows-and-columns">
						<div class="gh-row">
							<div class="gh-col">
								<label class="block">${__('Select a funnel', 'groundhogg')}</label>
								${select({
									id: 'select-funnel',
									name: 'funnel'
								}, FunnelsStore.getItems().map(f => ({
									value: f.ID,
									text: f.data.title
								})), funnel && funnel.ID)}
							</div>
						</div>
						${funnel ? steps() : ''}
						${funnel && step ? submit() : ''}
					</div>`
              }

              const mounted = () => {
                funnelPicker('#select-funnel', false, (items) => {
                  FunnelsStore.itemsFetched(items)
                }, {
                  status: 'active'
                }).on('change', ({ target }) => {
                  funnel = FunnelsStore.get(parseInt($(target).val()))

                  step = false
                  setContent(addToFunnel())
                  mounted()

                })

                $('#select-step').select2({
                  // templateSelection: template,
                  // templateResult: template
                }).on('change', ({ target }) => {
                  step = funnel.steps.find(s => s.ID === parseInt($(target).val()))
                  setContent(addToFunnel())
                  mounted()
                })

                $('#add-to-funnel').on('click', () => {

                  let limit = 500
                  let offset = 0

                  setContent(`<h2 id="has-dots">${__('Adding contacts to funnel')}</h2><div id="funnel-progress"></div>`)

                  const { stop: stopDots } = loadingDots('#has-dots')
                  const { setProgress } = progressBar('#funnel-progress')

                  const scheduleEvents = () => {
                    FunnelsStore.addContacts({
                      funnel_id: funnel.ID,
                      step_id: step.ID,
                      query: {
                        ...ContactQuery,
                        limit,
                        offset
                      }
                    }).then(() => {

                      limit = Math.min(totalContacts - offset, limit)
                      offset += limit

                      setProgress(offset / totalContacts)

                      if (offset >= totalContacts) {
                        closeFunnelModal()
                        stopDots()
                        dialog({
                          message: sprintf(__('%s contacts added to "%s"'), totalContactsFormatted, funnel.data.title)
                        })
                        return
                      }

                      scheduleEvents()
                    })
                  }

                  scheduleEvents()

                })
              }

              const { setContent, close: closeFunnelModal } = modal({
                content: addToFunnel()
              })

              mounted()

              break
            case 'broadcast':

              const { close: closeModal } = modal({
                content: `<h2>${__('Send a broadcast', 'groundhogg')}</h2><div id="gh-broadcast-form" style="width: 400px"></div>`
              })

              Groundhogg.SendBroadcast('#gh-broadcast-form', {
                query: {
                  ...ContactQuery,
                  number: -1,
                  offset: 0,
                },
                total_contacts: totalContacts,
                which: 'from_table'

              }, {
                onScheduled: () => {
                  closeModal()
                }
              })

              break
            case 'delete':

              let number = 100
              let deleted = 0

              const deleteContacts = (onDelete, onComplete) => {
                ContactsStore.deleteMany({
                  ...ContactQuery,
                  number,
                  offset: 0,
                }).then(items => {

                  deleted += items.length
                  onDelete()

                  if (items.length === 0) {
                    onComplete()
                    return
                  }

                  deleteContacts(onDelete, onComplete)
                })
              }

              dangerConfirmationModal({
                alert: `<p>${sprintf(__('Are you sure you want to delete %s contacts? This cannot be undone. Consider <i>exporting</i> first!', 'groundhogg'), `<b>${totalContactsFormatted}</b>`)}</p>`,
                onConfirm: () => {

                  modal({
                    content: `<div id="delete-progress"></div>`,
                    canClose: false,
                  })

                  const { setProgress } = progressBar('#delete-progress')

                  // Set the progress bar
                  const onDelete = () => {
                    setProgress(deleted / parseInt(totalContacts))
                  }

                  // Go back to the root contacts page
                  const onComplete = () => {
                    dialog({
                      message: sprintf(__('%s contacts deleted', 'groundhogg'), `<b>${deleted}</b>`)
                    })

                    window.location.href = adminPageURL('gh_contacts')
                  }

                  deleteContacts(onDelete, onComplete)
                }
              })
              break
          }
        }
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
        alert: `<p>${sprintf(__('Are you sure you want to delete %s?', 'groundhogg'), bold(`${contact.data.first_name} ${contact.data.last_name}`))}</p>`,
        onConfirm: () => {
          ContactsStore.delete(contact.ID).then(() => {
            $(`#contact-${contact.ID}`).remove()
            dialog({
              message: sprintf(__('%s was deleted!', 'groundhogg'), `${contact.data.first_name} ${contact.data.last_name}`)
            })
          })
        }
      })
    })

  })

  // QuickEdit
  $(() => {

    if (!userHasCap('edit_contacts')) {
      return
    }

    ContactsStore.itemsFetched(ContactsTable.items)

    $(document).on('click', '.editinline', (e) => {

      e.preventDefault()

      const ID = parseInt(e.currentTarget.dataset.id)

      const contact = ContactsStore.get(ID)

      if (contact && contact.tags) {
        TagsStore.itemsFetched(contact.tags)
      }

      const quickEdit = (editingName = false) => {

        // language=HTML
        return `
			<div class="contact-quick-edit" tabindex="0">
				<div class="contact-quick-edit-header">
					<div class="avatar-and-name">
						<img height="50" width="50" src="${contact.data.gravatar}" alt="avatar"/>
						<h2 class="contact-name">
							${specialChars(`${contact.data.first_name} ${contact.data.last_name}`)}</h2>
					</div>
					<div class="actions">
						<a class="gh-button secondary"
						   href="${contact.admin}">${__('Edit Full Profile', 'groundhogg')}</a>
					</div>
				</div>
				<div class="contact-quick-edit-fields">
					<div class="row">
						<div class="col">
							<label for="quick-edit-first-name">${__('First Name', 'groundhogg')}</label>
							${input({
								id: 'quick-edit-first-name',
								name: 'first_name',
								value: contact.data.first_name,
							})}
						</div>
						<div class="col">
							<label for="quick-edit-last-name">${__('Last Name', 'groundhogg')}</label>
							${input({
								id: 'quick-edit-last-name',
								name: 'last_name',
								value: contact.data.last_name,
							})}
						</div>
					</div>
					<div class="row">
						<div class="col">
							<label for="quick-edit-email">${__('Email Address', 'groundhogg')}</label>
							${input({
								type: 'email',
								name: 'email',
								id: 'quick-edit-email',
								value: contact.data.email
							})}
						</div>
						<div class="col">
							<div class="row phone">
								<div class="col">
									<label for="quick-edit-primary-phone">${__('Primary Phone', 'groundhogg')}</label>
									${input({
										type: 'tel',
										id: 'quick-edit-primary-phone',
										name: 'primary_phone',
										value: contact.meta.primary_phone
									})}
								</div>
								<div class="primary-phone-ext">
									<label
										for="quick-edit-primary-phone-extension">${_x('Ext.', 'phone number extension', 'groundhogg')}</label>
									${input({
										type: 'number',
										id: 'quick-edit-primary-phone-extension',
										name: 'primary_phone_extension',
										value: contact.meta.primary_phone_extension
									})}
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col">
							<label for="quick-edit-email">${__('Optin Status', 'groundhogg')}</label>
							${select({
								id: 'quick-edit-optin-status',
								name: 'optin_status'
							}, Groundhogg.filters.optin_status, contact.data.optin_status)}
						</div>
						<div class="col">
							<label for="quick-edit-mobile-phone">${__('Mobile Phone', 'groundhogg')}</label>
							${input({
								type: 'tel',
								id: 'quick-edit-mobile-phone',
								name: 'mobile_phone',
								value: contact.meta.mobile_phone
							})}
						</div>
					</div>
					<div class="row">
						<div class="col">
							<label for="quick-edit-email">${__('Owner', 'noun the contact owner', 'groundhogg')}</label>
							${select({
								id: 'quick-edit-owner',
								name: 'owner_id'
							}, Groundhogg.filters.owners.map(u => ({
								text: u.data.user_email,
								value: u.ID
							})), contact.data.owner_id)}
						</div>
						<div class="col">
							<label for="quick-edit-tags">${__('Tags', 'groundhogg')}</label>
							${select({
								id: 'quick-edit-tags',
								multiple: true
							}, TagsStore.getItems().map(t => ({
								value: t.ID,
								text: t.data.tag_name
							})), contact.tags.map(t => t.ID))}
						</div>
					</div>
				</div>
			</div>`
      }

      const { close, setContent } = modal({
        content: quickEdit(),
      })

      const quickEditMounted = () => {

        let payload

        const clearPayload = () => {
          payload = {
            data: {},
            meta: {},
            add_tags: [],
            remove_tags: []
          }
        }

        clearPayload()

        const mergePayload = (data) => {
          for (const dataKey in data) {
            if (data.hasOwnProperty(dataKey)) {

              if (Array.isArray(data[dataKey])) {
                payload[dataKey] = [
                  ...payload[dataKey],
                  ...data[dataKey]
                ]
              } else {
                payload[dataKey] = {
                  ...payload[dataKey],
                  ...data[dataKey]
                }
              }
            }
          }
        }

        let timeout

        const updateContact = (data) => {

          mergePayload(data)

          if (timeout) {
            clearTimeout(timeout)
          }

          timeout = setTimeout(() => {
            ContactsStore.patch(contact.ID, payload).then(() => {
              ajax({
                action: 'groundhogg_contact_table_row',
                contact: contact.ID
              }).then((r) => {
                dialog({
                  message: __('Contact updated!', 'groundhogg')
                })
                $(`#contact-${contact.ID}`).replaceWith(r.data.row)
              })
            })
            clearPayload()
          }, 2000)
        }

        const $quickEdit = $('.contact-quick-edit')

        $quickEdit.focus()

        tagPicker('#quick-edit-tags', true, (items) => {TagsStore.itemsFetched(items)}).on('select2:unselect', (e) => {
          updateContact({
            remove_tags: [
              e.params.data.id
            ]
          })
        }).on('select2:select', (e) => {
          updateContact({
            add_tags: [
              e.params.data.id
            ]
          })
        })

        $('#quick-edit-first-name,#quick-edit-last-name,#quick-edit-email,#quick-edit-optin-status,#quick-edit-owner').on('change', (e) => {
          updateContact({
            data: {
              [e.target.name]: e.target.value
            }
          })
        })

        $('#quick-edit-primary-phone,#quick-edit-primary-phone-extension,#quick-edit-mobile-phone').on('change', (e) => {
          updateContact({
            meta: {
              [e.target.name]: e.target.value
            }
          })
        })
      }

      quickEditMounted()
    })

  })

})(jQuery)