(function ($) {

  var $doc = $(document)

  const { sprintf, __, _x, _n } = wp.i18n

  function insertAtCursor (myField, myValue) {
    //IE support
    if (document.selection) {
      myField.focus()
      var sel = document.selection.createRange()
      sel.text = myValue
    }
    //MOZILLA and others
    else if (myField.selectionStart || myField.selectionStart == '0') {
      var startPos = myField.selectionStart
      var endPos = myField.selectionEnd
      myField.value = myField.value.substring(0, startPos)
        + myValue
        + myField.value.substring(endPos, myField.value.length)
    } else {

      myField.value += myValue
    }

    $(myField).trigger('change')
  }

  const Insert = {

    active: null,
    text: '',
    to_mce: false,

    init: function () {

      var self = this

      $doc.on('ghClearInsertTarget', function () {
        self.to_mce = false
        self.active = false
      })

      // GO TO MCE
      $doc.on('to_mce', function () {
        self.to_mce = true
        $doc.trigger('ghInsertTargetChanged')
      })

      // NOPE, GO TO TEXT
      $doc.on('focus', 'input:not(.no-insert), textarea:not(.no-insert)', function () {
        self.active = this
        self.to_mce = false
        $doc.trigger('ghInsertTargetChanged')
      })

    },

    setActive (el) {
      this.active = el
    },

    inserting () {
      return this.active || this.to_mce
    },

    insert: function (text) {

      console.log('insert', { text: text })

      // CHECK TINY MCE
      if (typeof tinymce != 'undefined' && tinymce.activeEditor != null && this.to_mce) {
        tinymce.activeEditor.execCommand('mceInsertContent', false, text)
        // INSERT REGULAR TEXT INPUT.
      }

      if (this.active != null && !this.to_mce) {

        insertAtCursor(this.active, text)

        return this.active
      }
    }

  }

  $(() => {
    Insert.init()
  })

  window.InsertAtCursor = Insert

  Object.filter = function (obj, predicate) {
    let result = {}, key

    for (key in obj) {
      if (obj.hasOwnProperty(key) && predicate(key, obj[key])) {
        result[key] = obj[key]
      }
    }

    return result
  }

  const tooltip = (selector, {
    content = '',
    position = 'bottom'
  }) => {

    const $el = $(selector)

    // language=HTML
    const $tip = $(`
		<div class="gh-tooltip ${position}">
			${content}
		</div>`)

    $el.addClass('gh-has-tooltip')
    $el.append($tip)
  }

  const stepNav = ({
    labels,
    currentStep,
  }) => {

    const stepNum = (label, num) => {
      // language=HTML
      return `
		  <div data-step="${num}" class="gh-step-nav-step-num ${num === currentStep ? 'current' : ''}">
			  <div class="gh-step-nav-step-num-circle">
				  ${num + 1}
			  </div>
			  <div class="gh-step-nav-step-num-label">
				  ${label}
			  </div>
		  </div>`
    }

    // language=HTML
    return `
		<div class="gh-step-nav">
			${labels.map((l, i) => stepNum(l, i)).join(`<hr class="gh-step-nav-join"/>`)}
		</div>`
  }

  const stepNavHandler = (selector, {
    currentStep = 0,
    steps = [],
    onStepChange = (step) => { console.log(step)},
    showNav = true,
    labels,
  }) => {

    this.currStep = currentStep

    const mountStep = () => {

      const step = steps[this.currStep]

      console.log({
        step: this.currStep
      })

      //language=HTML
      const html = `
		  <div class="step-nav-handler">
			  ${showNav ? stepNav({
				  labels,
				  currentStep: this.currStep
			  }) : ''}
			  <div class="step-nav-handler-step">
				  ${step()}
			  </div>
		  </div>`

      $el.html(html)

      if (showNav) {
        $('.gh-step-nav-step-num').on('click', ({ currentTarget }) => {
          setStep(parseInt(currentTarget.dataset.step))
        })
      }

      onStepChange(this.currStep, {
        nextStep,
        lastStep,
        setStep
      })
    }

    const $el = $(selector)

    // move to the last step
    const lastStep = () => {

      if (this.currStep === 0) {
        return
      }

      this.currStep--

      mountStep()
    }

    // move to the next step
    const nextStep = () => {

      if (this.currStep === steps.length - 1) {
        return
      }

      this.currStep++

      mountStep()
    }

    const setStep = (step) => {
      if (step > steps.length - 1 || step < 0) {
        return
      }

      this.currStep = step

      mountStep()
    }

    mountStep()

    return {
      $el,
      nextStep,
      lastStep,
      setStep
    }
  }

  const breadcrumbs = (parts) => {
    return parts.map((p, i) => i < parts.length - 1 ? `<span class="part">${p}</span>` : `<span class="base">${p}</span>`).join(`<span class="sep">/</span>`)
  }

  function improveTinyMCE () {

    if (typeof this.flag !== 'undefined') {
      return
    }

    $doc.on('tinymce-editor-setup', function (event, editor) {
      editor.settings.toolbar1 =
        'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,spellchecker,wp_adv,dfw,groundhoggreplacementbtn,groundhoggemojibtn'
      editor.settings.toolbar2 =
        'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help'
      editor.settings.height = 200
      editor.on('click', function (ed, e) {
        $doc.trigger('to_mce')
      })
    })

    this.flag = 'improved'
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

  function ordinal_suffix_of (i) {

    var j = i % 10,
      k = i % 100
    if (j == 1 && k != 11) {
      return i + 'st'
    }
    if (j == 2 && k != 12) {
      return i + 'nd'
    }
    if (j == 3 && k != 13) {
      return i + 'rd'
    }
    return i + 'th'
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
    return sprintf(_x('%s and %s', 'and preceding the last item in a list', 'groundhogg'), array.slice(0, -1).join(', '), array[array.length - 1])
  }

  function orList (array) {
    if (array.length === 1) {
      return array[0]
    }
    return sprintf(_x('%s or %s', 'or preceding the last item in a list', 'groundhogg'), array.slice(0, -1).join(', '), array[array.length - 1])
  }

  const progressBar = (selector) => {

    // language=HTML
    const html = `
		<div class="gh-progress-bar">
			<div class="gh-progress-bar-fill">
			</div>
		</div>`

    const $bar = $(html)
    const $fill = $bar.find('.gh-progress-bar-fill')

    const $el = $(selector)
    $el.html($bar)

    const setProgress = (progress) => {

      if (progress <= 1) {
        progress = progress * 100
      }

      if (progress === 100) {
        $bar.addClass('complete')
      } else {
        $bar.removeClass('complete')
      }

      $fill.css({
        width: progress + '%'
      }).html(`<span class="fill-amount">${Math.ceil(progress)}%</span>`)
    }

    return {
      setProgress
    }
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
      if (object.hasOwnProperty(prop) && typeof object[prop] !== 'undefined' && object[prop] !== false) {

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
      return Elements.select(props, {
        '': '- Do not map -',
        ...Groundhogg.fields.mappable
      }, selected)
    },
    textarea (props) {
      return `<textarea ${objectToProps(Object.filter(props, key => key !== 'value'))}>${specialChars(props.value || '')}</textarea>`
    },
    inputWithReplacementsAndEmojis (inputProps = {
      type: 'text'
    }, replacements = true, emojis = true) {
      const classList = [
        replacements && 'input-with-replacements',
        emojis && 'input-with-emojis'
      ]
      //language=HTML
      return `
		  <div class="input-wrap ${classList.filter(c => c).join(' ')}">
			  ${Elements.input(inputProps)}
			  ${emojis ? `<button class="emoji-picker-start gh-button dashicon" title="insert emoji"><span class="dashicons dashicons-smiley"></span>
			  </button>` : ''}
			  ${replacements ? `<button class="replacements-picker-start gh-button dashicon" title="insert replacement"><span
				  class="dashicons dashicons-admin-users"></span></button>` : ''}
		  </div>`
    },
    inputWithReplacements: function (atts) {
      return Elements.inputWithReplacementsAndEmojis(atts, true, false)
    },
    inputWithEmojis: function (atts) {
      return Elements.inputWithReplacementsAndEmojis(atts, false, true)
    },
    textAreaWithReplacementsAndEmojis: (props = {
      placeholder: ''
    }, replacements = true, emojis = true) => {
      const classList = [
        'textarea-with-buttons',
        replacements && 'textarea-with-replacements',
        emojis && 'textarea-with-emojis'
      ]
      //language=HTML
      return `
		  <div class="${classList.filter(c => c).join(' ')}" xmlns="http://www.w3.org/1999/html">
			  ${Elements.textarea(props)}
			  <div class="buttons">
				  ${replacements ? `<button class="replacements-picker-start gh-button dashicon" title="insert replacement"><span
				  class="dashicons dashicons-admin-users"></span></button>` : ''}
				  ${emojis ? `<button class="emoji-picker-start gh-button dashicon" title="insert emoji"><span class="dashicons dashicons-smiley"></span>
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

  var codeMirror
  var codeMirrorIsFocused

  $doc.on('ghInsertReplacement', function (e, insert) {
    if (codeMirrorIsFocused) {
      codeMirror.doc.replaceSelection(insert)
    }
  })

  $doc.on('ghReplacementTargetChanged', function () {
    codeMirrorIsFocused = false
  })

  const codeEditor = ({
    selector = '',
    onChange = (content) => {},
    initialContent = '',
    height = 500,
  }) => {

    var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {}

    editorSettings.codemirror = _.extend(
      {},
      editorSettings.codemirror,
      {
        indentUnit: 4,
        tabSize: 4
      }
    )

    codeMirror = wp.codeEditor.initialize($(selector), editorSettings).codemirror
    // self.htmlCode = self.htmlCode.codemirror;

    codeMirror.on('change', function () {
      onChange(codeMirror.doc.getValue())
    })

    codeMirror.on('focus', function () {
      codeMirrorIsFocused = true
      $doc.trigger('ghClearReplacementTarget')
    })

    codeMirror.doc.setValue(html_beautify(initialContent, { indent_with_tabs: true }))

    codeMirror.setSize(null, height)

    return {
      editor: codeMirror,
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

  const loadingModal = (text = 'Loading') => {

    let stop = () => {}

    return modal({
      content: `<h1>${text}</h1>`,
      canClose: false,
      dialogClasses: 'gh-modal-loading',
      onOpen: () => {
        stop = loadingDots('.gh-modal h1').stop
      },
      onClose: () => {
        stop()
      }
    })
  }

  const savingModal = () => {
    return loadingModal('Saving')
  }

  const dangerConfirmationModal = (props) => {
    return confirmationModal({
      ...props,
      confirmButtonType: 'danger',
      cancelButtonType: 'primary text'
    })
  }

  /**
   *
   * @param alert
   * @param confirmText
   * @param closeText
   * @param onConfirm
   * @param onClose
   * @param confirmButtonType
   * @param cancelButtonType
   * @param buttonSize
   */
  const confirmationModal = ({
    alert = '',
    confirmText = 'Confirm',
    closeText = 'Cancel',
    onConfirm = () => {},
    onClose = () => {},
    confirmButtonType = 'primary',
    cancelButtonType = 'danger text',
    buttonSize = 'medium',
  }) => {

    //language=html
    const content = `
		<button type="button" class="dashicon-button gh-modal-button-close-top gh-modal-button-close">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
		<div class="gh-modal-dialog-alert">
			${alert}
		</div>
		<div class="gh-modal-confirmation-buttons">
			<button type="button" class="gh-button ${buttonSize} ${cancelButtonType} gh-modal-button-close">
				${closeText}
			</button>
			<button type="button" class="gh-button ${buttonSize} ${confirmButtonType} gh-modal-button-confirm">
				${confirmText}
			</button>
		</div>`

    const { close, $modal } = modal({
      content,
      onClose,
      dialogClasses: 'gh-modal-confirmation'
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
    onOpen = () => {},
    dialogClasses = ''
  }) => {

    //language=html
    const html = `
		<div class="gh-modal">
			<div class="gh-modal-overlay"></div>
			<div class="gh-modal-dialog ${dialogClasses}">
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
      $('body').removeClass('modal-open')
      onClose()
    }

    const handleClose = () => {
      close()
    }

    const setContent = (content) => {
      $modal.find('.gh-modal-dialog-content').html(content)
    }

    $('body').append($modal).addClass('modal-open')

    onOpen()

    if (canClose) {
      $('.gh-modal-overlay, .gh-modal-button-close').on('click', handleClose)
    }

    return {
      $modal,
      close,
      setContent
    }
  }

  const errorDialog = (props) => {
    return dialog({
      ...props,
      type: 'error'
    })
  }

  const dialog = ({ message = '', animationDuration = 300, ttl = 3000, type = 'success' }) => {

    const $dialog = $(`<div class="gh-dialog gh-dialog-${type}">${message}</div>`)

    $('body').append($dialog).addClass('dialog-open')
    $dialog.animate({
      top: 40,
    }, animationDuration, 'swing', () => {
      setTimeout(() => {
        $dialog.animate({
          top: -100,
        }, animationDuration, 'swing', () => {
          $dialog.remove()
        })
      }, ttl)
    })

    return {
      $dialog
    }
  }

  const loadingDots = (selector) => {

    const $el = $('<span class="loading-dots"></span>')
    $(selector).append($el)

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
            : option.value == selected))
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
              : option == selected))
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

  const clickedIn = (e, selector) => {
    return clickInsideElement(e, selector)
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
    selector = '.search-options-widget-wrap',
    target = null,
    position = 'inline',
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
    focusedOptionId: -1,
    previousFocusedOptionId: false,
    focusedOption: false,
    render () {
      //language=HTML
      return `
		  <div class="search-options-widget-wrap">
			  <div class="search-options-widget ${position}" tabindex="0">
				  <div class="header">
					  ${Elements.input({
						  name: 'search',
						  type: 'search',
						  className: 'search-for-options no-insert',
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
    close () {
      $('.search-options-widget-wrap').remove()
    },
    selectOption (optionId, groupId) {
      if (!this.hasGroups()) {
        onSelect(this.getOptions()[optionId])
        this.close()
        onClose()
      } else {
        Object.keys(groups).forEach((group, g) => {
          this.getOptions().filter(option => option.group == group).forEach((option, o) => {
            if (group == groupId && o == optionId) {
              onSelect(option)
              this.close()
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

      switch (position) {
        default:
        case 'inline':
          const $el = $(selector)

          $el.html(self.render())
          this.mountOptions()
          break
        case 'fixed':
          const {
            left, top, right, bottom
          } = target.getBoundingClientRect()
          const $picker = $(self.render())
          $('body').append($picker)
          this.mountOptions()
          const $widget = $picker.find('.search-options-widget')
          $widget.css({
            top: top + $widget.outerHeight() > window.innerHeight ? 'initial' : top,
            bottom: top + $widget.outerHeight() > window.innerHeight ? 5 : 'initial',
            right: left + $widget.outerWidth() > window.innerWidth ? 5 : 'initial',
            left: left + $widget.outerWidth() > window.innerWidth ? 'initial' : left
          })
          break
      }

      const el = document.querySelector('.search-options-widget')

      if (!el) {
        return
      }

      const handleClose = () => {
        this.close()
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

  $(() => {

    const { groups, codes } = Groundhogg.replacements
    let isOpen = false
    let widget

    $doc.on('click', (e) => {
      if (isOpen && !clickedIn(e, '.search-options-widget')) {
        widget.close()
      }

      if (clickedIn(e, '.replacements-picker-start')) {

        widget = searchOptionsWidget({
          target: e.target.closest('.replacements-picker-start'),
          position: 'fixed',
          options: Object.values(codes),
          groups,
          filterOption: ({ name, code }, search) => name.match(regexp(search)) || code.match(regexp(search)),
          renderOption: (option) => option.name,
          onClose: () => {
            isOpen = false
          },
          onSelect: (option) => {
            let el = InsertAtCursor.insert(option.insert)
            $(el).focus()
          },
          onOpen: () => {
            isOpen = true
          }
        })

        widget.mount()
      }
    })
  })

  const inputRepeaterWidget = ({
    selector = '',
    rows = [],
    cellProps = [],
    cellCallbacks = [],
    onMount = () => {},
    onChange = (rows) => {
      console.log(rows)
    }
  }) => ({

    rows,

    mount () {
      $(selector).html(this.render())
      this.onMount()
    },

    onMount () {

      $(`${selector} .remove-row`).on('click', (e) => {
        const row = parseInt(e.currentTarget.dataset.row)
        this.rows.splice(row, 1)
        onChange(this.rows)
        this.mount()
      })

      $(`${selector} #add-row`).on('click', (e) => {
        this.rows.push(Array(cellProps.length).fill(''))
        onChange(this.rows)
        this.mount()
        $(`${selector} #add-row`).focus()
      })

      $(`${selector} input`).on('change', (e) => {
        const row = parseInt(e.target.dataset.row)
        const cell = parseInt(e.target.dataset.cell)
        this.rows[row][cell] = e.target.value
        onChange(this.rows)
      })

      onMount()
    },

    render () {

      const renderRow = (row, rowIndex) => {
        //language=HTML
        return `
			<div class="gh-input-repeater-row">
				${row.map((cell, cellIndex) => cellCallbacks[cellIndex]({
					...cellProps[cellIndex],
					value: cell,
					dataRow: rowIndex,
					dataCell: cellIndex,
				})).join('')}
				<button class="gh-button dashicon remove-row" data-row="${rowIndex}"><span
					class="dashicons dashicons-no-alt"></span></button>
			</div>`
      }

      //language=HTML
      return `
		  <div class="gh-input-repeater">
			  ${this.rows.map((row, i) => renderRow(row, i)).join('')}
			  <div class="gh-input-repeater-row-add">
				  <div class="spacer"></div>
				  <button id="add-row" class="gh-button dashicon">
					  <span class="dashicons dashicons-plus-alt2"></span></button>
			  </div>
		  </div>`
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

  const isValidEmail = (email) => {
    const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    return re.test(String(email).toLowerCase())
  }

  const primaryButton = ({ className, ...props }) => {
    return button({
      className: 'gh-button primary' + (className ? ' ' + className : ''),
      ...props
    })
  }

  const secondaryButton = ({ className, ...props }) => {
    return button({
      className: 'gh-button secondary' + (className ? ' ' + className : ''),
      ...props
    })
  }

  const dangerButton = ({ className, ...props }) => {
    return button({
      className: 'gh-button danger' + (className ? ' ' + className : ''),
      ...props
    })
  }

  const button = ({
    text = '',
    className = '',
    ...props
  }) => {
    // language=HTML
    return `
		<button ${objectToProps({
			className: 'gh-button' + (className ? ' ' + className : ''),
			...props
		})}>${text}
		</button>`
  }

  const setFrameContent = (frame, content) => {
    var blob = new Blob([content], { type: 'text/html; charset=utf-8' })
    frame.src = URL.createObjectURL(blob)
  }

  const moreMenu = (selector, {
    items = [],
    onSelect = (key) => { console.log(key) }

  }) => {

    // language=HTML
    const menu = `
		<div role="menu" class="gh-dropdown-menu" tabindex="0">
			${items.map(({
				key,
				text
			}) => `<div class="gh-dropdown-menu-item" data-key="${key}">${text}</div>`).join('')}
		</div>`

    const $menu = $(menu)

    const close = () => {
      $menu.remove()
      // console.log('closed')
    }

    $menu.on('click', '.gh-dropdown-menu-item', (e) => {
      onSelect(e.currentTarget.dataset.key)
      close()
    })

    $menu.on('blur', () => {
      close()
    })

    const $el = $(selector)

    $('body').append($menu)

    const {
      left,
      right,
      top,
      bottom
    } = $el[0].getBoundingClientRect()

    $menu.css({
      top: Math.min(bottom, window.innerHeight - $menu.height() - 20) + 'px',
      left: (right - $menu.outerWidth()) + 'px'
    })

    $menu.focus()
  }

  const uniqid = () => {
    return Date.now()
  }

  const adminPageURL = (page, params) => {

    params = $.param({
      page,
      ...params
    })

    return `${Groundhogg.url.admin.replace(/(\/|\\)$/, '')}/admin.php?${params}`
  }

  const icons = {
    // language=html
    rocket: `
		<svg viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path
				d="M8.888 7.173a21.621 21.621 0 017.22-4.783m-7.22 4.783a21.766 21.766 0 00-2.97 3.697m2.97-3.697c-1.445-.778-4.935-1.2-7.335 3.334l2.364 2.364 2-2m10.19-8.481A21.709 21.709 0 0123.22.843a21.708 21.708 0 01-1.546 7.112M16.108 2.39l5.565 5.565M5.917 10.87l1.885 4.057m9.088.248a21.62 21.62 0 004.783-7.22m-4.783 7.22a21.771 21.771 0 01-3.698 2.97m3.698-2.97c.778 1.445 1.2 4.934-3.334 7.335l-2.364-2.364 2-2m0 0L9.136 16.26m0 0l-1.334-1.334m1.334 1.334l-2.71 2.71-.667-.666-.667-.667 2.71-2.71m6.42-5.087a1.886 1.886 0 112.668-2.667 1.886 1.886 0 01-2.668 2.667z"
				stroke="currentColor" stroke-width="1.5"/>
		</svg>`,
    // language=html
    close: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 365.7 365.7">
			<path fill="currentColor"
			      d="M243.2 182.9L356.3 69.7a32 32 0 000-45.2l-15-15.1a32 32 0 00-45.3 0L182.9 122.5 69.7 9.4a32 32 0 00-45.2 0l-15.1 15a32 32 0 000 45.3L122.5 183 9.4 295.9a32 32 0 000 45.3l15 15.1a32 32 0 0045.3 0L183 243.2l113 113.1a32 32 0 0045.3 0l15.1-15a32 32 0 000-45.3zm0 0"/>
		</svg>`,
    // language=html
    verticalDots: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 384">
			<circle fill="currentColor" cx="192" cy="42.7" r="42.7"/>
			<circle fill="currentColor" cx="192" cy="192" r="42.7"/>
			<circle fill="currentColor" cx="192" cy="341.3" r="42.7"/>
		</svg>`,
    // language=html
    save: `
		<svg viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path
				d="M1 21.956V2.995c0-.748.606-1.355 1.354-1.355H17.93l4.74 4.74v15.576c0 .748-.606 1.354-1.354 1.354H2.354A1.354 1.354 0 011 21.956z"
				stroke="currentColor" stroke-width="1.5"/>
			<path d="M14.544 16.539a2.709 2.709 0 11-5.418 0 2.709 2.709 0 015.418 0z" stroke="#fff"
			      stroke-width="1.5"/>
			<path fill="currentColor" d="M5.619 6.298h9.634v2.846H5.619z"/>
		</svg>`,
    // language=html
    megaphone: `
		<svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<defs/>
			<path
				d="M343.2 49.7A45.1 45.1 0 00300 82L71.8 130a15 15 0 00-12 14.7v17.1H15a15 15 0 00-15 15V256a15 15 0 0015 15h44.9v21a15 15 0 0011.9 14.8l23.6 5v107.8a42.6 42.6 0 0069.2 33.5 42.5 42.5 0 0016.2-33.5V400h3c34 0 62.8-23.4 70.9-55l45.3 9.5a45.1 45.1 0 0088.3-12.5V94.7a45 45 0 00-45-45zM60 241H30v-49.2h29.9zm91 178.6a12.7 12.7 0 01-15.7 12.4 12.7 12.7 0 01-9.8-12.4V318l25.4 5.4v96.2zm33-49.5h-3v-40.5l44.4 9.4c-5.3 18-21.9 31-41.5 31zm114.3-46.5L89.9 280V157L298.2 113zm60 18.5a15 15 0 01-30 0V94.7a15 15 0 0130 0zM446.3 117a15 15 0 009.5-3.4l30.2-25a15 15 0 00-19.1-23l-30.2 24.8a15 15 0 009.6 26.6zM486 344.2l-30.2-25a15 15 0 00-19 23.2l30 25a15 15 0 0021.2-2 15 15 0 00-2-21.2zM497 201.4h-63.6a15 15 0 000 30H497a15 15 0 000-30z"/>
		</svg>`,
    // language=html
    export: `
		<svg height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 367 367">
			<defs/>
			<path
				fill="currentColor"
				stroke-width="1"
				d="M363.6 247l.4-.5.5-.7.4-.6.3-.6.4-.7.3-.7.2-.6c0-.3.2-.5.3-.7l.1-.7.2-.8.1-.8.1-.6.1-1.5V236l-.2-.6v-.8l-.3-.8-.1-.7-.3-.7-.2-.6-.3-.7-.4-.7-.3-.6-.4-.6-.5-.7-.4-.5a15 15 0 00-1-1v-.1l-37.5-37.5a15 15 0 00-21.2 21.2l11.9 11.9H270v-78.6-.4a15 15 0 00-3.4-9.5 15.2 15.2 0 00-1-1.2c-.2 0-.3-.2-.4-.4L155.6 23a15 15 0 00-1-.9l-.3-.2a14.9 14.9 0 00-1.9-1.3l-.3-.2-1.1-.6-.5-.1a14.5 14.5 0 00-2.2-.7l-.4-.1-1.2-.2h-1.4l-.3-.1H15a15 15 0 00-15 15v300a15 15 0 0015 15h240a15 15 0 0015-15v-81h45.8l-12 11.9a15 15 0 0021.3 21.2l37.5-37.5 1-1zM160 69.7l58.8 58.8H160V69.7zm80 248.8H30v-270h100v95a15 15 0 0015 15h95v64h-65a15 15 0 000 30h65v66z"/>
		</svg>`,
    // language=html
    share: `
		<svg height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="-33 0 512 512">
			<path fill="currentColor"
			      d="M361.8 344.4a83.6 83.6 0 00-62 27.4l-138-85.4a83.3 83.3 0 000-60.8l138-85.4a83.6 83.6 0 00145.8-56.4 83.9 83.9 0 10-161.9 30.4l-138 85.4A83.6 83.6 0 000 256a83.9 83.9 0 00145.8 56.4l138 85.4a83.9 83.9 0 10161.9 30.4 83.9 83.9 0 00-83.9-83.8zM308.6 83.8a53.3 53.3 0 11106.6.1 53.3 53.3 0 01-106.6-.1zM83.8 309.2a53.3 53.3 0 11.1-106.6 53.3 53.3 0 01-.1 106.6zm224.8 119a53.3 53.3 0 11106.6.1 53.3 53.3 0 01-106.6-.1zm0 0"/>
		</svg>`,
    // language=html
    chart: `
		<svg height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 510 510">
			<path fill="currentColor"
			      d="M495 420h-14V161.8a15 15 0 00-15-15h-82.2a15 15 0 00-15 15V420h-42.3V75a15 15 0 00-15-15h-82.3a15 15 0 00-15 15v345H172V232.2a15 15 0 00-15-15H74.7a15 15 0 00-15 15V420H30V75a15 15 0 00-30 0v360a15 15 0 0015 15h480a15 15 0 000-30zm-405.3 0V247.2h52.2V420zm154.5 0V90h52.2v330zm154.6 0V176.8H451V420z"/>
		</svg>`,
    // language=html
    folder: `
		<svg class="danger" height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 520 520">
			<defs/>
			<path fill="currentColor"
			      d="M475 125V90a45 45 0 00-45-45H219.4l-7.9-12.8a15 15 0 00-12.7-7.2H45A45 45 0 000 70v380a45 45 0 0045 45h430a45 45 0 0045-45V170a45 45 0 00-45-45zm-45-50a15 15 0 0115 15v35H268.4l-20-32.8L237.7 75zm60 375a15 15 0 01-15 15H45a15 15 0 01-15-15V70a15 15 0 0115-15h145.3l7.9 12.8 29 47.3 20 32.8A15 15 0 00260 155h215a15 15 0 0115 15v280z"/>
		</svg>`,
    // language=html
    trash: `
		<svg class="danger" height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<path fill="currentColor"
			      d="M436 60h-75V45a45 45 0 00-45-45H196a45 45 0 00-45 45v15H76a45 45 0 00-14 87.8l26.8 323a45.3 45.3 0 0044.8 41.2h244.8c23.2 0 43-18.1 44.8-41.3l26.8-323A45 45 0 00436 60zM181 45a15 15 0 0115-15h120a15 15 0 0115 15v15H181V45zm212.3 423.2a15 15 0 01-14.9 13.8H133.6a15 15 0 01-15-13.7L92.4 150h327.4l-26.4 318.2zM436 120H76a15 15 0 010-30h360a15 15 0 010 30z"/>
			<path fill="currentColor"
			      d="M196 436l-15-242a15 15 0 00-30 2l15 242a15 15 0 1030-2zM256 180a15 15 0 00-15 15v242a15 15 0 0030 0V195a15 15 0 00-15-15zM347 180a15 15 0 00-16 14l-15 242a15 15 0 0030 2l15-242a15 15 0 00-14-16z"/>
		</svg>`,
    // language=html
    tag: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="-21 -21 682 682.7">
			<path fill="currentColor"
			      d="M274.7 640c-20.1 0-39-7.8-53.1-22L22.2 418.3a75.1 75.1 0 010-106L291 43c27.7-27.7 64.5-43 103.8-43h170.5a75 75 0 0175 75v170c0 39.2-15.3 76-43 103.7L327.7 618.1a74.5 74.5 0 01-53 21.9zm120-590a96 96 0 00-68.3 28.4L57.6 347.7a25 25 0 000 35.3L257 582.7c4.7 4.7 11 7.3 17.7 7.3 6.6 0 13-2.6 17.6-7.3L562 313.4a96 96 0 0028.3-68.4V75a25 25 0 00-25-25zM459 253.8a75 75 0 11.2-150.2 75 75 0 01-.2 150.2zm0-100a25 25 0 100 50 25 25 0 000-50zm0 0"/>
		</svg>`

  }

  const bold = (text) => {
    return `<b>${text}</b>`
  }

  Groundhogg.element = {
    icons,
    ...Elements,
    adminPageURL,
    specialChars,
    uniqid,
    moreMenu,
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
    isValidEmail,
    loadingModal,
    savingModal,
    confirmationModal,
    dangerConfirmationModal,
    uuid,
    modal,
    setFrameContent,
    copyObject,
    loadingDots,
    flattenObject,
    objectEquals,
    inputRepeaterWidget,
    improveTinyMCE,
    primaryButton,
    dangerButton,
    secondaryButton,
    button,
    codeEditor,
    breadcrumbs,
    dialog,
    errorDialog,
    stepNav,
    stepNavHandler,
    progressBar,
    tooltip,
    clickedIn,
    ordinal_suffix_of,
    bold
  }

})(jQuery)