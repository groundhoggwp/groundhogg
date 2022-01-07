($ => {

  const {
    modal,
    errorDialog,
    loadingDots,
    select,
    uuid,
    addMediaToBasicTinyMCE,
    specialChars,
    tinymceElement,
    input,
    icons,
    dialog,
    tooltip,
    isValidEmail,
    textarea,

  } = Groundhogg.element
  const { contacts: ContactsStore, tags: TagsStore, forms: FormsStore } = Groundhogg.stores
  const { post, routes } = Groundhogg.api
  const { tagPicker } = Groundhogg.pickers
  const { sprintf, __, _x, _n } = wp.i18n
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { currentUser } = Groundhogg

  const selectContactModal = ({
    onSelect = () => {},
    exclude = []
  }) => {

    let search, timeout, results

    const form = () => {
      // language=HTML
      return `
		  <div id="search-form">
			  ${input({
				  id: 'contact-search',
				  value: search,
				  type: 'search',
				  placeholder: __('Search by name or email', 'groundhogg')
			  })}
		  </div>
		  <div id="search-results">
			  <table>
				  <tbody></tbody>
			  </table>
		  </div>`
    }

    const { close, setContent } = modal({
      content: form(),
      dialogClasses: 'no-padding'
    })

    const renderResult = (contact) => {
      // language=HTML
      return `
		  <tr data-id="${contact.ID}">
			  <td><img src="${contact.data.gravatar}" alt="${contact.data.full_name}"></td>
			  <td><b>${contact.data.full_name}</b><br/>${contact.data.email}</td>
			  <td>
				  <button class="select-contact gh-button primary text" data-id="${contact.ID}">${__('Select')}</button>
			  </td>
		  </tr>`
    }

    const noResults = () => {
      // language=HTML
      return `
		  <tr>
			  <td colspan="3"><p>${__('No contacts match that search...', 'groundhogg')}</p></td>
		  </tr>`
    }

    const onMount = () => {

      const setSearchResults = (results) => {

        if (!results.length) {
          $('#search-results table tbody').html(noResults())
          return
        }

        $('#search-results table tbody').html(results.map(r => renderResult(r)).join(''))

        $('#search-results tr, .select-contact').on('click', (e) => {
          close()
          onSelect(ContactsStore.get(parseInt(e.currentTarget.dataset.id)))
        })
      }

      const getResults = () => {
        ContactsStore.fetchItems({
          search,
          exclude,
          limit: 10
        }).then(items => {
          results = items
          setSearchResults(results)
        })
      }

      $('#contact-search').on('input change', (e) => {
        search = e.target.value

        if (timeout) {
          clearTimeout(timeout)
        }

        setTimeout(() => {

          getResults()

        }, 1000)

      }).focus()

      if (ContactsStore.hasItems()) {
        setSearchResults(ContactsStore.getItems())
      } else {
        getResults()
      }

    }

    onMount()

  }

  const quickEditContactModal = ({
    contact,
    prefix = 'quick-edit',
    onEdit = (contact) => {}
  }) => {

    if (contact && contact.tags) {
      TagsStore.itemsFetched(contact.tags)
    }

    const getContact = () => {
      return ContactsStore.get(contact.ID)
    }

    const quickEdit = (contact) => {

      // language=HTML
      return `
		  <div class="contact-quick-edit" tabindex="0">
			  <div class="gh-header space-between">
				  <div class="align-left-space-between">
					  <img height="40" width="40" src="${contact.data.gravatar}" alt="avatar"/>
					  <h3 class="contact-name">
						  ${specialChars(`${contact.data.first_name} ${contact.data.last_name}`)}</h3>
				  </div>
				  <div class="actions align-right-space-between">
					  <a class="gh-button secondary"
					     href="${contact.admin}">${__('Edit Full Profile', 'groundhogg')}</a>
					  <button class="gh-button dashicon no-border icon text ${prefix}-cancel"><span
						  class="dashicons dashicons-no-alt"></span></button>
				  </div>
			  </div>
			  <div class="contact-quick-edit-fields">
				  <div class="row">
					  <div class="col">
						  <label for="${prefix}-first-name">${__('First Name', 'groundhogg')}</label>
						  ${input({
							  id: `${prefix}-first-name`,
							  name: 'first_name',
							  value: contact.data.first_name,
						  })}
					  </div>
					  <div class="col">
						  <label for="${prefix}-last-name">${__('Last Name', 'groundhogg')}</label>
						  ${input({
							  id: `${prefix}-last-name`,
							  name: 'last_name',
							  value: contact.data.last_name,
						  })}
					  </div>
				  </div>
				  <div class="row">
					  <div class="col">
						  <label for="${prefix}-email">${__('Email Address', 'groundhogg')}</label>
						  ${input({
							  type: 'email',
							  name: 'email',
							  id: `${prefix}-email`,
							  value: contact.data.email
						  })}
					  </div>
					  <div class="col">
						  <div class="row phone">
							  <div class="col">
								  <label for="${prefix}-primary-phone">${__('Primary Phone', 'groundhogg')}</label>
								  ${input({
									  type: 'tel',
									  id: `${prefix}-primary-phone`,
									  name: 'primary_phone',
									  value: contact.meta.primary_phone
								  })}
							  </div>
							  <div class="primary-phone-ext">
								  <label
									  for="${prefix}-primary-phone-extension">${_x('Ext.', 'phone number extension', 'groundhogg')}</label>
								  ${input({
									  type: 'number',
									  id: `${prefix}-primary-phone-extension`,
									  name: 'primary_phone_extension',
									  value: contact.meta.primary_phone_extension
								  })}
							  </div>
						  </div>
					  </div>
				  </div>
				  <div class="row">
					  <div class="col">
						  <label for="${prefix}-email">${__('Optin Status', 'groundhogg')}</label>
						  ${select({
							  id: `${prefix}-optin-status`,
							  name: 'optin_status'
						  }, Groundhogg.filters.optin_status, contact.data.optin_status)}
					  </div>
					  <div class="col">
						  <label for="${prefix}-mobile-phone">${__('Mobile Phone', 'groundhogg')}</label>
						  ${input({
							  type: 'tel',
							  id: `${prefix}-mobile-phone`,
							  name: 'mobile_phone',
							  value: contact.meta.mobile_phone
						  })}
					  </div>
				  </div>
				  <div class="row">
					  <div class="col">
						  <label for="${prefix}-owner">${__('Owner', 'noun the contact owner', 'groundhogg')}</label>
						  ${select({
							  id: `${prefix}-owner`,
							  name: 'owner_id'
						  }, Groundhogg.filters.owners.map(u => ({
							  text: u.data.user_email,
							  value: u.ID
						  })), contact.data.owner_id)}
					  </div>
					  <div class="col">
						  <label for="${prefix}-tags">${__('Tags', 'groundhogg')}</label>
						  ${select({
							  id: `${prefix}-tags`,
							  multiple: true
						  })}
					  </div>
				  </div>
			  </div>
			  <div class="align-right-space-between" style="margin-top: 20px">
				  <button class="gh-button text danger ${prefix}-cancel">${__('Cancel', 'groundhogg')}</button>
				  <button class="gh-button primary" id="${prefix}-save">${__('Save Changes', 'groundhogg')}</button>
			  </div>
		  </div>`
    }

    const quickEditMounted = ({ close, setContent }) => {

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

      const updateContact = (data) => {

        payload = {
          ...data,
          data: {
            ...payload.data,
            ...data.data
          },
          meta: {
            ...payload.meta,
            ...data.meta
          },
        }
      }

      const $quickEdit = $('.contact-quick-edit')

      $quickEdit.focus()

      $(`#${prefix}-save`).on('click', (e) => {

        const $btn = $(e.target)

        $btn.prop('disabled', true)
        $btn.text(__('Saving', 'groundhogg'))
        const { stop } = loadingDots(`#${prefix}-save`)

        ContactsStore.patch(contact.ID, payload).then(c => {
          stop()
          clearPayload()
          onEdit(c)

          setContent(quickEdit(getContact()))
          quickEditMounted({ close, setContent })
        }).catch(e => {

          stop()
          clearPayload()
          setContent(quickEdit(getContact()))
          quickEditMounted({ close, setContent })

          console.log(e)

          dialog({
            type: 'error',
            message: e.message
          })
        })

      })

      $(`.${prefix}-cancel`).on('click', (e) => {
        clearPayload()
        close()
      })

      tagPicker(`#${prefix}-tags`, true, (items) => {TagsStore.itemsFetched(items)}, {
        data: getContact().tags.map(t => ({ id: t.ID, text: t.data.tag_name, selected: true }))
      }).on('select2:unselect', (e) => {

        updateContact({
          add_tags: payload.add_tags.filter(tId => tId != e.params.data.id),
          remove_tags: [
            ...payload.remove_tags,
            parseInt(e.params.data.id)
          ]
        })
      }).on('select2:select', (e) => {

        updateContact({
          add_tags: [
            ...payload.add_tags,
            parseInt(e.params.data.id)
          ].filter(tId => !getContact().tags.find(t => t.ID == tId)),
          remove_tags: payload.remove_tags.filter(tId => tId != e.params.data.id)
        })
      })

      $(`#${prefix}-first-name, #${prefix}-last-name, #${prefix}-email, #${prefix}-optin-status, #${prefix}-owner`).on('change', (e) => {
        updateContact({
          data: {
            [e.target.name]: e.target.value
          }
        })
      })

      $(`#${prefix}-primary-phone, #${prefix}-primary-phone-extension, #${prefix}-mobile-phone`).on('change', (e) => {
        updateContact({
          meta: {
            [e.target.name]: e.target.value
          }
        })
      })
    }

    const { close, setContent } = modal({
      content: quickEdit(getContact()),
      onOpen: quickEditMounted
    })
  }

  const quickAddForm = (selector, {
    prefix = 'quick-add',
    onCreate = () => {}
  }) => {

    const quickAddForm = () => {
      //language=HTML
      return `
		  <div class="gh-rows-and-columns">
			  <div class="gh-row">
				  <div class="gh-col">
					  <label for="${prefix}-first-name">${__('First Name', 'groundhogg')}</label>
					  ${input({
						  id: `${prefix}-first-name`,
						  name: 'first_name',
						  placeholder: 'John'
					  })}
				  </div>
				  <div class="gh-col">
					  <label for="${prefix}-last-name">${__('Last Name', 'groundhogg')}</label>
					  ${input({
						  id: `${prefix}-last-name`,
						  name: 'last_name',
						  placeholder: 'Doe'
					  })}
				  </div>
			  </div>
			  <div class="gh-row">
				  <div class="gh-col">
					  <label for="${prefix}-email">${__('Email Address', 'groundhogg')}</label>
					  ${input({
						  id: `${prefix}-email`,
						  name: 'email',
						  placeholder: 'john@example.com',
						  required: true,
					  })}
				  </div>
				  <div class="gh-col">
					  <div class="gh-row phone">
						  <div class="gh-col">
							  <label for="${prefix}-primary-phone">${__('Primary Phone', 'groundhogg')}</label>
							  ${input({
								  type: 'tel',
								  id: `${prefix}-primary-phone`,
								  name: 'primary_phone',
							  })}
						  </div>
						  <div class="primary-phone-ext">
							  <label
								  for="${prefix}-primary-phone-extension">${_x('Ext.', 'phone number extension', 'groundhogg')}</label>
							  ${input({
								  type: 'number',
								  id: `${prefix}-primary-phone-ext`,
								  name: 'primary_phone_extension',
							  })}
						  </div>
					  </div>
				  </div>
			  </div>
			  <div class="gh-row">
				  <div class="gh-col">
					  <label for="${prefix}-email">${__('Optin Status', 'groundhogg')}</label>
					  ${select({
						  id: `${prefix}-optin-status`,
						  name: 'optin_status'
					  }, Groundhogg.filters.optin_status)}
				  </div>
				  <div class="gh-col">
					  <label for="${prefix}-mobile-phone">${__('Mobile Phone', 'groundhogg')}</label>
					  ${input({
						  type: 'tel',
						  id: `${prefix}-mobile-phone`,
						  name: 'mobile_phone',
					  })}
				  </div>
			  </div>
			  <div class="gh-row">
				  <div class="gh-col">
					  <label for="${prefix}-owner">${__('Owner', 'noun the contact owner', 'groundhogg')}</label>
					  ${select({
						  id: `${prefix}-owner`,
						  name: 'owner_id'
					  }, Groundhogg.filters.owners.map(u => ({
						  text: u.data.user_email,
						  value: u.ID
					  })))}
				  </div>
				  <div class="gh-col">
					  <label for="${prefix}-tags">${__('Tags', 'groundhogg')}</label>
					  ${select({
						  id: `${prefix}-tags`,
						  multiple: true,
						  dataPlaceholder: __('Type to select tags...', 'groundhogg'),
						  style: {
							  width: '100%'
						  }
					  })}
				  </div>
			  </div>
			  <div class="gh-row">
				  <div class="gh-col">
					  <div>
						  <label
							  for="${prefix}-terms">${input({
							  id: `${prefix}-terms`,
							  type: 'checkbox',
							  name: 'terms_agreement',
							  value: 'yes'
						  })}
							  ${__('Agreed to the terms and conditions?', 'groundhogg')}</label>
					  </div>
					  <div>
						  <label
							  for="${prefix}-data-consent">${input({
							  id: `${prefix}-data-consent`,
							  type: 'checkbox',
							  name: 'data_consent',
							  value: 'yes'
						  })}
							  ${__('Agreed to data processing and storage? (GDPR)', 'groundhogg')}</label>
					  </div>
					  <div>
						  <label
							  for="${prefix}-marketing-consent">${input({
							  id: `${prefix}-marketing-consent`,
							  type: 'checkbox',
							  name: 'marketing_consent',
							  value: 'yes'
						  })}
							  ${__('Agreed to receive marketing? (GDPR)', 'groundhogg')}</label>
					  </div>
				  </div>
			  </div>
			  <div class="align-right-space-between">
				  <button id="${prefix}-create" class="gh-button primary">
					  ${__('Create Contact', 'groundhogg')}
				  </button>
			  </div>
		  </div>`
    }

    $(selector).html(quickAddForm())

    let payload = {
      data: {
        owner_id: currentUser.ID
      },
      meta: {}
    }

    const setPayload = (data) => {
      payload = {
        ...payload,
        ...data
      }
    }

    $(`#${prefix}-create`).on('click', ({ target }) => {

      if (!payload.data.email || !isValidEmail(payload.data.email)) {
        errorDialog({
          message: __('A valid email is required!', 'groundhogg')
        })
        return
      }

      $(target).prop('disabled', true)
      const { stop } = loadingDots(`#${prefix}-quick-add-button`)
      ContactsStore.post(payload).then(c => {
        stop()
        onCreate(c)
      })
    })

    $(`
    #${prefix}-first-name,
    #${prefix}-last-name,
    #${prefix}-owner,
    #${prefix}-optin-status,
    #${prefix}-email`).on('change input', ({ target }) => {
      setPayload({
        data: {
          ...payload.data,
          [target.name]: target.value
        }
      })
    })

    $(`
    #${prefix}-primary-phone,
    #${prefix}-primary-phone-ext,
    #${prefix}-mobile-phone`).on('change input', ({ target }) => {
      setPayload({
        meta: {
          ...payload.meta,
          [target.name]: target.value
        }
      })
    })

    $(`
    #${prefix}-terms,
    #${prefix}-data-consent,
    #${prefix}-marketing-consent`).on('change', ({ target }) => {
      setPayload({
        meta: {
          ...payload.meta,
          [target.name]: target.checked
        }
      })
    })

    tagPicker(`#${prefix}-tags`).on('change', ({ target }) => {
      setPayload({
        tags: $(target).val()
      })
    })

  }

  const addContactModal = ({
    prefix = 'quick-add',
    onCreate = () => {}
  }) => {

    let method = 'quick-add'
    let selectedForm

    const form = () => {

      const quickAddForm = () => {
        //language=HTML
        return `
			<div id="${prefix}-quick-add-form" style="margin-top: 50px"></div>`
      }

      const useForm = () => {
        //language=HTML
        return `
			<div class="gh-rows-and-columns" style="margin-top: 50px">
				<div class="gh-row">
					<div class="gh-col">
						<label for="${prefix}-select-form">${__('Select a form', 'groundhogg')}</label>
						${select({
							id: `${prefix}-select-form`,
							name: 'select_form',
						})}
					</div>
				</div>
			</div>
			<div style="margin-top: 20px">
				${selectedForm ? selectedForm.rendered : ''}
			</div>`
      }

      // language=HTML
      return `
		  <div class="quick-add-wrap" style="width: 500px">
			  <div class="gh-header modal-header">
				  <h3>${__('Add Contact', 'groundhogg')}</h3>
				  <div class="actions align-right-space-between">
					  <button
						  class="gh-button dashicon no-border icon ${method == 'quick-add' ? 'filled' : ''} use-quick-add">
						  ${icons.createContact}</span></button>
					  <button class="gh-button dashicon no-border icon ${method == 'form' ? 'filled' : ''} use-form">
						  ${icons.form}</span></button>
					  <button class="gh-button dashicon no-border icon text ${prefix}-cancel"><span
						  class="dashicons dashicons-no-alt"></span></button>
				  </div>
			  </div>
			  ${method == 'form' ? useForm() : quickAddForm()}
		  </div>
      `
    }

    const onMount = ({ close, setContent }) => {

      const reMount = () => {
        setContent(form())
        onMount({ close, setContent })
      }

      tooltip('.use-quick-add', {
        content: __('Use quick-add form', 'groundhogg')
      })

      tooltip('.use-form', {
        content: __('Use internal form', 'groundhogg')
      })

      $('.use-form').on('click', (e) => {

        method = 'form'
        reMount()
      })

      $('.use-quick-add').on('click', (e) => {

        method = 'quick-add'
        reMount()
      })

      $(`.${prefix}-cancel`).on('click', close)

      if (method == 'quick-add') {

        quickAddForm(`#${prefix}-quick-add-form`, {
          prefix,
          onCreate: (c) => {
            close()
            onCreate(c)
          }
        })

      } else {
        $(`#${prefix}-select-form`).ghPicker({
          endpoint: FormsStore.route,
          width: '100%',
          placeholder: __('Type to search...', 'groundhogg'),
          data: FormsStore.getItems().map(f => ({
            id: f.ID,
            text: f.name,
            selected: selectedForm && f.ID == selectedForm.ID
          })),
          getResults: ({ items }) => {
            FormsStore.itemsFetched(items)
            return items.map(f => ({ id: f.ID, text: f.name }))
          }
        }).on('select2:select', (e) => {
          selectedForm = FormsStore.get(e.params.data.id)
          reMount()
        })

        if (selectedForm) {
          $('.quick-add-wrap form.gh-form').on('submit', (e) => {

            e.preventDefault()

            var $form = $(e.currentTarget)

            let $btn = $form.find('#gh-submit')
            let origTxt = $btn.text()

            $btn.prop('disabled', true)
            $btn.text(__('Submitting', 'groundhogg'))
            const { stop } = loadingDots('.quick-add-wrap form.gh-form #gh-submit')
            var data = new FormData($form[0])

            data.append('action', 'groundhogg_ajax_form_submit')

            $.ajax({
              method: 'POST',
              // dataType: 'json',
              url: ajaxurl,
              data: data,
              processData: false,
              contentType: false,
              cache: false,
              timeout: 600000,
              enctype: 'multipart/form-data',
              success: (r) => {

                stop()
                $btn.prop('disabled', false)
                $btn.text(origTxt)

                if (!r.success) {

                  dialog({
                    message: r.data[0].message,
                    type: 'error'
                  })

                } else {
                  dialog({
                    message: __('Form submitted!'),
                  })

                  close()

                  ContactsStore.itemsFetched([
                    r.data.contact
                  ])

                  onCreate(r.data.contact)
                }

              },
              error: (e) => {
                dialog({
                  message: __('Something went wrong...', 'groundhogg'),
                  type: 'error'
                })
              }
            })

          })
        }
      }

    }

    return modal({
      content: form(),
      onOpen: onMount
    })

  }

  const emailModal = (props) => {

    const email = {
      to: [],
      from_name: '',
      from_email: '',
      cc: [],
      bcc: [],
      subject: '',
      content: '',
      ...props
    }

    // Modal is already open
    if ( $( '.gh-modal.send-email' ).length ){
      return;
    }

    let showCc = email.cc.length > 0
    let showBcc = email.bcc.length > 0

    const template = () => {
      //language=HTML
      return `
		  <div class="gh-rows-and-columns">
			  <div class="gh-row">
				  <label>${__('From:')}</label>
				  <div class="gh-col">
					  <select id="from"></select>
				  </div>
			  </div>
			  <div class="gh-row">
				  <label>${__('To:')}</label>
				  <div class="gh-col">
					  <select id="recipients"></select>
				  </div>
				  ${!showCc ? `<a id="send-email-cc" href="#">${__('Cc')}</a>` : ''}
				  ${!showBcc ? `<a id="send-email-bcc" href="#">${__('Bcc')}</a>` : ''}
			  </div>
			  ${showCc ? `<div class="gh-row">
				  <label>${__('Cc:')}</label>
				  <div class="gh-col">
					  <select id="cc"></select>
				  </div>
			  </div>` : ''}
			  ${showBcc ? `<div class="gh-row">
				  <label>${__('Bcc:')}</label>
				  <div class="gh-col">
					  <select id="bcc"></select>
				  </div>
			  </div>` : ''}
			  <div class="gh-row">
				  <div class="gh-col">
					  ${input({
						  placeholder: __('Subject line...'),
						  id: 'send-email-subject',
						  value: email.subject
					  })}
				  </div>
			  </div>
			  <div class="gh-row">
				  <div class="gh-col">
					  ${textarea({
						  id: 'send-email-content',
						  value: email.subject
					  })}
				  </div>
			  </div>
			  <div class="gh-row">
				  <div class="gh-col align-right-space-between">
					  <button class="gh-button danger text" id="discard-draft">${__('Discard')}</button>
					  <button class="gh-button primary" id="send-email-commit">${__('Send')}</button>
				  </div>
			  </div>
		  </div>`
    }

    const onMount = ({ close, setContent }) => {

      const reMount = () => {
        wp.editor.remove('send-email-content')
        setContent(template())
        onMount({ close, setContent })
      }

      const selectChange = (e, name) => {
        email[name] = $(e.target).val()
      }

      $('#recipients').ghPicker({
        endpoint: ContactsStore.route,
        getResults: r => r.items.map(c => ({ text: c.data.email, id: c.data.email })),
        getParams: q => ({ ...q, email: q.term, email_compare: 'starts_with' }),
        data: email.to.map(i => ({ id: i, text: i, selected: true })),
        tags: true,
        multiple: true,
        width: '100%',
        placeholder: __('Recipients'),
      }).on('change', e => selectChange(e, 'to'))

      $('#cc').ghPicker({
        endpoint: ContactsStore.route,
        getResults: r => r.items.map(c => ({ text: c.data.email, id: c.data.email })),
        getParams: q => ({ ...q, email: q.term, email_compare: 'starts_with' }),
        data: email.cc.map(i => ({ id: i, text: i, selected: true })),
        tags: true,
        multiple: true,
        width: '100%',
        placeholder: __('Cc'),
      }).on('change', e => selectChange(e, 'cc'))

      $('#from').select2({
        data: Groundhogg.filters.owners.map(u => ({
          id: u.ID,
          text: `${u.data.display_name} <${u.data.user_email}>`,
          selected: u.data.user_email === email.from_email
        })),
        width: '100%',
        placeholder: __('From'),
      }).on('change', e => {

        let u = Groundhogg.filters.owners.find(u => u.ID == $(e.target).val())

        email['from_email'] = u.data.user_email
        email['from_name'] = u.data.display_name
      })

      $('#bcc').select2({
        data: [...email.bcc.map(i => ({
          id: i,
          text: i,
          selected: true
        })),
          ...Groundhogg.filters.owners
            .filter(u => !email.bcc.includes(u.data.user_email))
            .map(u => ({ text: u.data.user_email, id: u.data.user_email }))],
        tags: true,
        multiple: true,
        width: '100%',
        placeholder: __('Bcc'),
      }).on('change', e => selectChange(e, 'bcc'))

      $('#send-email-subject').on('change', (e) => {
        email.subject = e.target.value
      }).focus()

      addMediaToBasicTinyMCE()

      let editor = tinymceElement('send-email-content', {
        quicktags: false,
        tinymce: {
          height: 300,
        }
      }, (content) => {
        email.content = content
      })

      $('#send-email-cc').on('click', () => {
        showCc = true
        reMount()
      })

      $('#send-email-bcc').on('click', () => {
        showBcc = true
        reMount()
      })

      $('#discard-draft').on('click', close)

      $('#send-email-commit').on('click', ({ target }) => {

        $(target).text(__('Sending', 'groundhogg')).prop('disabled', true)
        const { stop } = loadingDots(target)

        const release = () => {
          stop()
          $(target).text(__('Send', 'groundhogg')).prop('disabled', false)
        }

        post(`${routes.v4.emails}/send`, {
          ...email,
          content: editor.getContent({ format: 'raw' })
        }).then((r) => {

          release()

          if (r.status !== 'success') {

            dialog({
              message: r.message,
              type: 'error'
            })

            return
          }

          dialog({
            message: __('Message sent!', 'groundhogg')
          })

          close()
        }).catch(e => {

          release()

          dialog({
            message: e.message,
            type: 'error'
          })
        })
      })

    }

    return modal({
      content: template(),
      onOpen: onMount,
      onClose: () => {
        wp.editor.remove('send-email-content')
      },
      overlay: false,
      className: 'send-email',
      dialogClasses: 'gh-panel',
      disableScrolling: false
    })

  }

  const makeInput = (selector, {
    inputProps = {},
    value = '',
    onChange = () => {},
    replaceWith = () => {}
  }) => {

    inputProps = {
      id: uuid(),
      value,
      ...inputProps
    }

    $(selector).replaceWith(input(inputProps))

    $(`#${inputProps.id}`).focus().on('blur keydown', e => {

      if (e.type === 'keydown' && e.key !== 'Enter') {
        return
      }

      value = e.target.value
      onChange(value)

      $(`#${inputProps.id}`).replaceWith(replaceWith(value))
    })

  }

  Groundhogg.components = {
    addContactModal,
    quickAddForm,
    selectContactModal,
    quickEditContactModal,
    makeInput,
    emailModal,
  }

})(jQuery)