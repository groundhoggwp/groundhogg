(function ($) {

  function andList (array, text = 'and') {
    if (array.length === 1) {
      return array[0]
    }
    return `${array.slice(0, -1).join(', ')} ${text} ${array[array.length - 1]}`
  }

  function orList (array) {
    return andList(array, 'or')
  }

  const createSlotFillProvider = () => ({

    fills: [],
    _slotsMounted: [],
    _slotsDemounted: [],

    /**
     * Render a slot name
     *
     * @param slotName
     * @param args
     * @returns {string}
     */
    slot (slotName, ...args) {
      this._slotsMounted.push({
        name: slotName,
        args: args
      })
      return this.fills.filter(fill => fill.slot === slotName).map(fill => fill.render(...args)).join('')
    },

    /**
     * Call this after any slots have been added to the DOM
     */
    slotsMounted () {
      let slot

      while (this._slotsMounted.length > 0) {
        // Get the next mounted slot
        slot = this._slotsMounted.pop()
        this.fills.filter(fill => fill.slot === slot.name).forEach(fill => {
          fill.onMount(...slot.args)
        })

        // After a slot has been mounted, remember it has been so it can be demounted later
        this._slotsDemounted.push(slot)
      }
    },

    /**
     * Any callbacks to demount a slot
     * Call before any slots are removed from the DOM
     */
    slotsDemounted () {
      let slot

      while (this._slotsDemounted.length > 0) {
        // get the next demounted slot
        slot = this._slotsDemounted.pop()
        this.fills.filter(fill => fill.slot === slot.name).forEach(fill => {
          fill.onDemount(...slot.args)
        })
      }
    },

    /**
     * Register a fill for a slot
     *
     * @param slot
     * @param component
     */
    fill (slot, component) {
      this.fills.push({
        slot,
        ...{
          render () {},
          onMount () {},
          onDemount () {},
          ...component
        }
      })
    }
  })

  function isString (string) {
    return typeof string === 'string'
  }

  /**
   * If it's not a string just return the value
   *
   * @param string
   * @returns {*}
   */
  const specialChars = (string) => {
    if (!isString(string)) {
      return string
    }

    return string.replace(/&/g, '&amp;').replace(/>/g, '&gt;').replace(/</g, '&lt;').replace(/"/g, '&quot;')
  }

  const kebabize = str => {
    return str.split('').map((letter, idx) => {
      return letter.toUpperCase() === letter
        ? `${idx !== 0 ? '-' : ''}${letter.toLowerCase()}`
        : letter
    }).join('')
  }

  const objectToStyle = (object) => {
    const props = []

    for (const prop in object) {
      if (object.hasOwnProperty(prop)) {
        props.push(`${kebabize(prop)}:${specialChars(object[prop])}`)
      }
    }

    return props.join(';')
  }

  /**
   * Convert an object of HTML props into a string
   *
   * @param object
   * @returns {string}
   */
  const objectToProps = (object) => {
    const props = []

    for (const prop in object) {
      if (object.hasOwnProperty(prop)) {

        switch (prop) {
          case 'className':
            props.push(`class="${specialChars(object[prop])}"`)
            break
          case 'style':
            props.push(`style="${specialChars(objectToStyle(object[prop]))}"`)
            break
          default:
            props.push(`${kebabize(prop)}="${specialChars(object[prop])}"`)
            break
        }
      }
    }

    return props.join(' ')
  }

  const Elements = {
    input (props) {
      props = {
        type: 'text',
        className: 'input',
        ...props
      }

      return `<input ${objectToProps(props)}/>`
    },
    select (props, options, selected) {
      return `<select ${objectToProps(props)}>${createOptions(options, selected)}</select>`
    },
    option: function (value, text, selected) {
      if (typeof value === 'object') {
        value = value.value
        text = value.text
      }

      //language=HTML
      return `
		  <option value="${specialChars(value)}" ${selected ? 'selected' : ''}>${text}</option>`
    },
    mappableFields (props, selected) {
      return Elements.select(props, Groundhogg.fields.mappable, selected)
    },
    inputWithReplacementsAndEmojis ({
      type = 'text',
      name,
      id,
      value,
      className,
      placeholder = ''
    }, replacements = true, emojis = true) {
      const classList = [
        replacements && 'input-with-replacements',
        emojis && 'input-with-emojis'
      ]
      //language=HTML
      return `
		  <div class="input-wrap ${classList.filter(c => c).join()}">
			  <input type="${type}" id="${id}" name="${name}" value="${specialChars(value) || ''}" class="${className}"
			         placeholder="${specialChars(placeholder)}">
			  ${emojis ? `<button class="emoji-picker-start" title="insert emoji"><span class="dashicons dashicons-smiley"></span>
			  </button>` : ''}
			  ${replacements ? `<button class="replacements-picker-start" title="insert replacement"><span
				  class="dashicons dashicons-admin-users"></span></button>` : ''}
		  </div>`
    },
    inputWithReplacements: function (atts) {
      return Elements.inputWithReplacementsAndEmojis(atts, true, false)
    },
    inputWithEmojis: function (atts) {
      return Elements.inputWithReplacementsAndEmojis(atts, false, true)
    },
    textAreaWithReplacementsAndEmojis: function ({ name, id, value }) {

    },
    textAreaWithReplacements: function ({ name, id, value }) {

    },
    textAreaWithEmojis: function ({ name, id, value }) {

    }
  }

  /**
   * Create a list of options
   *
   * @param options
   * @param selected
   * @returns {string}
   */
  const createOptions = (options, selected) => {

    const optionsString = []

    // Options is an array format
    if (Array.isArray(options)) {
      options.forEach(option => {
        optionsString.push(Elements.option(
          option, option,
          Array.isArray(selected)
            ? selected.indexOf(option) !== -1
            : option === selected))
      })
    }
    // Assume object
    else {
      for (const option in options) {
        if (options.hasOwnProperty(option)) {
          optionsString.push(Elements.option(
            option, options[option],
            Array.isArray(selected)
              ? selected.indexOf(option) !== -1
              : option === selected))
        }
      }
    }

    return optionsString.join('')
  }

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
        if (typeof el.matches !== 'undefined' && el.matches(selector)) {
          return el
        }
      }
    }

    return false
  }

  const searchOptionsWidget = ({
    selector,
    options = [],
    groups = {},
    filterOption = (option, search) => option.match(regexp(search)),
    renderOption = (option) => option,
    noOptions = `No options...`,
    onSelect = (option) => console.log(option),
    onClose = () => {},
    onOpen = () => {}
  }) => ({
    selector,
    options,
    filterOption,
    renderOption,
    onClose,
    onSelect,
    groups,

    search: '',
    render () {
      //language=HTML
      return `
		  <div class="search-options-widget-wrap">
			  <div class="search-options-widget">
				  <div class="header">
					  ${Elements.input({
						  name: 'search',
						  type: 'search',
						  className: 'search-for-options',
						  autocomplete: 'off',
						  placeholder: 'Search...'
					  })}
					  <button class="close">
						  <span class="dashicons dashicons-no-alt"></span>
					  </button>
				  </div>
				  <div class="search-options ${this.hasGroups() ? 'has-groups' : 'no-groups'}"></div>
			  </div>
		  </div>`
    },
    getOptions () {
      return options.filter((option, i) => {
        if (this.search) {
          return filterOption(option, this.search)
        }

        return true
      })
    },
    hasGroups () {
      return Object.keys(groups).length > 0
    },
    renderSearchOptions () {

      const searchOptions = []

      if (Object.keys(groups).length > 0) {

        Object.keys(groups).forEach((group, g) => {
          const options = []

          this.getOptions().filter(option => option.group === group).forEach((option, o) => {
            options.push(`<div class="option" data-option="${o}" data-group="${group}">${renderOption(option)}</div>`)
          })

          if (options.length > 0) {
            searchOptions.push(`<div class="option-group" data-group="${group}">${groups[group]}</div>`, ...options)
          }

        })

      } else {
        this.getOptions().forEach((option, o) => {
          searchOptions.push(`<div class="option" data-option="${o}">${renderOption(option)}</div>`)
        })
      }

      return searchOptions.length ? searchOptions.join('') : `<div class="no-options">${noOptions}</div>`
    },
    mountOptions () {

      var self = this;
      $(`${selector} .search-options`).html(this.renderSearchOptions())
      $(`${selector} .option`).on('click', function (e) {
        const optionId = parseInt($(this).data('option'))
        const groupId = parseInt($(this).data('group'))
        if ( ! self.hasGroups() ){
          onSelect( self.getOptions()[optionId] )
          onClose()
        } else {
          Object.keys(groups).forEach((group, g) => {
            this.getOptions().filter(option => option.group === group).forEach((option, o) => {
              if ( g === groupId && o === optionId ){
                onSelect( option )
                onClose()
                return
              }
            })
          })
        }
      })
    },
    mount () {
      var self = this

      $(selector).html(self.render())
      this.mountOptions()

      $(`${selector} input.search-for-options`).on('change input', function (e) {
        self.search = $(this).val()
        self.mountOptions()
      })

      $(`${selector} button.close`).on('click', function (e) {
        onClose()
      })

      onOpen()
    }
  })

  Groundhogg.element = {
    ...Elements,
    specialChars,
    andList,
    orList,
    kebabize,
    regexp,
    objectToProps,
    objectToStyle,
    createOptions,
    createSlotFillProvider,
    clickInsideElement,
    searchOptionsWidget
  }

})(jQuery)