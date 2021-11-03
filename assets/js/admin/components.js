($ => {

  const {
    modal,
    errorDialog,
    loadingDots,
    select,
    uuid,
    addMediaToBasicTinyMCE,
    tinymceElement,
    input,
    dialog,
    isValidEmail,
    textarea
  } = Groundhogg.element
  const { contacts: ContactsStore } = Groundhogg.stores
  const { post, routes } = Groundhogg.api
  const { tagPicker } = Groundhogg.pickers
  const { sprintf, __, _x, _n } = wp.i18n
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting

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

        if ( ! results.length ){
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

        })

      }).focus()

      if ( ContactsStore.hasItems() ){
        setSearchResults( ContactsStore.getItems() )
      } else {
        getResults()
      }

    }

    onMount()

  }

  const addContactModal = ({
    prefix = 'quick-add',
    onCreate = () => {}
  }) => {

    const form = () => {
      return `	<div class="gh-rows-and-columns">
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
				</div>
				<div class="gh-row phone">
					<div class="cghol">
						<label for="quick-edit-primary-phone">${__('Primary Phone', 'groundhogg')}</label>
						${input({
        type: 'tel',
        id: `${prefix}-primary-phone`,
        name: 'primary_phone',
      })}
					</div>
					<div class="primary-phone-ext">
						<label
							for="quick-edit-primary-phone-extension">${_x('Ext.', 'phone number extension', 'groundhogg')}</label>
						${input({
        type: 'number',
        id: `${prefix}-primary-phone-ext`,
        name: 'primary_phone_extension',
      })}
					</div>
				</div>
				<div class="gh-row">
					<div class="gh-col">
						<label for="quick-edit-mobile-phone">${__('Mobile Phone', 'groundhogg')}</label>
						${input({
        type: 'tel',
        id: `${prefix}-mobile-phone`,
        name: 'mobile_phone',
      })}
					</div>
				</div>
				<div class="gh-row">
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
						<button id="${prefix}-create" class="gh-button primary">
							${__('Create Contact', 'groundhogg')}
						</button>
					</div>
				</div>
			</div>`
    }

    const onMount = ({ close, setContent }) => {

      let payload = {
        data: {},
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
          close()
          onCreate(c)
        })
      })

      $(`
      #${prefix}-first-name,
      #${prefix}-last-name,
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

      tagPicker(`#${prefix}-tags`).on('change', ({ target }) => {
        setPayload({
          tags: $(target).val()
        })
      })
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

    let showCc = email.cc.length > 0
    let showBcc = email.bcc.length > 0

    const template = () => {
      //language=HTML
      return `
		  <div class="gh-rows-and-columns">
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

      $('#discard-draft').on('click', close )

      $('#send-email-commit').on('click', ({ target }) => {

        $(target).text(__('Sending', 'groundhogg')).prop('disabled', true)
        const { stop } = loadingDots(target)

        post(`${routes.v4.emails}/send`, {
          ...email,
          content: editor.getContent({ format: 'raw' })
        }).then((r) => {

          stop()
          $(target).text(__('Send', 'groundhogg')).prop('disabled', false)

          if (r.status !== 'success') {

            console.log(r)

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
    selectContactModal,
    makeInput,
    emailModal,
  }

})(jQuery)