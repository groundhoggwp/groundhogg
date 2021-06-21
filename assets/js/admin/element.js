(function ($) {

  Object.filter = function (obj, predicate) {
    let result = {}, key

    for (key in obj) {
      if (obj.hasOwnProperty(key) && predicate(key, obj[key])) {
        result[key] = obj[key]
      }
    }

    return result
  }

  /**
   * Make a copy of the object
   *
   * @param object
   * @param initial
   * @returns {*}
   */
  function copyObject (object, initial) {
    initial = initial || {}
    return $.extend(true, initial, object)
  }

  /**
   *
   * @param array
   * @param text
   * @returns {string|*}
   */
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

  function uuid () { // Public Domain/MIT
    return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c =>
      (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
    )
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
    toggle ({ id, name, className, value = '1', onLabel = 'on', offLabel = 'off', checked }) {
      //language=HTML
      return `
		  <label class="gh-switch ${className}">
			  <input id="${id}" name="${name}" value="${value}" type="checkbox" ${checked ? 'checked' : ''}>
			  <span class="slider round"></span>
			  <span class="on">${onLabel}</span>
			  <span class="off">${offLabel}</span>
		  </label>`
    },
    input (props) {
      props = {
        type: 'text',
        className: 'input regular-text',
        ...props
      }

      return `<input ${objectToProps(props)}/>`
    },
    select (props, options, selected) {
      return `<select ${objectToProps(props)}>${createOptions(options, selected)}</select>`
    },
    option: function (value, text, selected) {
      //language=HTML
      return `
		  <option value="${specialChars(value)}" ${selected ? 'selected' : ''}>${text}</option>`
    },
    mappableFields (props, selected) {
      return Elements.select(props, Groundhogg.fields.mappable, selected)
    },
    textarea (props) {
      return `<textarea ${objectToProps(Object.filter(props, key => key !== 'value'))}>${specialChars(props.value)}</textarea>`
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
		  <div class="input-wrap ${classList.filter(c => c).join(' ')}">
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
    textAreaWithReplacementsAndEmojis: function ({
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
		  <div class="input-wrap ${classList.filter(c => c).join(' ')}" xmlns="http://www.w3.org/1999/html">
			  <textarea id="${id}" name="${name}" class="${className}"
	              placeholder="${specialChars(placeholder)}">${specialChars(value) || ''}</textarea>
			  <div class="buttons">
				  ${replacements ? `<button class="replacements-picker-start" title="insert replacement"><span
				  class="dashicons dashicons-admin-users"></span></button>` : ''}
				  ${emojis ? `<button class="emoji-picker-start" title="insert emoji"><span class="dashicons dashicons-smiley"></span>
			  </button>` : ''}
			  </div>
		  </div>`
    },
    textAreaWithReplacements: function (atts) {
      return Elements.textAreaWithReplacementsAndEmojis(atts, true, false)
    },
    textAreaWithEmojis: function (atts) {
      return Elements.textAreaWithReplacementsAndEmojis(atts, false, true)
    }
  }

  const tinymceElement = (editor_id, config = {}, onChange = (v) => {
    console.log(v)
  }) => {
    wp.editor.initialize(
      editor_id,
      {
        tinymce: true,
        quicktags: true,
        ...config
      }
    )

    tinymce.get(editor_id).on('keyup', function (e) {
      onChange(tinyMCE.activeEditor.getContent({ format: 'raw' }), tinyMCE.activeEditor.getContent({ format: 'raw' }))
    })
  }

  const loadingModal = () => {

    let stop = () => {}

    return modal({
      content: '<h1>Loading</h1>',
      canClose: false,
      onOpen: () => {
        stop = loadingDots( '.gh-modal h1' ).stop
      },
      onClose: () => {
        stop()
      }
    })
  }

  /**
   *
   * @param alert
   * @param confirmText
   * @param closeText
   * @param onConfirm
   * @param onClose
   */
  const confirmationModal = ({
    alert = '',
    confirmText = 'Confirm',
    closeText = 'Cancel',
    onConfirm = () => {},
    onClose = () => {},
  }) => {

    //language=html
    const content = `
		<button type="button" class="dashicon-button gh-modal-button-close-top gh-modal-button-close">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
		<div class="gh-modal-dialog-alert">
			${alert}
		</div>
		<div class="gh-modal-confirmation-buttons gh-button-group">
			<button type="button" class="gh-button danger gh-modal-button-close">${closeText}</button>
			<button type="button" class="gh-button primary gh-modal-button-confirm">${confirmText}</button>
		</div>`

    const { close, $modal } = modal({
      content,
      onClose
    })

    const confirm = () => {
      onConfirm()
      close()
    }

    const handleConfirm = () => {
      confirm()
    }

    $('.gh-modal-button-confirm').on('click', handleConfirm)

    return {
      close,
      confirm,
      $modal
    }
  }

  /**
   * Custom modal appended to the body.
   *
   * options:
   * (bool) isConfirmation Shows confirmation button if true.
   * (bool) closeOnOverlayClick Close the modal when the background overlay is clicked.
   * (bool) showCloseButton Show the close button at the top of the modal.
   * (string) messageHtml Html to be showed at the top of the modal.
   * (function) confirmCallBack Called when "confirm" button is clicked.
   *
   * @param (object) options Config options to overwrite defaults.
   */
  const modal = ({
    content = '',
    onClose = () => {},
    canClose = true,
    onOpen = () => {}
  }) => {

    //language=html
    const html = `
		<div class="gh-modal">
			<div class="gh-modal-overlay"></div>
			<div class="gh-modal-dialog">
				${canClose ? `	<button type="button" class="dashicon-button gh-modal-button-close-top gh-modal-button-close">
					<span class="dashicons dashicons-no-alt"></span>
				</button>` : ''}
				<div class="gh-modal-dialog-content">
					${content}
				</div>
			</div>
		</div>`

    const $modal = $(html)

    const close = () => {
      $modal.remove()
      onClose()
    }

    const handleClose = () => {
      close()
    }

    $('body').append($modal)

    onOpen()

    if (canClose) {
      $('.gh-modal-overlay, .gh-modal-button-close').on('click', handleClose)
    }

    return {
      $modal,
      close,
    }
  }

  const loadingDots = (selector) => {

    const $el = $('<span class="loading-dots"></span>')
    $(selector).append( $el )

    const stop = () => {
      clearInterval(interval)
    }

    const interval = setInterval(() => {
      if ($el.html().length >= 3) {
        $el.html('.')
      } else {
        $el.html($el.html() + '.')
      }
    }, 500)

    return {
      stop
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
          option.value, option.text,
          Array.isArray(selected)
            ? selected.indexOf(option.value) !== -1
            : option.value === selected))
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
    focusedOptionId: 0,
    previousFocusedOptionId: false,
    focusedOption: false,
    render () {
      //language=HTML
      return `
		  <div class="search-options-widget-wrap">
			  <div class="search-options-widget" tabindex="0">
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

      let focusedIndex = 0

      var self = this

      const optionDiv = (option, group, id, index) => {
        return `<div class="option ${index === this.focusedOptionId ? 'focused' : ''}" data-option="${id}" data-group="${group}">${renderOption(option)}</div>`
      }

      if (Object.keys(groups).length > 0) {

        Object.keys(groups).forEach((group, g) => {
          const options = []

          this.getOptions().filter(option => option.group === group).forEach((option, o) => {
            options.push(optionDiv(option, group, o, focusedIndex))
            focusedIndex++
          })

          if (options.length > 0) {
            searchOptions.push(`<div class="option-group" data-group="${group}">${groups[group]}</div>`, ...options)
          }

        })

      } else {
        this.getOptions().forEach((option, o) => {
          searchOptions.push(optionDiv(option, null, o, focusedIndex))
          focusedIndex++
        })
      }

      return searchOptions.length ? searchOptions.join('') : `<div class="no-options">${noOptions}</div>`
    },
    selectOption (optionId, groupId) {
      if (!this.hasGroups()) {
        onSelect(this.getOptions()[optionId])
        onClose()
      } else {
        Object.keys(groups).forEach((group, g) => {
          this.getOptions().filter(option => option.group == group).forEach((option, o) => {
            if (group == groupId && o == optionId) {
              onSelect(option)
              onClose()
              return
            }
          })
        })
      }
    },
    mountOptions () {

      var self = this
      const $options = $(`${selector} .search-options`)

      $options.html(this.renderSearchOptions())
      $(`${selector} .option`).on('click', function (e) {
        const optionId = $(this).data('option')
        const groupId = $(this).data('group')

        self.selectOption(optionId, groupId)
      })

      const $focused = $(`${selector} .option.focused`)

      let offset

      // Moving down
      if (this.focusedOptionId > this.previousFocusedOptionId) {
        offset = $focused.height() * ($focused.index() + 1)
        if (offset > $options.height())
          $options.scrollTop(offset - $options.height())
      }
      // Moving up
      else if (this.focusedOptionId < this.previousFocusedOptionId) {
        offset = $focused.height() * ($focused.index())
        if (offset < $options.scrollTop())
          $options.scrollTop(offset)
      }
    },
    mount () {
      var self = this

      $(selector).html(self.render())
      this.mountOptions()

      const el = document.querySelector('.search-options-widget')

      if ( ! el ){
        return;
      }

      const handleClose = () => {
        onClose()
      }

      $(`${selector} input.search-for-options`).on('change input', function (e) {
        self.search = $(this).val()
        self.focusedOptionId = false
        self.previousFocusedOptionId = false
        self.mountOptions()
      }).focus()

      // if current Y position (relative to window) + height > height of window
      if ((el.getBoundingClientRect().y + $(el).height()) > window.innerHeight) {
        el.classList.add('mount-from-bottom')
      }

      $(`${selector} button.close`).on('click', function (e) {
        handleClose()
      })

      const handleKeyDown = (e) => {

        const { type, key, keyCode } = e

        switch (key) {
          case 'Esc':
          case 'Escape':
            handleClose()
            break
          case 'Down':
          case 'ArrowDown':
            e.preventDefault()

            if (this.focusedOptionId === this.getOptions().length - 1) {
              return
            }

            this.previousFocusedOptionId = this.focusedOptionId
            this.focusedOptionId++
            this.mountOptions()

            break
          case 'Up':
          case 'ArrowUp':
            e.preventDefault()

            if (this.focusedOptionId === 0) {
              return
            }

            this.previousFocusedOptionId = this.focusedOptionId
            this.focusedOptionId--
            this.mountOptions()

            break
          case 'Enter':
            e.preventDefault()

            const $focused = $(`${selector} .option.focused`)
            this.selectOption($focused.data('option'), $focused.data('group'))

            break
        }
      }

      $('.search-options-widget').on('keydown', handleKeyDown)

      onOpen()
    }
  })

  /**
   * Global Functions
   */
  const flattenObject = (obj, parent_key = '') => {
    if (typeof obj !== 'object') {
      return {}
    }

    const flattened = {}

    let key_prefix = parent_key ? parent_key + '.' : ''

    for (const key in obj) {
      if (obj.hasOwnProperty(key)) {
        let value = obj[key]

        if (typeof value !== 'object') {
          flattened[key_prefix + key] = value
        } else {
          Object.assign(flattened, flattenObject(value, key_prefix + key))
        }
      }
    }

    return flattened
  }

  /**
   * Whether 2 objects are equal
   *
   * @param a
   * @param b
   * @returns {boolean}
   */
  function objectEquals (a, b) {
    return JSON.stringify(a) === JSON.stringify(b)
  }

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
    searchOptionsWidget,
    tinymceElement,
    loadingModal,
    confirmationModal,
    uuid,
    modal,
    copyObject,
    loadingDots,
    flattenObject,
    objectEquals
  }

})(jQuery)