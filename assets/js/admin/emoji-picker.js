(function ($) {

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
		  <div id="emoji-picker-widget" class="emoji-picker-widget picker-widget">
			  <div class="search-bar">
				  <input autocomplete="off" autofocus type="search" name="search" id="emoji-picker-search"
				         placeholder="search...">
			  </div>
			  <div class="emoji-groups">
				  ${Templates.groups()}
			  </div>
			  <div class="emojis">
				  ${Templates.options()}
			  </div>
		  </div>`
    },

    groups () {
      return Picker.emojiGroups.map(group => Templates
        .emojiGroup(group, group.group === Picker.focusedGroup))
        .join('')
    },

    options () {
      // return '' // todo remove this

      const options = []
      let index = 1

      Picker.getEmojis().forEach(e => {
        options.push(Templates.emoji(e, index === Picker.focusedIndex))
        index++
      })

      return options.length > 0 ? options.join('') : 'No options'
    },

    emojiGroup ({ char, group }, focused) {
      // language=HTML
      return `
		  <a
			  title="${group}"
			  class="emoji-group ${focused ? 'focused' : ''}"
			  data-group="${group}">${char}
		  </a>`
    },

    emoji ({ codes, char, name, group, subgroup }, focused) {
      // language=HTML
      return `
		  <div
			  title="${name}"
			  class="emoji option ${focused ? 'focused' : ''}"
			  data-codes="${codes}"
			  data-group="${group}"
			  data-subgroup="${subgroup}"
			  data-insert="${char}">${char}
		  </div>`
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
    emojis: [],
    emojiGroups: [],
    focusedGroup: 'Smileys & Emotion',

    init () {

      const emojiMap = new Map()
      const groupMap = new Map()
      for (const item of Emojis) {
        if (!emojiMap.has(item.name)) {
          emojiMap.set(item.name, true)    // set any value to Map
          this.emojis.push(item)
        }
        if (!groupMap.has(item.group)) {
          groupMap.set(item.group, true)
          if (item.group !== 'Component') {
            this.emojiGroups.push({ group: item.group, char: item.char })
          }
        }
      }

      this.clickListener()
      this.scrollListener()
      this.resizeListener()
    },

    mount (e) {
      const wrapper = document.createElement('div')
      wrapper.innerHTML = Templates.widget()
      document.querySelector('body').appendChild(wrapper)
      this.picker = document.querySelector(`#emoji-picker-widget`)
      this.searchListener()
      this.positionPicker(e)
      this.backupInputTarget = $(e.target).siblings('input, textarea')[0]
      this.keyupListener()
    },

    getEmojis () {
      return this.emojis
        .filter(emoji => {

          if (Picker.search) {
            return emoji.name.match(regexp(Picker.search)) || emoji.subgroup.match(regexp(Picker.search))
          } else if (emoji.group !== Picker.focusedGroup) {
            return false
          }

          return true
        })
    },

    searchListener () {
      $('#emoji-picker-search').on('input change', function (e) {
        Picker.search = e.target.value
        Picker.focusedIndex = 0
        Picker.renderOptions()
      }).focus()
    },

    renderOptions () {
      const $options = $('.emoji-picker-widget .emojis')
      const $groups = $('.emoji-picker-widget .emoji-groups')
      $options.html(Templates.options())
      $groups.html(Templates.groups())

      const $focused = $('.emoji-picker-widget .emojis .focused')

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

        const clickedElIsStart = clickInsideElement(e, '.emoji-picker-start')
        const clickedInsidePicker = clickInsideElement(e, `.emoji-picker-widget`)
        const clickedElIsEmoji = clickInsideElement(e, `.emoji.option`)
        const clickedElIsEmojiGroup = clickInsideElement(e, `.emoji-group`)

        if (clickedElIsStart) {
          e.preventDefault()
          self.mount(e)
        } else if (clickedElIsEmoji) {
          e.preventDefault()
          self.codePicked(clickedElIsEmoji.dataset.insert, e)
        } else if (!clickedInsidePicker) {
          self.deMount()
        } else if (clickedElIsEmojiGroup) {
          self.focusedGroup = clickedElIsEmojiGroup.dataset.group
          self.renderOptions()
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

          if (this.focusedIndex === this.getEmojis().length) {
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
      window.addEventListener('keydown', keyupHandler)
    },

    removeKeyupListener () {
      window.removeEventListener('keydown', keyupHandler)
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
      console.log(code, e)

      if (! InsertAtCursor.inserting()) {
        InsertAtCursor.setActive( this.backupInputTarget )
      }

      let el = InsertAtCursor.insert(code)

      this.deMount()

      $(el).focus()
    }
  }

  $(() => {Picker.init()})

})(jQuery)
