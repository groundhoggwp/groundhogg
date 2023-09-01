(function ($) {

  var $doc = $(document)

  const { sprintf, __, _x, _n } = wp.i18n

  function insertAtCursor (field, value) {
    //IE support
    if (document.selection) {
      field.focus()
      var sel = document.selection.createRange()
      sel.text = value
    }
    //MOZILLA and others
    else if (field.selectionStart || field.selectionStart == '0') {
      var startPos = field.selectionStart
      var endPos = field.selectionEnd
      field.value = field.value.substring(0, startPos)
        + value
        + field.value.substring(endPos, field.value.length)

      field.selectionStart = startPos + value.length
      field.selectionEnd = startPos + value.length
    } else {
      field.value += value
    }

    let input = new Event('input')
    let change = new Event('change')

    // Trigger input & change event
    field.dispatchEvent(input)
    field.dispatchEvent(change)
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
      $doc.on('focus', 'input:not(.no-insert), textarea:not(.no-insert)',
        function () {
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
      if (typeof tinymce != 'undefined' && tinymce.activeEditor != null &&
        this.to_mce) {
        tinymce.activeEditor.execCommand('mceInsertContent', false, text)
        // INSERT REGULAR TEXT INPUT.
      }

      if (this.active != null && !this.to_mce) {

        insertAtCursor(this.active, text)

        return this.active
      }
    },

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
    position = 'bottom',
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

    return `<${tag} ${objectToProps(atts)}>${Array.isArray(content)
      ? content.join('')
      : content}</${tag}>`
  }

  const stepNav = ({
    labels,
    currentStep,
  }) => {

    const stepNum = (label, num) => {
      // language=HTML
      return `
		  <div data-step="${num}"
		       class="gh-step-nav-step-num ${num === currentStep
			       ? 'current'
			       : ''}">
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
			${labels.map((l, i) => stepNum(l, i)).
			join(`<hr class="gh-step-nav-join"/>`)}
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
        step: this.currStep,
      })

      //language=HTML
      const html = `
		  <div class="step-nav-handler">
			  ${showNav ? stepNav({
				  labels,
				  currentStep: this.currStep,
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
        setStep,
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
      setStep,
    }
  }

  const breadcrumbs = (parts) => {
    return parts.map(
      (p, i) => i < parts.length - 1
        ? `<span class="part">${p}</span>`
        : `<span class="base">${p}</span>`).
    join(`<span class="sep">/</span>`)
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
    return sprintf(
      _x('%s and %s', 'and preceding the last item in a list', 'groundhogg'),
      array.slice(0, -1).join(', '), array[array.length - 1])
  }

  function orList (array) {
    if (!array || array.length === 0) {
      return ''
    }
    if (array.length === 1) {
      return array[0]
    }
    return sprintf(
      _x('%s or %s', 'or preceding the last item in a list', 'groundhogg'),
      array.slice(0, -1).join(', '),
      array[array.length - 1])
  }

  const progressBar = (selector) => {

    // language=HTML
    const html = `
		<div class="gh-progress-bar">
			<div class="gh-progress-bar-fill">
				<span class="fill-amount">0%</span>
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

      // cap at 100
      if (progress >= 100) {
        progress = 100
      }

      if (progress === 100) {
        $bar.addClass('complete')
      } else {
        $bar.removeClass('complete')
      }

      $fill.css({
        width: progress + '%',
      }).html(`<span class="fill-amount">${Math.ceil(progress)}%</span>`)
    }

    return {
      setProgress,
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
        args: args,
      })
      return this.fills.filter(fill => fill.slot === slotName).
      map(fill => fill.render(...args)).
      join('')
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
          ...component,
        },
      })
    },
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

    return string.replace(/&/g, '&amp;').
    replace(/>/g, '&gt;').
    replace(/</g, '&lt;').
    replace(/"/g, '&quot;')
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
        let val = object[prop]

        if (typeof val === 'undefined' || val === null || val === '' || val === 'null') {
          continue
        }

        // val = specialChars(val)

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

    if (props.length) {
      return props.join(';') + ';'
    }

    return ''
  }

  function uuid () { // Public Domain/MIT
    return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c =>
      (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(
        16),
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
      if (object.hasOwnProperty(prop) && typeof object[prop] !== 'undefined' &&
        object[prop] !== false) {

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
    toggle ({
      id,
      name,
      className,
      value = '1',
      onLabel = 'on',
      offLabel = 'off',
      checked,
      ...props
    }) {
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
        ...props,
      }

      return `<input ${objectToProps(props)}/>`
    },
    select (_props, _options = {}, _selected = false) {

      let { options = _options, selected = _selected, value, ...props } = _props

      if (value && !selected) {
        selected = value
      }

      return `<select ${objectToProps(props)}>${createOptions(options,
        selected)}</select>`
    },
    option: function (value, text, selected) {
      //language=HTML
      return `
		  <option value="${specialChars(value)}" ${selected ? 'selected' : ''}>
			  ${text}
		  </option>`
    },
    mappableFields (props, selected) {
      return Elements.select(props, {
        '': '- Do not map -',
        ...Groundhogg.fields.mappable,
      }, selected)
    },
    textarea (props) {
      return `<textarea ${objectToProps(
        Object.filter(props, key => key !== 'value'))}>${specialChars(
        props.value || '')}</textarea>`
    },
    inputWithReplacementsAndEmojis (inputProps = {
      type: 'text',
    }, replacements = true, emojis = true) {
      const classList = [
        replacements && 'input-with-replacements',
        emojis && 'input-with-emojis',
      ]
      //language=HTML
      return `
		  <div class="input-wrap ${classList.filter(c => c).join(' ')}">
			  ${Elements.input(inputProps)}
			  ${emojis ? `<button class="emoji-picker-start gh-button dashicon" title="insert emoji"><span class="dashicons dashicons-smiley"></span>
			  </button>` : ''}
			  ${replacements ? `<button type="button" class="replacements-picker-start gh-button dashicon" title="insert replacement"><span
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
      placeholder: '',
    }, replacements = true, emojis = true) => {
      const classList = [
        'textarea-with-buttons',
        replacements && 'textarea-with-replacements',
        emojis && 'textarea-with-emojis',
      ]
      //language=HTML
      return `
		  <div class="${classList.filter(c => c).join(' ')}"
		       xmlns="http://www.w3.org/1999/html">
			  ${Elements.textarea(props)}
			  <div class="buttons">
				  ${replacements ? `<button type="button" class="replacements-picker-start gh-button dashicon" title="insert replacement"><span
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
    },
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

    var editorSettings = wp.codeEditor.defaultSettings ? _.clone(
      wp.codeEditor.defaultSettings) : {}

    editorSettings.codemirror = _.extend(
      {},
      editorSettings.codemirror,
      {
        indentUnit: 4,
        tabSize: 4,
      },
    )

    codeMirror = wp.codeEditor.initialize($(selector),
      editorSettings).codemirror
    // self.htmlCode = self.htmlCode.codemirror;

    codeMirror.on('change', function () {
      onChange(codeMirror.doc.getValue())
    })

    codeMirror.on('focus', function () {
      codeMirrorIsFocused = true
      $doc.trigger('ghClearReplacementTarget')
    })

    codeMirror.doc.setValue(
      html_beautify(initialContent, { indent_with_tabs: true }))

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
        ...config,
      },
    )

    let editor = tinyMCE.get(editor_id)

    editor.on('Change keyup', function (e) {
      onChange(editor.getContent())

      if (e.type == 'keyup' && e.ctrlKey && e.shiftKey && e.which == 219) {

        if (GlobalReplacementsWidget && GlobalReplacementsWidget.isOpen()) {
          return
        }

        editor.execCommand('mceInsertContent', false,
          '<span id="rep-here">{</span>')

        GlobalReplacementsWidget = replacementsWidget({
          target: editor.iframeElement.contentWindow.document.getElementById(
            'rep-here'),
          offset: editor.iframeElement.getBoundingClientRect(),
          onClose: () => {
            editor.dom.remove('rep-here')
          },
        })

        GlobalReplacementsWidget.mount()
      }
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
      height = 200,
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

  const progressModal = ({
    beforeProgress = () => '',
    afterProgress = () => '',
    onOpen = () => {},
    ...rest
  }) => {

    return modal({
      canClose: false,
      width: 500,
      content: `
${beforeProgress()}
<div id="progress-modal"></div>
${afterProgress()}`,
      onOpen: ({ setContent, close }) => {
        const { setProgress } = progressBar('#progress-modal')
        onOpen({ setContent, setProgress, close })
      },
      ...rest,
    })

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

  const spinner = (color = 'white') => {

    // No color when not white labelled
    if (!Groundhogg.isWhiteLabeled) {
      color = ''
    }

    // language=HTML
    return `
		<div class="gh-spinner-wrap">
			<object class="gh-spinner ${color}"
			        data="${Groundhogg.assets.spinner}" type="image/svg+xml"
			        width="150"
			        height="150"/>
		</div>`
  }

  const savingModal = () => {
    return loadingModal('Saving')
  }

  const dangerConfirmationModal = (props) => {
    return confirmationModal({
      ...props,
      confirmButtonType: 'danger',
      cancelButtonType: 'primary text',
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
		<button type="button"
		        class="dashicon-button gh-modal-button-close-top gh-modal-button-close">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
		<div class="gh-modal-dialog-alert">
			${alert}
		</div>
		<div class="gh-modal-confirmation-buttons">
			<button type="button"
			        class="gh-button ${buttonSize} ${cancelButtonType} gh-modal-button-close">
				${closeText}
			</button>
			<button type="button"
			        class="gh-button ${buttonSize} ${confirmButtonType} gh-modal-button-confirm">
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
      ...rest,
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
      $modal,
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
				<button type="button"
				        class="dashicon-button gh-modal-button-close-top gh-modal-button-close">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
				${content}
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
      bottom,
    } = $el[0].getBoundingClientRect()

    $modal.css({
      top: Math.min(bottom, window.innerHeight - $modal.height() - 20) + 'px',
      left: (right - $modal.outerWidth()) + 'px',
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
      setContent,
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
    disableScrolling = true,
  }) => {

    //language=html
    const html = `
		<div class="gh-modal ${className} ${disableScrolling
			? 'disabled-scrolling'
			: ''}">
			${overlay ? `<div class="gh-modal-overlay"></div>` : ''}
			<div class="gh-modal-dialog ${dialogClasses}"
			     style="width: ${width ? width + 'px' : 'fit-content'}">
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

    const maybeMoveHeader = () => {
      $modal.find('.gh-modal-dialog > .gh-header').remove()

      let $header = $modal.find('.gh-modal-dialog-content .gh-header')

      if ($header) {
        $header.insertBefore($modal.find('.gh-modal-dialog-content'))
      }
    }

    const setContent = (content) => {
      $modal.find('.gh-modal-dialog-content').html(content)
      maybeMoveHeader()
      onSetContent()
    }

    $('body').append($modal).addClass(disableScrolling ? 'modal-open' : '')

    maybeMoveHeader()

    onOpen({ close, setContent })

    if (canClose) {
      $modal.find('.gh-modal-overlay, .gh-modal-button-close').
      on('click', handleClose)

    }

    return {
      $modal,
      close,
      setContent,
    }
  }

  const wpErrorDialog = (error, props) => {

    let message

    if (Array.isArray(error)) {
      message = error[0].message
    }

    if (typeof error === 'object' && error.message) {
      message = error.message
    }

    return errorDialog({
      message,
      ...props,
    })
  }

  const errorDialog = (props) => {
    return dialog({
      ...props,
      type: 'error',
    })
  }

  const dialog = ({
    message = '',
    animationDuration = 300,
    ttl = 3000,
    type = 'success',
  }) => {

    const $dialog = $(
      `<div class="gh-dialog gh-dialog-${type}">${message}</div>`)

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
      $dialog,
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
      stop,
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

        if (typeof option !== 'object') {
          option = {
            value: option,
            text: option,
          }
        }

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
    return new RegExp(escapeRegex(str), 'i')
  }

  function escapeRegex (string) {
    return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')
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
    offset = null,
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
    filterOptions = (opts, search) => opts,
  }) => ({
    selector,
    options,
    onInput,
    filterOption,
    renderOption,
    onClose,
    onSelect,
    groups,

    _open: false,
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
						  placeholder: __('Search...', 'groundhogg'),
					  })}
					  <button class="close">
						  <span class="dashicons dashicons-no-alt"></span>
					  </button>
				  </div>
				  <div class="search-options ${this.hasGroups()
					  ? 'has-groups'
					  : 'no-groups'}"></div>
			  </div>
		  </div>`
    },
    getOptions () {
      return filterOptions(this.options.filter((option, i) => {
        if (this.search) {
          return filterOption(option, this.search)
        }

        return true
      }), this.search)
    },
    hasGroups () {
      return Object.keys(groups).length > 0
    },
    renderSearchOptions () {

      const searchOptions = []

      let focusedIndex = 0

      var self = this

      const optionDiv = (option, group, id, index) => {
        return `<div class="option ${index === this.focusedOptionId
          ? 'focused'
          : ''}" data-option="${id}" data-group="${group}">${renderOption(
          option)}</div>`
      }

      if (Object.keys(groups).length > 0) {

        Object.keys(groups).forEach((group, g) => {
          const options = []

          this.getOptions().
          filter(option => option.group === group).
          forEach((option, o) => {
            options.push(optionDiv(option, group, o, focusedIndex))
            focusedIndex++
          })

          if (options.length > 0) {
            searchOptions.push(
              `<div class="option-group" data-group="${group}">${groups[group]}</div>`,
              ...options)
          }

        })

      } else {
        this.getOptions().forEach((option, o) => {
          searchOptions.push(optionDiv(option, null, o, focusedIndex))
          focusedIndex++
        })
      }

      return searchOptions.length
        ? searchOptions.join('')
        : `<div class="no-options">${noOptions}</div>`
    },
    close () {
      this._open = false
      $('.search-options-widget-wrap').remove()
    },
    isOpen () {
      return this._open
    },
    isClosed () {
      return !this._open
    },
    selectOption (optionId, groupId) {
      if (!this.hasGroups()) {
        onSelect(this.getOptions()[optionId])
        this.close()
        onClose()
      } else {
        Object.keys(groups).forEach((group, g) => {
          this.getOptions().
          filter(option => option.group == group).
          forEach((option, o) => {
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

        const optionId = parseInt($(this).data('option'))
        const groupId = $(this).data('group')

        self.selectOption(optionId, groupId)
      })

      const $focused = $(`${selector} .option.focused`)

      let offset

      // Moving down
      if (this.focusedOptionId > this.previousFocusedOptionId) {
        offset = $focused.height() * ($focused.index() + 1)
        if (offset > $options.height()) {
          $options.scrollTop(offset - $options.height())
        }
      }
      // Moving up
      else if (this.focusedOptionId < this.previousFocusedOptionId) {
        offset = $focused.height() * ($focused.index())
        if (offset < $options.scrollTop()) {
          $options.scrollTop(offset)
        }
      }

      this.repositionFixed()
    },
    repositionFixed () {

      if (position !== 'fixed' || !this.$widget) {
        return
      }

      let {
        left, top, right, bottom,
      } = target.getBoundingClientRect()

      if (offset) {
        left += offset.left
        top += offset.top
      }

      this.$widget.css({
        top: top + this.$widget.outerHeight() > window.innerHeight
          ? 'initial'
          : top,
        bottom: top + this.$widget.outerHeight() > window.innerHeight
          ? 5
          : 'initial',
        right: left + this.$widget.outerWidth() > window.innerWidth
          ? 5
          : 'initial',
        left: left + this.$widget.outerWidth() > window.innerWidth
          ? 'initial'
          : left,
      })
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

          const $picker = $(self.render())
          $('body').append($picker)
          this.mountOptions()
          const $widget = $picker.find('.search-options-widget')
          this.$widget = $widget
          this.repositionFixed()
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
      if ((el.getBoundingClientRect().y + $(el).height()) >
        window.innerHeight) {
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
            this.selectOption(parseInt($focused.data('option')),
              $focused.data('group'))

            break
        }
      }

      $('.search-options-widget').on('keydown', handleKeyDown)

      this._open = true

      onOpen(this)
    },
  })

  const replacementsWidget = (more) => {

    const { groups, codes } = Groundhogg.replacements

    return searchOptionsWidget({
      position: 'fixed',
      // filter out hidden codes
      options: Object.values(codes).filter(r => !r.hidden),
      groups,
      filterOption: ({ name, code }, search) => name.match(regexp(search)) ||
        code.match(regexp(search)),
      renderOption: (option) => option.name,
      onSelect: (option) => {
        let el = InsertAtCursor.insert(option.insert)
        $(el).focus()
      },
      ...more,
    })
  }

  let GlobalReplacementsWidget

  $(() => {

    const openWidget = (more) => {
      GlobalReplacementsWidget = replacementsWidget(more)

      GlobalReplacementsWidget.mount()
    }

    window.addEventListener('keyup', e => {
      if (e.ctrlKey && e.shiftKey && e.which == 219) {

        if (Insert.to_mce) {
          return
        }

        let $el = $('<div id="rep-here"></div>')
        $el.insertAfter(e.target)
        openWidget({
          target: document.getElementById('rep-here'),
          onClose: () => {
            $el.remove()
          },
        })
      }
    })

    $doc.on('click', (e) => {
      if (GlobalReplacementsWidget && GlobalReplacementsWidget.isOpen() &&
        !clickedIn(e, '.search-options-widget')) {
        GlobalReplacementsWidget.close()
      }

      if (clickedIn(e, '.replacements-picker-start')) {
        openWidget({
          target: e.target.closest('.replacements-picker-start'),
        })
      }
    })
  })

  const inputRepeater = (selector, {
    rows = [],
    sortable = false,
    cells = [],
    onMount = () => {},
    addRow = () => Array(cells.length).fill(''),
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

      $(`${selector} .add-row`).on('click', (e) => {
        this.rows.push(addRow())
        onChange(this.rows)
        this.mount()
        $(`${selector} .add-row`).focus()
      })

      $(`${selector} [data-cell][data-row]`).on('change', (e) => {
        const row = parseInt(e.target.dataset.row)
        const cell = parseInt(e.target.dataset.cell)
        this.rows[row][cell] = $(e.target).val()
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
            onChange(this.rows)

            this.mount()
          },
        })
      }

      onMount()
    },

    render () {

      const renderRow = (row, rowIndex) => {
        //language=HTML
        return `
			<div class="gh-input-repeater-row" data-row="${rowIndex}">
				${row.map((cell, cellIndex) => cells[cellIndex]({
					value: cell,
					dataRow: rowIndex,
					dataCell: cellIndex,
				}, row)).join('')}
				${sortable ? `<span class="handle" data-row="${rowIndex}"><span
					class="dashicons dashicons-move"></span></span>` : ''}
				<button class="gh-button dashicon remove-row"
				        data-row="${rowIndex}"><span
					class="dashicons dashicons-no-alt"></span></button>
			</div>`
      }

      //language=HTML
      return `
		  <div class="gh-input-repeater">
			  ${this.rows.map((row, i) => renderRow(row, i)).join('')}
			  <div class="gh-input-repeater-row-add">
				  <div class="spacer"></div>
				  <button class="add-row gh-button dashicon">
					  <span class="dashicons dashicons-plus-alt2"></span>
				  </button>
			  </div>
		  </div>`
    },
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
            onChange(this.rows)

            this.mount()
          },
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
				<button class="gh-button dashicon remove-row"
				        data-row="${rowIndex}"><span
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
					  <span class="dashicons dashicons-plus-alt2"></span>
				  </button>
			  </div>
		  </div>`
    },
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
      ...props,
    })
  }

  const secondaryButton = ({ className, ...props }) => {
    return button({
      className: 'gh-button secondary' + (className ? ' ' + className : ''),
      ...props,
    })
  }

  const dangerButton = ({ className, ...props }) => {
    return button({
      className: 'gh-button danger' + (className ? ' ' + className : ''),
      ...props,
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
			...props,
		})}>${text}
		</button>`
  }

  const setFrameContent = (frame, content) => {
    let blob = new Blob([content], { type: 'text/html; charset=utf-8' })
    frame.src = URL.createObjectURL(blob)
  }

  const moreMenu = (selector, args) => {

    let selectHandler = false
    let items = []

    if (Array.isArray(args)) {
      items = args
    } else {
      items = args.items ?? []
      let onSelect = args.onSelect ?? false
      if (onSelect !== false) {
        selectHandler = onSelect
      }
    }

    if (selectHandler === false) {
      selectHandler = (key) => {
        let item = items.find(i => i.key == key)
        const { onSelect = () => {} } = item
        onSelect()
      }
    }

    // language=HTML
    const menu = `
		<div role="menu" class="gh-dropdown-menu" tabindex="0">
			${items.filter(i => i && true).
			map(({
				key,
				text,
			}) => `<div class="gh-dropdown-menu-item" data-key="${key}">${text}</div>`).
			join('')}
		</div>`

    const $menu = $(menu)

    const close = () => {
      $menu.remove()
      // console.log('closed')
    }

    $menu.on('click', '.gh-dropdown-menu-item', (e) => {
      selectHandler(e.currentTarget.dataset.key)
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
      bottom,
    } = $el[0].getBoundingClientRect()

    $menu.css({
      top: Math.min(bottom, window.innerHeight - $menu.height() - 20) + 'px',
      left: (right - $menu.outerWidth()) + 'px',
    })

    $menu.focus()
  }

  const uniqid = () => {
    return Date.now()
  }

  const adminPageURL = (page, params) => {

    params = $.param({
      page,
      ...params,
    })

    return `${Groundhogg.url.admin.replace(/(\/|\\)$/, '')}/admin.php?${params}`
  }

  const icons = {
    // language=HTML
    eye: `
		<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 512 512">
  <path fill="currentColor"
        d="M509 246c-5-6-114-153-253-153S8 240 3 246c-4 6-4 14 0 20 5 6 114 153 253 153s248-147 253-153c4-6 4-14 0-20zM256 385c-103 0-191-97-218-129 27-32 115-129 218-129s191 97 218 129c-27 32-115 129-218 129z"/>
			<path fill="currentColor"
			      d="M256 155a101 101 0 1 0 0 203 101 101 0 0 0 0-203zm0 169a68 68 0 1 1 0-136 68 68 0 0 1 0 136z"/>
</svg>`,
    // language=HTML
    text: `
		<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 333 333">
  <path fill="currentColor"
        d="M323 32H10C5 32 0 36 0 42s5 10 10 10h313a10 10 0 0 0 0-20zm-93 83H10c-5 0-10 4-10 10s5 10 10 10h220a10 10 0 0 0 0-20zm93 84H10c-5 0-10 4-10 10s5 10 10 10h313a10 10 0 0 0 0-20zm-172 83H10c-5 0-10 4-10 10s5 10 10 10h141a10 10 0 0 0 0-20z"/>
</svg>`,
    // language=HTML
    tasks: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<path fill="currentColor"
			      d="M434.929 46.131C424.549 35.729 410.745 30 396.058 30H371.25v-5c0-13.785-11.215-25-25-25h-180c-13.785 0-25 11.215-25 25v5h-24.897c-30.261 0-54.908 24.646-54.942 54.939l-.411 372c-.016 14.702 5.691 28.528 16.07 38.93C87.45 506.271 101.255 512 115.942 512h279.704c30.262 0 54.909-24.646 54.942-54.939l.412-372c.017-14.703-5.691-28.529-16.071-38.93zM171.25 30h170v30h-170zm249.37 427.027C420.604 470.798 409.401 482 395.646 482H115.942c-6.676 0-12.951-2.604-17.669-7.332-4.718-4.729-7.312-11.013-7.305-17.695l.411-372C91.394 71.202 102.597 60 116.353 60h24.897v5c0 13.785 11.215 25 25 25h180c13.785 0 25-11.215 25-25v-5h24.808c6.676 0 12.951 2.604 17.669 7.332s7.313 11.013 7.305 17.695z"/>
			<path fill="currentColor"
			      d="M261.099 200H367.67c8.284 0 15-6.716 15-15s-6.716-15-15-15H261.099c-8.284 0-15 6.716-15 15s6.716 15 15 15zm0 100H367.67c8.284 0 15-6.716 15-15s-6.716-15-15-15H261.099c-8.284 0-15 6.716-15 15s6.716 15 15 15zm107 70h-107c-8.284 0-15 6.716-15 15s6.716 15 15 15h107c8.284 0 15-6.716 15-15s-6.715-15-15-15zM197.256 144.157l-34.592 34.592-8.156-8.157c-5.858-5.858-15.355-5.858-21.213 0-5.858 5.857-5.858 15.355 0 21.213l18.763 18.764a15 15 0 0 0 21.213 0l45.199-45.198c5.858-5.857 5.858-15.355 0-21.213-5.858-5.859-15.355-5.859-21.214-.001zm0 107.637-34.592 34.592-8.156-8.156c-5.858-5.858-15.355-5.858-21.213 0-5.858 5.857-5.858 15.354 0 21.213l18.763 18.764a15 15 0 0 0 21.213 0l45.199-45.199c5.858-5.857 5.858-15.355 0-21.213s-15.356-5.858-21.214-.001zm0 100-34.592 34.592-8.156-8.156c-5.858-5.858-15.355-5.858-21.213 0-5.858 5.857-5.858 15.354 0 21.213l18.763 18.764a15 15 0 0 0 21.213 0l45.199-45.199c5.858-5.857 5.858-15.355 0-21.213s-15.356-5.858-21.214-.001z"/>
		</svg>`,
    // language=HTML
    bell: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 371.263 371.263">
			<path fill="currentColor"
			      d="M305.402 234.794v-70.54c0-52.396-33.533-98.085-79.702-115.151.539-2.695.838-5.449.838-8.204C226.539 18.324 208.215 0 185.64 0s-40.899 18.324-40.899 40.899c0 2.695.299 5.389.778 7.964-15.868 5.629-30.539 14.551-43.054 26.647-23.593 22.755-36.587 53.354-36.587 86.169v73.115c0 2.575-2.096 4.731-4.731 4.731-22.096 0-40.959 16.647-42.995 37.845-1.138 11.797 2.755 23.533 10.719 32.276 7.904 8.683 19.222 13.713 31.018 13.713h72.217c2.994 26.887 25.869 47.905 53.534 47.905s50.54-21.018 53.534-47.905h72.217c11.797 0 23.114-5.03 31.018-13.713 7.904-8.743 11.797-20.479 10.719-32.276-2.036-21.198-20.958-37.845-42.995-37.845a4.704 4.704 0 0 1-4.731-4.731zM185.64 23.952c9.341 0 16.946 7.605 16.946 16.946 0 .778-.12 1.497-.24 2.275-4.072-.599-8.204-1.018-12.336-1.138-7.126-.24-14.132.24-21.078 1.198-.12-.778-.24-1.497-.24-2.275.002-9.401 7.607-17.006 16.948-17.006zm0 323.358c-14.431 0-26.527-10.3-29.342-23.952h58.683c-2.813 13.653-14.909 23.952-29.341 23.952zm143.655-67.665c.479 5.15-1.138 10.12-4.551 13.892-3.533 3.773-8.204 5.868-13.353 5.868H59.89c-5.15 0-9.82-2.096-13.294-5.868-3.473-3.772-5.09-8.743-4.611-13.892.838-9.042 9.282-16.168 19.162-16.168 15.809 0 28.683-12.874 28.683-28.683v-73.115c0-26.228 10.419-50.719 29.282-68.923 18.024-17.425 41.498-26.887 66.528-26.887 1.198 0 2.335 0 3.533.06 50.839 1.796 92.277 45.929 92.277 98.325v70.54c0 15.809 12.874 28.683 28.683 28.683 9.88 0 18.264 7.126 19.162 16.168z"/>
		</svg>`,
    // language=HTML
    backArrow: `
		<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
			<path d="M20 9H3.8l5.6-5.6L8 2l-8 8 8 8 1.4-1.4L3.8 11H20z"/>
		</svg>`,
    drag: `<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle fill="currentColor" cx="8" cy="4" r="2"/><circle fill="currentColor" cx="8" cy="12" r="2"/><circle fill="currentColor" cx="8" cy="20" r="2"/><circle fill="currentColor" cx="16" cy="4" r="2"/><circle fill="currentColor" cx="16" cy="12" r="2"/><circle fill="currentColor" cx="16" cy="20" r="2"/></svg>`,
    // language=html
    image: `
		<svg xmlns="http://www.w3.org/2000/svg"
		     style="enable-background:new 0 0 550.801 550.8"
		     xml:space="preserve"
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
			<path
				d="M14.544 16.539a2.709 2.709 0 11-5.418 0 2.709 2.709 0 015.418 0z"
				stroke="#fff"
				stroke-width="1.5"/>
			<path fill="currentColor" d="M5.619 6.298h9.634v2.846H5.619z"/>
		</svg>`,
    // language=html
    megaphone: `
		<svg width="20" height="20" xmlns="http://www.w3.org/2000/svg"
		     viewBox="0 0 512 512">
			<defs/>
			<path fill="currentColor"
			      d="M343.2 49.7A45.1 45.1 0 00300 82L71.8 130a15 15 0 00-12 14.7v17.1H15a15 15 0 00-15 15V256a15 15 0 0015 15h44.9v21a15 15 0 0011.9 14.8l23.6 5v107.8a42.6 42.6 0 0069.2 33.5 42.5 42.5 0 0016.2-33.5V400h3c34 0 62.8-23.4 70.9-55l45.3 9.5a45.1 45.1 0 0088.3-12.5V94.7a45 45 0 00-45-45zM60 241H30v-49.2h29.9zm91 178.6a12.7 12.7 0 01-15.7 12.4 12.7 12.7 0 01-9.8-12.4V318l25.4 5.4v96.2zm33-49.5h-3v-40.5l44.4 9.4c-5.3 18-21.9 31-41.5 31zm114.3-46.5L89.9 280V157L298.2 113zm60 18.5a15 15 0 01-30 0V94.7a15 15 0 0130 0zM446.3 117a15 15 0 009.5-3.4l30.2-25a15 15 0 00-19.1-23l-30.2 24.8a15 15 0 009.6 26.6zM486 344.2l-30.2-25a15 15 0 00-19 23.2l30 25a15 15 0 0021.2-2 15 15 0 00-2-21.2zM497 201.4h-63.6a15 15 0 000 30H497a15 15 0 000-30z"/>
		</svg>`,
    // language=html
    export: `
		<svg height="20" width="20" xmlns="http://www.w3.org/2000/svg"
		     viewBox="0 0 367 367">
			<defs/>
			<path
				fill="currentColor"
				stroke-width="1"
				d="M363.6 247l.4-.5.5-.7.4-.6.3-.6.4-.7.3-.7.2-.6c0-.3.2-.5.3-.7l.1-.7.2-.8.1-.8.1-.6.1-1.5V236l-.2-.6v-.8l-.3-.8-.1-.7-.3-.7-.2-.6-.3-.7-.4-.7-.3-.6-.4-.6-.5-.7-.4-.5a15 15 0 00-1-1v-.1l-37.5-37.5a15 15 0 00-21.2 21.2l11.9 11.9H270v-78.6-.4a15 15 0 00-3.4-9.5 15.2 15.2 0 00-1-1.2c-.2 0-.3-.2-.4-.4L155.6 23a15 15 0 00-1-.9l-.3-.2a14.9 14.9 0 00-1.9-1.3l-.3-.2-1.1-.6-.5-.1a14.5 14.5 0 00-2.2-.7l-.4-.1-1.2-.2h-1.4l-.3-.1H15a15 15 0 00-15 15v300a15 15 0 0015 15h240a15 15 0 0015-15v-81h45.8l-12 11.9a15 15 0 0021.3 21.2l37.5-37.5 1-1zM160 69.7l58.8 58.8H160V69.7zm80 248.8H30v-270h100v95a15 15 0 0015 15h95v64h-65a15 15 0 000 30h65v66z"/>
		</svg>`,
    // language=html
    share: `
		<svg height="20" width="20" xmlns="http://www.w3.org/2000/svg"
		     viewBox="-33 0 512 512">
			<path fill="currentColor"
			      d="M361.8 344.4a83.6 83.6 0 00-62 27.4l-138-85.4a83.3 83.3 0 000-60.8l138-85.4a83.6 83.6 0 00145.8-56.4 83.9 83.9 0 10-161.9 30.4l-138 85.4A83.6 83.6 0 000 256a83.9 83.9 0 00145.8 56.4l138 85.4a83.9 83.9 0 10161.9 30.4 83.9 83.9 0 00-83.9-83.8zM308.6 83.8a53.3 53.3 0 11106.6.1 53.3 53.3 0 01-106.6-.1zM83.8 309.2a53.3 53.3 0 11.1-106.6 53.3 53.3 0 01-.1 106.6zm224.8 119a53.3 53.3 0 11106.6.1 53.3 53.3 0 01-106.6-.1zm0 0"/>
		</svg>`,
    // language=html
    chart: `
		<svg height="20" width="20" xmlns="http://www.w3.org/2000/svg"
		     viewBox="0 0 510 510">
			<path fill="currentColor"
			      d="M495 420h-14V161.8a15 15 0 00-15-15h-82.2a15 15 0 00-15 15V420h-42.3V75a15 15 0 00-15-15h-82.3a15 15 0 00-15 15v345H172V232.2a15 15 0 00-15-15H74.7a15 15 0 00-15 15V420H30V75a15 15 0 00-30 0v360a15 15 0 0015 15h480a15 15 0 000-30zm-405.3 0V247.2h52.2V420zm154.5 0V90h52.2v330zm154.6 0V176.8H451V420z"/>
		</svg>`,
    // language=html
    folder: `
		<svg class="danger" height="20" width="20"
		     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 520 520">
			<defs/>
			<path fill="currentColor"
			      d="M475 125V90a45 45 0 00-45-45H219.4l-7.9-12.8a15 15 0 00-12.7-7.2H45A45 45 0 000 70v380a45 45 0 0045 45h430a45 45 0 0045-45V170a45 45 0 00-45-45zm-45-50a15 15 0 0115 15v35H268.4l-20-32.8L237.7 75zm60 375a15 15 0 01-15 15H45a15 15 0 01-15-15V70a15 15 0 0115-15h145.3l7.9 12.8 29 47.3 20 32.8A15 15 0 00260 155h215a15 15 0 0115 15v280z"/>
		</svg>`,
    // language=html
    trash: `
		<svg class="danger" height="20" width="20"
		     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
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
		<svg viewBox="38.053 7.279 310.877 351.102"
		     xmlns="http://www.w3.org/2000/svg">
			<g>
				<path
					d="M 348.93 258.42 L 348.93 107.24 C 348.927 98.919 344.486 91.231 337.28 87.07 L 206.35 11.47 C 199.144 7.31 190.266 7.31 183.06 11.47 L 52.13 87.08 C 44.926 91.242 40.489 98.93 40.49 107.25 L 40.49 258.43 C 40.491 266.749 44.927 274.437 52.13 278.6 L 183.06 354.2 C 190.268 358.364 199.152 358.364 206.36 354.2 L 337.28 278.6 C 344.486 274.439 348.927 266.751 348.93 258.43 L 348.93 258.42"
					fill="#ff7b01"/>
				<path
					d="M 88.07 257.31 C 87.492 255.966 86.753 254.696 85.87 253.53 C 85.87 253.53 75.79 259.83 66.65 246.29 C 57.52 232.74 60.35 188.96 60.35 188.96 C 60.35 188.96 42.72 188.33 38.62 171.01 C 34.52 153.69 53.74 137.94 60.98 134.16 C 62.88 118.41 65.71 111.17 65.71 111.17 C 65.71 111.17 45.87 101.72 50.28 73.06 C 54.68 44.4 86.81 29.28 108.54 45.66 C 154.84 11.64 214.37 9.44 257.52 35.58 C 271.38 26.13 290.9 23.93 307.28 42.2 C 323.66 60.46 312.32 83.45 305.71 89.12 C 311.06 97 314.21 107.39 314.21 107.39 C 314.21 107.39 332.47 116.84 337.83 131.96 C 343.18 147.08 327.75 155.26 327.75 155.26 C 327.75 155.26 337.02 180.22 335.94 201.56 C 334.68 226.45 325.54 235.26 317.67 234.32 C 314.71 244.389 309.668 253.724 302.87 261.72 C 309.867 269.053 316.315 280.873 320.091 288.526 L 206.36 354.2 C 199.152 358.364 190.268 358.364 183.06 354.2 L 57.495 281.698 C 65.004 274.649 79.541 261.535 88.07 257.31 Z"
					fill="#fff"/>
				<path
					d="M 114.83 259.62 C 106.396 249.472 99.012 238.496 92.79 226.86 C 92.79 226.86 78.39 242.27 77.09 216.3 C 75.79 190.33 76.41 174.58 76.41 174.58 C 76.41 174.58 49.96 173.95 53.74 163.24 C 57.51 152.54 78.3 137.42 78.3 137.42 C 78.3 137.42 79.56 114.11 85.23 101.52 C 69.48 95.22 63.81 85.14 70.74 68.76 C 77.67 52.38 99.09 48.6 109.17 63.09 C 155.78 25.92 225.7 26.55 259.72 55.53 C 272.32 39.78 288.06 37.89 298.77 53.63 C 309.48 69.39 288.07 85.13 288.07 85.13 L 300.67 113.48 C 300.67 113.48 321.83 126.2 322.27 135.9 C 322.71 145.6 311.37 141.2 311.37 141.2 C 311.37 141.2 323.97 179.62 322.71 200.4 C 321.45 221.2 309.48 213.63 309.48 213.63 C 304.309 230.841 295.5 246.74 283.65 260.25 C 295.286 275.752 301.79 288.694 305.262 297.089 L 206.36 354.2 C 199.152 358.364 190.269 358.364 183.06 354.2 L 70.841 289.404 C 84.305 277.838 99.062 267.842 114.83 259.62 Z M 220.19 58.11 C 201.083 47.078 177.2 60.868 177.2 82.93 C 177.2 93.169 182.663 102.631 191.53 107.75 C 210.637 118.782 234.52 104.992 234.52 82.93 C 234.52 72.691 229.057 63.229 220.19 58.11 Z M 270.205 143.687 C 255.272 134.642 236.147 145.155 235.78 162.61 C 235.603 171.052 240.13 178.894 247.53 182.961 C 262.83 191.372 281.497 180.065 281.13 162.61 C 280.967 154.848 276.845 147.709 270.205 143.687 Z M 165.451 152.781 C 150.809 143.909 132.053 154.213 131.69 171.33 C 131.514 179.612 135.956 187.305 143.216 191.293 C 158.221 199.537 176.523 188.447 176.16 171.33 C 175.999 163.722 171.959 156.725 165.451 152.781 Z M 201.77 248.91 L 228.85 245.13 L 230.74 225.6 L 214.36 219.93 L 196.73 230.01 L 201.77 248.91 Z"
					fill="#cfa756"/>
				<path
					d="M 206.8 128.81 C 135.84 135.52 95.1 149.38 57.3 169.12 C 48.9 166.18 64.44 148.54 78.3 137.41 C 78.14 125.096 80.498 112.88 85.23 101.51 C 85.23 101.51 95.94 101.93 105.18 98.15 C 108.54 86.81 118.2 77.15 118.2 77.15 C 116.262 72.366 113.722 67.85 110.64 63.71 C 123.65 45.24 174.47 26.76 226.54 38.94 C 278.61 51.12 299.19 114.1 299.19 114.1 C 299.19 114.1 323.55 125.03 322.29 139.3 C 304.65 130.9 255.42 124.2 206.8 128.8 L 206.8 128.81 Z M 220.19 58.11 C 201.083 47.079 177.2 60.868 177.2 82.93 C 177.2 93.17 182.662 102.631 191.53 107.751 C 210.636 118.782 234.52 104.993 234.52 82.93 C 234.52 72.691 229.057 63.23 220.19 58.11 Z"
					fill="#ff7b01"/>
				<path
					d="M 265.93 176.4 C 256.207 176.393 250.138 165.864 255.005 157.448 C 259.149 150.281 268.951 148.972 274.83 154.8 C 274.55 154.147 274.23 153.513 273.87 152.9 C 266.83 140.71 249.25 140.71 242.22 152.9 C 235.18 165.08 243.98 180.3 258.04 180.3 C 262.54 180.307 266.882 178.647 270.23 175.64 C 268.852 176.144 267.397 176.401 265.93 176.4 Z M 179.34 172.26 C 179.34 176.88 178.13 181.42 175.81 185.43 C 174.745 187.283 173.463 189.002 171.99 190.55 C 161.95 201.72 150.55 201.68 151.4 201.16 C 152.24 200.64 154.18 199.6 156.36 198.38 C 145.826 199.77 135.496 194.653 130.22 185.43 C 120.09 167.88 132.75 145.93 153.02 145.93 C 167.56 145.93 179.346 157.72 179.34 172.26 Z M 169.68 162.64 C 162.28 149.81 143.76 149.81 136.35 162.64 C 128.95 175.47 138.2 191.51 153.02 191.51 C 158.58 191.51 163.6 189.14 167.12 185.36 C 157.869 190.043 147.017 182.956 147.586 172.602 C 148.156 162.249 159.72 156.394 168.402 162.065 C 168.994 162.452 169.555 162.885 170.08 163.36 L 169.68 162.64 Z M 262.86 189.12 C 258.1 189.66 247.57 186.73 242.15 181.5 C 239.802 179.565 237.823 177.22 236.31 174.58 C 226.65 157.85 238.72 136.93 258.04 136.93 C 277.362 136.928 289.441 157.843 279.781 174.578 C 279.781 174.578 279.78 174.579 279.78 174.58 C 275.375 182.309 267.176 187.095 258.28 187.13 C 261.1 188.21 264.46 188.93 262.86 189.12 Z M 107.78 86.48 C 109.552 83.571 111.719 80.922 114.22 78.61 C 112.64 74.87 107.15 64.09 95.42 60.61 C 81.13 56.36 69.94 76.25 76.31 88.21 C 81.69 98.33 97.28 96.59 103.5 95.57 C 104.558 92.377 105.995 89.322 107.78 86.47 L 107.78 86.48 Z M 126.5 72.77 C 127.85 73.54 119.9 77.71 112.99 88.8 C 108.905 95.066 105.597 101.805 103.14 108.87 C 102.66 110.03 101.69 107.85 101.91 103.67 C 98.55 104.993 95.056 105.948 91.49 106.52 C 89.107 113.495 87.294 120.652 86.07 127.92 C 87.315 127.228 88.565 126.545 89.82 125.87 C 129.34 104.72 157.02 97.97 175.36 95.04 C 166.72 74.14 181.98 50.18 205.53 50.18 C 228.121 50.181 243.845 72.627 236.13 93.86 C 257.59 96.28 276.8 101.22 291.13 106.38 C 286.61 95.31 275.04 72.48 251.42 55.59 C 218.21 31.84 157.78 38.02 127.86 58.1 C 124.296 60.485 120.904 63.118 117.71 65.98 C 119.067 68.538 120.176 71.22 121.02 73.99 C 123.82 72.69 125.92 72.44 126.5 72.77 Z M 66.27 99.22 C 52.37 84.16 61.63 57.13 79.4 50.57 C 90.29 46.55 101.24 48.54 109.52 55.62 C 131.59 37.31 163.95 26.87 194.27 27.02 C 214.45 27.12 238.24 32.08 259.61 46.86 C 262.135 43.148 265.652 40.22 269.76 38.41 C 280.78 33.29 301.86 39.82 306.63 59.19 C 310.05 73.06 300.35 82.96 295.25 87.07 C 299.78 95.328 303.569 103.972 306.57 112.9 C 308.21 113.73 309.67 114.53 310.94 115.3 C 332.31 127.92 331.54 139.25 327.42 143.62 C 326.49 144.62 323.32 146.49 318.09 148.88 C 320.29 155.05 322.91 164.13 325.61 177.35 C 332.56 211.33 321.75 228.57 313 222.91 C 309.179 237.85 301.237 251.415 290.08 262.06 C 299.415 270.914 306.701 281.645 311.487 293.494 L 300.048 300.1 C 292.317 284.891 283.237 274.756 279.26 270.72 C 272.07 275.42 266.11 277.32 264.09 276.45 C 258.69 274.14 268.99 273.37 285.97 248.14 C 302.96 222.91 303.47 201.54 303.47 201.54 C 303.47 201.54 309.66 208.5 311.97 207.47 C 314.29 206.43 318.67 195.37 311.97 169.37 C 306.91 149.71 301.41 139.62 299.03 135.87 C 294.84 134.91 289.88 133.97 284.08 133.16 C 285.13 136 281.18 136.51 278.25 134.36 C 276.705 133.334 275.039 132.505 273.29 131.89 C 265.226 131.138 257.137 130.691 249.04 130.55 C 244.85 132.08 240.98 133.92 238.87 133.59 C 236.97 133.29 237.31 131.77 237.95 130.49 C 228.6 130.58 218.29 130.97 206.95 131.79 C 194.85 132.64 183.54 133.95 172.97 135.56 C 175.11 138.41 174.62 141.57 172.97 141.57 C 171.63 141.57 168.1 138.51 161.31 137.52 C 152.998 139.042 144.74 140.843 136.55 142.92 C 130.8 146.52 129.91 150.09 127.4 149.55 C 125.64 149.17 125.36 147.5 125.53 145.89 C 112.376 149.67 99.46 154.231 86.85 159.55 C 85.98 165.07 84.45 176.45 83.38 193.05 C 81.84 217.25 83.64 231.92 94.19 213.39 C 105.78 241.19 116.28 259.07 139.24 273.11 C 142.28 274.96 132.93 277.59 117.64 267.44 C 99.723 275.954 86.84 285.974 78.473 293.811 L 67.014 287.194 C 78.339 275.482 91.455 265.595 105.9 257.92 C 99.887 252.079 94.418 245.702 89.56 238.87 C 85.18 245.82 71.54 248.4 69.48 220.34 C 68.48 206.58 69.13 192.32 70.07 181.37 C 60.67 180.37 56.24 178.68 52.76 175.81 C 44.49 169.01 45.32 155.31 70.66 137.51 C 71.04 131.93 72.4 120.37 77.32 105.97 C 73.075 104.805 69.244 102.465 66.27 99.22 Z M 282.37 47.87 C 275.18 47.1 270.61 50.93 268.36 53.59 C 276.224 60.357 283.142 68.15 288.93 76.76 C 303.84 67.26 292.63 48.96 282.37 47.86 L 282.37 47.87 Z M 300.91 121.23 C 280.11 110.55 254.27 104.86 231.12 102.9 C 218.62 118.94 194.37 119.43 181.15 104.37 C 161.99 107.01 136.65 112.81 111.19 125.61 C 83.88 139.34 61.92 154.17 60.99 163.35 C 70.51 157.55 96.93 144.13 152.89 132.05 C 222.01 117.11 282.57 123.35 315.8 133.71 C 314.87 129.91 309.89 125.84 300.91 121.23 Z M 228.31 82.75 C 228.31 78.75 227.26 74.82 225.26 71.35 C 216.49 56.17 194.57 56.17 185.8 71.35 C 177.03 86.55 188 105.53 205.53 105.53 C 218.111 105.53 228.31 95.331 228.31 82.75 Z M 250.45 233.72 C 243.75 246.08 232.17 265.9 212.87 267.44 C 193.57 268.99 179.07 254.28 159.58 230.37 C 153.92 223.42 162.68 224.9 164.98 225.48 C 171.632 227.125 178.468 227.905 185.32 227.8 C 198.2 227.4 209.52 215.7 213.64 215.7 C 217.76 215.7 226.51 221.62 231.92 221.62 C 237.32 221.62 252.25 217.76 255.34 216.47 C 258.44 215.19 257.14 221.37 250.45 233.72 Z M 225.73 226.84 C 220.53 225.3 217.05 222.59 214.73 222.98 C 212.42 223.37 202.57 229.93 202.57 229.93 C 202.57 229.93 205.47 242.28 207.2 243.06 C 208.94 243.83 220.33 242.67 221.5 241.9 C 222.65 241.12 225.35 234.17 225.73 226.84 Z M 127.4 302.45 C 125.929 306.131 123.771 312.52 121.732 318.789 L 112.797 313.629 C 122.433 299.766 129.957 296.067 127.4 302.45 Z M 263.32 312.23 C 260.783 307.495 263.381 305.072 272.838 315.812 L 266.875 319.255 C 265.588 316.623 264.338 314.137 263.32 312.24 L 263.32 312.23 Z M 213.64 209.78 C 206.26 211.08 201.37 207.08 198.54 202.62 C 189.54 201.75 185.28 198.37 184.55 194.34 C 183.52 188.67 193.56 182.24 209.78 180.69 C 228.26 178.93 235.26 184.29 235.26 189.19 C 235.26 192.51 232.88 196.43 225.62 199.25 C 224.16 204.71 220.2 208.62 213.64 209.78 Z"/>
				<path
					d="M 348.93 262.44 L 348.93 103.22 C 348.93 97.39 347.28 32.26 342.23 29.34 L 202.88 9.47 C 197.825 6.549 191.595 6.549 186.54 9.47 L 44.92 24.52 C 39.87 27.44 40.49 97.39 40.49 103.22 L 40.49 262.44 C 40.49 268.27 43.6 273.67 48.66 276.59 L 186.54 356.19 C 191.595 359.111 197.825 359.111 202.88 356.19 L 340.76 276.59 C 345.815 273.671 348.93 268.277 348.93 262.44 Z"
					fill="none"/>
			</g>
		</svg>`,
    // language=html
    mailhawk: `
		<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"
		     viewBox="0 0 538.667 132.32">
  <defs>
    <linearGradient id="b" x1="0" x2="1" y1="0" y2="0"
                    gradientTransform="rotate(90 1056.534 -379.692) scale(3301.67)"
                    gradientUnits="userSpaceOnUse" spreadMethod="pad">
      <stop offset="0" stop-color="#c84c1f"/>
	    <stop offset=".691" stop-color="#c7493c"/>
	    <stop offset="1" stop-color="#c7493c"/>
    </linearGradient>
	  <linearGradient id="d" x1="0" x2="1" y1="0" y2="0"
	                  gradientTransform="rotate(90 1056.534 -379.692) scale(3301.67)"
	                  gradientUnits="userSpaceOnUse" spreadMethod="pad">
      <stop offset="0" stop-color="#c84c1f"/>
		  <stop offset=".691" stop-color="#c7493c"/>
		  <stop offset="1" stop-color="#c7493c"/>
    </linearGradient>
	  <linearGradient id="f" x1="0" x2="1" y1="0" y2="0"
	                  gradientTransform="matrix(0 .50743 .50743 0 453.387 649.541)"
	                  gradientUnits="userSpaceOnUse" spreadMethod="pad">
      <stop offset="0" stop-color="#c84c1f"/>
		  <stop offset=".691" stop-color="#c7493c"/>
		  <stop offset="1" stop-color="#c7493c"/>
    </linearGradient>
	  <linearGradient id="h" x1="0" x2="1" y1="0" y2="0"
	                  gradientTransform="matrix(0 .65966 .65966 0 449.145 648.968)"
	                  gradientUnits="userSpaceOnUse" spreadMethod="pad">
      <stop offset="0" stop-color="#c84c1f"/>
		  <stop offset=".691" stop-color="#c7493c"/>
		  <stop offset="1" stop-color="#c7493c"/>
    </linearGradient>
	  <clipPath id="a" clipPathUnits="userSpaceOnUse">
      <path
	      d="m921.758 359.43-65.235 206.679-.085-.031c-22.282 67.192-71.067 118.59-130.481 145.902a242.568 242.568 0 0 1-19.109 7.833c-1.782.64-3.586 1.21-5.383 1.812l-.008-.012-.769-1.101a36.74 36.74 0 0 0-10.243-8.657c.207-2.617.328-5.304.328-8.066 0-1.023-.015-2.043-.05-3.078-.336-10.106-2.504-20.582-8.028-29.469-5.015-8.062-12.726-14.82-24.64-18.586l-1.918-.445c-9.559-2.156-18.754 1.934-29.578 4.957-9.559 2.672-20.383 4.453-33.891.187-20.652-6.515-33.676-21.804-45.57-33.488-6.516-6.402-12.7-11.738-19.602-13.918-10.316-3.258-20.637-1.621-30.336 2.653-8.945 3.945-17.308 10.16-24.691 16.894a149.345 149.345 0 0 0-5.129 4.902 36.715 36.715 0 0 0-4.797-2.183c-4.195-1.551-8.668-2.442-13.289-2.442-4.797 0-9.586.95-14.207 2.579l-1.332.453c-.887-1.266-1.777-2.524-2.637-3.805a234.168 234.168 0 0 1-11.281-18.664 234.917 234.917 0 0 1-9.07-18.648c-21.539-49.665-26.118-107.11-9.106-163.84l.449-1.512 1.028-3.461 87.668-277.742 10.679-33.84 1.598-.602c8.48-3.175 17.047-6.152 25.75-8.73 70.844-20.957 148.48-21.746 224.234 2.164 75.739 23.906 138.84 69.109 184.813 126.926 5.656 7.117 10.973 14.484 16.105 21.965l.957 1.406z"/>
    </clipPath>
	  <clipPath id="c" clipPathUnits="userSpaceOnUse">
      <path
	      d="M801.555 385.355c-9.629-3.945-19.739-5.917-29.867-5.917-10.848 0-21.715 2.253-32.059 6.765-19.91 8.684-35.469 24.66-43.816 45.012a83.977 83.977 0 0 0-5.676 41.973l.754 6.23 5.972 1.895c15.539 4.921 34.282 14.726 52.778 27.617 12.859 8.957 24.675 18.828 34.168 28.554l4.257 4.356 5.782-1.914c22.968-7.598 41.484-24.973 50.793-47.664 17.273-42.114-2.063-90.071-43.086-106.907zm-85.93-117.539-90.098 36.817 1.485 7.832c.5 2.598 12.566 63.816 50.586 75.82h.007c37.969 11.934 83.047-31.191 84.946-33.039l5.711-5.555zm-92.109 153.868c-.012-.289-.731-13.532-8.383-29.442-9.387-19.5-29.235-43.019-71.395-51.719l-3.941 19.098c39.715 8.195 54.898 31.613 60.691 47.414-36.758-9.621-87.578-6.433-90.078-6.269l1.27 19.453c.668-.059 64.308-3.977 95.726 9.711l2.125.886 14.563 7.032zm-92.489 45.988c-62.113 10.266-83.32 65.141-83.32 65.141s56.07-24.45 109.738-21.34c-5.504 11.855-4.468 35.867-4.468 35.867s23.023-4.512 36.714-20.645l.422 33.551s46.555-23.762 27.551-85.414c0 0-35.367-15.633-86.637-7.16zm135.551 89.449c1.949 21.074 18.207 37.984 18.207 37.984s14.637-19.066 16.934-31.929c45.726 28.258 77.601 80.472 77.601 80.472s14.141-57.105-30.824-101.171c-37.113-36.375-75.047-43.879-75.047-43.879-50.953 39.574-26.48 85.757-26.48 85.757zm255.18-197.691-65.235 206.679-.085-.031c-22.282 67.192-71.067 118.59-130.481 145.902a242.568 242.568 0 0 1-19.109 7.833c-1.782.64-3.586 1.21-5.383 1.812l-.008-.012-.769-1.101a36.74 36.74 0 0 0-10.243-8.657c.207-2.617.328-5.304.328-8.066 0-1.023-.015-2.043-.05-3.078-.336-10.106-2.504-20.582-8.028-29.469-5.015-8.062-12.726-14.82-24.64-18.586l-1.918-.445c-9.559-2.156-18.754 1.934-29.578 4.957-9.559 2.672-20.383 4.453-33.891.187-20.652-6.515-33.676-21.804-45.57-33.488-6.516-6.402-12.7-11.738-19.602-13.918-10.316-3.258-20.637-1.621-30.336 2.653-8.945 3.945-17.308 10.16-24.691 16.894a149.345 149.345 0 0 0-5.129 4.902 36.715 36.715 0 0 0-4.797-2.183c-4.195-1.551-8.668-2.442-13.289-2.442-4.797 0-9.586.95-14.207 2.579l-1.332.453c-.887-1.266-1.777-2.524-2.637-3.805a234.168 234.168 0 0 1-11.281-18.664 234.917 234.917 0 0 1-9.07-18.648c-21.539-49.665-26.118-107.11-9.106-163.84l.449-1.512 1.028-3.461 87.668-277.742 10.679-33.84 1.598-.602c8.48-3.175 17.047-6.152 25.75-8.73 70.844-20.957 148.48-21.746 224.234 2.164 75.739 23.906 138.84 69.109 184.813 126.926 5.656 7.117 10.973 14.484 16.105 21.965l.957 1.406z"/>
    </clipPath>
	  <clipPath id="e" clipPathUnits="userSpaceOnUse">
      <path d="m453.621 649.871-.492-.098.516.071z"/>
    </clipPath>
	  <clipPath id="g" clipPathUnits="userSpaceOnUse">
      <path d="m449.934 649.359-1.579.039.899-.125z"/>
    </clipPath>
  </defs>
			<path fill="#c84d1f"
			      d="M171.4 72.828s.832 15.532.832 22.605c-9.639 2.565-15.532-2.498-15.532-2.498s.416-25.93.416-44.444c0-1.178 7.628-4.992 13.867 1.318 4.161 4.577 12.966 18.027 14.284 20.524 1.178-2.914 9.984-17.61 12.966-20.524 2.911-3.397 6.726-5.478 13.382-2.08l1.248 46.524s-8.32 4.577-15.531.347V73.245s-9.153 15.947-12.481 15.947c-4.646 0-13.451-16.364-13.451-16.364m70.72 7.142s-8.807-2.635-11.788.832c-2.495 3.813.348 6.795 4.09 6.795 3.469 0 7.698-1.318 7.698-1.318V79.97zm12.827 14.422s-2.635 2.15-7.28 2.15c-2.427 0-3.398-1.388-5.062-2.566-5.062 2.842-13.174 4.576-20.8.416-7.558-4.368-5.061-15.6-.902-19.067 3.882-3.468 11.51-4.646 21.217-2.565 0 0 .901-7.073-5.963-7.628-6.24-.346-9.153 3.952-13.382 2.15-3.398-1.248-3.398-7.21-3.398-7.21s4.578-2.912 9.222-4.3c6.865-1.665 15.67-2.774 21.981 2.98 4.713 3.954 3.883 11.927 3.465 20.387-.832 10.053.902 15.253.902 15.253m5.616-47.98c0-3.606 3.12-5.41 6.725-5.41 4.16 0 6.795 2.011 6.795 5.687-.278 3.883-3.398 5.685-7.003 5.685-3.675 0-6.517-2.565-6.517-5.962zm13.242 40.422c-.277 4.923-1.317 8.806-5.685 9.36-4.16.486-8.32-1.594-8.32-1.594.485-4.367.763-19.97.763-28.983 0-5.2-.486-8.805-.486-8.805 8.251-3.12 11.371-1.04 13.451.971 1.04 1.317.555 26.694.277 29.05m5.962-9.012c0-8.182.277-31.755.485-32.519.487-1.734.693-3.19 5.893-3.19 4.578 0 7.212 1.735 7.212 1.735s-.486 30.992-.486 37.994c.277 6.657 1.249 11.649 1.249 11.649s-2.496 2.705-6.17 2.705c-5.965.277-7.698-3.398-7.698-3.398-.485-4.02-.485-5.478-.485-14.976"/>
			<path fill="#522e18"
			      d="M300.15 72.483c0-11.51-.415-25.516-.415-25.516s6.448-.832 9.706-.832c3.883 0 5.131.832 6.031 3.814.417 2.98.417 16.085.417 16.085h13.59s.416-12.273.416-15.184c.416-2.15 0-4.715 0-4.715 5.477 0 13.105-1.734 15.67 2.149 1.664 3.328.486 14.422 0 24.199 0 9.706 1.664 18.65-1.249 21.978-2.98 3.467-9.776 1.734-14.421 1.734V88.15c-.416-4.714-.902-7.695-.902-7.695H315.89s0 4.298-.417 8.181c-.484 4.091-1.316 6.656-5.615 7.558-4.09.416-10.122-.416-10.122-.416s.414-11.857.414-23.296m75.434 7.488s-8.805-2.635-11.785.832c-2.498 3.813.345 6.795 4.089 6.795 3.468 0 7.696-1.318 7.696-1.318V79.97zm12.828 14.422s-2.635 2.15-7.28 2.15c-2.428 0-3.397-1.388-5.061-2.566-5.062 2.842-13.175 4.576-20.802.416-7.557-4.368-5.062-15.6-.901-19.067 3.883-3.468 11.51-4.646 21.216-2.565 0 0 .901-7.073-5.963-7.628-6.24-.346-9.15 3.952-13.381 2.15-3.397-1.248-3.397-7.21-3.397-7.21s4.574-2.912 9.221-4.3c6.864-1.665 15.67-2.774 21.979 2.98 4.716 3.954 3.882 11.927 3.468 20.387-.832 10.053.901 15.253.901 15.253m40.767-15.947s4.922-16.433 5.754-19.067c.763-2.358 2.082-3.953 4.508-4.161 2.358 0 6.031 0 9.499 1.248 0 0-11.37 37.788-12.69 38.828-1.594 1.596-10.538 1.596-13.242-.277 0 0-3.675-11.094-4.16-15.6-1.04 4.506-5.061 15.6-5.547 15.877-1.594 1.596-11.372 1.04-13.728-1.317l-10.053-37.51s3.952-1.526 8.667-1.526c3.742 0 5.893 2.843 6.101 4.438.277 1.317 2.981 14.56 3.675 19.621 0 0 5.061-15.6 6.378-22.256 0 0 1.872-1.526 5.27-1.526 3.19 0 6.101 1.526 6.101 1.526.485 5.06 2.912 19.275 3.467 21.702M451.295 43.5s3.674-1.457 8.944-1.457c3.258-.277 4.229.695 4.714 2.22.278 2.218-.206 10.192-.206 15.184-.279 4.784-.764 11.926-.764 11.926s2.565-2.913 4.992-5.41c2.774-2.702 8.736-10.953 11.509-10.953 4.715-.555 9.985 3.951 9.985 3.951-1.733 2.774-13.244 15.254-13.244 15.254s1.735 2.982 3.746 5.408c1.941 2.844 10.469 14.77 10.469 14.77-2.012 1.247-6.241 2.496-8.737 2.218-4.715-.694-6.516-3.676-8.251-5.755-2.495-2.982-5.963-9.221-5.963-9.221l-4.506 4.506s.209 8.251 0 9.013c-.486.486-4.715 1.249-7.696 1.249-3.259 0-5.27-1.525-5.27-1.525l.278-51.379"/>
			<g clip-path="url(#a)"
			   transform="matrix(.13333 0 0 -.13333 0 132.32)">
    <path fill="url(#b)"
          d="m921.758 359.43-65.235 206.679-.085-.031c-22.282 67.192-71.067 118.59-130.481 145.902a242.568 242.568 0 0 1-19.109 7.833c-1.782.64-3.586 1.21-5.383 1.812l-.008-.012-.769-1.101a36.74 36.74 0 0 0-10.243-8.657c.207-2.617.328-5.304.328-8.066 0-1.023-.015-2.043-.05-3.078-.336-10.106-2.504-20.582-8.028-29.469-5.015-8.062-12.726-14.82-24.64-18.586l-1.918-.445c-9.559-2.156-18.754 1.934-29.578 4.957-9.559 2.672-20.383 4.453-33.891.187-20.652-6.515-33.676-21.804-45.57-33.488-6.516-6.402-12.7-11.738-19.602-13.918-10.316-3.258-20.637-1.621-30.336 2.653-8.945 3.945-17.308 10.16-24.691 16.894a149.345 149.345 0 0 0-5.129 4.902 36.715 36.715 0 0 0-4.797-2.183c-4.195-1.551-8.668-2.442-13.289-2.442-4.797 0-9.586.95-14.207 2.579l-1.332.453c-.887-1.266-1.777-2.524-2.637-3.805a234.168 234.168 0 0 1-11.281-18.664 234.917 234.917 0 0 1-9.07-18.648c-21.539-49.665-26.118-107.11-9.106-163.84l.449-1.512 1.028-3.461 87.668-277.742 10.679-33.84 1.598-.602c8.48-3.175 17.047-6.152 25.75-8.73 70.844-20.957 148.48-21.746 224.234 2.164 75.739 23.906 138.84 69.109 184.813 126.926 5.656 7.117 10.973 14.484 16.105 21.965l.957 1.406-33.144 105.008"/>
  </g>
			<path fill="#fff"
			      d="M115.417 69.648c0 6.993-5.717 12.663-12.769 12.663-7.051 0-12.768-5.67-12.768-12.663s5.717-12.662 12.768-12.662c7.052 0 12.769 5.67 12.769 12.662"/>
			<path fill="#522e18"
			      d="M71.918 27.853c7.43-2.344 15.152-2.803 22.605-1.43.238-.423.46-.83.649-1.204.092-.185.108-.814-.241-1.722a6.978 6.978 0 0 0-1.648-2.46c-7.588-1.066-15.38-.437-22.904 1.936A51.791 51.791 0 0 0 46.002 39.39c.01.722.123 1.5.394 2.248.311.865.74 1.371.946 1.454.51.21 1.093.425 1.724.637a46.722 46.722 0 0 1 22.852-15.875M38.826 50.729c-5.3 11.398-6.532 24.726-2.46 37.63l4.88-1.54c-3.579-11.336-2.609-23.036 1.864-33.137l-.09-.034c-1.58-.648-2.99-1.646-4.194-2.92m71.289-24.29a52.998 52.998 0 0 0-3.494-1.642 12.23 12.23 0 0 1-1.069 5.109c.74.338 1.475.694 2.202 1.072 11.137 5.793 19.352 15.577 23.13 27.548l4.88-1.539c-4.19-13.276-13.3-24.124-25.65-30.548"/>
			<path fill="#569ab4"
			      d="M97.833 22.361c-.911-2.36-2.852-4.455-4.831-5.215-2.416-.928-8.893-3.1-16.532-3.1-3.816 0-7.919.54-11.954 2.05-12.108 4.528-18.985 16.06-20.803 19.485-.994 1.874-1.084 4.73-.223 7.107.345.952 1.152 2.642 2.69 3.27a34.327 34.327 0 0 0 4.794 1.536l.182.04a36.914 36.914 0 0 1 2.645-4.023l-.069-.206c-.223-.875-.27-1.771-.096-2.625.278-1.361 1.056-2.502 2.19-3.214a5.012 5.012 0 0 1 2.67-.754c.357 0 .713.048 1.067.117 2.607-3.613 6.535-7.468 12.162-9.245 5.764-1.82 11.32-.842 15.567.673.72-.559 1.578-.912 2.516-.96l.223-.006c1.774 0 3.273 1.076 4.067 2.678l.104.215c.447.133.892.28 1.334.43l.104-.153a33.947 33.947 0 0 0 2.31-3.866c.748-1.484.248-3.289-.117-4.234M61.672 48.025a5.103 5.103 0 0 1-1.771.325 5.71 5.71 0 0 1-1.895-.344l-.177-.06c-.119.169-.237.337-.352.507l.212.012c1.148.062 2.356.074 3.616.027.552-.02 1.119-.06 1.69-.105a19.913 19.913 0 0 1-.683-.653 4.895 4.895 0 0 1-.64.29m-1.255-2.34.069-.01-.003-.003zm-.426.055-.21-.005.12.016z"/>
			<path fill="#f9a92d"
			      d="M91.13 83.028c-2.55.805-4.103 4.75-4.733 7.09l2.336.954 3.536-4.433 5.44 1.6 1.366-2.124c-1.86-1.552-5.386-3.896-7.946-3.087M76.868 29.257h-.007c-.646.043-1.3.127-1.957.24a17.98 17.98 0 0 0-2.396.566 17.961 17.961 0 0 0-3.837 1.733l-.001.001c-2.79 1.658-4.983 3.903-6.642 6.106.284.203.547.438.801.686.276.27.534.563.765.882 3.322-1.83 7.157-3.734 10.374-4.749 3.22-1.017 7.466-1.667 11.244-2.083a5.86 5.86 0 0 1 .437-2.158c-2.226-.726-4.779-1.268-7.486-1.268-.428 0-.86.016-1.295.044M62.255 42.912l-.007-.035a3.806 3.806 0 0 0-.271-.997l-.12-.278a4.27 4.27 0 0 0-.315-.541l-.253-.29c-.107-.13-.21-.263-.328-.376l-.153-.138-.023-.017-.24-.175-.306-.222-.146-.092a3.648 3.648 0 0 0-.712-.298l-.01-.004-.164-.036a3.008 3.008 0 0 0-.592-.085l-.209-.005a2.62 2.62 0 0 0-.428.056l-.096.02-.1.019a2.26 2.26 0 0 0-.574.25 2.17 2.17 0 0 0-.405.332v.001l-.037.039-.012.013a2.27 2.27 0 0 0-.517.926 2.853 2.853 0 0 0-.103.568c-.027.328.011.674.09 1.021a3.892 3.892 0 0 0 .63 1.434l.114.13.048.054c.125.16.254.314.394.452l.025.028.178.157c.173.149.355.28.543.395l.148.093.288.121c.144.064.287.137.433.18l.166.038c.122.03.243.037.364.052l.211.03.015.002.21.005c.145-.005.287-.027.426-.055l.066-.013.133-.026c.199-.059.391-.138.57-.25h.003c.162-.102.305-.221.431-.356.44-.466.647-1.117.639-1.82l-.002-.105-.002-.202m25.614-9.515.019.08.05.198.033.135.08.187.117.266.112.173.147.218.139.145.095.096.002.001.02.02.058.058.16.11.086.055.114.072.177.07.22.075.192.024.277.022.166-.03c.137-.02.27-.055.398-.107h.002c.287-.116.547-.303.772-.547l.242-.299c.207-.302.361-.658.456-1.048.06-.252.1-.513.102-.786l.004-.048v-.007a3.497 3.497 0 0 0-.046-.54l-.028-.115-.014-.058-.084-.331-.078-.185-.117-.268-.11-.17-.15-.222-.137-.143-.176-.176-.163-.112-.196-.125-.182-.072-.215-.074-.198-.025-.271-.02h-.002c-.38.019-.73.173-1.04.41a2.43 2.43 0 0 0-.48.474c-.135.182-.243.39-.338.612a3.174 3.174 0 0 0-.148.412 3.22 3.22 0 0 0-.094.399l-.013.176c-.013.126-.029.25-.028.382 0 .188.017.37.045.546l.023.092"/>
			<path fill="#522e18"
			      d="m59.766 45.731-.21-.03c-.122-.014-.243-.022-.365-.05l-.166-.038c-.146-.044-.289-.117-.433-.181l-.288-.12-.148-.094a4.07 4.07 0 0 1-.543-.395l-.178-.157-.025-.028a4.533 4.533 0 0 1-.394-.452l-.048-.055-.113-.13a3.892 3.892 0 0 1-.63-1.433 3.405 3.405 0 0 1-.091-1.021c.016-.197.05-.387.103-.568a2.27 2.27 0 0 1 .516-.925l.001-.001.009-.009.04-.043a2.17 2.17 0 0 1 .405-.333 2.26 2.26 0 0 1 .573-.25l.1-.02.097-.02a2.62 2.62 0 0 1 .428-.055l.209.005c.196.008.393.036.592.085l.164.036.01.004c.241.072.48.171.712.298l.146.092.306.222.24.175.023.017.153.138c.118.113.22.247.328.376l.253.29c.123.176.224.357.315.54l.12.279c.136.332.232.667.27.997l.008.035.002.202.002.105c.008.703-.199 1.354-.639 1.82-.126.135-.27.254-.431.356h-.004c-.178.112-.37.19-.57.25l-.132.026.003.003-.069.01c-.14.028-.28.05-.426.055l-.09.011-.12-.016zm31.78-11.312-.241.3c-.225.243-.485.43-.772.545l-.002.001c-.128.052-.26.086-.398.107l-.166.03-.277-.022-.193-.024-.219-.076-.177-.069-.114-.072-.086-.055-.16-.11-.058-.058-.02-.02-.002-.001-.095-.096-.139-.145-.147-.218-.112-.173-.116-.266-.08-.187-.034-.135-.05-.199-.02-.08-.022-.091a3.522 3.522 0 0 1-.045-.546c-.001-.131.015-.256.028-.382l.013-.176a3.28 3.28 0 0 1 .094-.4c.04-.143.09-.279.148-.411.095-.221.203-.43.339-.612.14-.187.305-.34.48-.474.308-.237.66-.391 1.04-.41l.272.02.198.025.215.074.182.072.196.125.163.112.176.176.137.143.15.221.11.17.117.269.078.185.084.331.014.058.028.116c.029.173.044.354.045.539v.007l-.003.048a3.484 3.484 0 0 1-.102.786c-.095.39-.249.746-.456 1.048zM62.83 38.59a6.963 6.963 0 0 0-.8-.686c1.658-2.203 3.85-4.448 6.64-6.106l.002-.001a17.961 17.961 0 0 1 3.837-1.733c.801-.253 1.6-.43 2.396-.566.657-.113 1.31-.197 1.957-.24h.007a19.7 19.7 0 0 1 1.295-.044c2.707 0 5.26.542 7.486 1.268a5.86 5.86 0 0 0-.437 2.158c-3.778.416-8.023 1.066-11.244 2.083-3.217 1.015-7.052 2.92-10.374 4.75a6.974 6.974 0 0 0-.765-.883zm-11.856 8.906a34.327 34.327 0 0 1-4.793-1.536c-1.538-.63-2.345-2.32-2.69-3.27-.86-2.378-.771-5.234.223-7.108 1.818-3.426 8.695-14.957 20.803-19.486 4.035-1.509 8.138-2.049 11.954-2.049 7.64 0 14.116 2.172 16.532 3.1 1.979.76 3.92 2.856 4.831 5.215.365.945.865 2.75.118 4.234a33.947 33.947 0 0 1-2.31 3.867l-.105.151c-.442-.15-.887-.296-1.334-.43l-.104-.214c-.794-1.602-2.293-2.678-4.067-2.678l-.223.006c-.938.048-1.797.401-2.516.96-4.246-1.515-9.803-2.493-15.567-.673-5.627 1.777-9.555 5.632-12.162 9.245a5.557 5.557 0 0 0-1.067-.117c-.96 0-1.883.26-2.67.754-1.134.712-1.912 1.853-2.19 3.214-.175.854-.127 1.75.096 2.625l.069.206a36.914 36.914 0 0 0-2.644 4.023zm10.332.997c-1.26.047-2.468.035-3.616-.027l-.212-.012c.115-.17.233-.338.352-.507l.177.06a5.71 5.71 0 0 0 1.895.344 5.103 5.103 0 0 0 2.411-.616c.22.22.447.437.684.653-.572.045-1.14.085-1.69.105zm66.04 30.55-7.78-24.65-.008.003-.018.005c-3.43-9.805-10.484-17.405-19.11-21.739l.1-.147a38.402 38.402 0 0 0 2.065-3.579c1.224-2.428 1.255-5.428.09-8.446-1.452-3.76-4.448-6.901-7.818-8.197-5.193-1.995-18.721-6.094-32.17-1.066C49.25 16.254 41.73 28.228 39.12 33.144c-1.692 3.189-1.892 7.523-.52 11.313 1.101 3.043 3.094 5.286 5.612 6.315.506.206 1.077.418 1.678.63.85.3 1.78.597 2.808.874l.174.046c-2.955 7.549-3.474 16.135-.93 24.618l.001.006 11.72 37.153a51.452 51.452 0 0 0 3.66 1.897 51.252 51.252 0 0 0 3.535 1.485l-1.424-4.512-11.689-37.032-.137-.462-.06-.201c-2.268-7.564-1.657-15.224 1.215-21.846l.185.025c1.002.111 2.062.177 3.15.22.568.022 1.13.05 1.721.05 5.08 0 10.994-.86 17.533-3.307 3.892-1.456 7.245-3.19 10.132-5.057l.256-.06c1.588-.502 2.616-1.403 3.285-2.478a45.003 45.003 0 0 0 3.495-3.048 43.41 43.41 0 0 0 2.136-2.228l.136-.154.001-.002a32.726 32.726 0 0 1 17.398 19.454l.011-.004 8.698 27.557 4.42 14.001a51.319 51.319 0 0 0 2.042-3.246 51.44 51.44 0 0 0 1.911-3.662l-3.928-12.446"/>
			<path fill="#522e18"
			      d="M100.57 71.098a1.093 1.093 0 1 1-.658-2.085 1.093 1.093 0 0 1 .658 2.085zm-.915 7.345c-2.03-.886-3.62-2.52-4.475-4.605a8.597 8.597 0 0 1-.64-3.503 25.37 25.37 0 0 0 2.54-1.137 3.68 3.68 0 0 0 .036 2.083 3.704 3.704 0 1 0 7.066-2.23 3.689 3.689 0 0 0-2.707-2.482 35.997 35.997 0 0 0 4.283-3.492 8.532 8.532 0 0 1 4.456 4.595c1.759 4.29-.183 9.162-4.328 10.861-2 .82-4.214.79-6.231-.09zm6.191-18.113-.77-.255-.568.58c-1.266 1.297-2.841 2.613-4.556 3.808-2.466 1.718-4.965 3.026-7.037 3.682l-.796.253-.1.83a11.18 11.18 0 0 0 .756 5.597c1.113 2.713 3.188 4.843 5.842 6.001a10.67 10.67 0 0 0 4.275.902c1.35 0 2.698-.263 3.982-.789 5.47-2.244 8.048-8.639 5.745-14.254-1.241-3.025-3.71-5.342-6.773-6.355m-8.137 27.909-5.44-1.6-3.536 4.433-2.336-.955c.63-2.34 2.184-6.284 4.732-7.09 2.56-.808 6.086 1.536 7.946 3.088zm-7.362-7.69c-5.07 1.6-6.679 9.763-6.745 10.109l-.198 1.044 12.013 4.91 7.018-10.917-.762-.741c-.253-.247-6.263-5.997-11.326-4.405m-.554-14.709s5.058-1 10.006-5.85c5.996-5.876 4.11-13.49 4.11-13.49s-4.25 6.962-10.346 10.73c-.307-1.715-2.258-4.257-2.258-4.257s-2.168 2.254-2.428 5.064l-2.614-3.631s-3.264 6.158 3.53 11.434m-30.099-4.562s2.828 7.317 11.11 8.686c6.836 1.13 11.551-.955 11.551-.955 2.534-8.22-3.673-11.388-3.673-11.388l-.057 4.473c-1.825-2.151-4.895-2.753-4.895-2.753s-.138 3.202.596 4.783c-7.156.414-14.632-2.846-14.632-2.846m21.293 13.718c-4.189 1.825-12.674 1.303-12.763 1.295l-.17 2.594c.334.021 7.11.446 12.011-.836-.772 2.106-2.797 5.229-8.092 6.322l.525 2.546c5.622-1.16 8.268-4.296 9.52-6.896 1.02-2.121 1.116-3.887 1.117-3.926l.078-2.155-1.942.938-.284.118"/>
			<g clip-path="url(#c)"
			   transform="matrix(.13333 0 0 -.13333 0 132.32)">
    <path fill="url(#d)"
          d="M801.555 385.355c-9.629-3.945-19.739-5.917-29.867-5.917-10.848 0-21.715 2.253-32.059 6.765-19.91 8.684-35.469 24.66-43.816 45.012a83.977 83.977 0 0 0-5.676 41.973l.754 6.23 5.972 1.895c15.539 4.921 34.282 14.726 52.778 27.617 12.859 8.957 24.675 18.828 34.168 28.554l4.257 4.356 5.782-1.914c22.968-7.598 41.484-24.973 50.793-47.664 17.273-42.114-2.063-90.071-43.086-106.907zm-85.93-117.539-90.098 36.817 1.485 7.832c.5 2.598 12.566 63.816 50.586 75.82h.007c37.969 11.934 83.047-31.191 84.946-33.039l5.711-5.555zm-92.109 153.868c-.012-.289-.731-13.532-8.383-29.442-9.387-19.5-29.235-43.019-71.395-51.719l-3.941 19.098c39.715 8.195 54.898 31.613 60.691 47.414-36.758-9.621-87.578-6.433-90.078-6.269l1.27 19.453c.668-.059 64.308-3.977 95.726 9.711l2.125.886 14.563 7.032zm-92.489 45.988c-62.113 10.266-83.32 65.141-83.32 65.141s56.07-24.45 109.738-21.34c-5.504 11.855-4.468 35.867-4.468 35.867s23.023-4.512 36.714-20.645l.422 33.551s46.555-23.762 27.551-85.414c0 0-35.367-15.633-86.637-7.16zm135.551 89.449c1.949 21.074 18.207 37.984 18.207 37.984s14.637-19.066 16.934-31.929c45.726 28.258 77.601 80.472 77.601 80.472s14.141-57.105-30.824-101.171c-37.113-36.375-75.047-43.879-75.047-43.879-50.953 39.574-26.48 85.757-26.48 85.757zm255.18-197.691-65.235 206.679-.085-.031c-22.282 67.192-71.067 118.59-130.481 145.902a242.568 242.568 0 0 1-19.109 7.833c-1.782.64-3.586 1.21-5.383 1.812l-.008-.012-.769-1.101a36.74 36.74 0 0 0-10.243-8.657c.207-2.617.328-5.304.328-8.066 0-1.023-.015-2.043-.05-3.078-.336-10.106-2.504-20.582-8.028-29.469-5.015-8.062-12.726-14.82-24.64-18.586l-1.918-.445c-9.559-2.156-18.754 1.934-29.578 4.957-9.559 2.672-20.383 4.453-33.891.187-20.652-6.515-33.676-21.804-45.57-33.488-6.516-6.402-12.7-11.738-19.602-13.918-10.316-3.258-20.637-1.621-30.336 2.653-8.945 3.945-17.308 10.16-24.691 16.894a149.345 149.345 0 0 0-5.129 4.902 36.715 36.715 0 0 0-4.797-2.183c-4.195-1.551-8.668-2.442-13.289-2.442-4.797 0-9.586.95-14.207 2.579l-1.332.453c-.887-1.266-1.777-2.524-2.637-3.805a234.168 234.168 0 0 1-11.281-18.664 234.917 234.917 0 0 1-9.07-18.648c-21.539-49.665-26.118-107.11-9.106-163.84l.449-1.512 1.028-3.461 87.668-277.742 10.679-33.84 1.598-.602c8.48-3.175 17.047-6.152 25.75-8.73 70.844-20.957 148.48-21.746 224.234 2.164 75.739 23.906 138.84 69.109 184.813 126.926 5.656 7.117 10.973 14.484 16.105 21.965l.957 1.406-33.144 105.008"/>
  </g>
			<g clip-path="url(#e)"
			   transform="matrix(.13333 0 0 -.13333 0 132.32)">
    <path fill="url(#f)" d="m453.621 649.871-.492-.098.516.071z"/>
  </g>
			<g clip-path="url(#g)"
			   transform="matrix(.13333 0 0 -.13333 0 132.32)">
    <path fill="url(#h)" d="m449.934 649.359-1.579.039.899-.125z"/>
  </g>
			<path fill="#522e18"
			      d="M86.099 124.792c-22.138.002-42.723-14.228-49.733-36.433l4.88-1.54c7.8 24.714 34.252 38.473 58.967 30.672 24.712-7.8 38.472-34.253 30.671-58.966l4.88-1.539c8.65 27.403-6.61 56.735-34.012 65.386a51.95 51.95 0 0 1-15.653 2.42"/>
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
    mailhawk_bird: `
		<svg xmlns="http://www.w3.org/2000/svg"
		     xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve"
		     viewBox="0 0 238.5 190.9">
  <defs>
    <clipPath id="a" clipPathUnits="userSpaceOnUse">
      <path d="m636 672-5 1-1 1 6-2m-69 20c-12 9-24 20-35 33l38-23-3-10"/>
    </clipPath>
	  <clipPath id="b" clipPathUnits="userSpaceOnUse">
      <path d="M532 672h104v53H532Z"/>
    </clipPath>
	  <clipPath id="c" clipPathUnits="userSpaceOnUse">
      <path
	      d="m711 647-27 2-46 12-7 12 5-1c24-10 49-18 75-25m-101 21-3 1-4 2 2 7 5-10"/>
    </clipPath>
	  <clipPath id="d" clipPathUnits="userSpaceOnUse">
      <path d="M684 649c-13 1-28 4-44 9l-2 3 46-12m-73 19-4 1 3-1h1"/>
    </clipPath>
	  <clipPath id="e" clipPathUnits="userSpaceOnUse">
      <path d="M640 658c-10 2-19 5-29 10h-1l-5 10 2 6 23-10 1-1 7-12 2-3"/>
    </clipPath>
	  <clipPath id="f" clipPathUnits="userSpaceOnUse">
      <path d="M603 671c-12 6-24 12-36 21l3 10 37-18-2-6-2-7"/>
    </clipPath>
	  <clipPath id="g" clipPathUnits="userSpaceOnUse">
      <path
	      d="M714 647h-3a668 668 0 0 0-179 78l-13 17c58-38 129-70 214-89l-8-5-11-1"/>
    </clipPath>
	  <clipPath id="h" clipPathUnits="userSpaceOnUse">
      <path
	      d="M733 653c-85 19-156 51-214 89l-9 16s5 13 18 33c63-48 147-85 259-102l-54-36"/>
    </clipPath>
	  <clipPath id="i" clipPathUnits="userSpaceOnUse">
      <path
	      d="M787 689a564 564 0 0 0-259 102c32 45 109 122 274 122l61-3c178-19 20-151-76-221"/>
    </clipPath>
  </defs>
			<path fill="#fff" d="m169 126-17 59-92-27 17-59 92 27"/>
			<path fill="#cad9de" d="M61 146s56 17 90-26l-77-20-13 46"/>
			<path fill="#ffb341"
			      d="M194 26s9 5 13 10c4 4 9 13 9 13s3-11-4-18-18-5-18-5"/>
			<path fill="#593514"
			      d="m200 28 8 6c3 2 5 6 7 9 0-4-1-8-4-11s-7-4-11-4zm17 26-3-4s-5-9-9-13l-12-9-5-3 5-1c1 0 13-2 21 5 7 8 4 20 4 20l-1 5"/>
			<path fill="#ffb341"
			      d="M201 29c0 2-2 3-4 3-1-1-2-3-1-5 0-2 2-3 4-3 1 1 2 3 1 5"/>
			<path fill="#593514"
			      d="M199 20c-3 0-6 3-7 6-1 4 0 8 4 10h2c3 0 6-2 7-6 2-4 0-8-4-9l-2-1zm0 4h1c1 1 2 3 1 5 0 2-1 3-3 3h-1c-1-1-2-3-1-5 0-2 2-3 3-3m-38 165h-1l-99-29a1 1 0 0 1-1-2l43-22a1 1 0 0 1 1 2l-39 21 93 27-22-38a1 1 0 1 1 2-2l24 41a1 1 0 0 1-1 2"/>
			<path fill="#593514"
			      d="m82 98 34 58 59-31-93-27m33 61-1-1-36-62a1 1 0 0 1 2-2l99 29a1 1 0 0 1 1 2l-64 34h-1"/>
			<path fill="#593514"
			      d="m64 157 95 27 16-58-94-27-17 58m98 34L58 161l19-68 105 30-20 68M35 102h-2l1-18v9-9c4 0 17-1 24-8 3-3 5-9 5-15l18-1c1 12-3 22-10 29a53 53 0 0 1-36 13"/>
			<path fill="#ffb341"
			      d="M26 78a345 345 0 0 0 7 1l6 3a19 19 0 0 1 6 8 22 22 0 0 1 2 8v5l-1 1v2l-1 2v2l-1 2-1 2c-1 4-6 6-10 4s-5-7-4-11l1-1v-1l1-1 1-2v-2l1-1v-5l-1-1-3-2a15 15 0 0 0-3-1c-3 0-5-3-5-6s2-6 5-6"/>
			<path fill="#593514"
			      d="M26 73c-6 0-10 4-11 11 0 6 4 11 10 12h1a9 9 0 0 1 2 1l-1 1v2l-1 1v1l-1 1v1l-1 1c-3 7 0 15 7 18l5 2c6 0 10-4 13-9v-2a34 34 0 0 0 1-3l1-2v-2l1-1a27 27 0 0 0 0-5v-6l-1-6v-1l-4-6-4-4a26 26 0 0 0-14-5h-3zm0 5a345 345 0 0 0 7 1l6 3a19 19 0 0 1 6 8 21 21 0 0 1 2 8v5l-1 1v2l-1 2v2l-1 2-1 2c-1 3-4 5-7 5l-3-1c-4-2-5-7-4-11l1-1v-1l1-1 1-2v-2l1-1v-5l-1-1-3-2a15 15 0 0 0-3-1c-3 0-5-3-5-6s2-6 5-6"/>
			<path fill="#593514"
			      d="M44 114c-1-3-2-5-4-6-5-3-12 0-12 0l-1-3s8-3 14 0c3 2 5 4 6 8l-3 1M23 92l-1-3 1 1-1-1s4-1 5-4c1-2 0-5-2-8l3-1c2 4 2 7 1 10-2 4-6 6-6 6"/>
			<path fill="#ffb341"
			      d="M35 97h-2l1-7v3-3s18 0 28-10c5-5 7-11 6-20h8c0 11-3 19-9 25a49 49 0 0 1-32 12"/>
			<path fill="#509ab3"
			      d="M229 21c-1 7-17 17-35 14s-30-17-29-24 18-10 35-8c18 3 30 11 29 18"/>
			<path fill="#593514"
			      d="M188 5c-12 0-20 3-21 7-1 5 10 18 27 21 11 1 19-2 23-4 6-3 9-6 10-8 0-3-2-6-6-8-5-3-13-6-21-7l-12-1zm12 33h-6c-20-3-33-19-32-27 2-10 21-13 39-10 9 1 17 4 22 7 6 4 9 9 9 13-1 5-6 10-13 13-6 3-12 4-19 4"/>
			<path fill="#fff"
			      d="M199 24c-20-7-37 3-37 3H33s4 81 90 81c26 0 33-8 33-8h27c23 0 41-19 41-43 0-16-11-29-25-33"/>
			<path fill="#ffb341"
			      d="M199 24c-18-7-37 3-37 3H33s4 79 90 80c26 0 33-9 33-9h27c23 0 41-13 41-38 0-18-11-31-25-36"/>
			<path fill="#c95a00"
			      d="M199 23c-18-6-37 3-37 3L46 25s-9 75 77 75c26 0 33-8 33-8h27c23 0 41-12 41-35 0-16-11-29-25-34"/>
			<g clip-path="url(#a)" transform="matrix(.13333 0 0 -.13333 0 191)">
    <g clip-path="url(#b)">
      <image
	      xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABkAAAAOCAYAAADaOrdAAAAABHNCSVQICAgIfAhkiAAAAEFJREFUOI1j/L/N8z8DIxMDAyMzAwMTM4RGwUyYbCZmBgYGJqh6JhzqETQTAx3AqCWjloxaQpQl/+lhCe3B8LEEADyVBHJ2UKrQAAAAAElFTkSuQmCC"
	      width="1" height="1" image-rendering="optimizeSpeed"
	      preserveAspectRatio="none"
	      transform="matrix(121.2 0 0 -69.2 523 732)"/>
    </g>
  </g>
			<g clip-path="url(#c)" transform="matrix(.13333 0 0 -.13333 0 191)">
    <image
	    xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABoAAAAJCAYAAAAsCDz7AAAABHNCSVQICAgIfAhkiAAAADNJREFUKJFj/H+86D8DIxMDAyMzEmYiQMPY2OSxiDEwMTAx0AkMJYv+08si4sCoRYPfIgBYVASe/DITUgAAAABJRU5ErkJggg=="
	    width="1" height="1" image-rendering="optimizeSpeed"
	    preserveAspectRatio="none"
	    transform="matrix(125.2 0 0 -45.1999 595 684)"/>
  </g>
			<g clip-path="url(#d)" transform="matrix(.13333 0 0 -.13333 0 191)">
    <image
	    xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAAICAYAAAAbQcSUAAAABHNCSVQICAgIfAhkiAAAADdJREFUKJHtjKENACAAw7pxLjdyFBcMAYKgUYSptaLqrSYqBBMZMJNFVBabYND2T48xF/djL8UGNWQQSn2otqMAAAAASUVORK5CYII="
	    width="1" height="1" image-rendering="optimizeSpeed"
	    preserveAspectRatio="none"
	    transform="matrix(92.2 0 0 -40.2 600 679)"/>
  </g>
			<g clip-path="url(#e)" transform="matrix(.13333 0 0 -.13333 0 191)">
    <image
	    xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAJCAYAAAALpr0TAAAABHNCSVQICAgIfAhkiAAAADBJREFUGJXtyrERgEAQgEC0Jwv91uznwORjxwLckOG419UYCWNYjGCh4e4nH/3jqwcWShp+A4i//AAAAABJRU5ErkJggg=="
	    width="1" height="1" image-rendering="optimizeSpeed"
	    preserveAspectRatio="none"
	    transform="matrix(48.2 0 0 -44.2 600 693)"/>
  </g>
			<g clip-path="url(#f)" transform="matrix(.13333 0 0 -.13333 0 191)">
    <image
	    xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAsAAAAKCAYAAABi8KSDAAAABHNCSVQICAgIfAhkiAAAADBJREFUGJXtyrEVABAAxNAwlmVNZww5jafXqKT6RcroLdOgYMKx24FpiFC56M9v5gXlGxyizKafkAAAAABJRU5ErkJggg=="
	    width="1" height="1" image-rendering="optimizeSpeed"
	    preserveAspectRatio="none"
	    transform="matrix(54.2 0 0 -49.2 561 712)"/>
  </g>
			<g clip-path="url(#g)" transform="matrix(.13333 0 0 -.13333 0 191)">
    <image
	    xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC8AAAAXCAYAAACbDhZsAAAABHNCSVQICAgIfAhkiAAAAIJJREFUWIXtlUsOgCAMBR+Po3ll74cLNEFcWBuxVTsJ4VNIhtKEVOapIGXUxtpzm/eN+zEzgLXfxdr97M5SGO9i4GGNUFF0x25GKS9h/AUHyo/ngryPUmn5S+afRPbKTuVlhLwVIW9FyFvRyPv7Qc/4SubfhxN5Xck6kdcR8laEvBULwKEJwhUCjHcAAAAASUVORK5CYII="
	    width="1" height="1" image-rendering="optimizeSpeed"
	    preserveAspectRatio="none"
	    transform="matrix(227.2 0 0 -112.2 513 751)"/>
  </g>
			<g clip-path="url(#h)" transform="matrix(.13333 0 0 -.13333 0 191)">
    <image
	    xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADwAAAAgCAYAAABO6BuSAAAABHNCSVQICAgIfAhkiAAAAEVJREFUWIXtzwENwCAAwDCOMzRfIHcBSb8q2J79rj1+ZN4OOK1hXcO6hnUN6xrWNaxrWNewrmFdw7qGdQ3rGtY1rGtY9wGaoAMyDSMIIQAAAABJRU5ErkJggg=="
	    width="1" height="1" image-rendering="optimizeSpeed"
	    preserveAspectRatio="none"
	    transform="matrix(288.2 0 0 -155.2 504 799)"/>
  </g>
			<g clip-path="url(#i)" transform="matrix(.13333 0 0 -.13333 0 191)">
    <image
	    xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAG0AAAAxCAYAAADQtCfaAAAABHNCSVQICAgIfAhkiAAAATxJREFUeJztmW0OgyAQBZ89WK/cE5btD7+opqk2Kkx5kxgFFjWOCxi75+MeEaFIoYh+U4QiaSpHGurG9ix2jg/1h3k5a89itOwbWl8jtO4fIaVFvbLzpeFYy/56O89Ult7uYdprHZe39fVjXFY3xJ3N7fQrnEnXlb6DImyS1uizqRZ2pjWKpQGxNCCzNM9bGJxpQCwNCFZay58hWGktY2lALA2IpQGxNCCWdiBXLWiZ0hpe7ktUaY1jaUAsDUg90hqfp/ZQj7QNdD+Y/cd3ASXN9PCk/WPq7ORaaaD/Kb8MxVfByzRjaUQsDYil7ab8XGdpQCwNiKUB+SDtgHG71NBffso5HWcaEEsDYmlALA2IpQGxNCBfpTWwgsbhTANSlzSn9SYOlOYnfhV1ZZrZhKUBsTQglgbE0oBYGpAXoeO/nXdjZOgAAAAASUVORK5CYII="
	    width="1" height="1" image-rendering="optimizeSpeed"
	    preserveAspectRatio="none"
	    transform="matrix(524.2 0 0 -236.2 523 919)"/>
  </g>
			<path fill="#593514"
			      d="M35 30c1 6 3 21 12 36 16 26 41 39 76 39h1c23 0 30-7 30-7l1-1h28c12 0 21-3 28-10 7-6 10-17 10-30 0-15-9-29-23-33-19-6-37 5-37 5l-1 1zm89 81h-1c-46 0-69-23-80-42a94 94 0 0 1-14-42v-3h130c4-2 21-12 41-5 15 5 27 21 27 38 0 14-4 26-12 34s-18 11-32 11h-26c-3 3-12 9-33 9"/>
			<path fill="#ffb341" d="M224 61s15 2 11 20h-17s-2-12 6-15v-5"/>
			<path fill="#593514"
			      d="M227 65v3l-2 1c-4 1-5 6-4 9h12c1-4 0-7-2-10l-4-3zm-11 19v-3c-1-5-1-13 5-16v-7l3 1c1 0 8 0 12 6 3 4 3 10 2 17l-1 2h-21"/>
			<path fill="#593514" d="M228 81s-1-7 5-10l3 9-8 1"/>
			<path fill="#fff"
			      d="m200 59-6-4c-2 2-4 4-5 8-1 6 2 11 8 12s11-2 12-8v-6l-9-2"/>
			<path fill="#593514"
			      d="M194 57c-2 1-3 3-4 6-1 5 2 10 7 11s10-2 11-8v-4l-8-2-6-3zm5 20h-2c-7-2-11-8-9-15 0-3 2-6 6-8h1l6 3 8 3h1v1l1 6c-1 6-6 10-12 10m6-30 2-6s3 2 3 6l2-4s6 8-1 13c0 0-6 0-12-4-7-6-6-15-6-15s5 7 12 10"/>
			<path fill="#593514" d="M203 72a4 4 0 1 1 1-8 4 4 0 0 1-1 8"/>
			<path fill="#fff" d="M203 69a1 1 0 1 1 0-3 1 1 0 0 1 0 3"/>
			<path fill="#ffb341" d="M60 31 3 15l1 27h18l2 16s24 0 31-22"/>
			<path fill="#c95a00" d="M60 27 10 14l1 27h18l1 7s29 7 36-16"/>
			<path fill="#593514"
			      d="m22 60-2-14H1L0 11l65 14-2 5L6 18l1 23h18l2 14c5-1 21-4 26-18l5 2c-8 21-33 21-34 21h-2"/>
			<path fill="#c95a00" d="M161 54s21 87-72 85l6-25h-8l7-25h8l8-33"/>
			<path fill="#593514"
			      d="M93 141h-8l7-24h-9l9-31h8l7-31 5 1-8 35h-8l-6 21h8l-6 24c26 0 44-7 56-21 19-24 11-60 11-61l5-1c0 2 9 40-12 66-12 15-32 22-59 22"/>
			<path fill="#593514"
			      d="m118 116-1-2c9-6 13-14 14-20v-1h7c5-10 1-25 1-25l2-1c1 1 5 17-1 28v1h-7c-1 6-5 14-15 20M39 42l-2-1v-7l-21-2v-3l24 3-1 10"/>
			<path fill="#ffb341"
			      d="M199 21s9 5 13 9 9 13 9 13 4-11-3-18c-6-6-19-4-19-4"/>
			<path fill="#593514"
			      d="M206 23c3 1 6 4 7 6l7 8c0-3 0-7-3-10s-7-4-11-4zm15 24-2-3-8-13c-4-4-12-8-13-8l-4-3 5-1c1 0 14-2 21 5s3 19 3 19l-2 4"/>
			<path fill="#ffb341" d="M207 24c-1 2-2 3-4 2l-2-4 4-3c2 1 3 3 2 5"/>
			<path fill="#593514"
			      d="M204 15c-3 0-5 2-6 6-2 4 0 8 4 9l2 1c3 0 6-3 7-6 1-4-1-8-4-9l-3-1zm0 4h1c2 1 3 3 2 5l-3 3-1-1-2-4 3-3"/>
			<path fill="#593514" d="m207 28 28 9s0 10-14 8c0 0-5-12-14-17"/>
</svg>`,
    // language=html
    alignCenter: `
		<svg width="14" height="14" viewBox="0 0 14 14" fill="none"
		     xmlns="http://www.w3.org/2000/svg">
			<path opacity="0.6"
			      d="M12.5319 9.00262H1.19189M12.5319 0.755951H1.19189M9.95462 13.126H4.28462M9.95462 4.87928H4.28462"
			      stroke="currentColor" stroke-width="1.5"
			      stroke-linecap="round"
			      stroke-linejoin="round"/>
		</svg>`,
    // language=html
    alignRight: `
		<svg width="14" height="14" viewBox="0 0 14 14" fill="none"
		     xmlns="http://www.w3.org/2000/svg">
			<path opacity="0.6"
			      d="M12.5319 9.00262H1.19189M12.5319 0.755951H1.19189M9.95462 13.126H4.28462M9.95462 4.87928H4.28462"
			      stroke="currentColor" stroke-width="1.5"
			      stroke-linecap="round"
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
			<path
				d="M8.594 4.671v22.305l5.329-5.219 3.525 8.607 3.278-1.23-3.688-8.718h7.14L8.593 4.67z"
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
    groundhogg_black: `
		<svg viewBox="33.165 18.075 1746.382 313.392"
		     xmlns="http://www.w3.org/2000/svg">
			<g id="shine">
				<g>
					<path
						d="M 498.827 225.159 L 498.575 225.159 C 482.375 247.065 456.709 259.945 429.463 259.841 C 381.963 259.841 343.263 221.389 343.263 173.891 C 343.341 126.316 381.888 87.769 429.463 87.691 C 457.863 87.691 480.983 99.503 496.816 120.865 L 466.909 139.965 C 456.857 130.665 445.799 125.388 429.463 125.388 C 392.221 125.449 369.011 165.803 387.685 198.025 C 403.811 225.852 442.097 230.488 464.4 207.315 L 464.4 190.226 L 414.887 190.226 L 414.887 158.812 L 498.827 158.812 L 498.827 225.159 Z"/>
					<path
						d="M 591.564 124.381 L 591.564 157.3 C 588.914 156.788 586.22 156.536 583.521 156.546 C 564.921 156.546 548.84 170.871 548.84 196.002 L 548.84 255.82 L 510.14 255.82 L 510.14 126.643 L 548.84 126.643 L 548.84 141.722 C 550.599 137.45 557.64 122.873 579.5 122.873 C 583.57 122.848 587.626 123.355 591.564 124.381 Z"/>
					<path
						d="M 723.507 191.735 C 723.444 200.974 721.566 210.111 717.978 218.626 C 714.496 226.804 709.461 234.229 703.151 240.49 C 696.957 246.88 689.514 251.927 681.286 255.318 C 672.808 259.023 663.647 260.907 654.395 260.846 C 645.172 260.87 636.034 259.076 627.504 255.568 C 619.283 252.028 611.785 247.002 605.388 240.741 C 599.133 234.435 594.105 227.021 590.561 218.876 C 587.053 210.346 585.26 201.209 585.283 191.986 C 585.136 182.751 586.934 173.589 590.561 165.095 C 594.039 156.84 599.073 149.331 605.388 142.978 C 611.649 136.668 619.075 131.633 627.253 128.151 C 635.768 124.564 644.905 122.685 654.144 122.622 C 663.396 122.562 672.557 124.445 681.035 128.151 C 689.294 131.507 696.81 136.46 703.151 142.727 C 709.412 149.124 714.438 156.622 717.978 164.843 C 721.448 173.394 723.322 182.509 723.507 191.735 Z M 689.077 191.735 C 688.981 164.94 659.915 148.297 636.758 161.777 C 613.6 175.257 613.72 208.751 636.973 222.066 C 642.277 225.103 648.288 226.69 654.4 226.667 C 673.659 226.685 689.236 210.993 689.077 191.735 Z"/>
					<path
						d="M 733.059 190.478 L 733.059 126.391 L 772.013 126.391 L 772.013 190.478 C 772.013 211.086 779.05 221.892 801.92 221.892 C 818.507 221.892 831.073 208.572 831.32 191.483 C 831.32 191.483 831.82 151.273 831.82 126.391 L 870.774 126.391 L 870.774 255.82 L 831.827 255.82 L 831.827 245.52 C 831.575 245.771 818.255 260.097 797.396 260.097 C 744.368 260.093 733.059 220.887 733.059 190.478 Z"/>
					<path
						d="M 1019.812 191.735 L 1019.812 255.82 L 980.858 255.82 L 980.858 191.735 C 980.858 171.126 973.821 160.32 950.699 160.32 C 934.113 160.32 921.547 173.891 921.299 190.98 L 921.299 255.82 L 881.839 255.82 L 881.839 126.391 L 920.793 126.391 L 920.793 136.691 C 921.044 136.439 934.364 122.115 955.474 122.115 C 1008.5 122.12 1019.812 161.325 1019.812 191.735 Z"/>
					<path
						d="M 1178.9 78.138 L 1178.9 255.82 L 1139.69 255.82 L 1139.69 246.02 C 1127.627 254.82 1113.051 260.345 1097.218 260.345 C 1060.018 260.345 1027.603 228.679 1027.603 190.73 C 1027.603 154.792 1058.263 121.366 1097.218 121.366 C 1112.297 121.366 1126.873 126.392 1139.69 135.692 L 1139.69 78.138 L 1178.9 78.138 Z M 1132.155 190.729 C 1131.962 163.837 1102.73 147.239 1079.538 160.853 C 1056.346 174.466 1056.587 208.08 1079.973 221.358 C 1085.231 224.344 1091.175 225.914 1097.222 225.914 C 1116.592 225.864 1132.242 210.099 1132.151 190.729 L 1132.155 190.729 Z"/>
					<path
						d="M 1333.2 197.514 L 1333.2 255.82 L 1294.5 255.82 L 1294.5 197.514 C 1294.5 178.414 1286.709 165.848 1263.588 165.848 C 1247.001 165.848 1233.43 179.671 1233.179 196.761 L 1233.179 255.82 L 1193.722 255.82 L 1193.722 77.888 L 1233.179 77.888 L 1233.179 139.712 C 1233.43 139.46 1247.001 124.633 1268.112 124.633 C 1321.9 124.633 1333.2 166.853 1333.2 197.514 Z"/>
					<path
						d="M 1480.477 191.735 C 1480.414 200.974 1478.535 210.111 1474.948 218.626 C 1471.465 226.804 1466.43 234.229 1460.12 240.49 C 1453.926 246.88 1446.484 251.927 1438.256 255.318 C 1429.778 259.023 1420.617 260.906 1411.365 260.846 C 1402.142 260.87 1393.004 259.076 1384.474 255.568 C 1376.252 252.029 1368.755 247.002 1362.358 240.741 C 1356.103 234.435 1351.075 227.021 1347.53 218.876 C 1344.023 210.346 1342.229 201.209 1342.252 191.986 C 1342.106 182.751 1343.904 173.589 1347.53 165.095 C 1351.008 156.84 1356.043 149.331 1362.358 142.978 C 1368.619 136.669 1376.044 131.633 1384.222 128.151 C 1392.737 124.564 1401.874 122.685 1411.113 122.622 C 1420.364 122.562 1429.524 124.446 1438 128.151 C 1446.26 131.507 1453.775 136.46 1460.116 142.727 C 1466.377 149.124 1471.404 156.622 1474.944 164.843 C 1478.414 173.394 1480.289 182.508 1480.477 191.735 Z M 1446.046 191.735 C 1445.95 164.941 1416.884 148.299 1393.727 161.779 C 1370.571 175.26 1370.692 208.753 1393.944 222.066 C 1399.247 225.102 1405.255 226.689 1411.365 226.667 C 1430.625 226.687 1446.205 210.995 1446.046 191.735 Z"/>
					<path
						d="M 1630.013 126.391 L 1630.013 252.805 C 1630.013 287.234 1605.635 331.467 1551.35 331.467 C 1527.224 331.467 1508.123 318.9 1502.595 314.629 L 1528.732 285.476 C 1535.394 290.038 1543.275 292.49 1551.35 292.512 C 1572.055 292.434 1589.228 276.465 1590.807 255.82 C 1542.436 280.196 1485.816 243.068 1488.891 188.989 C 1491.009 151.74 1521.832 122.62 1559.141 122.62 C 1570.743 122.478 1582.031 126.389 1591.059 133.678 L 1591.059 126.389 L 1630.013 126.391 Z M 1591.059 192.237 C 1591.059 173.891 1575.979 156.55 1555.874 156.55 C 1528.983 156.753 1512.396 185.991 1526.018 209.178 C 1532.244 219.777 1543.582 226.323 1555.874 226.416 C 1573.718 226.416 1591.059 211.337 1591.059 192.237 L 1591.059 192.237 Z"/>
					<path
						d="M 1779.547 126.391 L 1779.547 252.805 C 1779.547 287.234 1755.169 331.467 1700.884 331.467 C 1676.758 331.467 1657.658 318.9 1652.129 314.629 L 1678.266 285.476 C 1684.928 290.038 1692.809 292.49 1700.884 292.512 C 1721.589 292.434 1738.762 276.465 1740.341 255.82 C 1691.97 280.196 1635.35 243.068 1638.425 188.989 C 1640.543 151.74 1671.366 122.62 1708.675 122.62 C 1720.277 122.478 1731.565 126.389 1740.593 133.678 L 1740.593 126.389 L 1779.547 126.391 Z M 1740.593 192.237 C 1740.593 173.891 1725.513 156.55 1705.408 156.55 C 1678.517 156.753 1661.93 185.991 1675.552 209.178 C 1681.778 219.777 1693.116 226.323 1705.408 226.416 C 1723.252 226.416 1740.593 211.337 1740.593 192.237 L 1740.593 192.237 Z"/>
				</g>
				<g transform="matrix(0.871726, 0, 0, 0.871726, -0.006921, 11.729314)">
					<path
						d="M 348.93 258.42 L 348.93 107.24 C 348.927 98.919 344.486 91.231 337.28 87.07 L 206.35 11.47 C 199.144 7.31 190.266 7.31 183.06 11.47 L 52.13 87.08 C 44.926 91.242 40.489 98.93 40.49 107.25 L 40.49 258.43 C 40.491 266.749 44.927 274.437 52.13 278.6 L 183.06 354.2 C 190.268 358.364 199.152 358.364 206.36 354.2 L 337.28 278.6 C 344.486 274.439 348.927 266.751 348.93 258.43 L 348.93 258.42"
						fill="#ff7b01"/>
					<path
						d="M 88.07 257.31 C 87.492 255.966 86.753 254.696 85.87 253.53 C 85.87 253.53 75.79 259.83 66.65 246.29 C 57.52 232.74 60.35 188.96 60.35 188.96 C 60.35 188.96 42.72 188.33 38.62 171.01 C 34.52 153.69 53.74 137.94 60.98 134.16 C 62.88 118.41 65.71 111.17 65.71 111.17 C 65.71 111.17 45.87 101.72 50.28 73.06 C 54.68 44.4 86.81 29.28 108.54 45.66 C 154.84 11.64 214.37 9.44 257.52 35.58 C 271.38 26.13 290.9 23.93 307.28 42.2 C 323.66 60.46 312.32 83.45 305.71 89.12 C 311.06 97 314.21 107.39 314.21 107.39 C 314.21 107.39 332.47 116.84 337.83 131.96 C 343.18 147.08 327.75 155.26 327.75 155.26 C 327.75 155.26 337.02 180.22 335.94 201.56 C 334.68 226.45 325.54 235.26 317.67 234.32 C 314.71 244.389 309.668 253.724 302.87 261.72 C 309.867 269.053 316.315 280.873 320.091 288.526 L 206.36 354.2 C 199.152 358.364 190.268 358.364 183.06 354.2 L 57.495 281.698 C 65.004 274.649 79.541 261.535 88.07 257.31 Z"
						fill="#fff"/>
					<path
						d="M 114.83 259.62 C 106.396 249.472 99.012 238.496 92.79 226.86 C 92.79 226.86 78.39 242.27 77.09 216.3 C 75.79 190.33 76.41 174.58 76.41 174.58 C 76.41 174.58 49.96 173.95 53.74 163.24 C 57.51 152.54 78.3 137.42 78.3 137.42 C 78.3 137.42 79.56 114.11 85.23 101.52 C 69.48 95.22 63.81 85.14 70.74 68.76 C 77.67 52.38 99.09 48.6 109.17 63.09 C 155.78 25.92 225.7 26.55 259.72 55.53 C 272.32 39.78 288.06 37.89 298.77 53.63 C 309.48 69.39 288.07 85.13 288.07 85.13 L 300.67 113.48 C 300.67 113.48 321.83 126.2 322.27 135.9 C 322.71 145.6 311.37 141.2 311.37 141.2 C 311.37 141.2 323.97 179.62 322.71 200.4 C 321.45 221.2 309.48 213.63 309.48 213.63 C 304.309 230.841 295.5 246.74 283.65 260.25 C 295.286 275.752 301.79 288.694 305.262 297.089 L 206.36 354.2 C 199.152 358.364 190.269 358.364 183.06 354.2 L 70.841 289.404 C 84.305 277.838 99.062 267.842 114.83 259.62 Z M 220.19 58.11 C 201.083 47.078 177.2 60.868 177.2 82.93 C 177.2 93.169 182.663 102.631 191.53 107.75 C 210.637 118.782 234.52 104.992 234.52 82.93 C 234.52 72.691 229.057 63.229 220.19 58.11 Z M 270.205 143.687 C 255.272 134.642 236.147 145.155 235.78 162.61 C 235.603 171.052 240.13 178.894 247.53 182.961 C 262.83 191.372 281.497 180.065 281.13 162.61 C 280.967 154.848 276.845 147.709 270.205 143.687 Z M 165.451 152.781 C 150.809 143.909 132.053 154.213 131.69 171.33 C 131.514 179.612 135.956 187.305 143.216 191.293 C 158.221 199.537 176.523 188.447 176.16 171.33 C 175.999 163.722 171.959 156.725 165.451 152.781 Z M 201.77 248.91 L 228.85 245.13 L 230.74 225.6 L 214.36 219.93 L 196.73 230.01 L 201.77 248.91 Z"
						fill="#cfa756"/>
					<path
						d="M 206.8 128.81 C 135.84 135.52 95.1 149.38 57.3 169.12 C 48.9 166.18 64.44 148.54 78.3 137.41 C 78.14 125.096 80.498 112.88 85.23 101.51 C 85.23 101.51 95.94 101.93 105.18 98.15 C 108.54 86.81 118.2 77.15 118.2 77.15 C 116.262 72.366 113.722 67.85 110.64 63.71 C 123.65 45.24 174.47 26.76 226.54 38.94 C 278.61 51.12 299.19 114.1 299.19 114.1 C 299.19 114.1 323.55 125.03 322.29 139.3 C 304.65 130.9 255.42 124.2 206.8 128.8 L 206.8 128.81 Z M 220.19 58.11 C 201.083 47.079 177.2 60.868 177.2 82.93 C 177.2 93.17 182.662 102.631 191.53 107.751 C 210.636 118.782 234.52 104.993 234.52 82.93 C 234.52 72.691 229.057 63.23 220.19 58.11 Z"
						fill="#ff7b01"/>
					<path
						d="M 265.93 176.4 C 256.207 176.393 250.138 165.864 255.005 157.448 C 259.149 150.281 268.951 148.972 274.83 154.8 C 274.55 154.147 274.23 153.513 273.87 152.9 C 266.83 140.71 249.25 140.71 242.22 152.9 C 235.18 165.08 243.98 180.3 258.04 180.3 C 262.54 180.307 266.882 178.647 270.23 175.64 C 268.852 176.144 267.397 176.401 265.93 176.4 Z M 179.34 172.26 C 179.34 176.88 178.13 181.42 175.81 185.43 C 174.745 187.283 173.463 189.002 171.99 190.55 C 161.95 201.72 150.55 201.68 151.4 201.16 C 152.24 200.64 154.18 199.6 156.36 198.38 C 145.826 199.77 135.496 194.653 130.22 185.43 C 120.09 167.88 132.75 145.93 153.02 145.93 C 167.56 145.93 179.346 157.72 179.34 172.26 Z M 169.68 162.64 C 162.28 149.81 143.76 149.81 136.35 162.64 C 128.95 175.47 138.2 191.51 153.02 191.51 C 158.58 191.51 163.6 189.14 167.12 185.36 C 157.869 190.043 147.017 182.956 147.586 172.602 C 148.156 162.249 159.72 156.394 168.402 162.065 C 168.994 162.452 169.555 162.885 170.08 163.36 L 169.68 162.64 Z M 262.86 189.12 C 258.1 189.66 247.57 186.73 242.15 181.5 C 239.802 179.565 237.823 177.22 236.31 174.58 C 226.65 157.85 238.72 136.93 258.04 136.93 C 277.362 136.928 289.441 157.843 279.781 174.578 C 279.781 174.578 279.78 174.579 279.78 174.58 C 275.375 182.309 267.176 187.095 258.28 187.13 C 261.1 188.21 264.46 188.93 262.86 189.12 Z M 107.78 86.48 C 109.552 83.571 111.719 80.922 114.22 78.61 C 112.64 74.87 107.15 64.09 95.42 60.61 C 81.13 56.36 69.94 76.25 76.31 88.21 C 81.69 98.33 97.28 96.59 103.5 95.57 C 104.558 92.377 105.995 89.322 107.78 86.47 L 107.78 86.48 Z M 126.5 72.77 C 127.85 73.54 119.9 77.71 112.99 88.8 C 108.905 95.066 105.597 101.805 103.14 108.87 C 102.66 110.03 101.69 107.85 101.91 103.67 C 98.55 104.993 95.056 105.948 91.49 106.52 C 89.107 113.495 87.294 120.652 86.07 127.92 C 87.315 127.228 88.565 126.545 89.82 125.87 C 129.34 104.72 157.02 97.97 175.36 95.04 C 166.72 74.14 181.98 50.18 205.53 50.18 C 228.121 50.181 243.845 72.627 236.13 93.86 C 257.59 96.28 276.8 101.22 291.13 106.38 C 286.61 95.31 275.04 72.48 251.42 55.59 C 218.21 31.84 157.78 38.02 127.86 58.1 C 124.296 60.485 120.904 63.118 117.71 65.98 C 119.067 68.538 120.176 71.22 121.02 73.99 C 123.82 72.69 125.92 72.44 126.5 72.77 Z M 66.27 99.22 C 52.37 84.16 61.63 57.13 79.4 50.57 C 90.29 46.55 101.24 48.54 109.52 55.62 C 131.59 37.31 163.95 26.87 194.27 27.02 C 214.45 27.12 238.24 32.08 259.61 46.86 C 262.135 43.148 265.652 40.22 269.76 38.41 C 280.78 33.29 301.86 39.82 306.63 59.19 C 310.05 73.06 300.35 82.96 295.25 87.07 C 299.78 95.328 303.569 103.972 306.57 112.9 C 308.21 113.73 309.67 114.53 310.94 115.3 C 332.31 127.92 331.54 139.25 327.42 143.62 C 326.49 144.62 323.32 146.49 318.09 148.88 C 320.29 155.05 322.91 164.13 325.61 177.35 C 332.56 211.33 321.75 228.57 313 222.91 C 309.179 237.85 301.237 251.415 290.08 262.06 C 299.415 270.914 306.701 281.645 311.487 293.494 L 300.048 300.1 C 292.317 284.891 283.237 274.756 279.26 270.72 C 272.07 275.42 266.11 277.32 264.09 276.45 C 258.69 274.14 268.99 273.37 285.97 248.14 C 302.96 222.91 303.47 201.54 303.47 201.54 C 303.47 201.54 309.66 208.5 311.97 207.47 C 314.29 206.43 318.67 195.37 311.97 169.37 C 306.91 149.71 301.41 139.62 299.03 135.87 C 294.84 134.91 289.88 133.97 284.08 133.16 C 285.13 136 281.18 136.51 278.25 134.36 C 276.705 133.334 275.039 132.505 273.29 131.89 C 265.226 131.138 257.137 130.691 249.04 130.55 C 244.85 132.08 240.98 133.92 238.87 133.59 C 236.97 133.29 237.31 131.77 237.95 130.49 C 228.6 130.58 218.29 130.97 206.95 131.79 C 194.85 132.64 183.54 133.95 172.97 135.56 C 175.11 138.41 174.62 141.57 172.97 141.57 C 171.63 141.57 168.1 138.51 161.31 137.52 C 152.998 139.042 144.74 140.843 136.55 142.92 C 130.8 146.52 129.91 150.09 127.4 149.55 C 125.64 149.17 125.36 147.5 125.53 145.89 C 112.376 149.67 99.46 154.231 86.85 159.55 C 85.98 165.07 84.45 176.45 83.38 193.05 C 81.84 217.25 83.64 231.92 94.19 213.39 C 105.78 241.19 116.28 259.07 139.24 273.11 C 142.28 274.96 132.93 277.59 117.64 267.44 C 99.723 275.954 86.84 285.974 78.473 293.811 L 67.014 287.194 C 78.339 275.482 91.455 265.595 105.9 257.92 C 99.887 252.079 94.418 245.702 89.56 238.87 C 85.18 245.82 71.54 248.4 69.48 220.34 C 68.48 206.58 69.13 192.32 70.07 181.37 C 60.67 180.37 56.24 178.68 52.76 175.81 C 44.49 169.01 45.32 155.31 70.66 137.51 C 71.04 131.93 72.4 120.37 77.32 105.97 C 73.075 104.805 69.244 102.465 66.27 99.22 Z M 282.37 47.87 C 275.18 47.1 270.61 50.93 268.36 53.59 C 276.224 60.357 283.142 68.15 288.93 76.76 C 303.84 67.26 292.63 48.96 282.37 47.86 L 282.37 47.87 Z M 300.91 121.23 C 280.11 110.55 254.27 104.86 231.12 102.9 C 218.62 118.94 194.37 119.43 181.15 104.37 C 161.99 107.01 136.65 112.81 111.19 125.61 C 83.88 139.34 61.92 154.17 60.99 163.35 C 70.51 157.55 96.93 144.13 152.89 132.05 C 222.01 117.11 282.57 123.35 315.8 133.71 C 314.87 129.91 309.89 125.84 300.91 121.23 Z M 228.31 82.75 C 228.31 78.75 227.26 74.82 225.26 71.35 C 216.49 56.17 194.57 56.17 185.8 71.35 C 177.03 86.55 188 105.53 205.53 105.53 C 218.111 105.53 228.31 95.331 228.31 82.75 Z M 250.45 233.72 C 243.75 246.08 232.17 265.9 212.87 267.44 C 193.57 268.99 179.07 254.28 159.58 230.37 C 153.92 223.42 162.68 224.9 164.98 225.48 C 171.632 227.125 178.468 227.905 185.32 227.8 C 198.2 227.4 209.52 215.7 213.64 215.7 C 217.76 215.7 226.51 221.62 231.92 221.62 C 237.32 221.62 252.25 217.76 255.34 216.47 C 258.44 215.19 257.14 221.37 250.45 233.72 Z M 225.73 226.84 C 220.53 225.3 217.05 222.59 214.73 222.98 C 212.42 223.37 202.57 229.93 202.57 229.93 C 202.57 229.93 205.47 242.28 207.2 243.06 C 208.94 243.83 220.33 242.67 221.5 241.9 C 222.65 241.12 225.35 234.17 225.73 226.84 Z M 127.4 302.45 C 125.929 306.131 123.771 312.52 121.732 318.789 L 112.797 313.629 C 122.433 299.766 129.957 296.067 127.4 302.45 Z M 263.32 312.23 C 260.783 307.495 263.381 305.072 272.838 315.812 L 266.875 319.255 C 265.588 316.623 264.338 314.137 263.32 312.24 L 263.32 312.23 Z M 213.64 209.78 C 206.26 211.08 201.37 207.08 198.54 202.62 C 189.54 201.75 185.28 198.37 184.55 194.34 C 183.52 188.67 193.56 182.24 209.78 180.69 C 228.26 178.93 235.26 184.29 235.26 189.19 C 235.26 192.51 232.88 196.43 225.62 199.25 C 224.16 204.71 220.2 208.62 213.64 209.78 Z"/>
					<path
						d="M 348.93 262.44 L 348.93 103.22 C 348.93 97.39 347.28 32.26 342.23 29.34 L 202.88 9.47 C 197.825 6.549 191.595 6.549 186.54 9.47 L 44.92 24.52 C 39.87 27.44 40.49 97.39 40.49 103.22 L 40.49 262.44 C 40.49 268.27 43.6 273.67 48.66 276.59 L 186.54 356.19 C 191.595 359.111 197.825 359.111 202.88 356.19 L 340.76 276.59 C 345.815 273.671 348.93 268.277 348.93 262.44 Z"
						fill="none"/>
				</g>
			</g>
		</svg>`,
    //language=HTML
    wp_fusion: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 38 39">
			<g id="Landing-Page" stroke="none" stroke-width="1" fill="none"
			   fill-rule="evenodd">
				<g id="Landing-Page-(Type-Outlined)"
				   transform="translate(-115 -38)">
					<g id="Section---Splash" transform="translate(106 -602)">
						<g id="Logo-(Larger)" transform="translate(9 639.05)">
							<g id="Mark" transform="translate(0 .5)">
								<path
									d="M8 .5h30v30a8 8 0 0 1-8 8H0v-30a8 8 0 0 1 8-8Z"
									id="BG" fill="#E55B10"/>
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
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
		     style="enable-background:new 0 0 512 512"
		     xml:space="preserve"><path fill="currentColor" d="M509.978 23.47A15.013 15.013 0 0 0 497 16H15a15.014 15.014 0 0 0-12.979 7.471 15.01 15.01 0 0 0-.043 14.97L50.153 121h411.694l48.175-82.559a15.012 15.012 0 0 0-.044-14.971zM127.295 256l42.856 75h171.698l42.856-75zM67.295 151l42.858 75h291.694l42.858-75zM181 361v120c0 8.291 6.709 15 15 15h120c8.291 0 15-6.709 15-15V361H181z"/></svg>`,
    //language=HTML
    hourglass: `
		<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"
		     viewBox="0 0 512 512">
  <path fill="currentColor"
        d="M426.7 103V58A42.5 42.5 0 0 0 448 21.4V10.7c0-6-4.8-10.7-10.7-10.7H74.7C68.7 0 64 4.8 64 10.7v10.6c0 15.8 8.6 29.4 21.3 36.8V103c0 42.3 18 82.7 49.5 111l46.6 42-46.6 42a149.5 149.5 0 0 0-49.5 111v45A42.5 42.5 0 0 0 64 490.6v10.6c0 6 4.8 10.7 10.7 10.7h362.6c6 0 10.7-4.8 10.7-10.7v-10.6c0-15.8-8.6-29.4-21.3-36.8V409c0-42.3-18-82.7-49.5-111l-46.6-42 46.6-42a149.5 149.5 0 0 0 49.5-111zm-78 79.3L284.4 240a21.3 21.3 0 0 0 0 31.8l64.3 57.8A106.9 106.9 0 0 1 384 409v39h-26.7l-92.8-123.7a11 11 0 0 0-17 0L154.7 448H128v-39c0-30.2 12.9-59 35.3-79.3l64.3-57.8a21.3 21.3 0 0 0 0-31.8l-64.3-57.8A106.9 106.9 0 0 1 128 103V64h256v39c0 30.2-12.9 59-35.3 79.3z"/>
			<path fill="currentColor"
			      d="M329.5 149.3h-147a10.7 10.7 0 0 0-8 17.9l74.3 68.5a10.6 10.6 0 0 0 14.4 0l74.2-68.5a10.7 10.7 0 0 0-7.9-17.9z"/>
        </svg>`,
    //language=HTML
    hollerbox: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="75.3 55.7 580.9 152.3">
			<path
				d="M264.4 128h18.1v-23.4h15.2v62.1h-15.2v-24h-18v24h-15.2v-62.1h15.1Zm39 14.8c0-34.3 51.2-34.2 51.2 0s-51.2 34-51.2 0Zm37.2 0c0-16.4-23.2-16.3-23.2 0 0 16 23.2 16.1 23.2 0Zm35-38.2v62.1h-14.3v-62.1Zm22 0v62.1h-14.2v-62.1Zm56 43.5h-34.4c1.7 4.4 6 6.6 10.3 6.7a10.8 10.8 0 0 0 8-3.3h15.2a24.2 24.2 0 0 1-24 15.7c-12.1-.2-24.2-8.4-24.2-24.6 0-16.5 12.5-24.7 25-24.6 12.3 0 24.6 8.2 24.6 24.6a27.1 27.1 0 0 1-.5 5.5ZM439 137c-1.3-4.7-5.2-7-9.8-7a9.5 9.5 0 0 0-9.4 7Zm37.9 29.6h-14v-48h12.7l.5 3.2a14.6 14.6 0 0 1 10.5-4.2 24 24 0 0 1 5.4.7l-.2 13.2a16.4 16.4 0 0 0-4.7-.7c-5 0-10.2 2.7-10.2 9.2Z"/>
			<path fill="#e8ad0b"
			      d="M529.9 104.6c9.7 0 16.3 8.3 16.4 16.3a13.8 13.8 0 0 1-6 11.7c6.6 3.4 9.2 8.8 9.2 14 0 10-6.8 20.1-19.8 20.1H501v-62.1ZM516 127h11.4c2.7 0 4-2.2 4-4.4s-1.4-4.2-3.8-4.2H516Zm0 25.3h13.6c3.2 0 4.8-2.7 4.8-5.6s-1.6-5.6-4.8-5.6H516Zm37.3-9.5c0-34.3 51.2-34.2 51.2 0s-51.2 34-51.2 0Zm37.2 0c0-16.4-23.2-16.3-23.2 0 0 16 23.2 16.1 23.2 0Zm46.3-18.8 3.5-5.4H656v1.4l-14.2 22.3 14.4 23.4v1h-15.6l-3.5-5.7-4.5-8.9-4.5 8.9-3.4 5.8h-15.5v-1l14.4-23.5-14.2-22.3v-1.4h15.5l3.5 5.4 4.2 8.3Zm-492.5 12.8L95 108.4V161l49.3 28.5 49.3-28.5v-52.6Zm0 43.6-18-10.4v-7.2l18 10.4Zm0-14.4-18-10.4v-7.3l18 10.4Z"/>
			<path fill="#e8ad0b"
			      d="m190 101.9-45.6-26.3-45.6 26.3 45.6 26.3 45.5-26.3z"/>
			<path
				d="m126.5 170 18 10.4v-7.2l-18-10.4v7.2zm18-11.3-18-10.4v7.3l18 10.4v-7.3z"/>
			<path
				d="m190 101.9-45.6 26.3-45.6-26.3-8.6-5 56.5-32.6 43 25.4 7.4-4.3-50.3-29.7L75.3 97 95 108.4l49.3 28.4 49.3-28.4 8.5-5V166l-57.8 33.4-35.8-20.6-10.6 8.6v-14.7l-14-8.1v-48.9l-7.5-4.3v57.5l14 8.1v26.2l18.9-15.3 35 20.2 65.3-37.8V90.5l-19.8 11.4z"/>
		</svg>`,
    //language=HTML
    heartbeat: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
			<path fill="currentColor"
			      d="M7 24H4a8 8 0 0 1 8-7h9a8 8 0 0 1 7 4 1 1 0 0 0 2 0 10 10 0 0 0-9-6h-9A10 10 0 0 0 2 25a1 1 0 0 0 1 1h4a1 1 0 0 0 0-2z"/>
			<path fill="currentColor"
			      d="M29 24h-3l-4-6a1 1 0 0 0-2 1l-4 8-2-3a1 1 0 0 0-1 0h-2a1 1 0 0 0 0 2h2l2 4a1 1 0 0 0 1 0 1 1 0 0 0 1-1l4-8 3 5a1 1 0 0 0 1 0h4a1 1 0 0 0 0-2zM16 14a6 6 0 1 0-6-6 6 6 0 0 0 6 6zm0-10a4 4 0 1 1-4 4 4 4 0 0 1 4-4z"/>
		</svg>`,
    link: `<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 277.279 277.279">
  <path fill="currentColor" d="m149.245 191.671-42.425 42.426-.001.001-.001.001c-17.544 17.545-46.092 17.546-63.638 0-8.5-8.5-13.18-19.801-13.18-31.82 0-12.018 4.68-23.317 13.177-31.817l.003-.003 42.425-42.426c5.857-5.858 5.857-15.356-.001-21.213-5.857-5.857-15.355-5.857-21.213 0l-42.425 42.426-.009.01C7.798 163.42 0 182.251 0 202.279c0 20.033 7.801 38.867 21.967 53.033C36.589 269.933 55.794 277.244 75 277.244c19.206 0 38.412-7.311 53.032-21.932v-.001l.001-.001 42.425-42.426c5.857-5.857 5.857-15.355-.001-21.213-5.856-5.857-15.353-5.857-21.212 0zM277.279 75c0-20.033-7.802-38.867-21.968-53.033-29.243-29.242-76.824-29.241-106.065 0l-.004.005-42.424 42.423c-5.858 5.857-5.858 15.356 0 21.213a14.952 14.952 0 0 0 10.607 4.394c3.838 0 7.678-1.465 10.606-4.394l42.424-42.423.005-.005c17.544-17.544 46.092-17.545 63.638 0 8.499 8.5 13.181 19.801 13.181 31.82 0 12.018-4.68 23.317-13.178 31.817l-.003.003-42.425 42.426c-5.857 5.857-5.857 15.355.001 21.213a14.954 14.954 0 0 0 10.606 4.394c3.839 0 7.678-1.465 10.607-4.394l42.425-42.426.009-.01C269.48 113.859 277.279 95.028 277.279 75z"/>
        <path fill="currentColor" d="M85.607 191.671a14.954 14.954 0 0 0 10.606 4.394c3.839 0 7.678-1.465 10.607-4.394l84.852-84.852c5.858-5.857 5.858-15.355 0-21.213-5.857-5.857-15.355-5.857-21.213 0l-84.852 84.851c-5.858 5.859-5.858 15.357 0 21.214z"/>
</svg>`,

  }

  const bold = (text) => {
    return `<b>${text}</b>`
  }

  const sanitizeKey = (label) => {
    return label.toLowerCase().replace(/[^a-z0-9]/g, '_')
  }

  Groundhogg.element = {
    icons,
    ...Elements,
    adminPageURL,
    sanitizeKey,
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
    improveTinyMCE,
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
    inputRepeater,
    inputRepeaterWidget,
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
    progressModal,
    tooltip,
    clickedIn,
    ordinal_suffix_of,
    bold,
    spinner,
    el,
    isNumeric,
    isString,
    replacementsWidget,
  }

})(jQuery)
