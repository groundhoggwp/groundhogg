(function ($) {

  const { uuid, loadingDots } = Groundhogg.element

  const InfoCard = (id = uuid(), {
    title = () => {},
    content = () => {},
    onMount = () => {},
    preload = () => {},
    isOpen = true,
    state = {},
    priority = 10
  }) => ({
    id,
    title,
    content,
    onMount,
    isOpen,
    priority,
    preload,
    state,

    render (args) {

      const {
        id,
        title,
        content,
        isOpen
      } = this

      //language=HTML
      return `
		  <div id="${id}" class="gh-info-card ${isOpen ? 'open' : 'closed'}">
			  <div class="gh-info-card-header">
				  <button class="gh-info-card-toggle"></button>
				  <div class="gh-info-card-title">
					  ${title(args, this.state)}
				  </div>
			  </div>
			  <div class="gh-info-card-content">
				  ${content(args, this.state)}
			  </div>
		  </div>`

    },
    mount ($el, args) {

      const setState = (state) => {
        this.state = {
          ...this.state,
          ...state
        }

        this.mount($el, args)
      }

      if ($el.find(`#${this.id}`).length) {
        $el.find(`#${this.id}`).replaceWith(this.render(args))
      } else {
        $el.append(this.render(args))
      }

      $(`#${this.id} .gh-info-card-header`).on('click', (e) => {
        if ($(`#${this.id}`).is('.closed')) {
          this.open($el, args)
        } else {
          this.close($el, args)
        }
      })

      this.onMount(args, this.state, setState)
    },

    open (...args) {
      this.isOpen = true

      this.mount(...args)
    },

    close (...args) {
      this.isOpen = false

      this.mount(...args)
    }
  })

  const InfoCardProvider = ({
    cards = []
  }) => ({

    cards,

    preload (args) {
      const promises = []

      this.cards.forEach(card => {

        let p = card.preload(args)

        if (!p) {
          return
        }

        // multiple promises
        if (Array.isArray(p) && p.length > 0) {
          promises.push(...p)

        }
        // Just the one promise
        else {
          promises.push(p)
        }
      })

      return Promise.all(promises)
    },

    async mount (el, args) {

      await this.preload(args)

      const $el = $(el)

      $el.addClass('gh-info-card-provider')
      $el.html('')

      this.cards.sort((a, b) => a.priority - b.priority).forEach(card => card.mount($el, args))

      console.log(this.cards)

      $el.sortable({
        handle: '.gh-info-card-header',
        placeholder: 'gh-info-card-provider',
        start: (e, ui) => {
          ui.placeholder.height(ui.item.height())
          ui.placeholder.width(ui.item.width())
        },
        update: (e, ui) => {

        },
      })
    },

    registerCard (id, card) {
      this.cards.push( InfoCard( id, card ) )
    },

  })

  Groundhogg.utils = {
    ...Groundhogg.utils,
    InfoCard,
    InfoCardProvider,
  }

})(jQuery)
