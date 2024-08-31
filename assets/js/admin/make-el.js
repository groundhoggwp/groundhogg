( ($) => {

  const clickedIn = (e, selector) => {
    var el = e.tagName ? e : e.srcElement || e.target

    if (el && el.matches(selector)) {
      return el
    }
    else {
      while (el = el.parentNode) {
        if (typeof el.matches !== 'undefined' && el.matches(selector)) {
          return el
        }
      }
    }

    return false
  }

  function isString (string) {
    return typeof string === 'string'
  }

  function isNumeric (n) {
    return !isNaN(parseFloat(n)) && isFinite(n)
  }

  const AttributeHandlers = {
    required: (el, value) => {
      el.required = value
    },
    autofocus: (el, value) => {
      el.autofocus = value
    },
    value: (el, value) => {
      el.value = value
    },
    className: (el, attribute) => {
      if (isString(attribute)) {
        attribute = attribute.split(' ').map(c => c.trim()).filter(c => c)
      }

      el.classList.add(...attribute)
    },
    eventHandlers: (el, events) => {
      for (let event in events) {
        el.addEventListener(event, events[event])
      }
    },
    onInput: (el, func) => AttributeHandlers.eventHandlers(el, { input: func }),
    onChange: (el, func) => AttributeHandlers.eventHandlers(el, { change: func }),
    onFocus: (el, func) => AttributeHandlers.eventHandlers(el, { focus: func }),
    onClick: (el, func) => AttributeHandlers.eventHandlers(el, { click: func }),
    style: (el, style) => {

      if (isString(style)) {
        el.style = style
        return
      }

      for (let attribute in style) {

        // variable
        if (attribute.startsWith('--')) {
          el.style.setProperty(attribute, style[attribute])
          continue
        }

        el.style[attribute] = style[attribute]
      }
    },
    onCreate: (el, func) => func(el),

  }

  /**
   *
   * @param html
   * @return {ChildNode}
   */
  function htmlToElement (html) {
    var template = document.createElement('template')
    html = html.trim() // Never return a text node of whitespace as the result
    template.innerHTML = html
    return template.content.firstChild
  }

  /**
   *
   * @param html
   * @return {NodeListOf<ChildNode>}
   */
  function htmlToElements (html) {
    var template = document.createElement('template')
    template.innerHTML = html
    return template.content.childNodes
  }

  /**
   *
   * @param tagName
   * @param attributes
   * @param children
   * @return {*}
   */
  const makeEl = (tagName, attributes, children = null) => {

    let el = tagName === 'fragment' ? document.createDocumentFragment() : document.createElement(tagName)

    if (children !== null) {
      if (!Array.isArray(children)) {
        if (children instanceof NodeList) {
          children = [...children]
        }
        else {
          children = [children]
        }
      }

      children.forEach(child => {

        if (!child) {
          return
        }

        child = maybeCall( child )

        // Template literals
        if (isString(child)) {
          let _children = htmlToElements(child)
          while (_children.length) {
            el.appendChild(_children[0])
          }
          return
        }

        el.appendChild(child)
      })
    }

    for (let attributeName in attributes) {

      if (attributes[attributeName] === false) {
        continue
      }

      if (AttributeHandlers.hasOwnProperty(attributeName)) {
        AttributeHandlers[attributeName](el, attributes[attributeName])
        continue
      }

      // Dataset attributes
      if (attributeName.startsWith('data')) {
        let dataName = attributeName.replace(/^data(.+)/, '$1')
        dataName = dataName.charAt(0).toLowerCase() + dataName.slice(1)

        el.dataset[dataName] = attributes[attributeName]
        continue
      }

      // Events like onKeypress
      if (attributeName.match(/^on[A-Z]/)) {
        let eventName = attributeName.replace(/^on(.+)/, '$1')
        eventName = eventName.charAt(0).toLowerCase() + eventName.slice(1)

        el.addEventListener(eventName, attributes[attributeName])
        continue
      }

      el.setAttribute(attributeName, attributes[attributeName])
    }

    return el
  }

  const Input = (attributes) => {
    return makeEl('input', {
      type: 'text',
      ...attributes,
    })
  }

  const Textarea = (attributes) => {
    return makeEl('textarea', {
      ...attributes,
    })
  }

  const Select = (attributes) => {

    let {
      options = {},
      selected = '',
      onChange = e => {},
      ...rest
    } = attributes

    if (!Array.isArray(options)) {
      options = Object.keys(options).map(key => ( { value: key, text: options[key] } ))
    }

    if (!Array.isArray(selected)) {
      selected = [selected]
    }

    options = options.map(opt => typeof opt === 'string' ? { value: opt, text: opt } : opt).
      map(({ value, text }) => makeEl('option', {
        value,
        selected: selected.includes(value),
      }, text))

    return makeEl('select', {
      ...rest,
      onChange: (e) => {
        if (rest.multiple) {
          e.target.values = e.target.querySelectorAll('option:checked').map(el => el.value)
        }

        onChange(e)
      },
    }, options)
  }

  const Button = (attributes, children) => {
    return makeEl('button', {
      ...attributes,
    }, children)
  }

  const Toggle = ({
    onLabel = 'On',
    offLabel = 'Off',
    ...atts
  }) => {

    return makeEl('label', {
      className: 'gh-switch',
    }, [
      Input({
        ...atts,
        type: 'checkbox',
      }),
      //language=HTML
      `<span class="slider round"></span>
      <span class="on">${ onLabel }</span>
      <span class="off">${ offLabel }</span>`,
    ])
  }

  const Div = (attributes = {}, children = []) => {
    return makeEl('div', attributes, children)
  }

  const Dashicon = (icon, children = null ) => {
    return makeEl('span', {
      className: `dashicons dashicons-${ icon }`,
    }, children )
  }

  const Fragment = (children, atts = {}) => {
    return makeEl('fragment', atts, children)
  }

  const Span = (attributes = {}, children = []) => {
    return makeEl('span', attributes, children)
  }

  const Label = (attributes = {}, children = []) => {
    return makeEl('label', attributes, children)
  }

  const InputRepeater = ({
    id = '',
    onChange = () => {},
    rows = [],
    cells = [],
    sortable = false,
    fillRow = () => Array(cells.length).fill(''),
    maxRows = 0,
  }) => {

    const handleChange = (rows) => {
      onChange(rows)
      morphdom(document.getElementById(id), Repeater())
    }

    const removeRow = (rowIndex) => {
      rows.splice(rowIndex, 1)
      handleChange(rows)
    }

    const addRow = () => {
      rows.push(fillRow())
      handleChange(rows)
    }

    const onCellChange = (rowIndex, cellIndex, value) => {
      rows[rowIndex][cellIndex] = value
      handleChange(rows)
    }

    const RepeaterRow = (row, rowIndex) => Div({
      className: 'gh-input-repeater-row',
      dataRow: rowIndex,
    }, [
      // Cells
      ...cells.map((cellCallback, cellIndex) => cellCallback({
        id: `${ id }-cell-${ rowIndex }-${ cellIndex }`,
        name: `${ id }[${ rowIndex }][${ cellIndex }]`,
        value: row[cellIndex] ?? '',
        dataRow: rowIndex,
        dataCell: cellIndex,
        onChange: e => onCellChange(rowIndex, cellIndex, e.target.value),
        setValue: value => onCellChange(rowIndex, cellIndex, value),
      }, row)),
      // Sortable Handle
      sortable ? makeEl('span', {
        className: 'handle',
        dataRow: rowIndex,
      }, Dashicon('move')) : null,
      // Remove Row Button
      Button({
        className: 'gh-button dashicon remove-row',
        dataRow: rowIndex,
        onClick: e => removeRow(rowIndex),
      }, Dashicon('no-alt')),
    ])

    const Repeater = () => Div({
      id,
      className: 'gh-input-repeater',
      onCreate: el => {

        if (!sortable) {
          return
        }

        $(el).sortable({
          handle: '.handle',
          update: (e, ui) => {

            let $row = $(ui.item)
            let oldIndex = parseInt($row.data('row'))
            let curIndex = $row.index()

            let row = rows[oldIndex]

            rows.splice(oldIndex, 1)
            rows.splice(curIndex, 0, row)
            onChange(rows)
          },
        })
      },
    }, [
      ...rows.map((row, i) => RepeaterRow(row, i)),
      maxRows === 0 || rows.length < maxRows ? Div({
        className: 'gh-input-repeater-row-add',
      }, [
        `<div class="spacer"></div>`,
        // Add Row Button
        Button({
          id: `${ id }-add-row`,
          className: 'add-row gh-button dashicon',
          onClick: e => addRow(),
        }, Dashicon('plus-alt2')),
      ]) : null,
    ])

    return Repeater()
  }

  const InputWithReplacements = ({ inputCallback = Input, ...attributes }) => {
    return Div({
      className: 'input-wrap',
    }, [
      inputCallback(attributes),
      Button({
        className: 'replacements-picker-start gh-button dashicon',
      }, Dashicon('admin-users')),
    ])
  }

  const Table = (atts, children) => makeEl('table', atts, children)
  const THead = (atts, children) => makeEl('thead', atts, children)
  const TBody = (atts, children) => makeEl('tbody', atts, children)
  const TFoot = (atts, children) => makeEl('tfoot', atts, children)
  const Tr = (atts, children) => makeEl('tr', atts, children)
  const Td = (atts, children) => makeEl('td', atts, children)
  const Th = (atts, children) => makeEl('th', atts, children)

  const maybeCall = (maybeFunc, ...args) => {
    if (maybeFunc instanceof Function) {
      return maybeFunc(...args)
    }

    return maybeFunc
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
   * @param children
   */
  const Modal = ({
    dialogClasses = '',
    onOpen = () => {},
    onClose = () => {},
    width,
    closeButton = true,
    closeOnOverlayClick = true
  }, children) => {

    const Dialog = ({ header = null, content = null }) => Div({
      className: `gh-modal-dialog ${ dialogClasses }`,
      style: {
        width
      }
    }, [
      header,
      Div({
        className: 'gh-modal-dialog-content',
      }, content ),
      closeButton && ! header ? Button({
        className: 'dashicon-button gh-modal-button-close-top gh-modal-button-close',
        onClick: e => {
          close()
        },
      }, Dashicon('no-alt')) : null,
    ])

    let modal = Div({
      className: 'gh-modal',
      tabindex: 0,
    }, [
      Div({
        className: 'gh-modal-overlay',
        onClick: e => {
          if ( closeOnOverlayClick ){
            close()
          }
        },
      }),
      Dialog({
        header: null,
        content: null
      })
    ])

    const close = () => {
      onClose()
      modal.remove()
    }

    const morph = () => {

      let content = getContent()

      let header = content.querySelector('.modal-header')

      morphdom( modal.querySelector('.gh-modal-dialog'), Dialog({header, content}) )
    }

    const getContent = () => maybeCall(children, { close, modal, morph })

    document.body.appendChild(modal)

    morph()

    // Run before positioning
    onOpen({ modal, close, morph })

    modal.focus()

    return modal
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
   * @param children
   */
  const ModalFrame = ({
    onOpen = () => {},
    onClose = () => {},
    frameAttributes = {},
    closeOnOverlayClick = true,
    closeOnEscape = true,
  }, children) => {

    let modal = Div({
      className: 'gh-modal',
      tabindex: 0,
      onKeydown: e => {
        if (closeOnEscape) {
          if (e.key === 'Esc' || e.key === 'Escape') {
            close()
          }
        }
      },
    }, [
      Div({
        className: 'gh-modal-overlay',
        onClick: e => {
          if (closeOnOverlayClick) {
            close()
          }
        },
      }),
      Div({
        className: `gh-modal-frame`,
        ...frameAttributes,
      }, []),
    ])

    const close = () => {
      onClose()
      modal.remove()
    }

    modal.querySelector('.gh-modal-frame').appendChild(Fragment(maybeCall(children, { close })))
    document.body.appendChild(modal)

    // Run before positioning
    onOpen({ close })

    modal.focus()

    return modal
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
   * @param children
   */
  const MiniModal = ({
    selector = '',
    from = 'right',
    dialogClasses = '',
    onOpen = () => {},
    onClose = () => {},
    // closeOnFocusout = false,
    closeOnFocusout = true,
  }, children) => {

    let modal = Div({
      className: 'gh-modal mini gh-panel',
      tabindex: 0,
      onFocusout: e => {
        if (closeOnFocusout) {
          if (!e.relatedTarget || !clickedIn(e.relatedTarget, '.gh-modal.mini')) {

            // this is a bit of a hack
            setTimeout( () => {
              // if the focused element is no longer within the modal
              if ( !clickedIn(document.activeElement, '.gh-modal.mini') ){
                close()
              }

            }, 10 )
          }
        }
      },
      onCreate: el => {
        el.focus()
      },
    }, Div({
      className: `gh-modal-dialog ${ dialogClasses }`,
    }, [
      Button({
        className: 'dashicon-button gh-modal-button-close-top gh-modal-button-close',
        onClick: e => {
          close()
        },
      }, Dashicon('no-alt')),
    ]))

    const close = () => {
      onClose()
      modal.remove()
    }

    modal.querySelector('.gh-modal-dialog').appendChild(Fragment(maybeCall(children, { close })))
    document.body.appendChild(modal)

    // Run before positioning
    onOpen()

    let targetElement = document.querySelector(selector)

    let {
      right,
      left,
      bottom,
      top,
    } = targetElement.getBoundingClientRect()

    let {
      width,
      height,
    } = modal.getBoundingClientRect()

    switch (from) {
      case 'left':
        modal.style.left = left + 'px'
        break
      case 'right':
        modal.style.left = ( right - width ) + 'px'
        break
    }

    if (( top + height ) > window.innerHeight) {
      modal.style.top = ( window.innerHeight - height - 20 ) + 'px'
    }
    else {
      modal.style.top = top + 'px'
    }

    modal.focus()

    return modal
  }

  const Autocomplete = ({
    fetchResults = async search => {},
    onInput,
    ...attributes
  }) => {

    let timeout

    const State = {
      pointer: 0,
      results: [],
      input: null,
    }

    const setValue = () => {

      let item = State.results[State.pointer]

      State.input.value = item.id
      State.input.dispatchEvent(new Event('change'))

      closeResults()
    }

    const updateResults = () => {

      if (!State.results.length) {
        closeResults()
        return
      }

      let resultsContainer = document.querySelector('.gh-autocomplete-results')
      let newResults = Results()

      if (!resultsContainer) {
        document.body.appendChild(newResults)
      }
      else {
        morphdom(resultsContainer, newResults)
      }

    }

    const closeResults = () => {
      let resultsContainer = document.querySelector('.gh-autocomplete-results')

      if (resultsContainer) {
        resultsContainer.remove()
      }
    }

    const Results = () => {

      const { results, input } = State

      let { height, width, top, left } = input.getBoundingClientRect()

      return Div({
        className: 'gh-autocomplete-results',
        style: {
          zIndex: 999999,
          top: `${ top + height }px`,
          left: `${ left }px`,
          width: `${ width }px`,
        },
      }, results.map(({ id, text }, index) => makeEl('a', {
        className: `${ index === State.pointer ? 'pointer' : '' }`,
        href: id,
        onClick: e => {
          e.preventDefault()
          setValue()
        },
        onMouseenter: e => {
          State.pointer = [...e.target.parentNode.children].indexOf(e.target)
          updateResults()
        },
      }, text)))
    }

    return Input({
      ...attributes,
      onFocusout: e => {
        const input = e.target
        input.classList.remove('has-results')

        if (e.relatedTarget && clickedIn(e.relatedTarget, 'a.pointer')) {
          setValue()
        }

        closeResults()
      },
      onKeydown: e => {
        const input = e.target

        switch (e.key) {
          case 'Esc':
          case 'Escape':
            e.preventDefault()
            closeResults()

            return
          case 'Down':
          case 'ArrowDown':
            e.preventDefault()

            if (State.pointer < State.results.length) {
              State.pointer++
            }

            break
          case 'Up':
          case 'ArrowUp':
            e.preventDefault()

            if (State.pointer > 0) {
              State.pointer--
            }

            break
          case 'Enter':
            e.preventDefault()

            setValue()

            break
          default:
            return
        }

        updateResults()
      },
      onInput: e => {

        if (timeout) {
          clearTimeout(timeout)
        }

        timeout = setTimeout(async () => {
          const input = e.target

          let search = input.value

          State.results = await fetchResults(search)
          State.input = input
          State.pointer = 0

          updateResults()

          input.classList.add('gh-autocomplete', 'has-results')
        }, 500)

        onInput(e)
      },
    })
  }

  const Ellipses = ( content, atts = {} ) => Span({
    ...atts,
    onCreate: el => {

      let ellipses = ''
      let count = 0

      let interval = setInterval(() => {

        // parentNode will be null once removed from the dom
        if (!el.parentNode) {
          clearInterval(interval)
          return
        }

        count = ( count + 1 ) % 4
        ellipses = '.'.repeat(count)
        el.textContent = content + ellipses

      }, 500)

    },
  }, content + '...' )


  const ItemPicker = ({
    id = '',
    placeholder = 'Type to search...',
    fetchOptions = (search, resolve) => {},
    selected = [],
    onChange = () => {},
    onSelect = () => {},
    onCreate = () => {},
    onUnselect = () => {},
    createOption = val => Promise.resolve({ id: val, text: val }),
    tags = false,
    noneSelected = 'Any',
    // Any none empty value
    isValidSelection = string => Boolean(string),
    multiple = true,
    clearable = true,
    ...attributes
  }) => {

    const state = Groundhogg.createState({
      search: '',
      searching: false,
      choosing: false,
      options: [],
      focused: false,
      morphing: false,
      clicked: false,
    })

    const optionsVisible = () => {
      return multiple
        ? state.focused && ( state.searching || state.options.length || ( tags && isValidSelection(state.search) ) )
        : state.focused
    }

    // ensure array
    if (!multiple && !Array.isArray(selected)) {
      selected = [selected]
    }

    let timeout

    const setState = (newState, trigger) => {
      state.set(newState)
      // console.log({
      //   state: state.get(),
      //   trigger,
      // })
      morph()
    }

    /**
     * Handles the onChange stuff with multiple in mind
     * @param selected
     */
    const handleOnChange = selected => {

      if (timeout) {
        clearTimeout(timeout)
      }

      if (multiple) {
        onChange(selected)
        return
      }

      if (!selected.length) {
        onChange(null)
        return
      }

      onChange(selected[0])
    }

    const morph = () => {

      if (state.morphing) {
        return
      }

      state.set({
        morphing: true,
      })

      morphdom(document.getElementById(id), Render())

      state.set({
        morphing: false,
      })
    }

    const focusSearch = () => document.getElementById(id)?.querySelector(`input[type=search]`)?.focus()
    const focusPicker = () => document.getElementById(id)?.focus()
    const focusParent = () => document.getElementById(id)?.parentElement.focus()

    const handleCreateOption = (value) => {
      state.options.unshift({ id: value, text: value, create: true })
      handleSelectOption(value)
    }

    /**
     * Given an ID, select the option
     *
     * @param id
     */
    const handleSelectOption = async (id) => {

      let option = { ...state.options.find(opt => opt.id == id) }

      if (option.create) {
        option.text = option.id
      }

      if (multiple) {
        selected.push(option)
      }
      else {
        selected = [option]
      }

      if (option.create) {
        await createOption(option.id).then(opt => {
          // Replace created with new option
          selected = selected.map(item => item.id == id ? opt : item)
          option = opt
        })
      }

      onSelect(option)
      handleOnChange(selected)

      if ( ! multiple ){
        state.set({
          focused: false
        })
      }

      morph()

      if ( multiple ){
        focusSearch()
      } else {
        focusPicker()
      }

    }

    /**
     * Given an Id, unselect an option
     *
     * @param id
     */
    const handleUnselectOption = (id) => {

      let opt = selected.find(opt => opt.id === id)
      selected = selected.filter(opt => opt.id != id)
      onUnselect(opt)
      handleOnChange(selected)
      morph()

      if ( multiple ){
        focusSearch()
      }
    }

    /**
     * Item picker item, what shows in the picker
     *
     * @param id
     * @param text
     * @param index
     * @returns {*}
     */
    const itemPickerItem = ({ id, text }, index) => {
      return Div({
        className: `gh-picker-item ${ isValidSelection(id) ? '' : 'is-invalid' }`,
        id: `item-${ id }-${ index }`,
      }, [
        Span({ className: 'gh-picker-item-text' }, text),
        selected.length > 1 || clearable ? Span({
          id: `delete-${ id }-${ index }`,
          className: 'gh-picker-item-delete',
          tabindex: '0',
          dataId: id,
          onClick: e => {
            handleUnselectOption(id)
          },
        }, '&times;') : null,
      ])
    }

    /**
     * The items for the actual dropdown
     *
     * @param id
     * @param text
     * @param index
     * @returns {*}
     */
    const itemPickerOption = ({ id, text }, index) => {
      return Div({
        className: 'gh-picker-option',
        dataId: id,
        tabindex: '0',
        id: `option-${ index }-${ id }`,
        onClick: e => {
          handleSelectOption(id)
        },
      }, text)
    }

    /**
     * The item picker options
     *
     * @returns {*}
     */
    const itemPickerOptions = () => {

      let picker = document.getElementById(id)
      let style = {}

      if (picker) {
        const {
          left,
          right,
          top,
          bottom,
          width,
        } = picker.getBoundingClientRect()

        let maxHeight = window.innerHeight - bottom - 20

        if ( maxHeight > 100 ){
          style.top = bottom + 'px'
          style.left = left + 'px'
          style.width = width + 'px'
          style.maxHeight = ( maxHeight ) + 'px'
        } else {
          style.bottom = window.innerHeight - top + 'px'
          style.left = left + 'px'
          style.width = width + 'px'
          style.maxHeight = ( top - 20 ) + 'px'
        }
      }

      // Remove createable options
      state.options = state.options.filter(opt => !opt.create)

      // Only show create option after search is complete
      if (!state.searching && tags && isValidSelection(state.search)) {
        // Only show create if there is not a similar option already
        if (!state.options.find(opt => opt.id == state.search || opt.text == state.search)) {
          state.options.unshift({ id: state.search, text: `Add "${ state.search }"`, create: true })
        }
      }

      // Filter out already selected options
      let options = state.options.filter(opt => !selected.some(_opt => opt.id == _opt.id))

      return Div({
        className: 'gh-picker-options',
        style,
        onCreate: el => {
          setTimeout(() => {

            let picker = document.getElementById(id)
            let optionsContainer = picker.querySelector('.gh-picker-options')

            const {
              left,
              right,
              top,
              bottom,
              width,
            } = picker.getBoundingClientRect()

            let maxHeight = window.innerHeight - bottom - 20

            if ( maxHeight > 100 ){
              optionsContainer.style.top = bottom + 'px'
              optionsContainer.style.left = left + 'px'
              optionsContainer.style.width = width + 'px'
              optionsContainer.style.maxHeight = maxHeight + 'px'

            } else {
              optionsContainer.style.bottom = window.innerHeight - top + 'px'
              optionsContainer.style.left = left + 'px'
              optionsContainer.style.width = width + 'px'
              optionsContainer.style.maxHeight = top - 20 + 'px'
            }

            if (!multiple) {
              focusSearch()
            }

          }, 0)
        },
      }, [
        // Search input
        multiple || !selected.length ? null : SearchInput(),

        // "Searching..."
        state.searching ? Div({ className: 'gh-picker-no-options' }, Ellipses( wp.i18n.__('Searching') ) ) : null,

        // The actual options
        ...options.map((opt, i) => itemPickerOption(opt, i)),

        // If there are no options
        options.length || state.searching ? null : Div({ className: 'gh-picker-no-options' }, 'No results found.'),
      ])
    }

    /**
     * Start searching for options
     *
     * @param search
     */
    const startSearch = search => {

      setState({
        search,
        searching: true,
      }, 'start search')

      if (timeout) {
        clearTimeout(timeout)
      }

      timeout = setTimeout(() => {
        fetchOptions(search).then(options => {

          // Search may have changed since then...
          if (search !== state.search) {
            return
          }

          setState({
            searching: false,
            options,
          }, 'options fetched')
        })
      }, 500)
    }

    /**
     * The search input
     *
     * @returns {*}
     * @constructor
     */
    const SearchInput = () => Input({
      className: 'gh-picker-search',
      value: state.search,
      name: 'search',
      type: 'search',
      autocomplete: 'off',
      id: `${id}-search-input`,
      // autofocus: true,
      placeholder: selected.length ? placeholder : noneSelected,
      onInput: e => startSearch(e.target.value),
      onFocus: e => {
        startSearch(e.target.value)
      },
      onKeydown: e => {
        if (tags) {
          if (e.key !== 'Enter' && e.key !== ',') {
            return
          }

          handleCreateOption(e.target.value)
        }
      },
    })

    /**
     * Render the input picker
     *
     * @returns {*}
     * @constructor
     */
    const Render = () => Div({
      id,
      className: `gh-picker-container`,
      tabindex: '0',
      ...attributes,
    }, Div({
      id: `${id}-picker`,
      className: `gh-picker ${ optionsVisible() ? 'options-visible' : '' }`,
      tabindex: '0',
      onCreate: el => {
        el.addEventListener('focusout', e => {

          setTimeout( () => {

            if ( state.morphing || document.getElementById(id).contains( document.activeElement ) ){
              return
            }

            setState({
              search: '',
              options: [],
              searching: false,
              focused: false,
            }, 'picker focusout')

          }, 10 )
        })

        el.addEventListener('focusin', e => {

          if ( state.focused ){
            return
          }

          setState({
            focused: true,
          }, 'picker focused')
        })
      },
    }, [
      Div({
        className: `gh-picker-selected ${ multiple ? 'multiple' : 'single' }`,
      }, [
        ...selected.map((item, i) => itemPickerItem(item, i)),
        multiple || !selected.length ? SearchInput() : null,
      ]),
      optionsVisible() ? itemPickerOptions() : null,
    ]))

    return Render()
  }

  /**
   * And input group
   *
   * @param inputs
   * @returns {*}
   * @constructor
   */
  const InputGroup = inputs => Div({ className: 'gh-input-group' }, inputs)

  /**
   * IFrame, useful for email previews
   *
   * @param onCreate
   * @param attributes
   * @param content
   * @returns {*}
   * @constructor
   */
  const Iframe = ({ onCreate = () => {}, ...attributes }, content = null) => {

    let blob = new Blob([content], { type: 'text/html; charset=utf-8' })
    let src = URL.createObjectURL(blob)

    return makeEl('iframe', {
      ...attributes,
      src,
    })
  }

  const ToolTip = (content, position = 'bottom') => {
    return Div({ className: `gh-tooltip ${ position }` }, content)
  }

  const ButtonToggle = ({
    id = '',
    options = [],
    selected = '',
    onChange = value => {},
  }) => {

    const render = () => Div({
      id,
      className: 'gh-input-group',
    }, options.map(opt => ButtonOption(opt)))

    const ButtonOption = option => Button({
      id: `${ id }-opt-${ option.id }`,
      className: `gh-button gh-button small ${ selected === option.id ? 'dark' : 'grey' }`,
      onClick: e => {
        selected = option.id
        morphdom(document.getElementById(id), render())
        onChange(option.id)
      },
    }, option.text)

    return render()
  }

  const ProgressBar = ({
    percent = 100,
    error = false,
  }) => {

    return Div({
      className: `gh-progress-bar ${ error ? 'error' : '' }`,
    }, Div({
      className: 'gh-progress-bar-fill',
      style: {
        width: `${ percent }%`,
      },
    }, Span({
      className: 'fill-amount',
    }, `${ Math.ceil(percent) }%`)))

  }

  const Pg = (props, children) => makeEl( 'p', props, children )
  const An = (props, children) => makeEl( 'a', props, children )
  const Ul = (props, children) => makeEl( 'ul', props, children )
  const Ol = (props, children) => makeEl( 'ol', props, children )
  const Li = (props, children) => makeEl( 'li', props, children )
  const H1 = (props, children) => makeEl( 'h1', props, children )
  const H2 = (props, children) => makeEl( 'h2', props, children )
  const H3 = (props, children) => makeEl( 'h3', props, children )

  window.MakeEl = {
    InputGroup,
    makeEl,
    Ellipses,
    Input,
    InputWithReplacements,
    Textarea,
    Select,
    ToolTip,
    Button,
    Toggle,
    Div,
    Span,
    Label,
    InputRepeater,
    Fragment,
    Table,
    TBody,
    THead,
    TFoot,
    Tr,
    Td,
    Th,
    Modal,
    MiniModal,
    ModalFrame,
    ItemPicker,
    Iframe,
    htmlToElements,
    Dashicon,
    ButtonToggle,
    Autocomplete,
    ProgressBar,
    Pg,
    An,
    Ul,
    Ol,
    Li,
    H1,
    H2,
    H3,
    maybeCall
  }
} )(jQuery ?? function () { throw new Error('jQuery was not loaded.') })
