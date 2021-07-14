(function ($) {

  const classPrefix = 'groundhogg-toolbar-quick-search'

  const {
    moreMenu,
    input,
    select,
    tooltip,
    loadingDots,
    isValidEmail,
    dialog,
    errorDialog,
    clickedIn,
    inputWithReplacements
  } = Groundhogg.element

  const { tagPicker } = Groundhogg.pickers

  const {
    contacts: ContactsStore
  } = Groundhogg.stores

  const Tabs = {
    search_contacts: {
      tooltip: `Search for contacts`,
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

          $(`.${classPrefix}-result`).on('click', (e) => {

            if (clickedIn(e, '.email-contact')) {
              e.stopPropagation()
              console.log('send-email')
              return
            }

            if (clickedIn(e, '.call-primary')) {
              e.preventDefault()
              console.log('call-primary')
              return
            }

            const ID = parseInt(e.currentTarget.dataset.contact)
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
				  <div class="actions">
					  <button class="gh-button secondary text icon">
						  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
							  <path fill="currentColor"
							        d="M256 0c-74.4 0-135 60.6-135 135s60.6 135 135 135 135-60.6 135-135S330.4 0 256 0zm0 240a105.1 105.1 0 010-210 105.1 105.1 0 010 210zM424 358.2c-37-37.5-86-58.2-138-58.2h-60c-52 0-101 20.7-138 58.2A196.7 196.7 0 0031 497a15 15 0 0015 15h420a15 15 0 0015-15c0-52.2-20.3-101.5-57-138.8zM61.7 482A166 166 0 01226 330h60c86 0 156.8 67 164.3 152H61.7z"/>
						  </svg>
					  </button>
					  <button data-contact="${item.ID}" class="email-contact gh-button secondary text icon">
						  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
							  <path fill="currentColor"
							        d="M467 61H45a45 45 0 00-45 45v300a45 45 0 0045 45h422a45 45 0 0045-45V106a45 45 0 00-45-45zm-6.2 30L257 294.8 51.4 91h409.4zM30 399.8V112l144.5 143.2L30 399.8zM51.2 421l144.6-144.6 50.6 50.3a15 15 0 0021.2 0l49.4-49.5L460.8 421H51.2zM482 399.8L338.2 256 482 112.2v287.6z"/>
						  </svg>
					  </button>
					  ${item.meta.primary_phone ? `
					  <a class="gh-button secondary text icon" href="tel:${item.meta.primary_phone}">
						  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 473.8 473.8">
							  <path fill="currentColor"
								  d="M374.5 293.5a46.6 46.6 0 00-33.8-15.5 48.2 48.2 0 00-34.2 15.4L274.9 325l-7.7-4a127 127 0 01-10-5.3 343.5 343.5 0 01-82.2-75 202.6 202.6 0 01-27-42.6c8.2-7.5 15.8-15.3 23.2-22.8l8.4-8.5c21-21 21-48.2 0-69.2l-27.3-27.3c-3.1-3-6.3-6.3-9.3-9.5-6-6.2-12.3-12.6-18.8-18.6-9.7-9.6-21.3-14.7-33.5-14.7s-24 5.1-34 14.7l-.2.2-34 34.3A73.2 73.2 0 00.8 123.1c-2.4 29.2 6.2 56.4 12.8 74.2C29.8 241 54 281.5 90 325a470.6 470.6 0 00156.7 122.7c23 11 53.7 23.8 88 26l6.3.2c23 0 42.5-8.3 57.7-24.8 0-.2.3-.3.4-.5 5.2-6.3 11.2-12 17.5-18 4.3-4.2 8.7-8.5 13-13a49.9 49.9 0 0015-34.6 48 48 0 00-15.3-34.3l-55-55zm35.8 105.3c-.1 0-.1.1 0 0-4 4.2-8 8-12.2 12.2a263 263 0 00-19.3 20 48.2 48.2 0 01-37.6 16c-1.5 0-3.1 0-4.6-.2-29.7-1.9-57.3-13.5-78-23.4A444.2 444.2 0 01111 307.8c-34.1-41-57-79-72-119.9-9.3-24.9-12.7-44.3-11.2-62.6 1-11.7 5.5-21.4 13.8-29.7l34-34A22.7 22.7 0 0191 54.3c6.3 0 11.4 3.8 14.6 7l.3.3c6 5.7 11.9 11.6 18 18l9.5 9.6 27.3 27.3c10.6 10.6 10.6 20.4 0 31l-8.6 8.6a522 522 0 01-25.1 24.4l-.5.5c-8.6 8.6-7 17-5.2 22.7l.3 1a219.2 219.2 0 0032.3 52.6v.1a367 367 0 0088.9 80.8c4 2.6 8.3 4.7 12.3 6.7 3.6 1.8 7 3.5 9.9 5.3l1.2.7c3.4 1.7 6.6 2.5 9.9 2.5 8.3 0 13.5-5.2 15.2-6.9l34.2-34.2c3.4-3.4 8.8-7.5 15-7.5 6.3 0 11.4 4 14.5 7.3l.2.2 55 55.1c10.4 10.2 10.4 20.7.2 31.3zM256 112.7c26.3 4.4 50 16.8 69 35.8s31.4 42.8 35.9 69a13.4 13.4 0 0013.3 11.2c.8 0 1.5 0 2.3-.2a13.5 13.5 0 0011-15.6c-5.3-31.7-20.3-60.6-43.2-83.5s-51.8-37.9-83.5-43.3c-7.4-1.2-14.3 3.7-15.6 11s3.5 14.4 10.9 15.6zM473.3 209c-9-52.2-33.5-99.7-71.3-137.5S316.7 9.1 264.5.2a13.4 13.4 0 10-4.4 26.6c46.6 8 89 30 122.9 63.7a226.5 226.5 0 0163.7 123 13.4 13.4 0 0013.3 11.1c.8 0 1.5 0 2.3-.2a13.2 13.2 0 0011-15.4z"/>
						  </svg>
					  </a>` : ''}
					  ${item.meta.mobile_phone ? `
					  <a class="gh-button secondary text icon">
						  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 473.8 473.8">
							  <path fill="currentColor"
								  d="M374.5 293.5a46.6 46.6 0 00-33.8-15.5 48.2 48.2 0 00-34.2 15.4L274.9 325l-7.7-4a127 127 0 01-10-5.3 343.5 343.5 0 01-82.2-75 202.6 202.6 0 01-27-42.6c8.2-7.5 15.8-15.3 23.2-22.8l8.4-8.5c21-21 21-48.2 0-69.2l-27.3-27.3c-3.1-3-6.3-6.3-9.3-9.5-6-6.2-12.3-12.6-18.8-18.6-9.7-9.6-21.3-14.7-33.5-14.7s-24 5.1-34 14.7l-.2.2-34 34.3A73.2 73.2 0 00.8 123.1c-2.4 29.2 6.2 56.4 12.8 74.2C29.8 241 54 281.5 90 325a470.6 470.6 0 00156.7 122.7c23 11 53.7 23.8 88 26l6.3.2c23 0 42.5-8.3 57.7-24.8 0-.2.3-.3.4-.5 5.2-6.3 11.2-12 17.5-18 4.3-4.2 8.7-8.5 13-13a49.9 49.9 0 0015-34.6 48 48 0 00-15.3-34.3l-55-55zm35.8 105.3c-.1 0-.1.1 0 0-4 4.2-8 8-12.2 12.2a263 263 0 00-19.3 20 48.2 48.2 0 01-37.6 16c-1.5 0-3.1 0-4.6-.2-29.7-1.9-57.3-13.5-78-23.4A444.2 444.2 0 01111 307.8c-34.1-41-57-79-72-119.9-9.3-24.9-12.7-44.3-11.2-62.6 1-11.7 5.5-21.4 13.8-29.7l34-34A22.7 22.7 0 0191 54.3c6.3 0 11.4 3.8 14.6 7l.3.3c6 5.7 11.9 11.6 18 18l9.5 9.6 27.3 27.3c10.6 10.6 10.6 20.4 0 31l-8.6 8.6a522 522 0 01-25.1 24.4l-.5.5c-8.6 8.6-7 17-5.2 22.7l.3 1a219.2 219.2 0 0032.3 52.6v.1a367 367 0 0088.9 80.8c4 2.6 8.3 4.7 12.3 6.7 3.6 1.8 7 3.5 9.9 5.3l1.2.7c3.4 1.7 6.6 2.5 9.9 2.5 8.3 0 13.5-5.2 15.2-6.9l34.2-34.2c3.4-3.4 8.8-7.5 15-7.5 6.3 0 11.4 4 14.5 7.3l.2.2 55 55.1c10.4 10.2 10.4 20.7.2 31.3zM256 112.7c26.3 4.4 50 16.8 69 35.8s31.4 42.8 35.9 69a13.4 13.4 0 0013.3 11.2c.8 0 1.5 0 2.3-.2a13.5 13.5 0 0011-15.6c-5.3-31.7-20.3-60.6-43.2-83.5s-51.8-37.9-83.5-43.3c-7.4-1.2-14.3 3.7-15.6 11s3.5 14.4 10.9 15.6zM473.3 209c-9-52.2-33.5-99.7-71.3-137.5S316.7 9.1 264.5.2a13.4 13.4 0 10-4.4 26.6c46.6 8 89 30 122.9 63.7a226.5 226.5 0 0163.7 123 13.4 13.4 0 0013.3 11.1c.8 0 1.5 0 2.3-.2a13.2 13.2 0 0011-15.4z"/>
						  </svg>
					  </a>` : ''}
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
      tooltip: `Create a contact`,
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
			<div class="gh-rows-and-columns">
				<div class="gh-row">
					<div class="gh-col">
						<label for="${subClassPrefix}-first-name">First Name</label>
						${input({
							id: `${subClassPrefix}-first-name`,
							name: 'first_name',
							placeholder: 'John'
						})}
					</div>
					<div class="gh-col">
						<label for="${subClassPrefix}-last-name">Last Name</label>
						${input({
							id: `${subClassPrefix}-last-name`,
							name: 'last_name',
							placeholder: 'Doe'
						})}
					</div>
				</div>
				<div class="gh-row">
					<div class="gh-col">
						<label for="${subClassPrefix}-email">Email Address</label>
						${input({
							id: `${subClassPrefix}-email`,
							name: 'email',
							placeholder: 'john@example.com',
							required: true,
						})}
					</div>
				</div>
				<div class="gh-row">
					<div class="gh-col">
						<label for="${subClassPrefix}-tags">Tags</label>
						${select({
							id: `${subClassPrefix}-tags`,
							multiple: true,
							dataPlaceholder: 'Type to select tags...'
						})}
					</div>
				</div>
				<div class="gh-row">
					<div class="gh-col">
						<button id="${classPrefix}-quick-add-button" class="gh-button primary">Create Contact</button>
					</div>
				</div>
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
    },
    send_email: {
      tooltip: `Send an email`,
      //language=HTML
      svg: `
		  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			  <path fill="currentColor"
			        d="M467 61H45a45 45 0 00-45 45v300a45 45 0 0045 45h422a45 45 0 0045-45V106a45 45 0 00-45-45zm-6.2 30L257 294.8 51.4 91h409.4zM30 399.8V112l144.5 143.2L30 399.8zM51.2 421l144.6-144.6 50.6 50.3a15 15 0 0021.2 0l49.4-49.5L460.8 421H51.2zM482 399.8L338.2 256 482 112.2v287.6z"/>
		  </svg>`,
      view: () => {
        // language=html
        return `
			<div class="gh-rows-and-columns">
				<div class="gh-row">
					<div class="gh-col">
						<div class="gh-input-inline-label">
							<label for="subject-line">Subject:</label>
							${inputWithReplacements({
								id: 'subject-line'
							})}
						</div>
					</div>
				</div>
				<div class="gh-row"></div>
			</div>`
      },
      onMount: () => {},
    },
    broadcast: {
      //language=HTML
      tooltip: `Send a broadcast`,
      svg: `
		  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			  <path fill="currentColor"
			        d="M346.4 30.1c-21 0-38.7 14.4-43.8 33.8l-169.5 56.5H45C20.4 120.4 0 140.8 0 165.6V256c0 24.9 20.3 45.2 45.2 45.2h.6l15.4 144.4a45.2 45.2 0 1088.3-18.6l-3.2-35.5H165.6c25 0 45.2-20.3 45.2-45.2v-19.2l91.8 30.6a45.3 45.3 0 0089-11.4v-271c0-25-20.3-45.2-45.2-45.2zM109 451.3a15 15 0 01-18-10.1l-15-140h31.7c12.8 138.6 12 130.2 12.3 131.8a15 15 0 01-11 18.3zM120.5 271H45.2a15 15 0 01-15-15v-90.4a15 15 0 0115-15h75.3V271zm60.2 75.3a15 15 0 01-15 15h-18.5a15 15 0 00-3.7.6l-5.4-59 42.6 14.1v29.3zm120.5-20.9l-150.6-50.2V146.4l150.6-50.2v229.2zm60.2 21a15 15 0 01-30.1 0V75.2a15 15 0 0130.1 0v271zM497 180.7h-60.3a15 15 0 000 30.1h60.2a15 15 0 000-30.1zM510.4 98.7a15 15 0 00-20.2-6.8L430 122a15 15 0 1013.4 27l60.3-30.1a15 15 0 006.7-20.2zM503.7 272.6l-60.3-30a15 15 0 10-13.4 26.8l60.2 30.2a15 15 0 1013.5-27z"/>
		  </svg>`,
      view: () => {
        // language=HTML
        return `
			<div id="send-broadcast"></div>`
      },
      onMount: ({ setTab }) => {
        Groundhogg.SendBroadcast('#send-broadcast', {}, {
          onScheduled: () => {
            setTab('broadcast')
          }
        })
      },
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

    $menuItem.on('click', (e) => {

      if (openFlag) {
        close()
        return
      }

      openFlag = true

      const { right, bottom } = e.currentTarget.getBoundingClientRect()

      const renderTabs = () => {

        const renderTab = (t, props) => {
          //language=HTML
          return `
			  <button id="gh-tab-${t}" data-tab=${t}
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

        $('.gh-tooltip').remove()

        // language=html
        const html = `
			<button type="button" class="dashicon-button ${classPrefix}-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
			${renderTabs()}${Tabs[tab].view()}`

        $quickSearch.html(html)

        const setTab = (t) => {
          tab = t
          mountQuickSearch()
        }

        $(`.${classPrefix}-tab-button`).on('click', ({ currentTarget }) => {
          setTab(currentTarget.dataset.tab)
        })

        Tabs[tab].onMount({ setTab })

        Object.keys(Tabs).forEach(t => tooltip(`#gh-tab-${t}`, {
          content: Tabs[t].tooltip,
          position: 'bottom'
        }))

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