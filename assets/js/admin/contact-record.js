(($) => {

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
    InfoCard,
    InfoCardProvider
  } = Groundhogg.utils

  const { contact } = GroundhoggContact

  const PrimaryInfoCards = InfoCardProvider({
    cards: [
      InfoCard('basic-details', {
        title: ({ data }) => {
          return sprintf(__('About %1$s %2$s', 'groundhogg'), data.first_name, data.last_name)
        },
        content: (contact) => {
          // language = HTML
          return `<p>${contact.data.email}</p>`
        },
        onMount: (contact) => {

          console.log('card was mounted')

        },
        preload: (contact) => {}
      }),
      InfoCard('location', {
        title: ({ data }) => {
          return sprintf(__('Location', 'groundhogg'), data.first_name, data.last_name)
        },
        content: ({ meta }) => {

          const {
            street_address_1,
            street_address_2,
            postal_zip,
            country,
            region,
            ip_address,
            time_zone,
          } = meta

          // language=HTML
          return `
			  <div class="location">
				  <p><label>${__('Line 1', 'groundhogg')} ${input({ name: 'street_address_1' })}</label></p>
				  <p><label>${__('Line 2', 'groundhogg')} ${input({ name: 'street_address_2' })}</label></p>
				  <p><label>${__('City', 'groundhogg')} ${input({ name: 'city' })}</label></p>
				  <p><label>${__('State', 'groundhogg')} ${input({ name: 'region' })}</label></p>
				  <p><label>${__('Country', 'groundhogg')} ${input({ name: 'country' })}</label></p>
				  <p><label>${__('Zip Code', 'groundhogg')} ${input({ name: 'postal_zip' })}</label></p>
			  </div>`
        },
        onMount: (contact) => {
          console.log('card was mounted')
        },
        preload: (contact) => {}
      })
    ]
  })

  const ContactActions = {}

  const ContactRecord = {

    contact,

    /**
     * Render the contact record HTML
     *
     * @returns {string}
     */
    render () {
      // language=HTML
      return `
		  <div id="contact-record" class="three-column-layout fixed-full-height">
			  <div class="column-left column">
				  <div class="image-to-left">
					  <img id="gravatar" class="image-on-left"
					       alt="${__('Profile Picture', 'groundhogg')}"
					       src="${this.contact.data.gravatar}"/>
					  <div class="right-of-image">
						  <h1 id="contact-name">${this.contact.data.first_name} ${this.contact.data.last_name}</h1>
						  <p id="email-address">${this.contact.data.email}</p>
					  </div>
				  </div>
				  <div id="contact-actions">
					  ${el('button', {
						  className: 'gh-button icon tertiary text'
					  }, icons.email)}
					  ${el('button', {
						  className: 'gh-button icon tertiary text'
					  }, icons.tag)}
					  ${el('button', {
						  className: 'gh-button icon tertiary text'
					  }, icons.phone)}
					  ${el('button', {
						  className: 'gh-button icon tertiary text'
					  }, icons.mobile)}
				  </div>
				  <div id="contact-primary-info-cards"></div>
			  </div>
			  <div class="column-middle column">
				  <div class="gh-panel">
					  <div class="inside"></div>
				  </div>
			  </div>
			  <div class="column-right column">
				  <div class="gh-panel">
					  <div class="inside"></div>

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
    },
  }

  $(() => {ContactRecord.mount()})

})(jQuery)