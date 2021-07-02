(function ($) {

  const classPrefix = 'groundhogg-toolbar-quick-search'

  const {
    moreMenu,
    input,
    select,
    loadingDots,
    isValidEmail,
    dialog,
    errorDialog
  } = Groundhogg.element

  const { tagPicker } = Groundhogg.pickers

  const {
    contacts: ContactsStore
  } = Groundhogg.stores

  const Tabs = {
    search_contacts: {
      // language=HTML
      svg: `
		  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			  <defs/>
			  <path fill="currentColor"
			        d="M486.3 430.8L426 370.5l-23 23-18-18A101.8 101.8 0 00286.6 215a156.9 156.9 0 00-55-36 99.4 99.4 0 10-119.3 0A156.7 156.7 0 0011.8 325v46.2h206.7a101.3 101.3 0 00145.3 25.6l17.9 18-23 23L419 498a47.3 47.3 0 0067.3 0 47.3 47.3 0 000-67.3zM102.5 99.4a69.5 69.5 0 1174.9 69.3h-11a69.5 69.5 0 01-64-69.3zM41.8 341.2V325A126.6 126.6 0 01171.9 199l6.6-.2c28.6.6 54.8 10.8 75.7 27.4A101.3 101.3 0 00205 341H41.8zm210.8 24.5a71.2 71.2 0 01-21-50.6 71.2 71.2 0 0171.7-71.6 71.7 71.7 0 11-50.6 122.2zm212.5 111.1a17.5 17.5 0 01-25 0l-39-39 24.9-25 39 39.2a17.5 17.5 0 010 24.8z"/>
		  </svg>`,
      view: () => {

        // language=HTML
        return `
			<div id="quick-search-wrap">
				${input({
					type: 'search',
					id: 'quick-search-input',
					placeholder: 'Search by name, email, or phone...'
				})}
				<div class="${classPrefix}-results"></div>
			</div>`
      },
      onMount: () => {

        const mountSearchResults = (items) => {
          $(`.${classPrefix}-results`).replaceWith(renderSearchResults(items))

          $(`.${classPrefix}-result`).on('click', ({ currentTarget }) => {
            const ID = parseInt(currentTarget.dataset.contact)
            const contact = ContactsStore.get(ID)

            console.log(contact)

            window.location.href = contact.admin
          })
        }

        const renderSearchResult = (item) => {

          //language=HTML
          return `
			  <div id="search-result-${item.ID}" data-contact="${item.ID}" class="${classPrefix}-result">
				  <img class="avatar" src="${item.data.gravatar}" alt="avatar"/>
				  <div class="details">
					  <div class="name">${item.data.first_name} ${item.data.last_name}</div>
					  <div class="email">${item.data.email}</div>
				  </div>
			  </div>`
        }

        const renderSearchResults = (items = []) => {
          if (!items || items.length === 0) {
            //language=HTML
            return `
				<div class="${classPrefix}-results"></div>`
          }

          //language=HTML
          return `
			  <div class="${classPrefix}-results">
				  ${items.map(item => renderSearchResult(item)).join('')}
			  </div>`
        }

        let timeout

        $('#quick-search-input').on('change input', ({ target }) => {

          if (timeout) {
            clearTimeout(timeout)
          }

          timeout = setTimeout(() => {
            ContactsStore.fetchItems({
              search: target.value,
              limit: 10
            }).then(items => {
              mountSearchResults(items)
            })
          }, 1000)
        }).focus()

      }
    },
    create_contact: {
      // language=HTML
      svg: `
		  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			  <defs/>
			  <path fill="currentColor"
			        d="M511 317a105.1 105.1 0 00-183-70.3 225.9 225.9 0 00-34.6-14.3 126 126 0 10-135-.1A224 224 0 0067 287.9 223.5 223.5 0 001 447v50a15 15 0 0015 15h420a15 15 0 0015-15v-50c0-11.3-.9-22.6-2.6-34a105.1 105.1 0 0062.6-96zM130 126a96.1 96.1 0 01192 0 96.1 96.1 0 01-192 0zm291 321v35H31v-35c0-107.5 87.5-195 195-195 29.5 0 58.6 6.8 85.2 19.8a105.1 105.1 0 00108 149.3c1.2 8.7 1.8 17.3 1.8 25.9zm6.7-58.2c-.4 0-.7.2-1.1.3A74.7 74.7 0 01331 317a75 75 0 1196.7 71.8z"/>
			  <path fill="currentColor"
			        d="M436 302h-15v-15a15 15 0 00-30 0v15h-15a15 15 0 000 30h15v15a15 15 0 0030 0v-15h15a15 15 0 000-30z"/>
		  </svg>`,
      view: () => {

        const subClassPrefix = `${classPrefix}-quick-add`

        // language=HTML
        return `
			<div class="${subClassPrefix}-fields">
				<div class="row">
					<div class="col">
						<label for="${subClassPrefix}-first-name">First Name</label>
						${input({
							id: `${subClassPrefix}-first-name`,
							name: 'first_name',
							placeholder: 'John'
						})}
					</div>
					<div class="col">
						<label for="${subClassPrefix}-last-name">Last Name</label>
						${input({
							id: `${subClassPrefix}-last-name`,
							name: 'last_name',
							placeholder: 'Doe'
						})}
					</div>
				</div>
				<div class="row">
					<div class="col">
						<label for="${subClassPrefix}-email">Email Address</label>
						${input({
							id: `${subClassPrefix}-email`,
							name: 'email',
							placeholder: 'john@example.com',
							required: true,
						})}
					</div>
				</div>
				<div class="row">
					<div class="col">
						<label for="${subClassPrefix}-tags">Tags</label>
						${select({
							id: `${subClassPrefix}-tags`,
							multiple: true,
							dataPlaceholder: 'Type to select tags...'
						})}
					</div>
				</div>
				<button id="${classPrefix}-quick-add-button" class="gh-button primary">Create Contact</button>
			</div>`
      },
      onMount: () => {

        const subClassPrefix = `${classPrefix}-quick-add`

        let payload = {
          data: {}
        }

        const setPayload = (data) => {
          payload = {
            ...payload,
            ...data
          }

          console.log(payload)
        }

        $(`#${classPrefix}-quick-add-button`).on('click', ({ target }) => {

          if (!payload.data.email || !isValidEmail(payload.data.email)) {
            errorDialog({
              message: 'A valid email is required!'
            })
            return
          }

          $(target).prop('disabled', true)
          const { stop } = loadingDots(`#${classPrefix}-quick-add-button`)
          ContactsStore.post(payload).then(c => {
            stop()
            window.location.href = c.admin
          })
        })

        $(`#${subClassPrefix}-first-name, #${subClassPrefix}-last-name, #${subClassPrefix}-email`).on('change input', ({ target }) => {
          setPayload({
            data: {
              ...payload.data,
              [target.name]: target.value
            }
          })
        })

        tagPicker(`#${subClassPrefix}-tags`).on('change', ({ target }) => {
          setPayload({
            tags: $(target).val()
          })
        })

      }
    }
  }

  $(() => {

    const $menuItem = $('#wp-admin-bar-groundhogg')

    let openFlag = false
    let tab = 'search_contacts'

    const close = () => {
      openFlag = false
      $('#groundhogg-toolbar-quick-search').remove()
      $('body').removeClass('groundhogg-toolbar-quick-search-open')
    }

    $menuItem.on('mouseenter', (e) => {

      if (openFlag) {
        return
      }

      openFlag = true

      const { right, bottom } = e.target.getBoundingClientRect()

      const renderTabs = () => {

        const renderTab = (t, props) => {
          //language=HTML
          return `
			  <button data-tab=${t}
			          class="${classPrefix}-tab-button gh-button text ${tab === t ? 'primary' : 'secondary'} icon">
				  ${props.svg}
			  </button>`
        }
        //language=HTML
        return `
			<div class="${classPrefix}-tabs">
				${Object.keys(Tabs).map(t => renderTab(t, Tabs[t])).join('')}
			</div>`
      }

      const renderQuickSearch = () => {
        // language=HTML
        return `
			<div id="groundhogg-toolbar-quick-search" class="${classPrefix}" tabindex="0"></div>`
      }

      const mountQuickSearch = () => {

        // language=html
        const html = `
			<button type="button" class="dashicon-button ${classPrefix}-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
			${renderTabs()}${Tabs[tab].view()}`

        $quickSearch.html(html)
        $(`.${classPrefix}-tab-button`).on('click', ({ currentTarget }) => {
          tab = currentTarget.dataset.tab
          mountQuickSearch()
        })
        Tabs[tab].onMount()
        $quickSearch.css({
          top: Math.min(bottom, window.innerHeight - $quickSearch.height() - 20),
          left: (right - $quickSearch.outerWidth())
        })

        $(`.${classPrefix}-close`).on('click', () => {
          close()
        })
      }

      const $quickSearch = $(renderQuickSearch())

      $('body').append($quickSearch).addClass('groundhogg-toolbar-quick-search-open')

      $quickSearch.on('keydown', ({ key }) => {
        switch (key) {
          case 'Esc':
          case 'Escape':
            close()
            break
        }
      })

      mountQuickSearch()

    })
  })
})(jQuery)