(function ($) {

  const { uuid, icons } = Groundhogg.element

  const InfoCard = (id = uuid(), {
    title = () => {},
    content = () => {},
    onMount = () => {},
    preload = () => {},
    priority = 10
  }) => ({
    id,
    title,
    content,
    onMount,
    priority,
    preload,

    render (args) {

      const {
        id,
        title,
        content,
      } = this

      const {
        isOpen
      } = args

      //language=HTML
      return `
		  <div id="${id}" class="gh-info-card ${isOpen ? 'open' : 'closed'}">
			  <div class="gh-info-card-header">
				  <button class="gh-info-card-toggle"></button>
				  <div class="gh-info-card-title">
					  ${title(args, this.state)}
				  </div>
				  <div class="align-right-space-between actions">
              <div class="move">${icons.drag}</div>
				  </div>
			  </div>
			  <div class="gh-info-card-content">
				  ${content(args)}
			  </div>
		  </div>`

    },
  })

  const InfoCardProvider = ({
    cards = [],
    order = [],
    openState = {},
    onUpdate = ({order,openState}) => { console.log({order,openState}) },
  }) => ({

    cards,
    order,
    openState,

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
      $el.html( this.cards.sort((a, b) => a.priority - b.priority).map(card => card.render({
          isOpen: this.openState[card.id] || false,
          ...args
        })).join(''))

      $el.find('.gh-info-card-header').on('click', (e) => {
        let id = $(e.target).closest('.gh-info-card').attr('id')
        this.openState[id] = ! this.openState[id];
        this.updated()
        this.mount()
      })

      $el.sortable({
        handle: '.gh-info-card-header .move',
        placeholder: 'gh-info-card-provider',
        start: (e, ui) => {
          ui.placeholder.height(ui.item.height())
          ui.placeholder.width(ui.item.width())
        },
        update: (e, ui) => {
          this.updated()
        },
      })

      this.cards.forEach( card => {
        card.onMount(args)
      })
    },

    updated(){
      let cards = $('.gh-info-card')

      let order = cards.toArray().map( e => e.id )

      let openState = {}

      cards.each( (i,e) => openState[e.id] = e.classList.contains('open') )

      onUpdate({
        order,
        openState
      })
    },

    registerCard (id, card) {
      this.cards.push(InfoCard(id, card))
    },

  })

  Groundhogg.utils = {
    ...Groundhogg.utils,
    InfoCard,
    InfoCardProvider,
  }

})(jQuery)
