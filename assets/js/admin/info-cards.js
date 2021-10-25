(function ($) {

  const { uuid, loadingDots } = Groundhogg.element

  const InfoCard = (id = uuid(), {
    title = () => {},
    content = () => {},
    onMount = () => {},
    preload = () => {},
    isOpen = true,
    priority = 10
  }) => ({
    id,
    title,
    content,
    onMount,
    isOpen,
    priority,
    preload,

    render (item) {

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
					  ${title(item)}
				  </div>
			  </div>
			  <div class="gh-info-card-content">
				  ${content(item)}
			  </div>
		  </div>`

    },
    mount ($el, item) {

      if ($el.find(`#${this.id}`).length) {
        $el.find(`#${this.id}`).replaceWith(this.render(item))
      } else {
        $el.append(this.render(item))
      }

      $(`#${this.id} .gh-info-card-header`).on('click', (e) => {
        if ($(`#${this.id}`).is('.closed')) {
          this.open($el, item)
        } else {
          this.close($el, item)
        }
      })

      this.onMount()
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

    preload (item) {
      const promises = []

      this.cards.forEach(card => {

        let p = card.preload(item)

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

    async mount (el, item) {

      await this.preload(item)

      const $el = $(el)

      $el.addClass('gh-info-card-provider')
      $el.html('')

      this.cards.sort((a, b) => a.priority - b.priority).forEach(card => card.mount($el, item))

      console.log(this.cards)

      $el.sortable({
        placeholder: 'gh-info-card-provider',
        start: (e, ui) => {
          ui.placeholder.height(ui.item.height())
          ui.placeholder.width(ui.item.width())
        },
        update: (e, ui) => {

        },
      }).disableSelection()
    },

    registerCard (card) {
      this.cards.push(card)
    },

  })

  Groundhogg.utils = {
    ...Groundhogg.utils,
    InfoCard,
    InfoCardProvider,
  }

})(jQuery)
