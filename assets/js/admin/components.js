($ => {

  const { modal, errorDialog, select, input, isValidEmail } = Groundhogg.element
  const { contacts: ContactsStore } = Groundhogg.stores
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
      content: form()
    })

    const renderResult = (contact) => {
      // language=HTML
      return `
		  <tr>
			  <td><img src="${contact.data.gravatar}" alt="${contact.data.full_name}"></td>
			  <td><b>${contact.data.full_name}</b><br/>${contact.data.email}</td>
			  <td>
				  <button class="select-contact gh-button primary text" data-id="${contact.ID}">${__('Select')}</button>
			  </td>
		  </tr>`
    }

    const onMount = () => {

      const setSearchResults = (results) => {
        $('#search-results table tbody').html(results.map(r => renderResult(r)).join(''))

        $('.select-contact').on('click', (e) => {
          close()
          onSelect(ContactsStore.get(parseInt(e.currentTarget.dataset.id)))
        })
      }

      $('#contact-search').on('input change', (e) => {
        search = e.target.value

        if (timeout) {
          clearTimeout(timeout)
        }

        setTimeout(() => {

          ContactsStore.fetchItems({
            search,
            exclude,
            limit: 10
          }).then(items => {
            results = items
            setSearchResults(results)
          })

        })

      })

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

    const { close, setContent } = modal({
      content: form()
    })

    const onMount = () => {

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
        const { stop } = loadingDots(`#${classPrefix}-quick-add-button`)
        ContactsStore.post(payload).then(c => {
          stop()
          close()
          onCreate(item)
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

    onMount()

  }

  Groundhogg.components = {
    addContactModal,
    selectContactModal
  }

})(jQuery)