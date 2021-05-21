(function ($, ghr) {

  /**
   * Helper for regex
   *
   * @param str
   * @returns {RegExp}
   */
  const regexp = (str) => {
    return new RegExp(str, 'i')
  }

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

  function getOffset (el) {
    const rect = el.getBoundingClientRect()
    return {
      x: rect.left,
      y: rect.top
    }
  }

  /**
   * Get's exact position of event.
   *
   * @param {Object} e The event passed in
   * @return {Object} Returns the x and y position
   */
  function getPosition (e) {

    const { clientX, clientY } = e

    return {
      x: clientX,
      y: clientY
    }
  }

  const Templates = {

    widget () {

      //language=HTML
      return `
		  <div id="replacements-picker-widget" class="replacements-picker-widget picker-widget">
			  <div class="search-bar">
				  <input autocomplete="off" autofocus type="search" name="search" id="replacement-picker-search"
				         placeholder="search...">
			  </div>
			  <div class="replacements">
				  ${Templates.options()}
			  </div>
		  </div>`
    },

    options () {

      const options = []

      let group
      let groupCodes
      let codes = Picker.getCodes()
      let index = 1

      for (group in ghr.groups) {
        if (ghr.groups.hasOwnProperty(group)) {

          groupCodes = codes.filter(code => group === code.group)

          if (groupCodes.length > 0) {
            options.push(`<div class="group" data-group="${group}">${ghr.groups[group]}</div>`)
            groupCodes.forEach(r => {
              options.push(Templates.replacement(r, index === Picker.focusedIndex))
              index++
            })
          }
        }
      }

      return options.length > 0 ? options.join('') : 'No options'
    },

    replacement ({ code, group, name, description, insert }, focused) {
      return `<div class="replacement ${focused ? 'focused' : false}" data-code="${code}" data-group="${group}" data-insert="${insert}">${name}</div>`
    },

  }

  const keyupHandler = (e) => {
    Picker.keyupHandler(e)
  }

  const Picker = {
    search: null,
    picker: null,
    targetInContext: false,
    pickerState: false,

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

    focusedIndex: 0,
    previousIndex: 0,
    backupInputTarget: null,

    init () {
      this.clickListener()
      this.scrollListener()
      this.resizeListener()
    },

    mount (e) {
      const wrapper = document.createElement('div')
      wrapper.innerHTML = Templates.widget()
      document.querySelector('body').appendChild(wrapper)
      this.picker = document.querySelector(`#replacements-picker-widget`)
      this.searchListener()
      this.positionPicker(e)
      this.backupInputTarget = $(e.target).siblings('input, textarea')[0]
      this.keyupListener()
    },

    getCodes () {
      return Object.values(ghr.codes)
        .filter(code => {
          if (Picker.search) {
            return code.name.match(regexp(Picker.search)) || code.code.match(regexp(Picker.search))
          }
          return code.group && true
        })
    },

    searchListener () {
      $('#replacement-picker-search').on('input change', function (e) {
        Picker.search = e.target.value
        Picker.focusedIndex = 0
        Picker.renderOptions()
      }).focus()
    },

    renderOptions () {
      const $options = $('.replacements-picker-widget .replacements')
      $options.html(Templates.options())

      const $focused = $('.replacements-picker-widget .replacements .focused')

      let offset
      // Moving down
      if (this.focusedIndex - this.previousIndex > 0) {
        offset = $focused.height() * ($focused.index() + 1)
        if (offset > $options.height())
          $options.scrollTop(offset - $options.height())
      }
      // Moving up
      else if (this.focusedIndex - this.previousIndex < 0) {
        offset = $focused.height() * ($focused.index())
        if (offset < $options.scrollTop())
          $options.scrollTop(offset)
      }
    },

    deMount () {
      Picker.search = null
      Picker.focusedIndex = 0
      $(this.picker).parent().remove()
      this.removeKeyupListener()
    },

    /**
     * Listens for contextpicker events.
     */
    clickListener () {

      var self = this

      document.addEventListener('click', function (e) {

        const clickedElIsStart = clickInsideElement(e, '.replacements-picker-start')
        const clickedInsidePicker = clickInsideElement(e, `.replacements-picker-widget`)
        const clickedElIsReplacement = clickInsideElement(e, `.replacement`)

        if (clickedElIsStart) {
          e.preventDefault()
          self.mount(e)
        } else if (clickedElIsReplacement) {
          e.preventDefault()
          self.codePicked(clickedElIsReplacement.dataset.insert, e)
        } else if (!clickedInsidePicker) {
          self.deMount()
        }
      })
    },

    /**
     * Listens for click events.
     */
    scrollListener () {
      const self = this
      document.addEventListener('scroll', function (e) {
        self.deMount()
      })
    },

    keyupHandler (e) {
      switch (e.key) {
        case 'Esc':
        case 'Escape':
          this.deMount()
          break
        case 'Down':
        case 'ArrowDown':

          if (this.focusedIndex === this.getCodes().length) {
            return
          }

          this.previousIndex = this.focusedIndex
          this.focusedIndex++
          this.renderOptions()

          // move focused element down
          break
        case 'Up':
        case 'ArrowUp':

          if (this.focusedIndex === 1) {
            return
          }

          this.previousIndex = this.focusedIndex
          this.focusedIndex--
          this.renderOptions()

          break
        case 'Enter':

          this.codePicked(this.getCodes()[this.focusedIndex]?.insert, e)
          break
      }
    },

    /**
     * Listens for keyup events.
     */
    keyupListener () {
      window.addEventListener('keydown', keyupHandler )
    },

    removeKeyupListener () {
      window.removeEventListener('keydown', keyupHandler )
    },

    /**
     * Window resize event listener
     */
    resizeListener () {
      var self = this
      window.onresize = function (e) {
        self.deMount()
      }
    },

    /**
     * Positions the picker properly.
     *
     * @param {Object} e The event
     */
    positionPicker (e) {
      // this.clickCoords = getPosition(e)
      this.clickCoords = getOffset(e.target)
      this.clickCoordsX = this.clickCoords.x
      this.clickCoordsY = this.clickCoords.y

      this.pickerWidth = this.picker.offsetWidth + 4
      this.pickerHeight = this.picker.offsetHeight + 4

      this.windowWidth = window.innerWidth
      this.windowHeight = window.innerHeight

      if ((this.windowWidth - this.clickCoordsX) < this.pickerWidth) {
        this.picker.style.left = this.windowWidth - this.pickerWidth + 'px'
      } else {
        this.picker.style.left = this.clickCoordsX + 'px'
      }

      if ((this.windowHeight - this.clickCoordsY) < this.pickerHeight) {
        this.picker.style.top = this.windowHeight - this.pickerHeight + 'px'
      } else {
        this.picker.style.top = this.clickCoordsY + 'px'
      }
    },

    /**
     * Dummy action function that logs an action when a picker item link is clicked
     *
     * @param {Boolean} code The link that was clicked
     * @param e
     */
    codePicked (code, e) {
      // this.togglePickerOff(e)
      console.log(code)

      if (! InsertAtCursor.inserting()) {
        InsertAtCursor.setActive( this.backupInputTarget )
      }

      let el = InsertAtCursor.insert(code)

      this.deMount()

      $(el).focus()
    }
  }

  $(() => {Picker.init()})

  ghr.picker = Picker

})(jQuery, Groundhogg.replacements, InsertAtCursor)