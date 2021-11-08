(($) => {

  const {
    searchOptionsWidget,
    regexp,
    specialChars,
    breadcrumbs,
    modal,
    input,
    tabs,
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
    button,
    infoCard,
    el,
    icons,
    adminPageURL
  } = Groundhogg.element
  const { post, get, patch, routes, ajax } = Groundhogg.api
  const { searches: SearchesStore, contacts: ContactsStore, tags: TagsStore, funnels: FunnelsStore } = Groundhogg.stores
  const { tagPicker, funnelPicker } = Groundhogg.pickers
  const { userHasCap } = Groundhogg.user
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { sprintf, __, _x, _n } = wp.i18n
  const {
    InfoCardProvider
  } = Groundhogg.utils

  const { contact } = GroundhoggContact

  const PrimaryInfoCards = InfoCardProvider({})
  const SecondaryInfoCards = InfoCardProvider({})

  PrimaryInfoCards.registerCard('contact-details', {
    title: ({ data }) => {
      return __('Contact info', 'groundhogg')
    },
    content: (contact, { editing = false }) => {

      const view = () => {

        // language=HTML
        return ``
      }

      const edit = () => {

        // language=HTML
        return ``
      }

      // language = HTML
      return !editing ? view() : edit()
    },
    onMount: (contact) => {
      console.log('card was mounted')
    },
    preload: (contact) => {}
  })

  PrimaryInfoCards.registerCard('company-details', {
    title: ({ data }) => {
      return __('Company', 'groundhogg')
    },
    content: (contact, { editing = false }) => {

      const view = () => {

        // language=HTML
        return ``
      }

      const edit = () => {

        // language=HTML
        return ``
      }

      // language = HTML
      return !editing ? view() : edit()
    },
    onMount: (contact) => {
      console.log('card was mounted')
    },
    preload: (contact) => {}
  })

  PrimaryInfoCards.registerCard('location', {
    title: ({ data }) => {
      return sprintf(__('Location', 'groundhogg'), data.first_name, data.last_name)
    },
    content: ({ meta }, { editing = false }) => {

      const {
        street_address_1 = '',
        street_address_2 = '',
        city = '',
        region = '',
        country = '',
        postal_zip = ''
      } = meta

      const edit = () => {
        // language=HTML
        return `
			<p><b>${__('Edit Address', 'groundhogg')}</b></p>
			<div class="gh-rows-and-columns">
				<div class="gh-row">
					<div class="gh-col">
						<label class="row-label">${__('Street Address 1', 'groundhogg')}</label>
						${input({
							id: 'street_address_1',
							value: street_address_1
						})}
					</div>
				</div>
				<div class="gh-row">
					<div class="gh-col">
						<label class="row-label">${__('Street Address 2', 'groundhogg')}</label>
						${input({
							id: 'street_address_2',
							value: street_address_2
						})}
					</div>
				</div>
				<div class="gh-row">
					<div class="gh-col">
						<label class="row-label">${__('City', 'groundhogg')}</label>
						${input({
							id: 'city',
							value: city
						})}
					</div>
				</div>
				<div class="gh-row">
					<div class="gh-col">
						<label class="row-label">${__('State', 'groundhogg')}</label>
						${input({
							id: 'region',
							value: region
						})}
					</div>
				</div>
				<div class="gh-row">
					<div class="gh-col">
						<label class="row-label">${__('Country', 'groundhogg')}</label>
						${select({
							id: 'country',
						})}
					</div>
				</div>
				<div class="gh-row">
					<div class="gh-col">
						<label class="row-label">${__('Zip Code', 'groundhogg')}</label>
						${input({
							id: 'postal_zip',
							value: postal_zip
						})}
					</div>
				</div>
			</div>
			<button class="gh-button small primary" id="save-location">${__('Save Changes')}</button>
        `
      }

      const view = () => {
        // language=HTML
        return `
			<p><b>${__('Address', 'groundhogg')}</b></p>
			<div title="${__('Line 1', 'groundhogg')}">${street_address_1}</div>
			<div title="${__('Line 2', 'groundhogg')}">${street_address_2}</div>
			<div title="${__('City', 'groundhogg')}">${city}</div>
			<div title="${__('State', 'groundhogg')}">${region}</div>
			<div title="${__('Zip Code', 'groundhogg')}">${postal_zip}</div>
			<button class="gh-button small secondary" id="edit-location">${__('Edit')}</button>
        `
      }

      // language=HTML
      return `
		  <div id="contact-location">
			  ${editing ? edit() : view()}
		  </div>`
    },
    onMount: (contact, { editing = false }, setState) => {

      if (editing) {
        $('#save-location').on('click', () => setState({ editing: false }))
      } else {
        $('#edit-location').on('click', () => setState({ editing: true }))
      }

    },
    preload: (contact) => {}
  })

  PrimaryInfoCards.registerCard('compliance-details', {
    title: ({ data }) => {
      return __('Compliance', 'groundhogg')
    },
    content: (contact, { editing = false }) => {

      const view = () => {

        // language=HTML
        return ``
      }

      const edit = () => {

        // language=HTML
        return ``
      }

      // language = HTML
      return !editing ? view() : edit()
    },
    onMount: (contact) => {
      console.log('card was mounted')
    },
    preload: (contact) => {}
  })

  SecondaryInfoCards.registerCard( 'user', {
    title: ({ data }) => {
      return __('WordPress User', 'groundhogg')
    },
    content: (contact, { editing = false }) => {

      const view = () => {

        // language=HTML
        return ``
      }

      const edit = () => {

        // language=HTML
        return ``
      }

      // language = HTML
      return !editing ? view() : edit()
    },
    onMount: (contact) => {
      console.log('card was mounted')
    },
    preload: (contact) => {}
  } )

  SecondaryInfoCards.registerCard( 'page_visits', {
    title: ({ data }) => {
      return __('Page Visits', 'groundhogg')
    },
    content: (contact, { editing = false }) => {

      const view = () => {

        // language=HTML
        return ``
      }

      const edit = () => {

        // language=HTML
        return ``
      }

      // language = HTML
      return !editing ? view() : edit()
    },
    onMount: (contact) => {
      console.log('card was mounted')
    },
    preload: (contact) => {}
  } )


  const ContactActions = {}

  const ContactRecord = {

    contact,

    /**
     * Render the contact record HTML
     *
     * @returns {string}
     */
    render () {

      const header = () => {
        return `
         <div class="gh-header space-between">
				  <div class="title-wrap">
					  <h1 class="breadcrumbs">
						  ${breadcrumbs([
          __('Contacts', 'groundhogg'),
          `<div class="space-between"><img width="30" height="30" alt="profile picture" src="${contact.data.gravatar}"/> ${contact.data.full_name}</div>`
        ])}
					  </h1>
				  </div>
				  <div class="actions">

				  </div>
			  </div>`
      }

      // language=HTML
      return `
		  <div id="contact-record">
			  <div class="space-between" id="contact-stuff">
				  <div class="gh-panel" id="general-details">
					  <img class="avatar" width="100" height="100" alt="profile picture"
					       src="${contact.data.gravatar}"/>
					  <div class="full-name">${contact.data.full_name}</div>
					  <div class="email-address">${contact.data.email}</div>
					  <div class="contact-actions">
						  <button class="gh-button secondary text icon">${icons.email}</button>
						  <button class="gh-button secondary text icon">${icons.phone}</button>
						  <button class="gh-button secondary text icon">${icons.note}</button>
						  <button class="gh-button secondary text icon">${icons.verticalDots}</button>
					  </div>
					  <div id="contact-primary-info-cards"></div>
				  </div>
				  <div class="gh-panel" id="main-stuff">

				  </div>
				  <div class="gh-panel" id="info-cards">

				  </div>
			  </div>
		  </div>`
    },

    /**
     * Add contact editor to the dom
     */
    mount () {
      $('#app').html(this.render())
      this.onMount()
    },

    /**
     * Add event listeners
     */
    onMount () {
      PrimaryInfoCards.mount('#contact-primary-info-cards', contact)
      SecondaryInfoCards.mount('#info-cards', contact)

      tabs('#main-stuff', {
        curTab: 'notes',
        tabs: [
          {
            id: 'notes',
            name: __('Notes', 'groundhogg'),
            content: () => {
              return `<div id="notes-here"></div>`
            },
            onMount: () => {
              Groundhogg.noteEditor('#notes-here', {
                object_id: contact.ID,
                object_type: 'contact',
                title: '',
              })
            }
          },
          {
            id: 'activity',
            name: __('Activity', 'groundhogg'),
            content: () => {
              return `<div id="activity-here"></div>`
            },
            onMount: () => {

            }
          }
        ]
      })
    },
  }

  $(() => {ContactRecord.mount()})

})(jQuery)