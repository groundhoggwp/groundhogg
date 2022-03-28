(function () {

  'use strict'

  if (!Element.prototype.matches) {
    Element.prototype.matches =
      Element.prototype.matchesSelector ||
      Element.prototype.mozMatchesSelector ||
      Element.prototype.msMatchesSelector ||
      Element.prototype.oMatchesSelector ||
      Element.prototype.webkitMatchesSelector ||
      function (s) {
        var matches = (this.document || this.ownerDocument).querySelectorAll(s),
          i = matches.length
        while (--i >= 0 && matches.item(i) !== this) {}
        return i > -1

      }
  }

  /**
   * Function to check if we clicked inside an element with a particular class
   * name.
   *
   * @param {Object} e The event
   * @param {String} selector The class name to check against
   * @return {Boolean}
   */
  function clickInsideElement (e, selector) {
    var el = e.srcElement || e.target

    if (el && el.matches(selector)) {
      return el
    } else {
      while (el = el.parentNode) {
        if (el.classList && el.matches(selector)) {
          return el
        }
      }
    }

    return false
  }

  /**
   * Get's exact position of event.
   *
   * @param {Object} e The event passed in
   * @return {Object} Returns the x and y position
   */
  function getPosition (e) {

    console.log(e)

    const { clientX, clientY } = e

    return {
      x: clientX,
      y: clientY
    }
  }

  const Templates = {
    menu (className, items) {

      //language=HTML
      return `
		  <div class="${className}-wrap">
			  <nav id="${className}" class="${className}">
				  <ul class="${className}__items">
					  ${items.map(item => Templates.menuItem(className, item)).join('')}
				  </ul>
			  </nav>
		  </div>`
    },
    menuItem (className, item) {
      //language=HTML
      return `
		  <li class="${className}__item">
			  <a class="${className}__link" data-key="${item.key}">${item.text}</a>
		  </li>`
    }
  }

  window.createContextMenu = ({
    menuClassName = 'context-menu',
    targetSelector,
    items,
    onSelect = function () {},
    onOpen = function () {},
    onClose = function () {},
  }) => ({

    menuClassName,
    targetSelector,
    items,
    onSelect,
    onOpen,
    onClose,

    /**
     * Variables.
     */
    contextMenuClassName: 'context-menu',
    contextMenuItemClassName: 'context-menu__item',
    contextMenuLinkClassName: 'context-menu__link',
    contextMenuActive: 'context-menu--active',

    targetInContext: false,

    clickCoords: 0,
    clickCoordsX: 0,
    clickCoordsY: 0,

    menuState: 0,
    menuWidth: 0,
    menuHeight: 0,
    menuPosition: 0,
    menuPositionX: 0,
    menuPositionY: 0,
    windowWidth: 0,
    windowHeight: 0,

    menu: null,
    menuItems: null,

    init () {

      this.contextMenuClassName = this.menuClassName
      this.contextMenuItemClassName = this.menuClassName + '__item'
      this.contextMenuLinkClassName = this.menuClassName + '__link'
      this.contextMenuActive = this.menuClassName + '--active'

      this.mount()
      this.contextListener()
      this.clickListener()
      this.scrollListener()
      this.keyupListener()
      this.resizeListener()
    },

    mount () {

      const wrapper = document.createElement('div')
      wrapper.innerHTML = Templates.menu(this.contextMenuClassName, this.items)
      document.querySelector('body').appendChild(wrapper)
      this.menu = document.querySelector(`#${this.contextMenuClassName}`)
    },

    /**
     * Listens for contextmenu events.
     */
    contextListener () {

      var self = this

      document.addEventListener('contextmenu', function (e) {

        self.targetInContext = clickInsideElement(e, self.targetSelector)

        if (self.targetInContext) {
          e.preventDefault()
          self.toggleMenuOn(e, self.targetInContext)
          self.positionMenu(e)
        } else {
          self.targetInContext = false
          self.toggleMenuOff(e)
        }
      })
    },

    /**
     * Listens for click events.
     */
    clickListener () {

      const self = this

      document.addEventListener('click', function (e) {
        const clickedElIsLink = clickInsideElement(e, `.${self.contextMenuLinkClassName}`)

        if (clickedElIsLink) {
          e.preventDefault()
          self.menuItemListener(clickedElIsLink, e)
        } else {
          var button = e.which || e.button
          if (button === 1) {
            self.toggleMenuOff(e)
          }
        }
      })
    },

    /**
     * Listens for click events.
     */
    scrollListener () {

      const self = this

      document.addEventListener('scroll', function (e) {
        self.toggleMenuOff(e)
      })
    },

    /**
     * Listens for keyup events.
     */
    keyupListener () {

      var self=this;
      window.onkeyup = function (e) {
        if (e.keyCode === 27) {
          self.toggleMenuOff(e)
        }
      }
    },

    /**
     * Window resize event listener
     */
    resizeListener () {
      var self=this;

      window.onresize = function (e) {
        self.toggleMenuOff(e)
      }
    },

    /**
     * Turns the custom context menu on.
     */
    toggleMenuOn (e, el) {
      if (this.menuState !== 1) {
        this.menuState = 1
        this.menu.classList.add(this.contextMenuActive)
      }

      this.onOpen(e, el)
    },

    /**
     * Turns the custom context menu off.
     */
    toggleMenuOff (e) {
      if (this.menuState !== 0) {
        this.menuState = 0
        this.menu.classList.remove(this.contextMenuActive)
      }

      this.onClose(e)
    },

    /**
     * Positions the menu properly.
     *
     * @param {Object} e The event
     */
    positionMenu (e) {
      this.clickCoords = getPosition(e)
      this.clickCoordsX = this.clickCoords.x
      this.clickCoordsY = this.clickCoords.y

      this.menuWidth = this.menu.offsetWidth + 4
      this.menuHeight = this.menu.offsetHeight + 4

      this.windowWidth = window.innerWidth
      this.windowHeight = window.innerHeight

      if ((this.windowWidth - this.clickCoordsX) < this.menuWidth) {
        this.menu.style.left = this.windowWidth - this.menuWidth + 'px'
      } else {
        this.menu.style.left = this.clickCoordsX + 'px'
      }

      if ((this.windowHeight - this.clickCoordsY) < this.menuHeight) {
        this.menu.style.top = this.windowHeight - this.menuHeight + 'px'
      } else {
        this.menu.style.top = this.clickCoordsY + 'px'
      }
    },

    /**
     * Dummy action function that logs an action when a menu item link is clicked
     *
     * @param {Boolean} link The link that was clicked
     * @param e
     */
    menuItemListener (link, e) {
      this.toggleMenuOff(e)
      console.log(link)
      this.onSelect(link.dataset.key)
    }

  })

})()
