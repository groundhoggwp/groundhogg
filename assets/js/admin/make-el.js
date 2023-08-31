(($) => {

  const { clickedIn, isString, setFrameContent } = Groundhogg.element

  const AttributeHandlers = {
    required: (el, value) => {
      el.require = value
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
        } else {
          children = [children]
        }
      }

      children.forEach(child => {

        if (!child) {
          return
        }

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
      options = Object.keys(options).map(key => ({ value: key, text: options[key] }))
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
	  <span class="on">${onLabel}</span>
	  <span class="off">${offLabel}</span>`,
    ])
  }

  const Div = (attributes = {}, children = []) => {
    return makeEl('div', attributes, children)
  }

  const Dashicon = (icon) => {
    return makeEl('span', {
      className: `dashicons dashicons-${icon}`,
    })
  }

  const Fragment = (children) => {
    return makeEl('fragment', {}, children)
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
        id: `cell-${rowIndex}-${cellIndex}`,
        value: row[cellIndex] ?? '',
        dataRow: rowIndex,
        dataCell: cellIndex,
        onChange: e => onCellChange(rowIndex, cellIndex, e.target.value),
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
          id: `${id}-add-row`,
          className: 'add-row gh-button dashicon',
          onClick: e => addRow(),
        }, Dashicon('plus-alt2')),
      ]) : null,
    ])

    return Repeater()
  }

  const InputWithReplacements = (attributes) => {
    return Div({
      className: 'input-wrap',
    }, [
      Input(attributes),
      Button({
        className: 'replacements-picker-start gh-button dashicon',
      }, Dashicon('admin-users')),
    ])
  }

  const Table = (atts, children) => makeEl('table', atts, children)
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
  }, children) => {

    let modal = Div({
      className: 'gh-modal',
      tabindex: 0,
    }, [
      Div({
        className: 'gh-modal-overlay',
        onClick: e => {
          close()
        },
      }),
      Div({
        className: `gh-modal-dialog ${dialogClasses}`,
      }, [
        Div({
          className: 'gh-modal-dialog-content',
        }),
        Button({
          className: 'dashicon-button gh-modal-button-close-top gh-modal-button-close',
          onClick: e => {
            close()
          },
        }, Dashicon('no-alt')),
      ])])

    const close = () => {
      onClose()
      modal.remove()
    }

    modal.querySelector('.gh-modal-dialog-content').appendChild(Fragment(maybeCall(children, { close })))
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

    let keepAlive = false

    let modal = Div({
      className: 'gh-modal mini gh-panel',
      tabindex: 0,
      onMousedown: e => {

        console.log('mousedown')

        // Don't focus out
        keepAlive = true
        setTimeout(() => {
          keepAlive = false
        }, 10)
      },
      onFocusout: e => {

        if (keepAlive) {
          return
        }

        if (closeOnFocusout) {
          if (!e.relatedTarget || !clickedIn(e.relatedTarget, '.gh-modal.mini')) {
            setTimeout(() => {
              if (!keepAlive) {
                close()
              }
            })
            // close()
          }
        }
      },
      onCreate: el => {
        el.focus()
      },
    }, Div({
      className: `gh-modal-dialog ${dialogClasses}`,
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
        modal.style.left = (right - width) + 'px'
        break
    }

    console.log({
      // combined: top +
    })

    if ((top + height) > window.innerHeight) {
      modal.style.top = (window.innerHeight - height - 20) + 'px'
    } else {
      modal.style.top = top + 'px'
    }

    modal.focus()

    return modal
  }

  const Autocomplete = ({
    ...attributes
  }) => {

  }

  const ItemPicker = ({
    id = '',
    placeholder = 'Type to search...',
    fetchOptions = (search, resolve) => {},
    selected = [],
    onChange = () => {},
    tags = false,
    noneSelected = 'Any',
    isValidSelection = () => true,
    multiple = true,
    ...attributes
  }) => {

    let state = {
      search: '',
      searching: false,
      options: [],
      isFocused: false,
    }

    // ensure array
    if (!multiple && !Array.isArray(selected)) {
      selected = [selected]
    }

    let timeout
    let isMorphing = false

    const setState = newState => {
      state = {
        ...state,
        ...newState,
      }

      morph()
    }

    /**
     * Handles the onChange stuff with multiple in mind
     * @param selected
     */
    const handleOnChange = selected => {

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
      if (isMorphing) {
        return
      }

      isMorphing = true
      morphdom(document.getElementById(id), Render())
      isMorphing = false
    }

    const setSearching = searching => {
      setState({
        searching,
      })
    }

    const setOptions = options => {
      setState({
        options,
      })
    }

    const focusSearch = () => document.getElementById(id)?.querySelector(`input[type=search]`)?.focus()

    const createOption = (value) => {
      state.options.unshift({ id: value, text: value })
      selectOption(value)
    }

    const selectOption = (id) => {

      let option = { ...state.options.find(opt => opt.id == id) }

      console.log( id, option )

      if (option.create) {
        option.text = option.id
      }

      if (multiple) {
        selected.push(option)
      } else {
        selected = [option]
      }

      setState({
        options: [],
        search: '',
        searching: false,
      })

      handleOnChange(selected)
    }

    const unSelectOption = (id) => {
      selected = selected.filter(opt => opt.id != id)
      handleOnChange(selected)
      morph()
    }

    const itemPickerItem = ({ id, text }, index) => {
      return Div({
        className: `gh-picker-item ${isValidSelection(id) ? '' : 'is-invalid'}`,
        id: `item-${index}`,
      }, [
        Span({ className: 'gh-picker-item-text' }, text),
        Span({
          id: `delete-${index}`,
          className: 'gh-picker-item-delete',
          tabindex: 0,
          dataId: id,
          onClick: e => {
            unSelectOption(id)
            focusSearch()
          },
        }, '&times;'),
      ])
    }

    const itemPickerOption = ({ id, text }, index) => {
      return Div({
        className: 'gh-picker-option',
        dataId: id,
        tabindex: '0',
        id: `option-${index}-${id}`,
        onClick: e => {
          selectOption(id)
          focusSearch()
        },
      }, text)
    }

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

        style.top = bottom + 'px'
        style.left = left + 'px'
        style.width = width + 'px'
        style.maxHeight = (window.innerHeight - bottom) + 'px'
      }

      if (tags && isValidSelection(state.search)) {
        state.options.unshift({ id: state.search, text: `Add "${state.search}"`, create: true })
      }

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

            optionsContainer.style.top = bottom + 'px'
            optionsContainer.style.left = left + 'px'
            optionsContainer.style.width = width + 'px'
            optionsContainer.style.maxHeight = (window.innerHeight - bottom) + 'px'

            if (!multiple) {
              focusSearch()
            }

          }, 0)
        },
      }, [
        multiple || !selected.length ? null : SearchInput(),
        state.searching && state.search ? Div({
          className: 'gh-picker-no-options',
        }, 'Searching...') : null,
        ...state.options.filter(opt => !selected.some(_opt => opt.id == _opt.id)).
        map((opt, i) => itemPickerOption(opt, i)),
        state.options.length || state.searching ? null : Div({
          className: 'gh-picker-no-options',
        }, 'No results found.'),
      ])
    }

    const SearchInput = () => Input({
      className: 'gh-picker-search',
      value: state.search,
      name: 'search',
      type: 'search',
      autocomplete: 'off',
      // autofocus: true,
      placeholder: selected.length ? placeholder : noneSelected,
      onInput: e => {

        if (!e.target.value && multiple) {
          setState({
            search: '',
            searching: false,
          })
          return
        }

        setState({
          search: e.target.value,
          searching: true,
        })

        if (timeout) {
          clearTimeout(timeout)
        }

        timeout = setTimeout(() => {
          fetchOptions(state.search).then(options => {
            // console.log( options )
            setState({
              searching: false,
              options,
            })
          })
        }, 500)
      },
      onKeydown: e => {
        if (tags) {
          if (e.key !== 'Enter' && e.key !== ',') {
            return
          }

          createOption(e.target.value)
        }
      },
    })

    const Render = () => Div({
      id,
      className: `gh-picker ${state.search || (state.searching && !multiple) ? 'options-visible' : ''}`,
      tabindex: '0',
      onFocusout: e => {

        if (e.relatedTarget && clickedIn(e.relatedTarget, '.gh-picker')) {
          return
        }

        setState({
          search: '',
          options: [],
          searching: false,
        })
      },
      onFocus: e => {
        if (multiple) {
          return
        }

        setState({
          searching: true,
        })

      },
      ...attributes,
    }, [
      Div({
        className: `gh-picker-selected ${multiple ? 'multiple' : 'single'}`,
      }, [
        ...selected.map((item, i) => itemPickerItem(item, i)),
        multiple || !selected.length ? SearchInput() : null,
      ]),
      state.searching || state.options.length || state.search ? itemPickerOptions() : null,
    ])

    return Render()
  }

  const InputGroup = inputs => Div({ className: 'gh-input-group' }, inputs)

  const Iframe = ({ onCreate = () => {}, ...attributes }, content) => {

    return makeEl('iframe', {
      ...attributes,
      onCreate: el => {
        setTimeout(() => {
          setFrameContent(el, content)
          onCreate(el)
        })
      },
    })
  }

  window.MakeEl = {
    InputGroup,
    makeEl,
    Input,
    InputWithReplacements,
    Textarea,
    Select,
    Button,
    Toggle,
    Div,
    Span,
    Label,
    InputRepeater,
    Fragment,
    Table,
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
  }
})(jQuery)