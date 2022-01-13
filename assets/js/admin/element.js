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

    if ($el.hasClass('gh-has-tooltip')) {
      return
    }

    // language=HTML
    const $tip = $(`
		<div class="gh-tooltip ${position}">
			${content}
		</div>`)

    $el.addClass('gh-has-tooltip')
    $el.append($tip)
  }

  const el = (tag, atts = {}, content = '', closing = false) => {

    if (!content && closing) {
      return `<${tag} ${objectToProps(atts)}/>`
    }

    return `<${tag} ${objectToProps(atts)}>${Array.isArray(content) ? content.join('') : content}</${tag}>`
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
    onStepChange = (step) => {console.log(step)},
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
    if (!array || array.length === 0) {
      return ''
    }
    if (array.length === 1) {
      return array[0]
    }
    return sprintf(_x('%s and %s', 'and preceding the last item in a list', 'groundhogg'), array.slice(0, -1).join(', '), array[array.length - 1])
  }

  function orList (array) {
    if (!array || array.length === 0) {
      return ''
    }
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

  function isNumeric (n) {
    return !isNaN(parseFloat(n)) && isFinite(n)
  }

  const objectToStyle = (object) => {
    const props = []

    for (const prop in object) {
      if (object.hasOwnProperty(prop)) {

        let attr = kebabize(prop)
        let val = specialChars(object[prop])

        switch (attr) {
          case 'font-size':
          case 'height':
          case 'width':
          case 'margin':
          case 'padding':
          case 'margin-top':
            if (isNumeric(val)) {
              val += 'px'
            }
            break
        }

        props.push(`${attr}:${val}`)
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
    toggle ({ id, name, className, value = '1', onLabel = 'on', offLabel = 'off', checked, ...props }) {
      //language=HTML
      return `
		  <label class="gh-switch ${className}">
			  ${Elements.input({
				  name,
				  id,
				  value,
				  checked,
				  ...props,
				  type: 'checkbox',
			  })}
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
    select (_props, _options, _selected) {

      let { options = _options, selected = _selected, value, ...props } = _props

      if ( value && ! selected ){
        selected = value
      }

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

    tinyMCE.get(editor_id).on('keyup keydown mouseup', function (e) {
      onChange(tinyMCE.activeEditor.getContent({ format: 'raw' }))
    })

    return tinyMCE.get(editor_id)
  }

  function addMediaToBasicTinyMCE () {

    if (typeof this.flag !== 'undefined') {
      return
    }

    $doc.on('tinymce-editor-setup', function (event, editor) {
      editor.settings.toolbar1 =
        'bold,italic,bullist,numlist,alignleft,aligncenter,alignright,link,wp_add_media'
      editor.on('click', function (ed, e) {
        $doc.trigger('to_mce')
      })
    })

    this.flag = 'improved'
  }

  function improveTinyMCE (settings = {}) {

    const {
      height = 200
    } = settings

    if (typeof this.flag !== 'undefined') {
      return
    }

    $doc.on('tinymce-editor-setup', function (event, editor) {
      editor.settings.toolbar1 =
        'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,spellchecker,wp_adv,wp_add_media,dfw'
      editor.settings.toolbar2 =
        'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help'
      editor.settings.height = height
      editor.on('click', function (ed, e) {
        $doc.trigger('to_mce')
      })
    })

    this.flag = 'improved'
  }

  /**
   *
   * @param text
   * @param props
   * @return {{setContent: setContent, $modal: (*|jQuery|HTMLElement), close: close}}
   */
  const loadingModal = (text = 'Loading', props = {}) => {

    return modal({
      content: spinner(),
      canClose: false,
      dialogClasses: 'gh-modal-loading',
      ...props,
    })
  }

  const spinner = () => {
    // language=HTML
    return `
		<div class="gh-spinner-wrap">
			<object class="gh-spinner" data="${Groundhogg.assets.spinner}" type="image/svg+xml" width="100" height="100"/>
		</div>`
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
   * @param onCancel
   * @param onClose
   * @param confirmButtonType
   * @param cancelButtonType
   * @param buttonSize
   * @param rest
   */
  const confirmationModal = ({
    alert = '',
    confirmText = _x('Confirm', 'verb', 'groundhogg'),
    closeText = __('Cancel', 'groundhogg'),
    onConfirm = () => {},
    onCancel = () => {},
    onClose = () => {},
    confirmButtonType = 'primary',
    cancelButtonType = 'danger text',
    buttonSize = 'medium',
    ...rest
  }) => {

    let confirmed = false

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

    const handleClose = () => {
      if (!confirmed) {
        onCancel()
      }

      onClose()
    }

    const { close, $modal } = modal({
      content,
      onClose: handleClose,
      dialogClasses: 'gh-modal-confirmation',
      ...rest
    })

    const confirm = () => {
      confirmed = true
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
   * @param selector
   */
  const miniModal = (selector, {
    content = '',
    onSetContent = () => {},
    onClose = () => {},
    onOpen = () => {},
    dialogClasses = '',
    closeOnFocusout = true,
  }) => {

    //language=html
    const html = `
		<div class="gh-modal mini gh-panel" tabindex="0">
			<div class="gh-modal-dialog ${dialogClasses}">
				<button type="button" class="dashicon-button gh-modal-button-close-top gh-modal-button-close">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
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
      onSetContent()
    }

    const $el = $(selector)

    $('body').append($modal)

    const {
      left,
      right,
      top,
      bottom
    } = $el[0].getBoundingClientRect()

    $modal.css({
      top: Math.min(bottom, window.innerHeight - $modal.height() - 20) + 'px',
      left: (right - $modal.outerWidth()) + 'px'
    })

    onOpen()

    $modal.find('.gh-modal-button-close').on('click', handleClose)

    if (closeOnFocusout) {
      $modal.on('focusout', (e) => {

        if (!e.relatedTarget || !clickedIn(e.relatedTarget, '.gh-modal.mini')) {
          handleClose()
        }
      })
    }

    $modal.focus()

    return {
      $modal,
      close,
      setContent
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
    onSetContent = () => {},
    onClose = () => {},
    beforeClose = () => true,
    canClose = true,
    onOpen = () => {},
    width = false,
    className = '',
    dialogClasses = '',
    overlay = true,
    disableScrolling = true
  }) => {

    //language=html
    const html = `
		<div class="gh-modal ${className} ${disableScrolling ? 'disabled-scrolling' : ''}">
			${overlay ? `<div class="gh-modal-overlay"></div>` : ''}
			<div class="gh-modal-dialog ${dialogClasses}" style="width: ${width ? width + 'px' : 'fit-content'}">
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

      if (!beforeClose(close)) {
        return
      }

      $modal.remove()

      // If this modal was disabling scrolling and no other modals exist that also disable scroll.
      if (disableScrolling && $('.gh-modal.disabled-scrolling').length === 0) {
        $('body').removeClass('modal-open')
      }

      onClose()
    }

    const handleClose = () => {
      close()
    }

    const setContent = (content) => {
      $modal.find('.gh-modal-dialog-content').html(content)
      onSetContent()
    }

    $('body').append($modal).addClass(disableScrolling ? 'modal-open' : '')

    onOpen({ close, setContent })

    if (canClose) {
      $modal.find('.gh-modal-overlay, .gh-modal-button-close').on('click', handleClose)
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
      $el.remove()
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
    var el = e.tagName ? e : e.srcElement || e.target

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
    noOptions = __('No options...', 'groundhogg'),
    onSelect = (option) => {},
    onInput = (search) => {},
    onClose = () => {},
    onOpen = () => {},
    filterOptions = (opts, search) => opts
  }) => ({
    selector,
    options,
    onInput,
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
						  placeholder: __('Search...', 'groundhogg')
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
      return filterOptions( this.options.filter((option, i) => {
        if (this.search) {
          return filterOption(option, this.search)
        }

        return true
      }), this.search )
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

        const optionId = parseInt( $(this).data('option') )
        const groupId = parseInt( $(this).data('group') )

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

      $(`${selector} input.search-for-options`).on('input', (e) => {
        this.search = $(e.target).val()
        this.focusedOptionId = -1
        this.previousFocusedOptionId = -1
        onInput(this.search, this)
        this.mountOptions()
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
            this.selectOption(parseInt($focused.data('option')), parseInt($focused.data('group')))

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
    sortable = false,
    cellCallbacks = [],
    onMount = () => {},
    onChange = (rows) => {},
    onRemove = (row) => {},

  }) => ({

    rows,

    mount () {
      $(selector).html(this.render())
      this.onMount()
    },

    onMount () {

      $(`${selector} .remove-row`).on('click', (e) => {
        const row = parseInt(e.currentTarget.dataset.row)
        onRemove(this.rows[row])
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

      if (sortable) {
        $(`${selector} .gh-input-repeater`).sortable({
          handle: '.handle',
          update: (e, ui) => {

            let $row = $(ui.item)
            let oldIndex = parseInt($row.data('row'))
            let curIndex = $row.index()

            let row = this.rows[oldIndex]

            this.rows.splice(oldIndex, 1)
            this.rows.splice(curIndex, 0, row)

            this.mount()
          }
        })
      }

      onMount()
    },

    render () {

      const renderRow = (row, rowIndex) => {
        //language=HTML
        return `
			<div class="gh-input-repeater-row" data-row="${rowIndex}">
				${row.map((cell, cellIndex) => cellCallbacks[cellIndex]({
					...cellProps[cellIndex],
					value: cell,
					dataRow: rowIndex,
					dataCell: cellIndex,
				})).join('')}
				${sortable ? `<span class="handle" data-row="${rowIndex}"><span
					class="dashicons dashicons-move"></span></span>` : ''}
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
			${items.filter(i => i && true).map(({
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
    drag: `<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle fill="currentColor" cx="8" cy="4" r="2"/><circle fill="currentColor" cx="8" cy="12" r="2"/><circle fill="currentColor" cx="8" cy="20" r="2"/><circle fill="currentColor" cx="16" cy="4" r="2"/><circle fill="currentColor" cx="16" cy="12" r="2"/><circle fill="currentColor" cx="16" cy="20" r="2"/></svg>`,
    // language=html
    image: `
		<svg xmlns="http://www.w3.org/2000/svg" style="enable-background:new 0 0 550.801 550.8" xml:space="preserve"
		     viewBox="0 0 550.801 550.8">
          <path fill="currentColor"
                d="M515.828 61.201H34.972C15.659 61.201 0 76.859 0 96.172V454.63c0 19.312 15.659 34.97 34.972 34.97h480.856c19.314 0 34.973-15.658 34.973-34.971V96.172c0-19.313-15.658-34.971-34.973-34.971zm0 34.971V350.51l-68.92-62.66c-10.359-9.416-26.289-9.04-36.186.866l-69.752 69.741-137.532-164.278c-10.396-12.415-29.438-12.537-39.99-.271L34.972 343.219V96.172h480.856zm-148.627 91.8c0-26.561 21.523-48.086 48.084-48.086 26.562 0 48.086 21.525 48.086 48.086s-21.523 48.085-48.086 48.085c-26.56.001-48.084-21.524-48.084-48.085z"/></svg>`,

    // language=html
    duplicate: `
		<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
			<g data-name="Layer 2">
				<path fill="currentColor"
				      d="M3.625 23h9.75A2.629 2.629 0 0 0 16 20.375V7.625A2.629 2.629 0 0 0 13.375 5h-9.75A2.629 2.629 0 0 0 1 7.625v12.75A2.629 2.629 0 0 0 3.625 23zM3 7.625A.625.625 0 0 1 3.625 7h9.75a.625.625 0 0 1 .625.625v12.75a.625.625 0 0 1-.625.625h-9.75A.625.625 0 0 1 3 20.375z"/>
				<path fill="currentColor"
				      d="M20.37 1h-9.74a2.629 2.629 0 0 0-2.421 1.61 1 1 0 1 0 1.842.78.63.63 0 0 1 .579-.39h9.74a.631.631 0 0 1 .63.63v12.74a.631.631 0 0 1-.63.63H18a1 1 0 0 0 0 2h2.37A2.633 2.633 0 0 0 23 16.37V3.63A2.633 2.633 0 0 0 20.37 1z"/>
			</g>
		</svg>`,
    // language=html
    move: `
		<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
			<path fill="currentColor"
			      d="m60.958 33.398-8.489 8.818A2.029 2.029 0 0 1 49 40.817v-3.8L37 37v11.982l3.817.017a2.029 2.029 0 0 1 1.4 3.47l-8.819 8.488a2.01 2.01 0 0 1-2.796 0l-8.818-8.489A2.029 2.029 0 0 1 23.183 49h3.8l.015-12H15.017L15 40.817a2.029 2.029 0 0 1-3.47 1.4l-8.488-8.819a2.01 2.01 0 0 1 0-2.796l8.489-8.818A2.029 2.029 0 0 1 15 23.183v3.8l12 .015V15.017L23.183 15a2.029 2.029 0 0 1-1.4-3.47l8.819-8.488a2.008 2.008 0 0 1 2.796 0l8.818 8.489A2.029 2.029 0 0 1 40.817 15h-3.8L37 27h11.982L49 23.183a2.03 2.03 0 0 1 3.47-1.4l8.488 8.819a2.01 2.01 0 0 1 0 2.796z"
			      data-name="11 Move"/>
		</svg>`,
    // language=html
    form: `
		<svg viewBox="0 0 35 31" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path
				d="M1.5 29.802a.25.25 0 01-.25-.25v-6a.25.25 0 01.25-.25h32a.25.25 0 01.25.25v6a.25.25 0 01-.25.25h-32z"
				fill="currentColor" stroke="currentColor" stroke-width="1.5"/>
			<path
				d="M1.5 7.733a.25.25 0 01-.25-.25v-6a.25.25 0 01.25-.25h32a.25.25 0 01.25.25v6a.25.25 0 01-.25.25h-32zm0 11a.25.25 0 01-.25-.25v-6a.25.25 0 01.25-.25h32a.25.25 0 01.25.25v6a.25.25 0 01-.25.25h-32z"
				stroke="currentColor" stroke-width="1.5"/>
		</svg>`,
    note: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
  <path fill="currentColor" d="M512 334.667V20c0-11.046-8.954-20-20-20H20C8.954 0 0 8.954 0 20v472c0 11.046 8.954 20 20 20h314.667c5.375 0 10.489-2.203 14.145-5.86L506.14 348.811c3.652-3.65 5.86-8.747 5.86-14.144zM40 40h432v274.667H334.667c-11.046 0-20 8.954-20 20V472H40zm403.716 314.667-89.049 89.049v-89.049zM118 177.333c0-11.046 8.954-20 20-20h236c11.046 0 20 8.954 20 20s-8.954 20-20 20H138c-11.046 0-20-8.954-20-20zM138 276c-11.046 0-20-8.954-20-20s8.954-20 20-20h236c11.046 0 20 8.954 20 20s-8.954 20-20 20z"/>
</svg>`,
    // language=html
    mobile: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<path fill="currentColor"
			      d="M298.7 25.6h-85.4a8.5 8.5 0 000 17h85.4a8.5 8.5 0 000-17zM358.4 25.6h-8.5a8.5 8.5 0 000 17h8.5a8.5 8.5 0 000-17zM266.6 435.2h-21.2c-13 0-23.5 10.6-23.5 23.5v4.2c0 13 10.5 23.5 23.5 23.5h21.2c13 0 23.5-10.6 23.5-23.5v-4.2c0-13-10.5-23.5-23.5-23.5zm6.5 27.7c0 3.5-3 6.4-6.5 6.4h-21.2a6.5 6.5 0 01-6.5-6.4v-4.2c0-3.5 3-6.4 6.5-6.4h21.2c3.6 0 6.5 2.9 6.5 6.4v4.2z"/>
			<path fill="currentColor"
			      d="M370.2 0H141.8c-17 0-30.9 13.8-30.9 30.8v450.4c0 17 13.9 30.8 30.9 30.8h228.4c17 0 30.9-13.8 30.9-30.8V30.8c0-17-13.9-30.8-30.9-30.8zM384 481.2c0 7.5-6.2 13.7-13.8 13.7H141.8c-7.6 0-13.8-6.2-13.8-13.7V30.8c0-7.5 6.2-13.7 13.8-13.7h228.4c7.6 0 13.8 6.2 13.8 13.7v450.4z"/>
			<path fill="currentColor"
			      d="M392.5 51.2h-273a8.5 8.5 0 00-8.6 8.5v358.4c0 4.7 3.9 8.6 8.6 8.6h273c4.7 0 8.6-3.9 8.6-8.6V59.7c0-4.7-3.9-8.5-8.6-8.5zM384 409.6H128V68.3h256v341.3z"/>
		</svg>`,// language=html
    phone: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 473.8 473.8">
			<path fill="currentColor"
			      d="M374.5 293.5a46.6 46.6 0 00-33.8-15.5 48.2 48.2 0 00-34.2 15.4L274.9 325l-7.7-4a127 127 0 01-10-5.3 343.5 343.5 0 01-82.2-75 202.6 202.6 0 01-27-42.6c8.2-7.5 15.8-15.3 23.2-22.8l8.4-8.5c21-21 21-48.2 0-69.2l-27.3-27.3c-3.1-3-6.3-6.3-9.3-9.5-6-6.2-12.3-12.6-18.8-18.6-9.7-9.6-21.3-14.7-33.5-14.7s-24 5.1-34 14.7l-.2.2-34 34.3A73.2 73.2 0 00.8 123.1c-2.4 29.2 6.2 56.4 12.8 74.2C29.8 241 54 281.5 90 325a470.6 470.6 0 00156.7 122.7c23 11 53.7 23.8 88 26l6.3.2c23 0 42.5-8.3 57.7-24.8 0-.2.3-.3.4-.5 5.2-6.3 11.2-12 17.5-18 4.3-4.2 8.7-8.5 13-13a49.9 49.9 0 0015-34.6 48 48 0 00-15.3-34.3l-55-55zm35.8 105.3c-.1 0-.1.1 0 0-4 4.2-8 8-12.2 12.2a263 263 0 00-19.3 20 48.2 48.2 0 01-37.6 16c-1.5 0-3.1 0-4.6-.2-29.7-1.9-57.3-13.5-78-23.4A444.2 444.2 0 01111 307.8c-34.1-41-57-79-72-119.9-9.3-24.9-12.7-44.3-11.2-62.6 1-11.7 5.5-21.4 13.8-29.7l34-34A22.7 22.7 0 0191 54.3c6.3 0 11.4 3.8 14.6 7l.3.3c6 5.7 11.9 11.6 18 18l9.5 9.6 27.3 27.3c10.6 10.6 10.6 20.4 0 31l-8.6 8.6a522 522 0 01-25.1 24.4l-.5.5c-8.6 8.6-7 17-5.2 22.7l.3 1a219.2 219.2 0 0032.3 52.6v.1a367 367 0 0088.9 80.8c4 2.6 8.3 4.7 12.3 6.7 3.6 1.8 7 3.5 9.9 5.3l1.2.7c3.4 1.7 6.6 2.5 9.9 2.5 8.3 0 13.5-5.2 15.2-6.9l34.2-34.2c3.4-3.4 8.8-7.5 15-7.5 6.3 0 11.4 4 14.5 7.3l.2.2 55 55.1c10.4 10.2 10.4 20.7.2 31.3zM256 112.7c26.3 4.4 50 16.8 69 35.8s31.4 42.8 35.9 69a13.4 13.4 0 0013.3 11.2c.8 0 1.5 0 2.3-.2a13.5 13.5 0 0011-15.6c-5.3-31.7-20.3-60.6-43.2-83.5s-51.8-37.9-83.5-43.3c-7.4-1.2-14.3 3.7-15.6 11s3.5 14.4 10.9 15.6zM473.3 209c-9-52.2-33.5-99.7-71.3-137.5S316.7 9.1 264.5.2a13.4 13.4 0 10-4.4 26.6c46.6 8 89 30 122.9 63.7a226.5 226.5 0 0163.7 123 13.4 13.4 0 0013.3 11.1c.8 0 1.5 0 2.3-.2a13.2 13.2 0 0011-15.4z"/>
		</svg>`,
    // language=html
    email: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<path fill="currentColor"
			      d="M467 61H45a45 45 0 00-45 45v300a45 45 0 0045 45h422a45 45 0 0045-45V106a45 45 0 00-45-45zm-6.2 30L257 294.8 51.4 91h409.4zM30 399.8V112l144.5 143.2L30 399.8zM51.2 421l144.6-144.6 50.6 50.3a15 15 0 0021.2 0l49.4-49.5L460.8 421H51.2zM482 399.8L338.2 256 482 112.2v287.6z"/>
		</svg>`,
    // language=html
    contact: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<path fill="currentColor"
			      d="M256 0c-74.4 0-135 60.6-135 135s60.6 135 135 135 135-60.6 135-135S330.4 0 256 0zm0 240a105.1 105.1 0 010-210 105.1 105.1 0 010 210zM424 358.2c-37-37.5-86-58.2-138-58.2h-60c-52 0-101 20.7-138 58.2A196.7 196.7 0 0031 497a15 15 0 0015 15h420a15 15 0 0015-15c0-52.2-20.3-101.5-57-138.8zM61.7 482A166 166 0 01226 330h60c86 0 156.8 67 164.3 152H61.7z"/>
		</svg>`,
    // language=html
    createContact: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<defs/>
			<path fill="currentColor"
			      d="M511 317a105.1 105.1 0 00-183-70.3 225.9 225.9 0 00-34.6-14.3 126 126 0 10-135-.1A224 224 0 0067 287.9 223.5 223.5 0 001 447v50a15 15 0 0015 15h420a15 15 0 0015-15v-50c0-11.3-.9-22.6-2.6-34a105.1 105.1 0 0062.6-96zM130 126a96.1 96.1 0 01192 0 96.1 96.1 0 01-192 0zm291 321v35H31v-35c0-107.5 87.5-195 195-195 29.5 0 58.6 6.8 85.2 19.8a105.1 105.1 0 00108 149.3c1.2 8.7 1.8 17.3 1.8 25.9zm6.7-58.2c-.4 0-.7.2-1.1.3A74.7 74.7 0 01331 317a75 75 0 1196.7 71.8z"/>
			<path fill="currentColor"
			      d="M436 302h-15v-15a15 15 0 00-30 0v15h-15a15 15 0 000 30h15v15a15 15 0 0030 0v-15h15a15 15 0 000-30z"/>
		</svg>`,
    // language=html
    contactSearch: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<defs/>
			<path fill="currentColor"
			      d="M486.3 430.8L426 370.5l-23 23-18-18A101.8 101.8 0 00286.6 215a156.9 156.9 0 00-55-36 99.4 99.4 0 10-119.3 0A156.7 156.7 0 0011.8 325v46.2h206.7a101.3 101.3 0 00145.3 25.6l17.9 18-23 23L419 498a47.3 47.3 0 0067.3 0 47.3 47.3 0 000-67.3zM102.5 99.4a69.5 69.5 0 1174.9 69.3h-11a69.5 69.5 0 01-64-69.3zM41.8 341.2V325A126.6 126.6 0 01171.9 199l6.6-.2c28.6.6 54.8 10.8 75.7 27.4A101.3 101.3 0 00205 341H41.8zm210.8 24.5a71.2 71.2 0 01-21-50.6 71.2 71.2 0 0171.7-71.6 71.7 71.7 0 11-50.6 122.2zm212.5 111.1a17.5 17.5 0 01-25 0l-39-39 24.9-25 39 39.2a17.5 17.5 0 010 24.8z"/>
		</svg>`,
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
			<path fill="currentColor"
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
		</svg>`,
    // language=HTML
    groundhogg: `
		<svg height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="11.4 13.2 212.1 237.9">
			<linearGradient id="a" x1="35.6" x2="199.3" y1="214" y2="50.4" gradientUnits="userSpaceOnUse">
				<stop offset="0.3" stop-color="#db851a"/>
				<stop offset="1" stop-color="#db6f1a"/>
			</linearGradient>
			<path fill="url(#a)"
			      d="M22.7 64.4l83.4-48.2c7-4 15.7-4 22.7 0l83.4 48.2c7 4 11.3 11.5 11.3 19.6v96.3c0 8.1-4.3 15.6-11.3 19.6l-83.4 48.2c-7 4-15.7 4-22.7 0L22.7 200c-7-4-11.3-11.5-11.3-19.6V84a22.5 22.5 0 0111.3-19.6z"/>
			<path fill="#db5100"
			      d="M183.5 140.8v4.9A66.1 66.1 0 11164 98.8l-24.5 24.3a31.4 31.4 0 103.6 40.9h-25.6v-23.3h66z"/>
			<path fill="#fff"
			      d="M183.5 126.1v4.9A66.1 66.1 0 11164 84.1l-24.5 24.3a31.4 31.4 0 103.6 40.9h-25.6V126h66z"/>
		</svg>`,
    // language=html
    alignLeft: `
		<svg width="13" height="14" viewBox="0 0 13 14" fill="none"
		     xmlns="http://www.w3.org/2000/svg">
			<path
				d="M0.777832 13.1662H6.4477M0.777832 9.0427H12.1176M0.777832 0.795624H12.1176M0.777832 4.91916H6.4477"
				stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
				stroke-linejoin="round"/>
		</svg>`,
    // language=html
    alignCenter: `
		<svg width="14" height="14" viewBox="0 0 14 14" fill="none"
		     xmlns="http://www.w3.org/2000/svg">
			<path opacity="0.6"
			      d="M12.5319 9.00262H1.19189M12.5319 0.755951H1.19189M9.95462 13.126H4.28462M9.95462 4.87928H4.28462"
			      stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
			      stroke-linejoin="round"/>
		</svg>`,
    // language=html
    alignRight: `
		<svg width="14" height="14" viewBox="0 0 14 14" fill="none"
		     xmlns="http://www.w3.org/2000/svg">
			<path opacity="0.6"
			      d="M12.5319 9.00262H1.19189M12.5319 0.755951H1.19189M9.95462 13.126H4.28462M9.95462 4.87928H4.28462"
			      stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
			      stroke-linejoin="round"/>
		</svg>`,
    // language=HTML
    smartphone: `
		<svg width="12" height="19" viewBox="0 0 12 19" fill="none"
		     xmlns="http://www.w3.org/2000/svg">
			<path
				d="M8.74288 15.776C9.15709 15.776 9.49288 15.4402 9.49288 15.026C9.49288 14.6117 9.15709 14.276 8.74288 14.276V15.776ZM3.54739 14.276C3.13318 14.276 2.79739 14.6117 2.79739 15.026C2.79739 15.4402 3.13318 15.776 3.54739 15.776V14.276ZM8.74288 1.48749V2.23749V1.48749ZM3.54739 1.48749L3.54739 0.737488L3.54739 1.48749ZM1.23828 15.0259H1.98828H1.23828ZM1.23828 3.94903H0.488281H1.23828ZM8.74286 17.4875V16.7375V17.4875ZM3.54739 17.4875V18.2375V17.4875ZM11.052 15.026L11.802 15.026L11.052 15.026ZM11.052 3.94903L10.302 3.94903L11.052 3.94903ZM8.74288 0.737488L3.54739 0.737488L3.54739 2.23749L8.74288 2.23749V0.737488ZM1.98828 15.0259L1.98828 3.94903H0.488281L0.488281 15.0259H1.98828ZM8.74286 16.7375H3.54739V18.2375H8.74286V16.7375ZM11.802 15.026L11.802 3.94903L10.302 3.94903L10.302 15.026L11.802 15.026ZM8.74286 18.2375C10.4768 18.2375 11.802 16.7538 11.802 15.026L10.302 15.026C10.302 16.0171 9.55949 16.7375 8.74286 16.7375V18.2375ZM8.74288 2.23749C9.55951 2.23749 10.302 2.95789 10.302 3.94903L11.802 3.94903C11.802 2.22123 10.4768 0.737488 8.74288 0.737488V2.23749ZM3.54739 0.737488C1.81345 0.737488 0.488281 2.22123 0.488281 3.94903L1.98828 3.94903C1.98828 2.95788 2.73076 2.23749 3.54739 2.23749L3.54739 0.737488ZM0.488281 15.0259C0.488281 16.7537 1.81345 18.2375 3.54739 18.2375V16.7375C2.73076 16.7375 1.98828 16.0171 1.98828 15.0259H0.488281ZM3.54739 15.776H8.74288V14.276H3.54739V15.776Z"
				fill="currentColor"/>
		</svg>`,

    // language=HTML
    desktop: `
		<svg width="18" height="19" viewBox="0 0 18 19" fill="none"
		     xmlns="http://www.w3.org/2000/svg">
			<path
				d="M15.2702 13.7952V13.0452V13.7952ZM2.57008 13.7952V14.5452H2.57008L2.57008 13.7952ZM16.4247 2.71826L17.1747 2.71826L16.4247 2.71826ZM16.4247 12.5644H15.6747H16.4247ZM1.41553 2.71827H0.665527H1.41553ZM1.41553 12.5644H2.16553H1.41553ZM15.2702 1.48749V2.23749H15.2702L15.2702 1.48749ZM2.57008 1.4875L2.57008 0.737501L2.57008 1.4875ZM16.4247 11.4683C16.8389 11.4683 17.1747 11.1325 17.1747 10.7183C17.1747 10.304 16.8389 9.96826 16.4247 9.96826V11.4683ZM1.41553 9.96826C1.00131 9.96826 0.665527 10.304 0.665527 10.7183C0.665527 11.1325 1.00131 11.4683 1.41553 11.4683L1.41553 9.96826ZM12.3838 18.2375C12.798 18.2375 13.1338 17.9017 13.1338 17.4875C13.1338 17.0733 12.798 16.7375 12.3838 16.7375V18.2375ZM5.45646 16.7375C5.04225 16.7375 4.70646 17.0733 4.70646 17.4875C4.70646 17.9017 5.04225 18.2375 5.45646 18.2375V16.7375ZM5.86102 17.4875C5.86102 17.9017 6.19681 18.2375 6.61102 18.2375C7.02523 18.2375 7.36102 17.9017 7.36102 17.4875H5.86102ZM7.36102 13.7952C7.36102 13.381 7.02523 13.0452 6.61102 13.0452C6.19681 13.0452 5.86102 13.381 5.86102 13.7952H7.36102ZM10.4792 17.4875C10.4792 17.9017 10.815 18.2375 11.2292 18.2375C11.6434 18.2375 11.9792 17.9017 11.9792 17.4875H10.4792ZM11.9792 13.7952C11.9792 13.381 11.6434 13.0452 11.2292 13.0452C10.815 13.0452 10.4792 13.381 10.4792 13.7952H11.9792ZM15.2702 13.0452L2.57008 13.0452L2.57008 14.5452L15.2702 14.5452V13.0452ZM15.6747 2.71826L15.6747 12.5644H17.1747L17.1747 2.71826L15.6747 2.71826ZM0.665527 2.71827L0.665528 12.5644H2.16553L2.16553 2.71827H0.665527ZM15.2702 0.737488L2.57008 0.737501L2.57008 2.2375L15.2702 2.23749L15.2702 0.737488ZM17.1747 2.71826C17.1747 1.67019 16.3665 0.737486 15.2702 0.737488L15.2702 2.23749C15.4492 2.23749 15.6747 2.40685 15.6747 2.71826L17.1747 2.71826ZM2.16553 2.71827C2.16553 2.40686 2.39109 2.2375 2.57008 2.2375L2.57008 0.737501C1.47378 0.737502 0.665527 1.67021 0.665527 2.71827H2.16553ZM2.57008 13.0452C2.39109 13.0452 2.16553 12.8758 2.16553 12.5644H0.665528C0.665528 13.6125 1.47378 14.5452 2.57008 14.5452L2.57008 13.0452ZM15.2702 14.5452C16.3665 14.5452 17.1747 13.6125 17.1747 12.5644H15.6747C15.6747 12.8758 15.4492 13.0452 15.2702 13.0452V14.5452ZM16.4247 9.96826H1.41553L1.41553 11.4683H16.4247V9.96826ZM12.3838 16.7375H5.45646V18.2375H12.3838V16.7375ZM7.36102 17.4875V13.7952H5.86102V17.4875H7.36102ZM11.9792 17.4875V13.7952H10.4792V17.4875H11.9792Z"
				fill="#0075FF"/>
		</svg>`,
    // language=HTML
    campaign: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<defs/>
			<path
				d="M432.7 80.3H303.1v-1A63.3 63.3 0 00240 16H94.3V15a15 15 0 00-30 0v482a15 15 0 0030 0V255h98.5v81.3a15 15 0 0015 15h225a15 15 0 0015-15v-241a15 15 0 00-15-15zM94.3 46.1h145.6a33.2 33.2 0 0133.2 33.2v155c-9.6-6-21-9.4-33.2-9.4H94.3zm145.6 275.2h-17.1V255h17.1a33.2 33.2 0 010 66.4zm177.8 0h-124c6-9.6 9.4-21 9.4-33.2V110.3h114.6z"/>
		</svg>`,
    //language=HTML
    open_email: `
		<svg viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path
				d="M3.334 10.017l-.447-.894a1 1 0 00-.553.894h1zm28.333 0h1a1 1 0 00-.553-.894l-.447.894zM17.501 2.934l.447-.895-.447-.223-.448.223.448.895zm0 14.166l-.448.895.448.224.447-.224-.447-.895zm13.166 11.75H4.334v2h26.333v-2zm0-18.833v18.834h2V10.017h-2zM4.334 28.851V10.017h-2v18.834h2zm-.553-17.94l14.167-7.083-.895-1.789L2.887 9.123l.894 1.789zm13.272-7.083l14.167 7.084.895-1.79L17.947 2.04l-.895 1.79zM31.22 9.123l-14.167 7.083.895 1.789 14.166-7.083-.894-1.79zm-13.272 7.083L3.78 9.123l-.894 1.789 14.166 7.083.895-1.789zM4.334 28.851h-2a2 2 0 002 2v-2zm26.333 2a2 2 0 002-2h-2v2z"
				fill="currentColor"/>
		</svg>`,
    //language=HTML
    link_click: `
		<svg viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M8.594 4.671v22.305l5.329-5.219 3.525 8.607 3.278-1.23-3.688-8.718h7.14L8.593 4.67z"
			      stroke="currentColor" stroke-width="2"/>
		</svg>`,
    //language=HTML
    login: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<path
				fill="currentColor"
				d="M260.52 329.539a24 24 0 0 0 33.941 33.941l90.51-90.51a24 24 0 0 0 0-33.941l-90.51-90.509a24 24 0 0 0-33.941 0 24 24 0 0 0 0 33.941L310.059 232H48a24 24 0 0 0-24 24 24 24 0 0 0 24 24h262.059z"/>
			<path
				fill="currentColor"
				d="M448 24H224a40 40 0 0 0-40 40v32a24 24 0 0 0 48 0V72h208v368H232v-24a24 24 0 0 0-48 0v32a40 40 0 0 0 40 40h224a40 40 0 0 0 40-40V64a40 40 0 0 0-40-40z"/>
		</svg>`,
    //language=HTML
    logout: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<path
				fill="currentColor"
				d="m480.971 239.029-90.51-90.509a24 24 0 0 0-33.942 0 24 24 0 0 0 0 33.941L406.059 232H144a24 24 0 0 0-24 24 24 24 0 0 0 24 24h262.059l-49.54 49.539a24 24 0 0 0 33.942 33.941l90.51-90.51a24 24 0 0 0 0-33.941z"/>
			<path
				fill="currentColor"
				d="M304 392a24 24 0 0 0-24 24v24H72V72h208v24a24 24 0 0 0 48 0V64a40 40 0 0 0-40-40H64a40 40 0 0 0-40 40v384a40 40 0 0 0 40 40h224a40 40 0 0 0 40-40v-32a24 24 0 0 0-24-24z"/>
		</svg>`,
    //language=HTML
    wp_fusion: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 38 39">
			<g id="Landing-Page" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
				<g id="Landing-Page-(Type-Outlined)" transform="translate(-115 -38)">
					<g id="Section---Splash" transform="translate(106 -602)">
						<g id="Logo-(Larger)" transform="translate(9 639.05)">
							<g id="Mark" transform="translate(0 .5)">
								<path d="M8 .5h30v30a8 8 0 0 1-8 8H0v-30a8 8 0 0 1 8-8Z" id="BG" fill="#E55B10"/>
								<path
									d="M31 15.5a1.5 1.5 0 0 1-1.5 1.5l-12-.001V29a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5V15a1.5 1.5 0 0 1 .719-1.28A1.5 1.5 0 0 1 15.5 13h14a1.5 1.5 0 0 1 1.5 1.5v1Z"
									id="Path" fill="#FFF"/>
								<path
									d="M8 23a1.5 1.5 0 0 1 1.5-1.5l12 .001V9.5A1.5 1.5 0 0 1 23 8h1a1.5 1.5 0 0 1 1.5 1.5v14a1.5 1.5 0 0 1-.719 1.28 1.5 1.5 0 0 1-1.281.72h-14A1.5 1.5 0 0 1 8 24v-1Z"
									fill="#FFF"/>
							</g>
						</g>
					</g>
				</g>
			</g>
		</svg>`,
    //language=HTML
    funnel: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512"
		     xml:space="preserve"><path fill="currentColor" d="M509.978 23.47A15.013 15.013 0 0 0 497 16H15a15.014 15.014 0 0 0-12.979 7.471 15.01 15.01 0 0 0-.043 14.97L50.153 121h411.694l48.175-82.559a15.012 15.012 0 0 0-.044-14.971zM127.295 256l42.856 75h171.698l42.856-75zM67.295 151l42.858 75h291.694l42.858-75zM181 361v120c0 8.291 6.709 15 15 15h120c8.291 0 15-6.709 15-15V361H181z"/></svg>`

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
    addMediaToBasicTinyMCE,
    isValidEmail,
    loadingModal,
    savingModal,
    confirmationModal,
    dangerConfirmationModal,
    uuid,
    modal,
    miniModal,
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
    bold,
    spinner,
    isNumeric,
  }

})(jQuery)