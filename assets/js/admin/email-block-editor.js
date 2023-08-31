(($) => {

  const {
    Div,
    Fragment,
    Button,
    Input,
    InputWithReplacements,
    Select,
    Table,
    Tr,
    Td,
    Th,
    Modal,
    MiniModal,
    ModalFrame,
    Span,
    Textarea,
    InputRepeater,
    InputGroup,
    Toggle,
    ItemPicker,
    Iframe,
    makeEl,
    htmlToElements,
    Dashicon,
  } = MakeEl

  const {
    el, objectToStyle, icons, uuid, tinymceElement,
    improveTinyMCE,
    textarea,
    modal,
    clickedIn,
    copyObject,
    moreMenu,
    dialog,
    confirmationModal,
    dangerConfirmationModal,
    adminPageURL,
    isValidEmail,
  } = Groundhogg.element

  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { __, _x, _n, _nx, sprintf } = wp.i18n
  const { linkPicker } = Groundhogg.pickers
  const { get, post } = Groundhogg.api
  const { emails: EmailsStore } = Groundhogg.stores

  improveTinyMCE({
    height: 400,
  })

  let fontWeights = [
    '300',
    '400',
    '500',
    '600',
    '700',
    'normal',
    'bold',
    'bolder',
  ]

  let fontFamilies = {
    'system-ui, sans-serif': 'System UI',
    'Arial, sans-serif': 'Arial',
    '"Arial Black", Arial, sans-serif': 'Arial Black',
    '"Century Gothic", Times, serif': 'Century Gothic',
    'Courier, monospace': 'Courier',
    '"Courier New", monospace': 'Courier New',
    'Geneva, Tahoma, Verdana, sans-serif': 'Geneva',
    'Georgia, Times, Times New Roman, serif': 'Georgia',
    'Helvetica, Arial, sans-serif': 'Helvetica',
    'Lucida, Geneva, Verdana, sans-serif': 'Lucida',
    'Tahoma, Verdana, sans-serif': 'Tahoma',
    'Times, "Times New Roman", Baskerville, Georgia, serif': 'Times',
    '"Times New Roman", Times, Georgia, serif': 'Times New Roman',
    'Verdana, Geneva, sans-serif': 'Verdana',
  }

  function onlyUnique (value, index, array) {
    return array.indexOf(value) === index
  }

  const DesignTemplates = [
    {
      id: 'boxed',
      name: __('Boxed'),
      controls: () => {

        let {
          alignment = 'left',
          width = 600,
          backgroundColor = 'transparent',
        } = getEmailMeta()

        return ControlGroup({
          name: 'Template Settings',
        }, [
          Control({
            label: 'Email Width',
          }, Input({
            id: 'email-width',
            name: 'width',
            value: width,
            className: 'control-input',
            type: 'number',
            onInput: e => {
              updateSettings({
                width: parseInt(e.target.value),
                reRender: true,
              })
            },
          })),
          Control({
            label: 'Alignment',
          }, Div({
            className: 'gh-input-group',
          }, [
            Button({
              id: 'align-left',
              className: `change-alignment gh-button ${alignment === 'left' ? 'primary' : 'secondary'}`,
              onClick: e => {
                updateSettings({
                  alignment: 'left',
                  reRender: true,
                })
              },
            }, icons.alignLeft),
            Button({
              id: 'align-center',
              className: `change-alignment gh-button ${alignment === 'center' ? 'primary' : 'secondary'}`,
              onClick: e => {
                updateSettings({
                  alignment: 'center',
                  reRender: true,
                })
              },
            }, icons.alignCenter),
          ])),
          Control({
            label: 'Background Color',
          }, ColorPicker({
            type: 'text',
            id: 'background-color',
            value: backgroundColor,
            onChange: backgroundColor => {
              updateSettings({
                backgroundColor,
                reRender: true,
              })
            },
          })),
        ])
      },
      html: (blocks) => {

        const {
          width = 640,
          alignment = 'left',
          backgroundColor = 'transparent',
        } = getEmailMeta()

        return Div({
          style: {
            backgroundColor,
          },
        }, Div({
          className: `template-boxed ${alignment}`,
          style: {
            maxWidth: `${width || 640}px`,
            backgroundColor,
          },
        }, blocks))
      },
      mceCss: () => {

        let bodyStyle = {}

        let {
          backgroundColor,
        } = getEmailMeta()

        if (backgroundColor) {
          bodyStyle.backgroundColor = backgroundColor
        }

        // language=CSS
        return `body {
            ${objectToStyle(bodyStyle)}
        }`
      },
    },
    {
      id: 'full_width',
      name: __('Full-Width'),
      controls: () => {

        let {
          backgroundColor = 'transparent',
        } = getEmailMeta()

        // no settings
        return ControlGroup({
          name: 'Template Settings',
        }, [
          Control({
            label: 'Background Color',
          }, ColorPicker({
            type: 'text',
            id: 'background-color',
            value: backgroundColor,
            onChange: backgroundColor => {
              updateSettings({
                backgroundColor,
                reRender: true,
              })
            },
          })),
        ])
      },
      html: (blocks) => {
        const {
          backgroundColor = 'transparent',
        } = getEmailMeta()

        return Div({
          className: `template-full-width`,
          style: {
            backgroundColor,
          },
        }, blocks)
      },
      mceCss: () => {

        let bodyStyle = {}

        let {
          backgroundColor,
        } = getEmailMeta()

        if (backgroundColor) {
          bodyStyle.backgroundColor = backgroundColor
        }

        // language=CSS
        return `body {
            ${objectToStyle(bodyStyle)}
        }`
      },
    },
  ]

  const GlobalFonts = {
    fonts: [],

    add () {

      let font = {
        id: uuid(),
        name: 'New font',
        style: fontDefaults({}),
      }

      this.fonts.push(font)

      return font
    },

    delete (id) {
      this.fonts = this.fonts.filter(f => f.id !== id)
    },

    get (id) {
      return this.fonts.find(f => f.id === id)
    },

    has (id) {
      return this.fonts.some(f => f.id === id)
    },

    update (id, style) {
      this.fonts = this.fonts.map(font => font.id === id ? { ...font, style: { ...font.style, ...style } } : font)
    },

    save () {
      return Groundhogg.stores.options.patch({
        gh_email_editor_global_fonts: this.fonts,
      })
    },
  }

  const getStateCopy = () => JSON.parse(JSON.stringify(State))

  const History = {
    pointer: 0,
    changes: [],
    timeout: null,
    addChange (state) {

      // use a timeout to avoid creating too many states from onInput events
      if (this.timeout) {
        clearTimeout(this.timeout)
      }

      this.timeout = setTimeout(() => {

        // remove elements past the current pointer
        this.changes = this.changes.slice(0, this.pointer + 1)

        // Add the new state
        this.changes.push(state)

        // Maintain size of 50 for memory reasons
        if (this.changes.length > 50) {
          this.changes.shift()
        }

        // Set the pointer to the end of the changelist
        this.pointer = this.changes.length - 1

        // Update the undo/redo buttons
        let el = document.getElementById('undo-and-redo')
        if (el) {
          morphdom(el, UndoRedo())
        }
      }, 100)
    },
    hasChanges () {
      return this.changes.length > 0
    },

    getState (index) {
      return this.changes[index]
    },

    canUndo () {
      return this.changes.length && this.pointer > 0
    },

    canRedo () {
      return this.pointer < this.changes.length - 1
    },

    restoreState () {
      let state = this.getState(this.pointer)
      // console.log(state)
      setState(state)
      morphEmailEditor()
      updateStyles()
    },

    undo () {

      if (!this.canUndo()) {
        return
      }

      this.pointer--
      this.restoreState()
    },

    redo () {

      if (!this.canRedo()) {
        return
      }

      this.pointer++
      this.restoreState()
    },

    clear () {
      this.pointer = 0
      this.changes = []
    },
  }

  document.addEventListener('keydown', e => {
    if (e.key === 'Z' && e.ctrlKey && e.shiftKey) {
      History.redo()
    }

    if (e.key === 'z' && e.ctrlKey && !e.shiftKey) {
      History.undo()
    }
  })

  document.addEventListener('keydown', e => {
    if (e.key === 'y' && e.ctrlKey) {
      History.redo()
    }
  })

  let State = {
    email: {},
    activeBlock: null,
    openPanels: {},
    blockControlsTab: 'block',
    emailControlsTab: 'email',
    isGeneratingHTML: false,
    hasChanges: false,
    preview: '',
  }

  const setState = newState => {
    State = {
      ...State,
      ...newState,
      id: uuid(),
    }
  }

  const getState = () => State

  let onSave = () => {}
  let onClose = () => {}

  /**
   * Saves the email
   *
   * @return {Promise<*>}
   */
  const saveEmail = () => {

    // Save editor settings
    Groundhogg.stores.options.patch({
      gh_email_editor_color_palette: colorPalette,
      gh_email_editor_global_fonts: GlobalFonts.fonts,
    })

    // No ID, creating the email
    if (!State.email.ID) {

      return EmailsStore.create(State.email).then(email => {
        dialog({
          message: 'Email created!',
        })

        if (isEmailEditorPage()) {
          window.history.pushState({}, `${email.data.title} &lsaquo; ${__('Edit')}`,
            adminPageURL('gh_emails', {
              action: 'edit',
              email: email.ID,
            }))
        }

        setState({
          email,
          hasChanges: false,
        })

        onSave(email)
      })

    }

    return EmailsStore.patch(State.email.ID, State.email).then(email => {
      dialog({
        message: 'Email updated!',
      })

      setState({
        email,
        hasChanges: false,
      })

      onSave(email)
    })
  }

  /**
   * Updates the preview of the email
   *
   * @return {*}
   */
  function updatePreview () {

    setState({
      previewLoading: true,
    })

    morphHeader()

    if (this.timeout) {
      clearTimeout(this.timeout)
    }

    const reset = () => {
      this.controller = new AbortController()
      this.signal = this.controller.signal
    }

    this.timeout = setTimeout(() => {

      if (!this.controller) {
        reset()
      } else {
        this.controller.abort()
        reset()
      }

      let endpoint = getEmail().ID ? `${EmailsStore.route}/${getEmail().ID}/preview` : `${EmailsStore.route}/preview`

      return post(endpoint, {
        data: getEmailData(),
        meta: getEmailMeta(),
      }, {
        signal: this.signal,
      }).then(({ item }) => {
        setState({
          preview: item.context.built,
          previewLoading: false,
        })
        morphHeader()
      }).catch(err => {})

    }, 1000)
  }

  const setActiveBlock = (idOrNull) => {
    setState({
      activeBlock: idOrNull,
    })
    morphBlocks()
    // Completely remove controls before changing to new block
    removeControls()
    morphControls()
  }

  /**
   * IF the block is active
   *
   * @param id
   * @return {boolean}
   */
  const isActiveBlock = (id) => State.activeBlock === id
  const hasActiveBlock = () => State.activeBlock !== null
  const getActiveBlock = () => __findBlock(State.activeBlock, getBlocks())
  const getBlockControlsTab = () => State.blockControlsTab
  const getEmailControlsTab = () => State.emailControlsTab
  const setBlockControlsTab = (tab) => State.blockControlsTab = tab
  const setEmailControlsTab = (tab) => State.emailControlsTab = tab
  const getBlocks = () => State.email.meta.blocks
  const getBlocksCopy = () => JSON.parse(JSON.stringify(State.email.meta.blocks))
  const getEmail = () => State.email
  const getEmailData = () => State.email.data
  const getEmailMeta = () => State.email.meta
  const getParentBlocks = () => {}
  const isGeneratingHTML = () => State.isGeneratingHTML
  const setIsGeneratingHTML = isGenerating => State.isGeneratingHTML = isGenerating
  const setEmailData = (data = {}) => {
    State.email.data = {
      ...State.email.data,
      ...data,
    }

    setState({
      hasChanges: true,
    })
  }

  const setEmailMeta = (meta = {}) => {
    State.email.meta = {
      ...State.email.meta,
      ...meta,
    }

    setState({
      hasChanges: true,
    })
  }

  /**
   * Update the settings of the email
   *
   * @param reRender
   * @param newSettings
   */
  const updateSettings = ({ reRender = false, ...newSettings }) => {
    setEmailMeta(newSettings)

    if (reRender) {
      morphEmailEditor()
    }

    updatePreview()

    History.addChange(getStateCopy())
  }

  function getSubstringUpToSecondHyphen (inputString) {
    const regex = /^([^-]*-[^-]*)/
    const match = inputString.match(regex)
    return match ? match[1] : inputString
  }

  const closeOtherPanels = panel => {
    for (let p in State.openPanels) {
      if (p === panel || !p.includes(getSubstringUpToSecondHyphen(panel))) {
        continue
      }

      State.openPanels[p] = false
    }
  }
  const openPanel = panel => {
    State.openPanels[panel] = true
    closeOtherPanels(panel)
  }
  const closePanel = panel => State.openPanels[panel] = false
  const togglePanel = panel => isPanelOpen(panel) ? closePanel(panel) : openPanel(panel)
  const isPanelOpen = panel => State.openPanels[panel]
  const getTemplate = () => DesignTemplates.find(t => t.id === getEmailMeta().template) || DesignTemplates[0]

  const setBlocks = (blocks = [], hasChanges = true) => {

    let css = renderBlocksCSS(blocks)
    let content = renderBlocksHTML(blocks)
    let plain_text = renderBlocksPlainText(blocks)

    setState({
      hasChanges,
      email: {
        ...State.email,
        data: {
          ...State.email.data,
          content,
        },
        meta: {
          ...State.email.meta,
          blocks,
          css,
          plain_text,
        },
      },
    })

    if (hasChanges) {
      updatePreview()
    }

    History.addChange(getStateCopy())
  }

  /**
   * Parse HTML content to make better plain text emails
   *
   * @param node
   * @return {string|*}
   */
  function extractPlainText (node) {

    if (node.nodeType === Node.TEXT_NODE) {
      return node.textContent
    } else if (node.nodeType === Node.ELEMENT_NODE) {
      const tagName = node.tagName.toLowerCase()

      if ([
        'div',
        'p',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'span',
        'b',
        'a',
        'strong',
        'em',
        'i',
        'ul',
        'ol',
        'li',
        'body',
        'br',
      ].includes(tagName)) {
        let text = ''
        let index = Array.from(node.parentNode.childNodes).indexOf(node)

        for (const childNode of node.childNodes) {
          text += extractPlainText(childNode)
        }

        if (tagName === 'a') {
          return `${text} (${node.getAttribute('href')})`
        }

        if (tagName === 'br') {
          return '\n'
        }

        if (tagName === 'li') {

          if (node.parentNode.tagName.toLowerCase() === 'ol') {
            return `\n${index + 1}. ${text}`
          }

          return `\n- ${text}`
        }

        if (['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tagName) && index > 0) {
          return `\n\n${text}`
        }

        if (['ul', 'ol'].includes(tagName) && index > 0) {
          return `\n${text}`
        }

        return text
      }
    }

    return ''
  }

  const TopRightBottomLeft = ({
    id = '',
    values = {},
    onChange = (values) => {},
  }) => {

    values = {
      top: '', right: '', bottom: '', left: '', linked: true,
      ...values,
    }

    const setValue = (which, val) => {

      if (values.linked) {
        values = {
          ...values,
          top: val,
          right: val,
          bottom: val,
          left: val,
        }
      } else {
        values = {
          ...values,
          [which]: val,
        }
      }

      onChange(values)
    }

    const toggleLinked = () => {
      values.linked = !values.linked
      setValue('top', values.top)
    }

    return Div({
      className: 'gh-input-group',
      id,
    }, [
      Input({
        type: 'number',
        id: `${id}-top`,
        name: 'top',
        value: values.top,
        className: `design-attr full-width`,
        placeholder: 'Top',
        onInput: e => setValue('top', e.target.value),
      }),
      Input({
        type: 'number',
        id: `${id}-right`,
        name: 'right',
        value: values.right,
        className: `design-attr full-width`,
        placeholder: 'Right',
        onInput: e => setValue('right', e.target.value),
      }),
      Input({
        type: 'number',
        id: `${id}-bottom`,
        name: 'bottom',
        value: values.bottom,
        className: `design-attr full-width`,
        placeholder: 'Bottom',
        onInput: e => setValue('bottom', e.target.value),
      }),
      Input({
        type: 'number',
        id: `${id}-left`,
        name: 'left',
        value: values.left,
        className: `design-attr full-width`,
        placeholder: 'Left',
        onInput: e => setValue('left', e.target.value),
      }),
      Button({
        id: `${id}-link-toggle`,
        className: `gh-button ${values.linked ? 'primary' : 'secondary'} icon small`,
        value: values.linked ? 'linked' : 'not-linked',
        dataLinked: values.linked,
        onClick: e => toggleLinked(),
      }, icons.link),
    ])
  }

  const ColorPicker = ({
    id = '',
    onChange = color => {},
    value = '',
    ...attributes
  }) => {

    return Div({
      className: 'gh-color-picker',
      id,
    }, [
      Div({
        id: `${id}-current`,
        className: 'current-color',
        style: {
          backgroundColor: value,
        },
      }),
      Button({
        id: `${id}-open-picker-${value.substr(1)}`,
        className: 'gh-button secondary small',
        onClick: e => {

          MiniModal({
            selector: `#${id}`,
            onOpen: () => {
              let $picker = $(`#${id}-picker`)

              $picker.iris({
                hide: false,
                border: false,
                color: value,
                palettes: colorPalette,
                change: (e, ui) => {
                  document.getElementById(`${id}-current`).style.backgroundColor = ui.color.toString()
                  onChange(ui.color.toString())
                },
              })
            },
          }, Div({
            className: 'gh-color-picker-grid',
          }, [
            Input({
              type: 'text',
              id: `${id}-picker`,
              className: 'full-width code',
              style: {
                marginBottom: '10px',
              },
              value,
            }),
            Button({
              id: `${id}-clear`,
              className: 'gh-button secondary small clear-color',
              onClick: e => {
                onChange('')
                let $picker = $(`#${id}-picker`)
                $picker.val('')
                $picker.iris('color', '')
                document.getElementById(`${id}-current`).style.backgroundColor = ''
              },
            }, 'Clear'),
          ]))

        },
      }, 'Select Color'),
    ])
  }

  const ImageControls = ({
    id = '',
    maxWidth = 0,
    image = {},
    onChange = ({ src, alt, width }) => {},
    supports = { alt: true, width: true },
  }) => {

    const setImage = (newProps) => {
      image = {
        ...image,
        ...newProps,
      }

      onChange(image)
    }

    return Fragment([
      Control({
        label: 'Image SRC',
        stacked: true,
      }, InputGroup([
        Input({
          type: 'text',
          id: `${id}-src`,
          value: image.src,
          className: 'control full-width',
          name: 'src',
          onChange: e => {
            setImage({
              src: e.target.value,
            })
          },
        }),
        Button({
          id: `${id}-select`,
          className: 'gh-button secondary icon',
          onClick: e => {

            let file_frame

            e.preventDefault()
            // If the media frame already exists, reopen it.
            if (file_frame) {
              // Open frame
              file_frame.open()
              return
            }
            // Create the media frame.
            file_frame = wp.media.frames.file_frame = wp.media({
              title: __('Select a image to upload'),
              button: {
                text: __('Use this image'),
              },
              multiple: false,	// Set to true to allow multiple files to be selected

            })
            // When an image is selected, run a callback.
            file_frame.on('select', function () {
              // We set multiple to false so only get one image from the uploader
              let attachment = file_frame.state().get('selection').first().toJSON()

              let { height, width } = attachment

              if (maxWidth) {
                width = Math.min(maxWidth, width)
              }

              setImage({
                src: attachment.url,
                alt: attachment.alt,
                // title: attachment.title,
                width: width,
              })
            })
            // Finally, open the modal
            file_frame.open()
          },
        }, icons.image),
      ])),
      supports.width ? Control({
        label: 'Width',
      }, Input({
        id: `${id}-width`,
        type: 'number',
        className: 'control-input',
        max: maxWidth,
        value: image.width,
        onChange: e => {
          setImage({
            width: parseInt(e.target.value),
          })
        },
      })) : null,
      supports.alt ? Control({
        label: 'Alt Text',
      }, Input({
        id: `${id}-alt`,
        className: 'input',
        value: image.alt,
        onChange: e => {
          setImage({
            alt: e.target.value,
          })
        },
      })) : null,
    ])
  }

  const AlignmentButtons = ({
    alignment = 'left',
    onChange = alignment => {},
  }) => {

    return Div({
      className: 'gh-input-group',
    }, [
      Button({
        className: `gh-button change-alignment align-left gh-button ${alignment === 'left' ? 'primary' : 'secondary'}`,
        onClick: e => onChange('left'),
      }, icons.alignLeft),
      Button({
        className: `gh-button change-alignment align-center gh-button ${alignment === 'center'
          ? 'primary'
          : 'secondary'}`,
        onClick: e => onChange('center'),
      }, icons.alignCenter),
      Button({
        className: `gh-button change-alignment align-right gh-button ${alignment === 'right'
          ? 'primary'
          : 'secondary'}`,
        onClick: e => onChange('right'),
      }, icons.alignRight),
    ])
  }

  const BorderControlGroup = ({
    borderStyle = 'none',
    borderWidth = {},
    borderColor = '',
    borderRadius = {},
    onChange = style => {},
  }) => {

    return ControlGroup({ name: 'Border' }, [
      Control({
        label: 'Style',
      }, Select({
        id: 'border-style',
        options: {
          none: __('None', 'groundhogg'),
          solid: __('Solid', 'groundhogg'),
          dashed: __('Dashed', 'groundhogg'),
          dotted: __('Dotted', 'groundhogg'),
        },
        selected: borderStyle,
        onChange: e => onChange({ borderStyle: e.target.value }),
      })),
      Control({
        label: __('Color', 'groundhogg'),
      }, ColorPicker({
        type: 'text',
        id: 'border-color',
        value: borderColor,
        onChange: borderColor => onChange({
          borderColor,
        }),
      })),
      Control({
        label: 'Width',
        stacked: true,
      }, TopRightBottomLeft({
        id: 'border-width',
        values: borderWidth,
        onChange: borderWidth => {
          onChange({
            borderWidth,
          })
        },
      })),
      Control({
        label: 'Radius',
        stacked: true,
      }, TopRightBottomLeft({
        id: 'border-radius',
        values: borderRadius,
        onChange: borderRadius => {
          onChange({
            borderRadius,
          })
        },
      })),
    ])
  }

  const extract4 = ({ top = 0, right = 0, bottom = 0, left = 0 }) => {
    const usePixel = num => {
      num = isNaN(parseInt(num)) ? 0 : parseInt(num)
      return num !== 0 ? `${num}px` : num
    }
    return [
      top,
      right,
      bottom,
      left,
    ].map(usePixel).join(' ')
  }

  const addBorderStyle = (style, initial = {}) => {
    const {
      borderStyle = 'none',
      borderColor = 'transparent',
      borderWidth = {},
      borderRadius = {},
    } = style

    if (Object.values(borderRadius).some(v => v && parseInt(v) !== 0)) {
      initial.borderRadius = extract4(borderRadius)
    }

    if (borderStyle !== 'none') {
      initial.borderStyle = borderStyle
      initial.borderColor = borderColor
      initial.borderWidth = extract4(borderWidth)
    }

    return initial
  }

  const AdvancedStyleControls = {
    getInlineStyle: block => {
      const { advancedStyle = {}, id, selector } = block
      const {
        padding = {},
        // margin = {},
        borderStyle = 'none',
        borderColor = 'transparent',
        borderWidth = {},
        borderRadius = {},
        backgroundColor = 'transparent',
        backgroundImage = '',
      } = advancedStyle

      const style = {}

      if (Object.values(padding).some(v => v && parseInt(v) !== 0)) {
        style.padding = extract4(padding)
      }

      addBorderStyle({
        borderStyle,
        borderColor,
        borderWidth,
        borderRadius,
      }, style)

      if (backgroundColor !== 'transparent') {
        style.backgroundColor = backgroundColor
      }

      if (backgroundImage) {
        style.backgroundImage = `url(${backgroundImage})`
        style.backgroundSize = 'cover'
        style.backgroundRepeat = 'no-repeat'
      }

      return style
    },
    css: (block) => {
      const { selector } = block

      //language=CSS
      return `
          ${selector} {
              ${objectToStyle(AdvancedStyleControls.getInlineStyle(block))}
          }
      `
    },
    render: ({ id, advancedStyle = {}, updateBlock }) => {

      const updateStyle = ({
        reRenderControls = false,
        reRenderBlocks = false,
        ...changes
      }) => {

        advancedStyle = copyObject({
          ...advancedStyle,
          ...changes,
        })

        updateBlock({
          advancedStyle,
          reRenderControls,
          reRenderBlocks,
        })
      }

      return Fragment([
        ControlGroup({
          name: 'Layout',
        }, [
          // Control({
          //   label: 'Width',
          //   stacked: false,
          // }, Input({
          //   type: 'number',
          //   // max: getEmailMeta().width,
          //   min: 0,
          //   id: 'advanced-width',
          //   value: advancedStyle.width || '',
          //   name: 'advanced_width',
          //   onInput: e => updateStyle({
          //     width: e.target.value,
          //   }),
          // })),
          Control({
            label: 'Padding',
            stacked: true,
          }, TopRightBottomLeft({
            id: 'padding',
            values: advancedStyle.padding,
            onChange: padding => {
              updateStyle({
                padding,
                reRenderControls: true,
              })
            },
          })),
        ]),
        BorderControlGroup({
          ...advancedStyle,
          onChange: style => updateStyle({
            ...style,
            reRenderControls: true,
          }),
        }),
        ControlGroup({
          name: 'Background',
        }, [
          Control({
            label: 'Color',
          }, ColorPicker({
            type: 'text',
            id: 'background-color',
            value: advancedStyle.backgroundColor,
            onChange: backgroundColor => updateStyle({
              backgroundColor,
              reRenderControls: true,
            }),
          })),
          ImageControls({
            id: 'background-image',
            maxWidth: document.getElementById(`b-${id}`).getBoundingClientRect().width,
            image: {
              src: advancedStyle.backgroundImage || '',
            },
            supports: {
              alt: false,
              width: false,
            },
            onChange: img => {
              updateStyle({
                backgroundImage: img.src,
                reRenderControls: true,
              })
            },
          }),
          `<p><i>Background images do not function in any Windows desktop client. Always set the background color as a fallback.</i></p>`,
        ]),
      ])
    },
  }

  const BlockRegistry = {

    get (type) {
      return this.blocks[type]
    },

    css (block) {

      let css = ''

      try {
        css += this.get(block.type).css({
          ...this.defaults(block),
          ...block,
          selector: `#b-${block.id}`,
        })
      } catch (e) {
        // css += '\n\n//oops\n\n'
      }

      css += '\n\n' + AdvancedStyleControls.css({
        ...this.defaults(block),
        ...block,
        selector: `#b-${block.id}`,
      })

      if (block.css) {
        css += '\n\n' + block.css.replaceAll(/selector/g, `#b-${block.id}`)
      }

      return css
    },

    edit (block, editing) {
      return this.get(block.type).edit({
        ...this.defaults(block),
        ...block,
      }, editing)
    },

    defaults ({ type }) {
      return this.get(type).defaults
    },

    html (block, editing) {
      return this.get(block.type).html({
        ...this.defaults(block),
        ...block,
      }, editing)
    },

    controls (block) {
      return this.get(block.type).controls({
        ...this.defaults(block),
        ...block,
      })
    },

    collections: {
      core: 'Groundhogg',
    },

    blocks: {},

  }

  /**
   * Register a new block
   *
   * @param type
   * @param name
   * @param edit function
   * @param html function
   * @param block
   */
  const registerBlock = (type, name, { edit = false, html = false, ...block }) => {

    // If edit is undefined copy the html method
    if (!edit) {
      edit = html
    }

    BlockRegistry.blocks[type] = {
      type,
      name,
      collection: 'core',
      edit,
      html,
      ...block,
    }
  }

  const dynamicContentCache = {
    cache: {},
    has (key) {
      return this.cache.hasOwnProperty(key)
    },
    get (key, _default = false) {
      return this.has(key) ? this.cache[key] : _default
    },
    set (key, value) {
      this.cache[key] = value
    },
  }

  const base64_json_encode = (stuff) => {
    return utf8_to_b64(JSON.stringify(stuff))
  }

  function utf8_to_b64 (str) {
    return window.btoa(unescape(encodeURIComponent(str)))
  }

  const registerDynamicBlock = (type, name, { generateCacheKey, ...block }) => {

    let prevContent = null
    let timeout

    const fetchDynamicContent = (block) => {

      if (timeout) {
        clearTimeout(timeout)
      }

      timeout = setTimeout(async () => {
        let { content = '' } = await get(`${EmailsStore.route}/blocks/${block.type}?props=${base64_json_encode(block)}`)
        dynamicContentCache.set(generateCacheKey(block), content)
        prevContent = content
        morphBlocks()
      }, 1000)
    }

    const renderHtml = ({ updateBlock = () => {}, ...block }) => {

      let cacheKey = generateCacheKey(block)

      if (dynamicContentCache.has(cacheKey)) {
        return dynamicContentCache.get(cacheKey)
      }

      return Div({
        id: `dynamic-content-${block.id}`,
        onCreate: el => {
          fetchDynamicContent(block)
        },
      }, Div({
        className: 'dynamic-content-loader',
      }, prevContent))

    }

    registerBlock(type, name, {
      ...block,
      edit: renderHtml,
      html: (block) => isGeneratingHTML() ? `<!-- ${type}:${block.id} -->` : renderHtml(block),
      plainText: ({ type, id }) => `[[${type}:${id}]]`,
    })
  }

  const Control = ({
    label = '',
    stacked = false,
    ...rest
  }, control) => {

    let labelProps = {
      className: 'control-label',
    }

    if (!Array.isArray(control) && control) {

      if (control.id) {
        labelProps.for = control.id
      } else {
        let inputElement = control.querySelector('select, input, textarea')

        if (inputElement && inputElement.id) {
          labelProps.for = inputElement.id
        }
      }
    }

    return Div({
      className: stacked ? 'display-flex column gap-10' : 'space-between',
      dataFor: hasActiveBlock() ? getActiveBlock().id : null,
      ...rest,
    }, [
      makeEl('label', labelProps, label),
      control,
    ])
  }

  const ControlGroup = ({
    name = '',
    closable = true,
  }, controls) => {

    let panel = ''

    if (hasActiveBlock()) {
      panel = `${getActiveBlock().type}-${getBlockControlsTab()}-${name.toLowerCase().replaceAll(' ', '-')}`

      // Check to see if the block has no open panels
      if (!Object.keys(getState().openPanels).
      some(panelId => panelId.startsWith(`${getActiveBlock().type}-${getBlockControlsTab()}`) &&
        State.openPanels[panelId])) {
        // Open this one by default
        openPanel(panel)
      }

    } else {

      panel = `email-${getEmailControlsTab()}-${name.toLowerCase().replaceAll(' ', '-')}`

      // Check to see if the block has no open panels
      if (!Object.keys(getState().openPanels).
      some(panelId => panelId.startsWith(`email-${getEmailControlsTab()}`) &&
        State.openPanels[panelId])) {
        // Open this one by default
        openPanel(panel)
      }
    }

    return Div({
      className: `gh-panel control-group ${isPanelOpen(panel) || !closable ? 'open' : 'closed'}`,
      dataFor: getActiveBlock().id,
      id: panel,
    }, [
      Div({
        className: 'gh-panel-header',
        onClick: e => {
          if (closable) {
            openPanel(panel)
            morphControls()
          }
        },
      }, [
        `<h2>${name}</h2>`,
        closable ? Button({
          className: 'toggle-indicator',
        }) : null,
      ]),
      Div({
        className: 'inside controls display-flex column gap-10',
      }, controls),
    ])
  }

  const BlockToolbar = ({
    block,
    duplicateBlock,
    deleteBlock,
  }) => {

    return Div({
      className: 'block-toolbar',
    }, [
      Button({
        className: 'gh-button secondary small text icon move-block',
      }, icons.move),
      Button({
        className: 'gh-button secondary small text icon duplicate-block',
        id: `duplicate-${block.id}`,
        onClick: e => {
          duplicateBlock(block.id)
        },
      }, icons.duplicate),
      Button({
        className: 'gh-button secondary small text icon delete-block',
        id: `delete-${block.id}`,
        onClick: e => {
          deleteBlock(block.id)
        },
      }, icons.close),
    ])
  }

  const BlockEdit = (block) => {
    return BlockRegistry.edit({
      ...block,
      updateBlock,
    })
  }

  const BlockHTML = (block) => {

    let { advancedStyle = {} } = block
    let {
      backgroundColor = '',
      backgroundImage = '',
    } = advancedStyle

    return Tr({}, [
      `<!-- START:${block.id} -->`,
      Td({
          id: `b-${block.id}`,
          style: {
            ...AdvancedStyleControls.getInlineStyle(block),
            overflow: 'hidden',
          },
          bgcolor: backgroundColor,
          background: backgroundImage,
          valign: 'top',
          // width: '100%',
        }, [
          BlockRegistry.html(block),
        ],
      ),
      `<!-- END:${block.id} -->`,
    ])
  }

  const EditBlockWrapper = (block) => {

    return Div({
      id: `b-${block.id}`,
      className: `builder-block ${isActiveBlock(block.id) ? 'is-editing' : ''}`,
      dataId: block.id,
      dataType: block.type,
      onClick: e => {
        e.preventDefault()

        if (clickedIn(e, '.delete-block, .duplicate-block')) {
          return
        }

        if (isActiveBlock(block.id)) {
          e.stopPropagation()
          return
        }

        setActiveBlock(block.id)
        e.stopPropagation()
      },
    }, [
      isActiveBlock(block.id) ? BlockEdit(block) : BlockRegistry.html(block),
      block.filters_enabled ? Div({
        className: 'filters-enabled',
      }, icons.eye) : null,
      BlockToolbar({
        block,
        duplicateBlock,
        deleteBlock,
      }),
    ])
  }

  const createBlock = (type, props = {}) => {

    return {
      id: uuid(),
      type,
      advancedStyle: {},
      ...copyObject(BlockRegistry.blocks[type].defaults),
      ...props,
    }
  }

  /**
   * Find the parent of a block
   *
   * @private
   */
  const __findParent = (blockId, blocks, parent = false) => {

    // Block is root level
    if (blocks.some(b => b.id === blockId)) {
      return parent
    }

    // Find columns
    let columnBlocks = blocks.filter(b => b.columns && Array.isArray(b.columns))

    for (let columnBlock of columnBlocks) {
      for (let column of columnBlock.columns) {
        if (__findParent(blockId, column, columnBlock)) {
          return columnBlock
        }
      }
    }

    return false
  }

  /**
   * Recursively find a block in the block array
   *
   * @param blockId
   * @param blocks
   * @return {*|boolean}
   * @private
   */
  const __findBlock = (blockId, blocks) => {

    for (let block of blocks) {
      if (block.id === blockId) {
        return block
      }

      if (block.columns && Array.isArray(block.columns)) {
        try {
          for (let column of block.columns) {
            let found = __findBlock(blockId, column)
            if (found) {
              return found
            }
          }
        } catch (e) {
          // console.log(e, block)
        }

      }
    }

    return false

  }

  /**
   * Add a block at the specified location
   *
   * @param newBlock
   * @param parent
   * @param column
   * @param index
   * @param blocks
   * @return {*|boolean}
   * @private
   */
  const __insertBlock = (newBlock, index = 0, blocks = [], parent = false, column = 0) => {

    if (!parent) {
      blocks.splice(index, 0, newBlock)
      return true
    }

    for (let block of blocks) {
      if (block.id === parent && block.columns && Array.isArray(block.columns)) {
        return __insertBlock(newBlock, index, block.columns[column])
      }

      if (block.columns && Array.isArray(block.columns)) {
        for (let _column of block.columns) {
          let inserted = __insertBlock(newBlock, index, _column, parent, column)
          if (inserted) {
            return true
          }
        }
      }
    }

    return false

  }

  /**
   * Insert a block after the given block
   *
   * @param newBlock
   * @param blockId
   * @param blocks
   * @return {boolean|*}
   * @private
   */
  const __insertAfter = (newBlock, blockId, blocks) => {

    for (let block of blocks) {
      if (block.id === blockId) {
        blocks.splice(blocks.findIndex(b => b.id === blockId) + 1, 0, newBlock)
        return true
      }

      if (block.columns && Array.isArray(block.columns)) {
        for (let column of block.columns) {
          if (__insertAfter(newBlock, blockId, column)) {
            return true
          }
        }
      }
    }

    return false
  }

  /**
   * Find a block recursively and delete it
   *
   * @param blockId
   * @param blocks
   * @return {boolean|*}
   * @private
   */
  const __deleteBlock = (blockId, blocks) => {

    for (let block of blocks) {
      if (block.id === blockId) {
        blocks.splice(blocks.findIndex(b => b.id === blockId), 1)
        return true
      }

      if (block.columns && Array.isArray(block.columns)) {
        for (let column of block.columns) {
          if (__deleteBlock(blockId, column)) {
            return true
          }
        }
      }
    }

    return false
  }

  /**
   * Replace the ID of a block when duplicating it
   *
   * @param block
   * @return {*&{id: *}}
   * @private
   */
  const __replaceId = (block) => {

    if (block.columns && Array.isArray(block.columns)) {
      block.columns = block.columns.map(column => column.map(_block => __replaceId(_block)))
    }

    return {
      ...block,
      id: uuid(),
    }

  }

  /**
   * Update the blocks with the edited block
   */
  const __updateBlocks = (blocks, edited) => {
    return blocks.map(block => {

      if (block.id === edited.id) {
        return edited
      }

      if (block.type === 'columns' && Array.isArray(block.columns)) {
        block.columns = block.columns.map(column => __updateBlocks(column, edited))
      }

      return block

    })

  }

  /**
   * Add a block to the main column structure
   *
   * @param type the block type
   * @param index
   * @param parent
   * @param column
   */
  const addBlock = (type, index = 0, parent = false, column = 0) => {

    let newBlock = createBlock(type)
    let tempBlocks = getBlocksCopy()

    __insertBlock(newBlock, index, tempBlocks, parent, column)

    setBlocks(tempBlocks)
    morphBlocks()
    updateStyles()
  }

  /**
   * Move a block from one place to another
   *
   * @param blockId
   * @param index
   * @param parent
   * @param column
   */
  const moveBlock = (blockId, index = 0, parent = false, column = 0) => {

    let tempBlocks = getBlocksCopy()
    let block = __findBlock(blockId, tempBlocks)

    __deleteBlock(blockId, tempBlocks)

    __insertBlock(block, index, tempBlocks, parent, column)

    setBlocks(tempBlocks)
    morphBlocks()
    updateStyles()
  }

  /**
   * Delete a block
   *
   * @param blockId
   */
  const deleteBlock = (blockId) => {
    let tempBlocks = getBlocksCopy()

    if (isActiveBlock(blockId)) {
      setActiveBlock(null)
    }

    __deleteBlock(blockId, tempBlocks)

    setBlocks(tempBlocks)
    morphBlocks()
    updateStyles()
  }

  /**
   * Duplicates a block
   *
   * @param blockId
   */
  const duplicateBlock = (blockId) => {
    let tempBlocks = getBlocksCopy()
    let block = __findBlock(blockId, tempBlocks)

    __insertAfter(__replaceId(copyObject(block)), blockId, tempBlocks)

    setBlocks(tempBlocks)
    morphBlocks()
    updateStyles()
  }

  /**
   * Update the active block with new settings
   *
   * @param reRenderBlocks
   * @param reRenderControls
   * @param newSettings
   */
  const updateBlock = ({
    reRenderBlocks = true,
    reRenderControls = false,
    ...newSettings
  }) => {

    let tempBlocks = getBlocksCopy()

    setBlocks(__updateBlocks(tempBlocks, {
      ...getActiveBlock(),
      ...newSettings,
    }))

    if (reRenderBlocks) {
      morphBlocks()
    }

    if (reRenderControls) {
      morphControls()
    }

    updateStyles()
  }

  const isEmailEditorPage = () => _BlockEditor.hasOwnProperty('email')

  const makeSortable = el => {
    const sortableHelper = (e, $el) => {
      let blockId = $el.data('id')
      let blockType = $el.data('type')

      return `
			<div class="block gh-panel" data-id="${blockId}">
				<div class="icon">
					${BlockRegistry.blocks[blockType].svg}
				</div>
			</div>`
    }
    $(el).sortable({
      placeholder: 'block-placeholder',
      connectWith: '.sortable-blocks',
      handle: '.move-block',
      helper: sortableHelper,
      cancel: '',
      tolerance: 'pointer',
      start: (e, ui) => {
        ui.helper.width(50)
        ui.helper.height(50)
      },
      cursorAt: {
        left: 70,
        top: 5,
      },
      receive: (e, ui) => {

        // moving block
        let parent = $(e.target).is('.column') ? $(e.target).closest('.builder-block').data('id') : false
        let column = parseInt(e.target.dataset.col)

        // adding block
        if (ui.item.is('.new-block')) {

          let type = ui.item.data('type')
          let index = ui.helper.index()

          addBlock(type, index, parent, column)
          return
        }

      },
      update: (e, ui) => {

        // moving block
        let parent = $(e.target).is('.column') ? $(e.target).closest('.builder-block').data('id') : false
        let column = parseInt(e.target.dataset.col)

        // moving block
        let blockId = ui.item.data('id')
        let index = ui.item.index()

        if (blockId) {
          moveBlock(blockId, index, parent, column)
        }
      },
    })
  }

  /**
   * The blocks content
   *
   * @return {*}
   * @constructor
   */
  const BlockEditorContent = () => {

    return Div({
      id: 'builder-content',
      className: 'sortable-blocks',
      onCreate: el => {
        makeSortable(el)
      },
    }, getBlocks().filter(b => b.type).map(block => EditBlockWrapper(block)))

  }

  /**
   * Draggable component from the left hand side of the editor
   *
   * @constructor
   */
  const Block = ({ type, name, svg }) => {
    // language=HTML
    return `
		<div class="block-wrap">
			<div class="block new-block gh-panel" data-type="${type}">
				<div class="icon">
					${svg}
				</div>
			</div>
			<div class="block-name">${name}</div>
		</div>
    `
  }

  /**
   * List of addable blocks to the editor
   *
   * @return {*[]}
   * @constructor
   */
  const Blocks = () => {
    return Div({
      id: 'blocks-panel',
      onCreate: el => {
        $(el).find('.block').draggable({
          connectToSortable: '.sortable-blocks',
          helper: 'clone',
          revert: 'invalid',
          revertDuration: 0,
          start: (e, ui) => {
            ui.helper.addClass('dragging')
          },
        })
      },
    }, Object.values(BlockRegistry.blocks).map(b => Block(b)))
  }

  const AdvancedBlockControls = () => {
    return Fragment([
      ControlGroup({
          name: 'Conditional Visibility',
        },
        [
          Control({
            label: 'Enable conditional visibility',
          }, Toggle({
            id: 'toggle-filters',
            checked: getActiveBlock().filters_enabled || false,
            onChange: e => updateBlock({ filters_enabled: e.target.checked, reRenderControls: true }),
          })),
          getActiveBlock().filters_enabled ? Div({
            id: 'block-include-filters',
            onCreate: el => {
              setTimeout(() => {
                Groundhogg.filters.functions.createFilters(
                  '#block-include-filters', getActiveBlock().include_filters, (include_filters) => {
                    updateBlock({
                      include_filters,
                      reRenderBlocks: false,
                    })
                  }).init()
              })
            },
          }) : null,
          getActiveBlock().filters_enabled ? Div({
            id: 'block-exclude-filters',
            onCreate: el => {
              setTimeout(() => {
                Groundhogg.filters.functions.createFilters(
                  '#block-exclude-filters', getActiveBlock().exclude_filters, (exclude_filters) => {
                    updateBlock({
                      exclude_filters,
                      reRenderBlocks: false,
                    })
                  }).init()
              })
            },
          }) : null,
        ]),
      ControlGroup({ name: 'Custom CSS' }, [
        Textarea({
          id: 'code-css-editor',
          value: getActiveBlock().css || '',
          onCreate: el => {

            // Wait for add to dom
            setTimeout(() => {

              let editor = wp.codeEditor.initialize('code-css-editor', {
                ...wp.codeEditor.defaultSettings,
                codemirror: {
                  ...wp.codeEditor.defaultSettings.codemirror,
                  mode: 'text/css',
                  gutters: [
                    'CodeMirror-lint-markers',
                  ],
                },
              }).codemirror

              editor.on('change', instance => updateBlock({
                css: instance.getValue(),
              }))

              editor.setSize(null, 300)
            }, 100)
          },
        }),
        `<p>Use the <code>selector</code> tag to target elements withing the current block.</p>`,
        `<p>CSS entered here may not be universally supported by email clients. Check your <a href="https://www.campaignmonitor.com/css/" target="_blank">CSS compatibility</a>.</p>`
      ]),
    ])
  }

  const BlockControls = () => {
    let controls
    switch (getBlockControlsTab()) {
      case 'block':
        controls = BlockRegistry.get(getActiveBlock().type).controls({
          ...getActiveBlock(),
          updateBlock,
        })
        break
      case 'advanced':
        controls = Fragment([
          AdvancedStyleControls.render({
            ...getActiveBlock(),
            updateBlock,
          }),
          AdvancedBlockControls(),
        ])
    }

    if (Array.isArray(controls)) {
      controls = Fragment(controls)
    }

    return Fragment([
      controls,
    ])
  }

  const AdvancedEmailControls = () => {

    let customHeaders = getEmailMeta().custom_headers || {}

    return Fragment([
      ControlGroup({
        name: 'Custom Headers',
      }, [
        InputRepeater({
          id: 'custom-headers-editor',
          rows: Object.keys(customHeaders).map(k => ([k, customHeaders[k]])),
          cells: [
            props => Input({
              ...props,
              placeholder: 'Key',
            }),
            props => Input({
              ...props,
              placeholder: 'Value',
            }),
          ],
          onChange: rows => {

            customHeaders = {}

            rows.forEach(([key, val]) => customHeaders[key] = val)

            setEmailMeta({
              custom_headers: customHeaders,
            })
          },
        }),
        `<p>${__('You can define custom email headers and override existing ones.')}</p>`,
        `<p>${__('For example <code>X-Custom-Header</code> <code>From</code> <code>Bcc</code> <code>Cc</code>')}</p>`,
      ]),
    ])
  }

  const BasicEmailControls = () => {

    let {
      message_type = 'marketing',
      reply_to_override = '',
      browser_view = false,
    } = getEmailMeta()

    let { from_user } = getEmailData()

    let fromOptions = [
      { id: 0, text: __('Contact Owner') },
      { id: 'default', text: `${Groundhogg.defaults.from_name} &lt;${Groundhogg.defaults.from_email}&gt;` },
      ...Groundhogg.filters.owners.map(({ data, ID }) => ({
        id: ID,
        text: `${data.display_name} &lt;${data.user_email}&gt;`,
      }))]

    let replyToOptions = [
      Groundhogg.defaults.from_email,
      ...Groundhogg.filters.owners.map(({ data }) => data.user_email),
    ].filter(onlyUnique)

    return Fragment([
      ControlGroup({
        name: 'Email Settings',
        closable: false,
      }, [
        Control({
          label: 'Send this email from...',
          stacked: true,
        }, ItemPicker({
          id: 'from-user',
          multiple: false,
          placeholder: 'Search for a sender...',
          noneSelected: 'Pick a sender...',
          fetchOptions: search => Promise.resolve(fromOptions.filter(item => item.text.includes(search))),
          selected: fromOptions.find(opt => from_user === opt.id),
          onChange: item => setEmailData({ from_user: item.id }),
        })),
        Control({
          label: 'Send replies to...',
          stacked: true,
        }, ItemPicker({
          id: 'reply-to',
          multiple: false,
          tags: true,
          isValidSelection: id => isValidEmail(id),
          placeholder: 'Type an email address...',
          noneSelected: getEmail().context.from_email,
          fetchOptions: search => Promise.resolve(
            replyToOptions.filter(item => item.includes(search)).map(em => ({ id: em, text: em }))),
          selected: reply_to_override ? { id: reply_to_override, text: reply_to_override } : [],
          onChange: item => setEmailMeta({ reply_to_override: item ? item.id : '' }),
        })),
        Control({
          label: 'Message type',
        }, Select({
          id: 'message-type',
          options: {
            marketing: 'Marketing',
            transactional: 'Transactional',
          },
          selected: message_type,
          onChange: e => {
            setEmailMeta({
              message_type: e.target.value,
            })
            // This may update the footer block
            setBlocks(getBlocks())
            morphBlocks()
          },
        })),
        Control({
          label: 'Enable browser view',
        }, Toggle({
          id: 'enable-browser-view',
          checked: Boolean(browser_view),
          onChange: e => {
            setEmailMeta({
              browser_view: e.target.checked,
            })
          },
        })),
        Control({
          label: 'Show in my templates',
        }, Toggle({
          id: 'save-as-template',
          checked: Boolean(parseInt(getEmailData().is_template)),
          onChange: e => {
            setEmailData({
              is_template: e.target.checked,
            })
          },
        })),
        `<hr/>`,
        Control({
          label: 'Template',
        }, Select({
          id: 'select-template',
          options: DesignTemplates.map(({ id, name }) => ({ value: id, text: name })),
          selected: getTemplate().id,
          onChange: e => {
            setEmailMeta({
              template: e.target.value,
            })
            morphEmailEditor()
          },
        })),
      ]),
      getTemplate().controls(),
    ])
  }

  const EditorControls = () => {

    const DisplayFont = font => Div({
      id: `font-${font.id}`,
      className: 'font space-between',
    }, [
      Span({ style: { ...fillFontStyle(font.style), margin: '0' } }, font.name),
      Div({
        className: 'display-flex',
      }, [
        Button({
          id: `delete-${font.id}`,
          className: 'gh-button danger text icon small',
          onClick: e => {

            dangerConfirmationModal({
              //language=HTML
              alert: `<p>${__('You\'re about to delete a global font! This cannot be undone.')}</p>
			  <p>${__('Any blocks currently using this font will inherit the font settings.')}</p>`,
              confirmText: 'Delete',
              onConfirm: () => {
                GlobalFonts.delete(font.id)
                morphControls()
                // close()
              },
            })
          },
        }, Dashicon('trash')),
        Button({
          id: `edit-${font.id}`,
          className: 'gh-button secondary text small icon',
          onClick: e => {
            MiniModal({
                selector: `#font-${font.id}`,
              }, ({ close }) => Div({
                className: 'display-flex column gap-10',
              }, [
                Input({
                  className: 'full-width',
                  id: `font-name`,
                  value: font.name,
                  padding: 'Font name...',
                  onChange: e => {
                    GlobalFonts.get(font.id).name = e.target.value
                    morphControls()
                  },
                }),
                // `<hr/>`,
                FontControls(GlobalFonts.get(font.id).style, style => {
                  GlobalFonts.update(font.id, style)
                  morphBlocks()
                  morphControls()
                }),
              ]),
            )
          },
        }, Dashicon('edit')),
      ]),
    ])

    return Fragment([
      // ControlGroup({ name: 'Buttons' }),
      ControlGroup({ name: 'Global Fonts' }, [
        ...GlobalFonts.fonts.map(f => DisplayFont(f)),
        `<hr/>`,
        Button({
          id: 'add-new-font',
          className: 'gh-button grey',
          onClick: e => {
            let font = GlobalFonts.add()
            morphControls()
            document.getElementById(`edit-${font.id}`).click()
          },
        }, 'Add Font'),
      ]),
      ControlGroup({ name: 'Color Palettes' }, [
        `<p>Choose up to 8 colors for the color picker.</p>`,
        InputRepeater({
          id: 'global-colors',
          rows: colorPalette.map(color => ['', color]),
          maxRows: 8,
          cells: [
            ({ onChange, value, ...props }, row) => Div({
              style: {
                width: '33px',
                flexShrink: 0,
                border: 'solid white',
                borderWidth: '3px 0 3px 3px',
                borderRadius: '5px 0 0 5px',
                backgroundColor: row[1],
              },
              ...props,
            }),
            props => Input({
              ...props,
              placeholder: '#FFFFFF',
            }),
          ],
          onChange: rows => {
            colorPalette = rows.map(r => r[1])
          },
        }),
      ]),
    ])
  }

  const EmailControls = () => {

    let controls

    switch (getEmailControlsTab()) {
      case 'email':
        controls = BasicEmailControls()
        break
      case 'advanced':
        controls = AdvancedEmailControls()
        break
      case 'editor':
        controls = EditorControls()
        break
    }

    return Fragment([
      controls,
    ])
  }

  const Navigation = () => {

    let nav

    if (hasActiveBlock()) {

      nav = Div({
        className: 'gh-button-nav',
      }, [
        Button({
          className: `tab ${getBlockControlsTab() === 'block' ? 'active' : 'inactive'}`,
          onClick: e => {
            setBlockControlsTab('block')
            morphControls()
          },
        }, __('Block')),
        Button({
          className: `tab ${getBlockControlsTab() === 'advanced' ? 'active' : 'inactive'}`,
          onClick: e => {
            setBlockControlsTab('advanced')
            morphControls()
          },
        }, __('Advanced')),
      ])

    } else {

      nav = Div({
        className: 'gh-button-nav',
      }, [
        Button({
          className: `tab ${getEmailControlsTab() === 'email' ? 'active' : 'inactive'}`,
          onClick: e => {
            setEmailControlsTab('email')
            morphControls()
          },
        }, __('Settings')),
        Button({
          className: `tab ${getEmailControlsTab() === 'advanced' ? 'active' : 'inactive'}`,
          onClick: e => {
            setEmailControlsTab('advanced')
            morphControls()
          },
        }, __('Advanced')),
        Button({
          className: `tab ${getEmailControlsTab() === 'editor' ? 'active' : 'inactive'}`,
          onClick: e => {
            setEmailControlsTab('editor')
            morphControls()
          },
        }, Dashicon('admin-settings')),
      ])

    }

    const breadcrumbs = [
      Span({
        onClick: e => {
          if (hasActiveBlock()) {
            setActiveBlock(null)
          }
        },
      }, 'Email'),
    ]

    if (hasActiveBlock()) {
      breadcrumbs.push(Span({
        className: 'slash',
      }, '/'))
      breadcrumbs.push(Span({}, BlockRegistry.get(getActiveBlock().type).name))
    }

    return Div({
      className: 'controls-nav',
    }, [
      makeEl('h2', {
        className: 'breadcrumbs',
      }, breadcrumbs),
      nav,
    ])

  }

  const ControlsPanel = () => {

    let controls
    if (hasActiveBlock()) {
      controls = BlockControls()
    } else {
      controls = EmailControls()
    }

    return Div({
        id: 'controls-panel',
        className: 'display-flex column',
      }, [
        Navigation(),
        controls,
      ],
    )
  }

  const ContentEditor = () => {

    return Div({
      id: 'content',
      className: 'gh-panel',
      onClick: e => {
        if (!clickedIn(e, '#builder-content') && !clickedIn(e, '.block-toolbar')) {
          setActiveBlock(null)
        }
      },
    }, [
      // Subject & Preview
      Div({
        className: 'inside',
      }, [
        Div({
          className: 'inline-label',
        }, [
          `<label for="subject">${__('Subject:', 'groundhogg')}</label>`,
          InputWithReplacements({
            id: 'subject-line',
            placeholder: 'Subject line...',
            value: getEmailData().subject,
            onChange: e => {
              setEmailData({
                subject: e.target.value,
              })
            },
          }),
        ]),
        Div({
          className: 'inline-label',
        }, [
          `<label for="preview-text">${__('Preview:', 'groundhogg')}</label>`,
          InputWithReplacements({
            id: 'preview-text',
            name: 'pre_header',
            placeholder: 'Preview text...',
            value: getEmailData().pre_header,
            onChange: e => {
              setEmailData({
                pre_header: e.target.value,
              })
            },
          }),
        ]),
      ]),
      // Block Editor
      Div({
        id: 'block-editor-content-wrap',
      }, getTemplate().html(
        BlockEditorContent(),
      )),
    ])
  }

  const Title = () => {

    const { isEditingTitle = false } = getState()

    let title

    const stopEditing = () => {
      if (getState().isEditingTitle) {
        setState({ isEditingTitle: false })
        morphHeader()
      }
    }

    const startEditing = () => {
      setState({ isEditingTitle: true })
      morphHeader()
    }

    if (isEditingTitle) {
      title = Input({
        id: 'admin-title-edit',
        value: getEmailData().title,
        onCreate: el => {
          setTimeout(() => {
            el.focus()
          })
        },
        onInput: e => {
          setEmailData({ title: e.target.value })
        },
        onBlur: e => {
          stopEditing()
        },
        onKeydown: e => {
          if (e.key === 'Enter') {
            stopEditing()
          }
        },
      })
    } else {
      title = Fragment([
        __('Now editing '),
        Span({
          className: 'admin-title',
          id: 'admin-title',
          onClick: e => {
            startEditing()
          },
        }, getEmailData().title),
      ])
    }

    return Div({
      className: 'admin-title-wrap',
    }, title)

  }

  const UndoRedo = () => {
    return Div({
      className: 'gh-input-group',
      id: 'undo-and-redo',
    }, [
      Button({
        id: 'editor-undo',
        className: 'gh-button secondary text',
        disabled: !History.canUndo(),
        onClick: e => {
          History.undo()
        },
      }, Dashicon('undo')),
      Button({
        id: 'editor-redo',
        className: 'gh-button secondary text',
        disabled: !History.canRedo(),
        onClick: e => {
          History.redo()
        },
      }, Dashicon('redo')),
    ])
  }

  const PreviewButtons = () => {

    return Div({
      className: `gh-input-group ${getState().previewLoading ? 'flashing' : ''}`,
    }, [
      Button({
        id: 'preview-desktop',
        className: 'gh-button secondary icon',
        disabled: !Boolean(getState().preview),
        onClick: e => {
          ModalFrame({}, Iframe({
            id: 'mobile-desktop-iframe',
            height: window.innerHeight * 0.9,
            width: Math.min(1200, window.innerWidth * 0.8),
            style: {
              backgroundColor: '#fff',
            },
            onCreate: frame => {
              setTimeout(() => {
                document.getElementById('mobile-desktop-iframe').contentDocument.body.style.padding = '20px'
              }, 100)
            },
          }, getState().preview))
        },
      }, icons.desktop),
      Button({
        id: 'preview-mobile',
        className: 'gh-button secondary icon',
        disabled: !Boolean(getState().preview),
        onClick: e => {
          ModalFrame({}, Iframe({
            id: 'mobile-preview-iframe',
            height: Math.min(915, window.innerHeight * 0.9),
            width: 412,
            style: {
              backgroundColor: '#fff',
            },
            onCreate: frame => {
              setTimeout(() => {
                document.getElementById('mobile-preview-iframe').contentDocument.body.style.padding = '20px'
              }, 100)
            },
          }, getState().preview))
        },
      }, icons.mobile),
      Button({
        id: 'preview-plain-text',
        className: 'gh-button secondary icon',
        disabled: !Boolean(getState().preview),
        onClick: e => {
          Modal({}, makeEl('p', {
            className: 'code',
          }, getEmailMeta().plain_text.replaceAll('\n', '<br/>')))
        },
      }, icons.text),
      Button({
        id: 'send-test-email',
        className: 'gh-button secondary',
        disabled: !Boolean(getState().preview),
        onClick: e => {

          Modal({}, ({ close }) => Fragment([
            `<h2>Send a test email to the following addresses...</h2>`,
            Div({
              className: 'display-flex gap-10',
            }, [
              ItemPicker({
                id: 'test-email-addresses',
                noneSelected: __('Type an email address...'),
                selected: Groundhogg.user_test_emails.map(email => ({ id: email, text: email })),
                fetchOptions: (search) => Promise.resolve(
                  Groundhogg.filters.owners.filter(user => user.data.user_email.includes(search)).map(user => ({
                    id: user.data.user_email,
                    text: user.data.user_email,
                  }))),
                onChange: items => {
                  Groundhogg.user_test_emails = items.map(({ id }) => id)
                },
                tags: true,
                style: {
                  minWidth: '300px',
                  maxWidth: '500px',
                },
              }),
              Button({
                id: 'send-test',
                className: 'gh-button primary',
                onClick: e => {

                  e.currentTarget.innerHTML = `<span class="gh-spinner"></span>`

                  let endpoint = getEmail().ID
                    ? `${EmailsStore.route}/${getEmailData().ID}/test`
                    : `${EmailsStore.route}/test`

                  post(endpoint, {
                    to: Groundhogg.user_test_emails.join(','),
                    data: getEmailData(),
                    meta: getEmailMeta(),
                  }).then((r) => {
                    dialog({
                      message: __('Test sent!'),
                    })
                    close()
                  })

                },
              }, 'Send'),
            ]),
          ]))
        },
      }, 'Send Test'),
    ])
  }

  const PublishActions = () => {

    const isDraft = () => getEmailData().status === 'draft'
    const isReady = () => getEmailData().status === 'ready'

    return Div({
      className: 'publish-actions display-flex gap-10',
    }, [
      isDraft() ? Button({
        id: 'save-draft',
        className: 'gh-button secondary text',
        onClick: e => {
          saveEmail()
        },
      }, 'Save draft') : Button({
        id: 'switch-to-draft',
        className: 'gh-button danger text',
        onClick: e => {
          dangerConfirmationModal({
            alert: `<p>Are you sure you want to switch this email to <b>draft</b>?</p><p>Doing so will prevent it from being sent in any funnels.</p>`,
            onConfirm: () => {
              setEmailData({
                status: 'draft',
              })
              saveEmail()
              morphHeader()
            },
          })
        },
      }, 'Move to draft'),
      isDraft() ? Button({
        id: 'publish-email',
        className: 'gh-button action',
        onClick: e => {

          // Subject line is required
          if (!getEmailData().subject) {
            // dialog()
          }

          setEmailData({
            status: 'ready',
          })
          e.currentTarget.innerHTML = `<span class="gh-spinner"></span>`
          saveEmail().then(morphHeader)
        },
      }, 'Publish') : Button({
        id: 'update-email',
        className: 'gh-button primary',
        onClick: e => {
          e.currentTarget.innerHTML = `<span class="gh-spinner"></span>`
          saveEmail().then(morphHeader)
        },
      }, 'Update'),
    ])

  }

  const Header = () => {

    return Div({
      id: 'email-header',
      className: 'gh-header sticky',
    }, [
      icons.groundhogg,
      Title(),
      UndoRedo(),
      PreviewButtons(),
      PublishActions(),
      getEmail().ID ? Button({
        id: 'email-more-menu',
        className: 'gh-button secondary text icon',
        onClick: e => {
          moreMenu('#email-more-menu', [
            {
              key: 'broadcast',
              text: 'Broadcast',
              onSelect: e => {

                if (getEmailData().status !== 'ready') {
                  dialog({
                    message: 'This email must be published before it can be sent!',
                    type: 'error',
                  })
                  return
                }

                modal({
                  dialogClasses: 'overflow-visible',
                  content: `<div id="gh-broadcast-form" style="width: 400px"></div>`,
                  onOpen: ({ close }) => {
                    Groundhogg.SendBroadcast('#gh-broadcast-form', {
                      email: EmailsStore.get(getEmail().ID),
                    }, {
                      onScheduled: () => {
                        close()
                        dialog({
                          message: 'Broadcast scheduled!',
                        })
                      },
                    })
                  },
                })

              },
            },
            {
              key: 'export',
              text: 'Export',
              onSelect: e => {

                window.open(adminPageURL('gh_emails', {
                  action: 'export',
                  email: getEmail().ID,
                  _wpnonce: Groundhogg.nonces._wpnonce,
                }))

              },
            },
            {
              key: 'delete',
              text: `<span class="gh-text danger">${__('Delete')}</span>`,
              onSelect: e => {

                dangerConfirmationModal({
                  alert: `<p>Are you sure you want to delete this email?</p><p>This action cannot be undone.</p>`,
                  onConfirm: () => {
                    window.open(adminPageURL('gh_emails', {
                      action: 'delete',
                      email: getEmail().ID,
                      _wpnonce: Groundhogg.nonces._wpnonce,
                    }), '_self')
                  },
                })
              },
            },
          ])
        },
      }, icons.verticalDots) : null,
      CloseButton(),
    ])
  }

  const CloseButton = () => !isEmailEditorPage() ? Button({
    id: 'close-editor',
    className: 'gh-button secondary text icon',
    onClick: e => {

      if (getState().hasChanges) {
        dangerConfirmationModal({
          alert: `<p>You have unsaved changes! Are you sure you want to leave?</p>`,
          onConfirm: onClose,
        })
        return
      }

      onClose()
    },
  }, Dashicon('no-alt')) : null

  const EmailEditor = () => {

    if (getState().page === 'templates') {
      return Div({
        id: 'email-editor',
      }, [
        // Header
        Div({
            id: 'email-header',
            className: 'gh-header is-sticky',
          },
          [
            icons.groundhogg,
            Div({
              className: 'admin-title-wrap',
              style: {
                marginRight: 'auto',
              },
            }, __('Select a template...')),
            Button({
              id: 'import-email',
              className: 'gh-button secondary',
              onClick: e => {
                Modal({}, ({ close }) => Div({}, [
                  `<h2>Select a template to import</h2>`,
                  `<p>${__(
                    'If you have a popup JSON file, you can upload it below 👇')}</p>`,
                  Input({
                    type: 'file',
                    accept: 'application/json',
                    id: 'import-email-file',
                    onChange: e => {
                      let file = e.target.files[0]

                      let reader = new FileReader()
                      reader.onload = function (e) {

                        let contents = e.target.result
                        let email = JSON.parse(contents)

                        if (!email) {
                          dialog({
                            type: 'error',
                            message: __('Invalid import. Choose another file.'),
                          })
                          return
                        }

                        if (!email.ID) {
                          dialog({
                            type: 'error',
                            message: __('Invalid import. Choose another file.'),
                          })
                          return
                        }

                        let {
                          meta,
                          data,
                        } = email

                        setEmailMeta(meta)
                        setEmailData({
                          title: data.title,
                          subject: data.subject,
                          preview_text: data.preview_text,
                        })
                        setBlocks(meta.blocks, false)
                        setState({ page: 'editor' })
                        renderEditor()
                        close()
                      }

                      reader.readAsText(file)
                    },
                  }),
                ]))
              },
            }, [Dashicon('upload'), 'Import a template']),
            Button({
              id: 'start-from-scratch',
              className: 'gh-button secondary',
              onClick: e => {
                setState({
                  page: 'editor',
                })
                morphEmailEditor()
              },
            }, 'Start from scratch'),
            CloseButton(),
          ]),
        // Templates
        TemplatePicker(),
      ])
    }

    return Div({
      id: 'email-editor',
    }, [
      // header
      Header(),
      // Block editor
      BlockEditor(),
    ])
  }

  const Template = ({
    ID,
    data,
    meta,
    context,
  }) => {

    const parser = new DOMParser()
    const doc = parser.parseFromString(context.built, 'text/html')
    doc.querySelector('html').style.zoom = '50%'
    doc.querySelector('html').style.overflow = 'hidden'
    doc.body.style.padding = '20px'
    doc.body.classList.remove('responsive')

    return Div({
      className: 'template span-4',
    }, Div({
      id: `template-${ID}`,
      className: 'gh-panel',
      onClick: e => {

        setEmailData({
          title: data.title,
        })
        setEmailMeta({
          ...meta,
        })
        setBlocks(meta.blocks)
        setState({ page: 'editor' })
        renderEditor()
      },
      onMouseenter: e => {
        const iframe = document.getElementById(`preview-${ID}`)
        // *0.51 for zoom
        iframe.style.height = (iframe.contentWindow.document.body.offsetHeight * 0.51) + 'px'
      },
      onMouseleave: e => {
        const iframe = document.getElementById(`preview-${ID}`)
        iframe.style.height = 500 + 'px'
      },
    }, [
      `<div class="overlay"></div>`,
      Iframe({
        id: `preview-${ID}`,
        className: 'template-preview',
        style: {
          // zoom: '80%',
          // width: '100%',
          backgroundColor: '#fff',
        },
      }, doc.documentElement.outerHTML),
      `<p>${data.title}</p>`,
    ]))
  }

  const TemplatePicker = () => {

    const Grid = items => Div({
      id: 'template-grid',
      className: 'display-grid gap-20',
    }, items)

    let items

    // Has templates
    EmailsStore.fetchItems({ is_template: 1, status: 'ready' }).then(items => {
      morph(Grid(items.map(t => Template(t))))
    })

    items = [...Array(9).keys()].map(k => Div({
      className: 'gh-panel span-4',
    }, [
      Div({
        className: 'skeleton-loading',
        style: {
          height: '500px',
        },
      }),
      makeEl('p', {}, Span({ className: 'skeleton-loading', style: { width: '100px' } }, '&nbsp;'.repeat(30))),
    ]))

    return Grid(items)
  }

  /**
   * The main block editor component
   *
   * @return {*}
   * @constructor
   */
  const BlockEditor = () => {

    return Div({
      id: 'email-block-editor',
    }, [
      // Blocks
      Blocks(),
      // Content
      ContentEditor(),
      // Controls
      ControlsPanel(),
    ])
  }

  const ColumnGap = (gap = 10) => Td({
    className: 'email-columns-cell gap',
    style: {
      width: `${gap}px`,
      height: `${gap}px`,
    },
    height: gap,
    width: gap,
  })

  const Column = ({ blocks = [], col, className, style = {}, verticalAlign = 'top', ...props }) => {

    if (isGeneratingHTML()) {
      return Td({
        className: `email-columns-cell ${className}`,
        style: {
          verticalAlign,
          ...style,
          fontWeight: 'normal',
        },
        ...props,
      }, Table({
        className: `column ${blocks.length ? '' : 'empty'}`,
        cellpadding: '0',
        cellspacing: '0',
        width: '100%',
      }, blocks.map(b => BlockHTML(b))))
    }

    return Td({
      className: `email-columns-cell ${className}`,
      style: {
        verticalAlign,
        ...style,
      },
      ...props,
    }, Div({
      dataCol: col,
      className: `column sortable-blocks ${blocks.length ? '' : 'empty'}`,
      onCreate: el => makeSortable(el),
    }, blocks.map(b => EditBlockWrapper(b))))
  }

  const cellReducer = (cols, col, props, i) => {

    let {
      columns = [],
      gap = 10,
      verticalAlign = 'top',
      ...rest
    } = props

    if (i > 0) {
      cols.push(ColumnGap(gap))
    }

    cols.push(Column({
      blocks: columns[i] || [],
      col: i,
      verticalAlign,
      ...col,
      ...rest,
    }))

    return cols
  }

  let columnProps = {
    oneThird: {
      className: 'one-third',
      width: '33%',
      style: {
        width: '33%',
      },
    },
    twoThirds: {
      className: 'two-third',
      width: '66%',
      style: {
        width: '66%',
      },
    },
    oneHalf: {
      className: 'one-half',
      width: '50%',
      style: {
        width: '50%',
      },
    },
    oneQuarter: {
      className: 'one-fourth',
      width: '25%',
      style: {
        width: '25%',
      },
    },
    threeQuarters: {
      className: 'three-quarters',
      width: '75%',
      style: {
        width: '75%',
      },
    },
    full: {
      className: 'full',
      width: '100%',
      style: {
        width: '100%',
      },
    },
  }

  const makeColumns = (cols, props) => cols.reduce((cols, col, i) => cellReducer(cols, col, props, i), [])

  const columnLayouts = {
    one_column: [
      columnProps.full,
    ],
    two_columns: [
      columnProps.oneHalf,
      columnProps.oneHalf,
    ],
    three_columns: [
      columnProps.oneThird,
      columnProps.oneThird,
      columnProps.oneThird,
    ],
    four_columns: [
      columnProps.oneQuarter,
      columnProps.oneQuarter,
      columnProps.oneQuarter,
      columnProps.oneQuarter,
    ],
    three_columns_center: [
      columnProps.oneQuarter,
      columnProps.oneHalf,
      columnProps.oneQuarter,
    ],
    three_columns_left: [
      columnProps.oneHalf,
      columnProps.oneQuarter,
      columnProps.oneQuarter,
    ],
    three_columns_right: [
      columnProps.oneQuarter,
      columnProps.oneQuarter,
      columnProps.oneHalf,
    ],
    two_columns_right: [
      columnProps.oneThird,
      columnProps.twoThirds,
    ],
    two_columns_left: [
      columnProps.twoThirds,
      columnProps.oneThird,
    ],
  }

  /**
   *
   * @param blockId
   */
  const getBlockBackgroundColor = (blockId) => {

    let block = __findBlock(blockId, getBlocks())

    let {
      backgroundColor = '',
    } = block.advancedStyle

    if (backgroundColor) {
      return backgroundColor
    }

    let parent = __findParent(blockId, getBlocks())

    // console.log( parent )

    if (parent) {
      return getBlockBackgroundColor(parent.id, getBlocks())
    }

    return ''
  }

  const tinyMceCSS = () => {

    let {
      p,
      h1,
      h2,
      h3,
      a,
    } = getActiveBlock()

    let bodyStyle = {}

    let backgroundColor = getBlockBackgroundColor(getActiveBlock().id)

    if (backgroundColor) {
      bodyStyle.backgroundColor = backgroundColor
    }

    // language=CSS
    return `

        ${getTemplate().mceCss()}
        body {
            ${objectToStyle(bodyStyle)}
        }

        p, li {
            ${fontStyle(p)}
        }

        a {
            ${fontStyle({
                ...p,
                ...a,
            })}
        }

        b, strong {
            font-weight: bold;
        }

        ul {
            list-style: disc;
            padding-left: 30px;
        }

        ol {
            padding-left: 30px;
        }

        h1 {
            ${fontStyle(h1)}
        }

        h2 {
            ${fontStyle(h2)}
        }

        h3 {
            ${fontStyle(h3)}
        }
    `
  }

  const fontDefaults = style => ({
    lineHeight: '1.4',
    fontFamily: 'system-ui, sans-serif',
    fontWeight: 'normal',
    // color: '#1a1a1a',
    fontSize: 13,
    fontStyle: 'normal',
    textTransform: 'none',
    ...style,
  })

  const fillFontStyle = ({ use = 'custom', color = '', fontSize = 16, ...style }) => {

    // global font
    if (GlobalFonts.has(use)) {
      let font = GlobalFonts.get(use).style
      if (font) {
        return fillFontStyle({
          ...font,
          color,
        })
      }
    }

    return {
      ...fontDefaults(style),
      color,
      fontSize: `${fontSize}px`,
    }
  }

  const fontStyle = style => {
    return objectToStyle(fillFontStyle(style))
  }

  const FontControls = (style = {}, onChange = style => {}, supports = {}) => {

    supports = {
      fontSize: true,
      fontFamily: true,
      fontWeight: true,
      lineHeight: true,
      fontStyle: true,
      textTransform: true,
      ...supports,
    }

    let {
      fontSize = '14',
      fontFamily = '',
      fontWeight = 'normal',
      fontStyle = 'normal',
      textTransform = 'none',
      lineHeight = '1.4',
    } = fontDefaults(style)

    return Div({
      className: 'font-controls display-flex column gap-10',
    }, [
      !supports.fontSize ? null : Control({ label: __('Font Size', 'groundhogg') }, Input({
        type: 'number',
        id: `font-size`,
        name: `font_size`,
        className: 'font-control control-input',
        value: fontSize,
        onInput: e => onChange({ fontSize: e.target.value }),
      })),
      !supports.lineHeight ? null : Control({ label: __('Line Height', 'groundhogg') }, Input({
        type: 'number',
        id: `line-height`,
        name: `line_height`,
        className: 'font-control control-input',
        value: lineHeight,
        step: '0.1',
        max: 10,
        onInput: e => onChange({ lineHeight: e.target.value }),
      })),
      !supports.fontWeight ? null : Control({ label: __('Font Weight', 'groundhogg') }, Select({
        id: `font-weight`,
        name: `font_weight`,
        className: 'font-control control-input',
        selected: fontWeight,
        options: fontWeights.map(i => ({ value: i, text: i })),
        onChange: e => onChange({ fontWeight: e.target.value }),
      })),
      !supports.fontFamily ? null : Control({ label: __('Font Family', 'groundhogg') }, Select({
        id: `font-family`,
        name: `font_family`,
        className: 'font-control control-input',
        selected: fontFamily,
        options: fontFamilies,
        onChange: e => onChange({ fontFamily: e.target.value }),
      })),
      !supports.fontStyle ? null : Control({ label: __('Font Style', 'groundhogg') }, Select({
        id: `font-style`,
        name: `font_style`,
        className: 'font-control control-input',
        selected: fontStyle,
        options: {
          normal: 'Normal',
          italic: 'Italic',
          oblique: 'Oblique',
        },
        onChange: e => onChange({ fontStyle: e.target.value }),
      })),
      !supports.textTransform ? null : Control({ label: __('Transform', 'groundhogg') }, Select({
        id: `text-transform`,
        name: `text_transform`,
        className: 'font-control control-input',
        selected: textTransform,
        options: {
          none: 'None',
          capitalize: 'Capitalize',
          uppercase: 'Uppercase',
          lowercase: 'Lowercase',
        },
        onChange: e => onChange({ textTransform: e.target.value }),
      })),
    ])
  }

  const TagFontControlGroup = (name, tag = '', style = {}, updateBlock = () => {}, supports = {}) => {

    let {
      use = 'global',
      color = '',
    } = style

    const updateStyle = (newStyle) => {
      style = {
        ...getActiveBlock()[tag],
        ...newStyle,
      }

      updateBlock({
        [tag]: style,
      })
    }

    const DisplayFont = (font, selected, close) => {
      return Div({
        className: `select-font ${selected ? 'selected' : ''}`,
        id: font.id,
        onClick: e => {
          use = font.id
          updateStyle({
            use,
            ...font.style,
          })
          morphControls()
          close()
        },
      }, Span({
        style: {
          ...fillFontStyle(font.style),
          // margin: 0
        },
      }, font.name))
    }

    return ControlGroup({
      name,
    }, [

      Control({
        label: 'Font',
      }, Div({
        className: 'gh-input-group',
      }, [
        Button({
          id: `${tag}-use-global`,
          className: `gh-button small ${GlobalFonts.has(use) ? 'primary' : 'secondary'}`,
          onClick: e => {
            MiniModal({
              selector: `#${tag}-use-global`,
              dialogClasses: 'no-padding',
            }, ({ close }) => Div({
              className: 'display-flex column global-font-select',
            }, [
              ...GlobalFonts.fonts.map(font => DisplayFont(font, use === font.id, close)),
            ]))
          },
        }, Dashicon('admin-site')),
        Button({
          id: `${tag}-use-custom`,
          className: `gh-button small ${!GlobalFonts.has(use) ? 'primary' : 'secondary'}`,
          onClick: e => {

            updateStyle({
              use: 'custom',
            })

            morphControls()

            MiniModal({
                dialogClasses: 'no-padding',
                selector: `#${tag}-use-custom`,
                // onClose: () => morphControls(),
              }, Div({
                className: 'display-flex column gap-10',
              }, [
                FontControls(style, style => {
                  updateStyle(style)
                }, supports),
              ]),
            )
          },
        }, Dashicon('edit')),
      ])),
      Control({ label: __('Color', 'groundhogg') }, ColorPicker({
        id: `${tag}-font-color`,
        value: color,
        onChange: color => updateStyle({ color }),
      })),
    ])
  }

  registerBlock('columns', 'Columns', {
    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.667 426.667"
		     style="enable-background:new 0 0 426.667 426.667" xml:space="preserve"><path 
        fill="currentColor"
        d="M384 21.333h-42.667c-23.552 0-42.667 19.157-42.667 42.667v298.667c0 23.531 19.115 42.667 42.667 42.667H384c23.552 0 42.667-19.136 42.667-42.667V64c0-23.509-19.115-42.667-42.667-42.667zM234.667 21.333H192c-23.552 0-42.667 19.157-42.667 42.667v298.667c0 23.531 19.115 42.667 42.667 42.667h42.667c23.552 0 42.667-19.136 42.667-42.667V64c-.001-23.509-19.115-42.667-42.667-42.667zM85.333 21.333H42.667C19.136 21.333 0 40.491 0 64v298.667c0 23.531 19.136 42.667 42.667 42.667h42.667c23.531 0 42.667-19.136 42.667-42.667V64C128 40.491 108.864 21.333 85.333 21.333z"/></svg>`,
    controls: ({ layout = 'two_columns', gap = 0, verticalAlign = 'top', updateBlock }) => {

      const LayoutChoice = l => Button({
        className: `layout-choice ${l} ${layout === l ? 'selected' : ''}`,
        dataLayout: l,
        id: `layout-${l}`,
        onClick: e => {
          updateBlock({
            layout: l,
            reRenderControls: true,
          })
        },
      }, columnLayouts[l].map((col, i) => `<span class="col col-${i + 1}"></span>`))

      return [
        ControlGroup({
          name: 'Layout',
        }, [
          Div({
            className: 'layouts',
          }, [
            ...Object.keys(columnLayouts).map(k => LayoutChoice(k)),
          ]),
          Control({
            label: 'Gap',
          }, Input({
            type: 'number',
            id: 'column-gap',
            className: 'control-input',
            value: gap,
            onInput: e => updateBlock({ gap: e.target.value }),
          })),
          Control({
            label: 'Vertical Alignment',
          }, Select({
            id: 'column-vertical-alignment',
            selected: verticalAlign,
            options: {
              top: 'Top',
              middle: 'Middle',
              bottom: 'Bottom',
            },
            onChange: e => updateBlock({ verticalAlign: e.target.value }),
          })),
        ]),
      ]
    },
    html: ({ columns, layout = 'two_columns', gap = 0, verticalAlign = 'top' }) => {
      return Table({
          className: `email-columns ${layout}`,
          cellspacing: '0',
          cellpadding: '0',
          width: '100%',
          style: {
            borderCollapse: 'collapse',
            tableLayout: 'fixed',
            width: '100%',
          },
        },
        Tr({ className: 'email-columns-row' }, makeColumns(columnLayouts[layout], { columns, gap, verticalAlign })))
    },
    plainText: ({ columns }) => {
      return columns.map(column => renderBlocksPlainText(column)).join('\n\n')
    },
    css: ({ selector, id, columns, gap = 10 }) => {
      //language=CSS
      return `
          ${columns.map(col => col.length ? renderBlocksCSS(col) : '').join('')}
      `
    },
    defaults: {
      layout: 'two_columns',
      columns: [
        [], [], [], [],
      ],
      gap: 10,
    },
  })

  const textContent = ({ content, p, h1, h2, h3, a }) => {

    if (!content) {
      return Div({
        className: 'text-content-wrap',
      }, '')
    }

    const parser = new DOMParser()
    const doc = parser.parseFromString(content, 'text/html')

    const inlineStyle = (tag, style) => {
      style = fillFontStyle(style)
      doc.querySelectorAll(tag).forEach(el => {
        for (let attr in style) {
          el.style[attr] = style[attr]
        }
      })
    }

    inlineStyle('p', {
      ...p,
      margin: '1em 0',
    })
    inlineStyle('li', p)
    inlineStyle('h1', h1)
    inlineStyle('h2', h2)
    inlineStyle('h3', h3)
    inlineStyle('a', {
      ...p,
      ...a,
    })

    if (doc.body.firstElementChild) {
      doc.body.firstElementChild.style.marginTop = 0
    }

    if (doc.body.lastElementChild) {
      doc.body.lastElementChild.style.marginBottom = 0
    }

    return Div({
      className: 'text-content-wrap',
    }, doc.body.childNodes)
  }

  registerBlock('text', 'Text', {
    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" style="enable-background:new 0 0 977.7 977.7" xml:space="preserve"
		     viewBox="0 0 977.7 977.7">
        <path fill="currentColor"
              d="M770.7 930.6v-35.301c0-23.398-18-42.898-41.3-44.799-17.9-1.5-35.8-3.1-53.7-5-34.5-3.6-72.5-7.4-72.5-50.301L603 131.7c136-2 210.5 76.7 250 193.2 6.3 18.7 23.8 31.3 43.5 31.3h36.2c24.9 0 45-20.1 45-45V47.1c0-24.9-20.1-45-45-45H45c-24.9 0-45 20.1-45 45v264.1c0 24.9 20.1 45 45 45h36.2c19.7 0 37.2-12.6 43.5-31.3 39.4-116.5 114-195.2 250-193.2l-.3 663.5c0 42.9-38 46.701-72.5 50.301-17.9 1.9-35.8 3.5-53.7 5-23.3 1.9-41.3 21.4-41.3 44.799v35.3c0 24.9 20.1 45 45 45h473.8c24.8 0 45-20.199 45-45z"/></svg>`,
    controls: ({ p = {}, a = {}, h1 = {}, h2 = {}, h3 = {}, updateBlock, curBlock }) => {

      return Fragment([
        TagFontControlGroup(__('Paragraphs'), 'p', p, updateBlock),
        TagFontControlGroup(__('Links'), 'a', a, updateBlock, {
          fontSize: false,
          lineHeight: false,
        }),
        TagFontControlGroup(__('Heading 1'), 'h1', h1, updateBlock),
        TagFontControlGroup(__('Heading 2'), 'h2', h2, updateBlock),
        TagFontControlGroup(__('Heading 3'), 'h3', h3, updateBlock),
      ])
    },
    edit: ({ id, content, updateBlock, ...block }) => {

      const editContent = () => {

        let editorId = `text-${id}`

        ModalFrame({
          // content: ,
          // width: 600,
          frameAttributes: {
            style: {
              width: '600px',
            },
          },
          onOpen: () => {
            wp.editor.remove(editorId)
            tinymceElement(editorId, {
              tinymce: {
                content_style: tinyMceCSS(),
              },
              quicktags: true,
              settings: {
                height: 800,
                width: 800,
              },
            }, (newContent) => {
              content = newContent
              updateBlock({
                content,
              })
            })

            let butn = Button({ className: 'replacements-picker-start gh-button dashicon' }, Dashicon('admin-users'))
            document.querySelector('.gh-modal .wp-editor-tools').prepend(butn)
          },
        }, Div({}, [
          Textarea({
            value: content,
            id: editorId,
          })]))
      }

      return Fragment([
        Div({
          className: 'maybe-edit-text',
          style: {
            textAlign: 'left',
          },
        }, [
          textContent({
            content,
            ...block,
          }),
        ]),
        Button({
          className: `gh-button primary edit-text-content`,
          onClick: e => {
            editContent()
          },
        }, __('Edit Content', 'groundhogg'))])
    },
    html: textContent,
    css: ({ selector, p, h1, h2, h3, a = {} }) => {

      //language=CSS
      return `
          ${selector} p, ${selector} li {
              ${fontStyle(p)}
          }

          ${selector} a {
              ${fontStyle({
                  ...p,
                  ...a,
              })}
          }

          ${selector} b, ${selector} strong {
              font-weight: bold;
          }

          ${selector} ul {
              list-style: disc;
              padding-left: 30px;
          }

          ${selector} ol {
              padding-left: 30px;
          }

          ${selector} h1 {
              ${fontStyle(h1)}
          }

          ${selector} h2 {
              ${fontStyle(h2)}
          }

          ${selector} h3 {
              ${fontStyle(h3)}
          }
      `
    },
    plainText: ({ content }) => {
      const parser = new DOMParser()
      const doc = parser.parseFromString(content, 'text/html')
      return extractPlainText(doc.body)
    },
    defaults: {
      content: `<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin egestas dolor non nulla varius, id fermentum ante euismod. Ut a sodales nisl, at maximus felis. Suspendisse potenti. Etiam fermentum magna nec diam lacinia, ut volutpat mauris accumsan. Nunc id convallis magna. Ut eleifend sem aliquet, volutpat sapien quis, condimentum leo.</p>`,
      p: fontDefaults({
        fontSize: 14,
      }),
      a: {
        color: '#488aff',
      },
      h1: fontDefaults({
        fontSize: 42,
        fontWeight: '500',
      }),
      h2: fontDefaults({
        fontSize: 24,
        fontWeight: '500',
      }),
      h3: fontDefaults({
        fontSize: 20,
        fontWeight: '500',
      }),
    },
  })

  registerBlock('button', 'Button', {
    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
			<path fill="currentColor"
			      d="m15.7 5.3-1-1c-.2-.2-.4-.3-.7-.3H1c-.6 0-1 .4-1 1v5c0 .3.1.6.3.7l1 1c.2.2.4.3.7.3h13c.6 0 1-.4 1-1V6c0-.3-.1-.5-.3-.7zM14 10H1V5h13v5z"/>
		</svg>`,
    controls: ({ text, link, style, align, size, borderStyle = {}, updateBlock = () => {} }) => {
      return [
        ControlGroup({
          name: 'Content',
        }, [
          Control({
            label: 'Text',
            stacked: true,
          }, InputWithReplacements({
            type: 'text',
            id: 'button-text',
            className: 'full-width',
            value: text,
            onInput: e => updateBlock({ text: e.target.value }),
          })),
          Control({
            label: 'Link',
            stacked: true,
          }, InputWithReplacements({
            type: 'text',
            id: 'button-link',
            className: 'full-width',
            value: link,
            onChange: e => updateBlock({ link: e.target.value }),
          })),
          Control({
            label: 'Size',
          }, Select({
            id: 'button-size',
            options: {
              sm: __('Small'),
              md: __('Medium'),
              lg: __('Large'),
            },
            selected: size,
            onChange: e => updateBlock({ size: e.target.value }),
          })),
          Control({
            label: 'Alignment',
          }, AlignmentButtons({
            alignment: align,
            onChange: align => updateBlock({
              align,
              reRenderControls: true,
            }),
          })),
          Control({
            label: __('Button Color', 'groundhogg'),
          }, ColorPicker({
            type: 'text',
            id: 'button-color',
            value: style.backgroundColor,
            onChange: backgroundColor => updateBlock({
              style: {
                ...style,
                backgroundColor,
              },
            }),
          })),
        ]),
        BorderControlGroup({
          ...borderStyle,
          onChange: newStyle => updateBlock({
            borderStyle: {
              ...getActiveBlock().borderStyle,
              ...newStyle,
            },
            reRenderControls: true,
          }),
        }),
        TagFontControlGroup('Font', 'style', style, updateBlock),
      ]
    },
    html: ({ text, align, style, size, link, borderStyle = {} }) => {
      let padding
      switch (size) {
        case 'sm':
          padding = '8px 12px'
          break
        case 'md':
          padding = '12px 18px'
          break
        case 'lg':
          padding = '16px 24px'
          break
      }

      return Table({
        width: '100%',
        border: '0',
        cellspacing: '0',
        cellpadding: '0',
      }, [
        Tr({}, Td({
          align,
          style: {
            padding: '20px 0',
          },
        }, Table({
          border: '0',
          cellspacing: '0',
          cellpadding: '0',
        }, Tr({}, Td({
          className: 'email-button',
          bgcolor: style.backgroundColor,
          style: {
            padding,
            borderRadius: '3px',
            ...addBorderStyle(borderStyle),
          },
          align: 'center',
        }, makeEl('a', {
          href: link,
          style: {
            ...style,
            fontSize: `${style.fontSize}px`,
            textDecoration: 'none',
            display: 'inline-block',
          },
        }, text)))))),
      ])
    },
    edit: ({ text, align, style, size, updateBlock, borderStyle = {} }) => {

      let padding
      switch (size) {
        case 'sm':
          padding = '8px 12px'
          break
        case 'md':
          padding = '12px 18px'
          break
        case 'lg':
          padding = '16px 24px'
          break
      }

      const textUpdate = e => {
        updateBlock({
          text: e.currentTarget.textContent,
          reRenderControls: true,
        })
      }

      return Table({
        width: '100%',
        border: '0',
        cellspacing: '0',
        cellpadding: '0',
      }, [
        Tr({}, Td({
          align,
          style: {
            padding: '20px 0',
          },
        }, Table({
          border: '0',
          cellspacing: '0',
          cellpadding: '0',
        }, Tr({}, Td({
          className: 'email-button',
          bgcolor: style.backgroundColor,
          style: {
            padding,
            borderRadius: '3px',
            ...addBorderStyle(borderStyle),
          },
          align: 'center',
        }, makeEl('a', {
          id: `text-edit-link`,
          contenteditable: true,
          style: {
            ...style,
            fontSize: `${style.fontSize}px`,
            textDecoration: 'none !important',
            display: 'inline-block',
          },
          eventHandlers: {
            'input': textUpdate,
          },
        }, text)))))),
      ])

    },
    plainText: ({ text, link = '' }) => {
      return `[${text}](${link})`
    },
    css: ({ selector, style, borderStyle = {} }) => {
      //language=CSS
      return `
          ${selector} td.email-button {
              ${objectToStyle(addBorderStyle(borderStyle))}
          }

          ${selector} a {
              ${fontStyle(style)}
          }`
    },
    defaults: {
      link: Groundhogg.url.home,
      align: 'center',
      text: 'Click me!',
      size: 'md',
      style: {
        backgroundColor: '#dd3333',
        color: '#ffffff',
        fontSize: 20,
        fontWeight: '600',
        fontFamily: 'Arial, sans-serif',
      },
    },
  })

  registerBlock('image', 'Image', {
    svg: icons.image,
    controls: ({ id, src, link = '', width, height, alt = '', align = 'center', updateBlock, borderStyle = {} }) => {

      return Fragment([
        ControlGroup({
          name: 'Image',
        }, [
          ImageControls({
            id: 'image',
            image: {
              src,
              alt,
              // title,
              width,
            },
            maxWidth: document.getElementById(`b-${id}`).getBoundingClientRect().width,
            onChange: image => {
              updateBlock({
                ...image,
                reRenderControls: true,
              })
            },
          }),
          Control({
            label: 'Alignment',
          }, AlignmentButtons({
            alignment: align,
            onChange: align => {
              updateBlock({
                align,
                reRenderControls: true,
              })
            },
          })),
          Control({
            label: 'Link',
            stacked: true,
          }, InputWithReplacements({
            type: 'text',
            id: 'image-link',
            className: 'full-width',
            value: link,
            onChange: e => updateBlock({ link: e.target.value }),
          })),
        ]),
        BorderControlGroup({
          ...borderStyle,
          onChange: newStyle => updateBlock({
            borderStyle: {
              ...getActiveBlock().borderStyle,
              ...newStyle,
            },
            reRenderControls: true,
          }),
        }),
      ])
    },
    edit: ({ src, width, height, alt = '', align = 'center', updateBlock, borderStyle = {} }) => {

      return Div({
        className: 'img-container full-width',
        style: {
          textAlign: align,
        },
      }, makeEl('img', {
        className: 'resize-me',
        onCreate: el => {

          setTimeout(() => {
            let $el = $('img.resize-me')
            $el.resizable({
              aspectRatio: true,
              maxWidth: $el.parent().width(),
              stop: (e, ui) => {
                updateBlock({
                  width: Math.ceil(ui.size.width),
                  reRenderControls: true,
                  reRenderBlocks: false,
                })
              },
            })
          }, 100)
        },
        src,
        alt,
        // title,
        width,
        height: 'auto',
        style: {
          verticalAlign: 'bottom',
          height: 'auto',
          width,
          ...addBorderStyle(borderStyle),
        },
      }))
    },
    html: ({ src, width, height, link = '', alt = '', align = 'center', borderStyle = {} }) => {

      let img = makeEl('img', {
        src,
        alt,
        // title,
        width,
        height: 'auto',
        style: {
          verticalAlign: 'bottom',
          height: 'auto',
          width,
          ...addBorderStyle(borderStyle),
        },
      })

      if (link) {
        img = makeEl('a', {
          href: link,
        }, img)
      }

      return Div({
        className: 'img-container',
        style: {
          textAlign: align,
        },
      }, img)
    },
    plainText: ({ src = '', alt = '', link = '' }) => {
      return `[${alt} ${src}] ${link ? `${link}` : ''}`
    },
    defaults: {
      src: 'http://via.placeholder.com/600x338',
      alt: 'placeholder image',
      title: 'placeholder image',
      width: 600,
      height: 338,
      align: 'center',
    },
  })

  registerBlock('spacer', 'Spacer', {
    svg: `
        <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 512 512">
  <path fill="currentColor"
        d="M352 384h-48V128h48a16 16 0 0 0 11-27L267 5c-6-7-16-7-22 0l-96 96c-5 4-6 11-4 17 3 6 9 10 15 10h48v256h-48a16 16 0 0 0-11 27l96 96c6 7 16 7 22 0l96-96a16 16 0 0 0-11-27z"/>
</svg>`,
    controls: ({ height = 10, updateBlock }) => {
      return ControlGroup({
        name: 'Spacer',
      }, [
        Control({
          label: 'Height',
        }, Input({
          type: 'number',
          className: 'control-input',
          value: height,
          id: 'spacer-height',
          onChange: e => {
            updateBlock({
              height: parseInt(e.target.value),
            })
          },
        })),
      ])
    },
    html: ({ height = 20 }) => {
      // language=HTML
      return Table({
        cellspacing: '0',
        cellpadding: '0',
      }, Tr({}, Td({
        height,
        style: {
          height: `${height}px`,
        },
      })))
    },
    defaults: {
      height: 20,
    },
  })

  registerBlock('divider', 'Divider', {
    svg: `
        <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 409.6 409.6">
  <path fill="currentColor" d="M393 188H17a17 17 0 1 0 0 34h376a17 17 0 1 0 0-34z"/>
</svg>`,
    controls: ({ height = 10, width = 80, color, updateBlock, lineStyle }) => {
      return ControlGroup({
        name: 'Divider',
      }, [
        Control({
          label: 'Style',
        }, Select({
          id: 'line-style',
          options: {
            solid: __('Solid', 'groundhogg'),
            dashed: __('Dashed', 'groundhogg'),
            dotted: __('Dotted', 'groundhogg'),
          },
          selected: lineStyle,
          onChange: e => updateBlock({ lineStyle: e.target.value }),
        })),
        Control({
          label: 'Height',
        }, Input({
          type: 'number',
          className: 'control-input',
          value: height,
          id: 'divider-height',
          onChange: e => {
            updateBlock({
              height: parseInt(e.target.value),
            })
          },
        })),
        Control({
          label: 'Width',
        }, Input({
          type: 'number',
          className: 'control-input',
          value: width,
          id: 'divider-width',
          onChange: e => {
            updateBlock({
              width: parseInt(e.target.value),
            })
          },
        })),
        Control({
          label: 'Color',
        }, ColorPicker({
          id: 'divider-color',
          value: color,
          onChange: color => {
            updateBlock({
              color,
            })
          },
        })),
      ])
    },
    edit: ({ height, width, color, lineStyle = 'solid' }) => {

      // language=HTML
      return `
		  <hr class="divider"
		      style="border-width: ${height}px 0 0 0;width:${width}%;border-top-color: ${color};border-style: ${lineStyle};">
		  </hr>`
    },
    html: ({ height, width, color, lineStyle = 'solid' }) => {
      // language=HTML
      return `
		  <hr class="divider"
		      style="border-width: ${height}px 0 0 0;width:${width}%;border-top-color: ${color};border-style: ${lineStyle};">
		  </hr>`
    },
    defaults: {
      height: 3,
      color: '#ccc',
      width: 100,
      lineStyle: 'solid',
    },
  })

  registerBlock('html', 'HTML', {
    // language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 512 512">
  <path fill="currentColor"
        d="M507 243 388 117a19 19 0 1 0-28 27l106 112-106 112a19 19 0 0 0 28 27l119-126c7-7 7-19 0-26zM152 368 46 256l106-112a19 19 0 0 0-28-27L5 243c-7 7-7 19 0 26l119 126a19 19 0 0 0 27 0c7-7 8-19 1-27zM287 53c-10-2-20 5-22 16l-56 368a19 19 0 0 0 38 6l56-368c2-11-5-21-16-22z"/>
</svg>`,
    controls: ({ content = '', updateBlock }) => {
      return Fragment([
        Textarea({
          id: 'code-block-editor',
          value: content,
          onCreate: el => {

            // Wait for add to dom
            setTimeout(() => {
              let editor = wp.codeEditor.initialize('code-block-editor', {
                ...wp.codeEditor.defaultSettings,
              }).codemirror

              editor.on('change', instance => updateBlock({
                content: instance.getValue(),
              }))

              editor.setSize(null, 500)
            }, 100)
          },
        }),
      ])
    },
    edit: ({ content }) => {
      return content
    },
    html: ({ content }) => {
      return content
    },
    plainText: ({ content }) => {
      const parser = new DOMParser()
      const doc = parser.parseFromString(content, 'text/html')
      return extractPlainText(doc.body)
    },
    defaults: {
      content: '<p>HTML CODE</p>',
    },
  })

  registerDynamicBlock('posts', 'Posts', {
    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 193.826 193.826"
		     style="enable-background:new 0 0 193.826 193.826" xml:space="preserve">
        <path fill="currentColor"
              d="M191.495 55.511 137.449 1.465a4.998 4.998 0 0 0-7.07 0l-.229.229a17.43 17.43 0 0 0-5.14 12.406c0 3.019.767 5.916 2.192 8.485l-56.55 48.533c-4.328-3.868-9.852-5.985-15.703-5.985a23.444 23.444 0 0 0-16.689 6.913l-.339.339a4.998 4.998 0 0 0 0 7.07l32.378 32.378-31.534 31.533c-.631.649-15.557 16.03-25.37 28.27-9.345 11.653-11.193 13.788-11.289 13.898a4.995 4.995 0 0 0 .218 6.822 4.987 4.987 0 0 0 3.543 1.471c1.173 0 2.349-.41 3.295-1.237.083-.072 2.169-1.885 13.898-11.289 12.238-9.813 27.619-24.74 28.318-25.421l31.483-31.483 30.644 30.644c.976.977 2.256 1.465 3.535 1.465s2.56-.488 3.535-1.465l.339-.339a23.446 23.446 0 0 0 6.913-16.689 23.43 23.43 0 0 0-5.985-15.703l48.533-56.55a17.434 17.434 0 0 0 8.485 2.192c4.687 0 9.093-1.825 12.406-5.14l.229-.229a5 5 0 0 0 0-7.072z"/></svg>`,
    controls: ({
      layout = '',
      featured = false,
      excerpt = false,
      gap = 20,
      number,
      offset,
      post_type,
      excerptStyle = {},
      headingStyle = {},
      selectedTags = [],
      tag = [],
      selectedCategories = [],
      category = [],
      updateBlock,
      queryId = '',
    }) => {

      return Fragment([
        ControlGroup({
          name: 'Layout',
        }, [
          Control({
            label: 'Layout',
          }, Select({
            options: {
              ul: 'List',
              grid: 'Grid',
              cards: 'Cards',
            },
            selected: layout,
            onChange: e => updateBlock({ layout: e.target.value }),
          })),
          Control({
            label: 'Featured',
          }, Toggle({
            id: 'toggle-featured',
            checked: featured,
            onChange: e => updateBlock({ featured: e.target.checked }),
          })),
          Control({
            label: 'Excerpt',
          }, Toggle({
            id: 'toggle-excerpt',
            checked: excerpt,
            onChange: e => updateBlock({
              excerpt: e.target.checked,
              reRenderControls: true,
            }),
          })),
          Control({
            label: 'Gap',
          }, Input({
            type: 'number',
            id: 'column-gap',
            className: 'control-input',
            value: gap,
            onInput: e => updateBlock({ gap: e.target.value }),
          })),
        ]),
        TagFontControlGroup(__('Heading'), 'headingStyle', headingStyle, updateBlock),
        excerpt ? TagFontControlGroup(__('Excerpt'), 'excerptStyle', excerptStyle, updateBlock) : null,
        ControlGroup({
          name: 'Query',
        }, [
          Control({
            label: 'Post Type',
          }, Select({
            id: 'post-type',
            selected: post_type,
            options: {
              posts: 'Posts',
            },
            onChange: e => updateBlock({ post_type: e.target.value }),
          })),
          Control({
            label: 'Number of posts',
          }, Input({
            type: 'number',
            id: 'number-of-posts',
            className: 'control-input',
            value: number,
            onChange: e => updateBlock({ number: e.target.value }),
          })),
          Control({
            label: 'Offset',
          }, Input({
            type: 'number',
            id: 'posts-offset',
            className: 'control-input',
            value: offset,
            onChange: e => updateBlock({ offset: e.target.value }),
          })),
          `<hr/>`,
          Control({
            label: 'Tags',
            stacked: true,
          }, ItemPicker({
            id: 'post-tags',
            selected: selectedTags,
            tags: false,
            fetchOptions: async (search) => {
              let terms = await get(`${Groundhogg.api.routes.wp.tags}`, {
                search,
                per_page: 20,
              })
              terms = terms.map(({ id, name }) => ({ id, text: name }))
              return terms
            },
            onChange: selected => {
              updateBlock({
                selectedTags: selected,
                tag: selected.map(opt => opt.id),
              })
            },
          })),
          Control({
            label: 'Categories',
            stacked: true,
          }, ItemPicker({
            id: 'post-cats',
            selected: selectedCategories,
            tags: false,
            fetchOptions: async (search) => {
              let terms = await get(`${Groundhogg.api.routes.wp.categories}`, {
                search,
                per_page: 20,
              })
              terms = terms.map(({ id, name }) => ({ id, text: name }))
              return terms
            },
            onChange: selected => {
              updateBlock({
                selectedCategories: selected,
                category: selected.map(opt => opt.id),
              })
            },
          })),
          `<hr/>`,
          Control({ label: 'Query ID' }, Input({
            id: 'query-id',
            name: 'query_id',
            value: queryId,
            onChange: e => updateBlock({ queryId: e.target.value }),
          })),
          `<p>This allows you to filter this specific query with additional parameters.</p>`,
        ]),
      ])
    },
    css: ({
      selector,
      headingStyle = {},
      excerptStyle = {},
    }) => {

      //language=CSS
      return `
          ${selector} h2 {
              ${fontStyle(headingStyle)}
          }

          ${selector} h2 a {
              color: inherit;
          }

          ${selector} p.post-excerpt {
              ${fontStyle(excerptStyle)}
          }
      `
    },
    generateCacheKey: ({
      number,
      layout,
      offset,
      featured,
      columns,
      post_type,
      excerpt,
      gap,
      tag,
      category,
      queryId,
    }) => {
      return base64_json_encode({
        number,
        layout,
        offset,
        featured,
        columns,
        post_type,
        excerpt,
        gap,
        tag,
        category,
        queryId,
      })
    },
    defaults: {
      layout: 'grid',
      number: 5,
      offset: 0,
      featured: false,
      excerpt: false,
      thumbnail: false,
      post_type: 'post',
      columns: 2,
      gap: 20,
      headingStyle: fontDefaults({
        fontSize: 24,
      }),
      excerptStyle: fontDefaults({
        fontSize: 16,
      }),
    },
  })

  registerBlock('footer', 'Footer', {
    // language=HTML
    svg: `
		<svg id="fi_3596176" enable-background="new 0 0 24 24" height="512" viewBox="0 0 24 24" width="512"
		     xmlns="http://www.w3.org/2000/svg">
			<path fill="currentColor"
			      d="m21.5 24h-19c-1.379 0-2.5-1.121-2.5-2.5v-19c0-1.379 1.121-2.5 2.5-2.5h19c1.379 0 2.5 1.121 2.5 2.5v19c0 1.379-1.121 2.5-2.5 2.5zm-19-23c-.827 0-1.5.673-1.5 1.5v19c0 .827.673 1.5 1.5 1.5h19c.827 0 1.5-.673 1.5-1.5v-19c0-.827-.673-1.5-1.5-1.5z"></path>
			<path fill="currentColor"
			      d="m19.5 21h-15c-.827 0-1.5-.673-1.5-1.5v-4c0-.827.673-1.5 1.5-1.5h15c.827 0 1.5.673 1.5 1.5v4c0 .827-.673 1.5-1.5 1.5zm-15-6c-.275 0-.5.225-.5.5v4c0 .275.225.5.5.5h15c.275 0 .5-.225.5-.5v-4c0-.275-.225-.5-.5-.5z"></path>
		</svg>`,
    controls: ({ style = {}, linkStyle = {}, alignment = 'left', updateBlock }) => {
      return Fragment([
        ControlGroup({ name: 'Footer' }, [
          Control({ label: 'Alignment' },
            AlignmentButtons({ alignment, onChange: alignment => updateBlock({ alignment, reRenderControls: true }) })),
        ]),
        TagFontControlGroup('Font Style', 'style', style, updateBlock),
        TagFontControlGroup(__('Link Style'), 'linkStyle', linkStyle, updateBlock, {
          fontSize: false,
          lineHeight: false,
        }),
      ])
    },
    html: ({ style = {}, linkStyle = {}, alignment = 'left' }) => {

      const footerLine = (content) => makeEl('p', {
        style: {
          ...fillFontStyle(style),
          textAlign: alignment,
          margin: '0.5em 0',
        },
      }, content)

      let {
        business_name,
        address,
        links,
        unsubscribe,
      } = _BlockEditor.footer

      let footer = Div({
        id: 'footer',
        className: 'footer',
      }, [
        footerLine(`&copy; ${business_name}`),
        footerLine(address),
        footerLine(links),
        getEmailMeta().message_type !== 'transactional' ? footerLine(unsubscribe) : null,
      ])

      linkStyle = fillFontStyle({
        ...style,
        ...linkStyle,
      })

      footer.querySelectorAll('a').forEach(el => {
        for (let attr in linkStyle) {
          el.style[attr] = linkStyle[attr]
        }
      })

      return footer
    },
    plainText: ({}) => {

      let {
        business_name,
        address,
        links,
        unsubscribe,
      } = _BlockEditor.footer

      return `Copyright ${business_name}\n${address}`
    },
    defaults: {
      style: fontDefaults({
        fontSize: 13,
        color: '#999',
        lineHeight: 1,
      }),
      linkStyle: {
        color: '#488aff',
      },
    },
  })

  const morph = (selector, html = null) => {

    try {
      if (html === null) {
        return morphdom(document.getElementById(selector.id), selector)
      }

      morphdom(document.querySelector(selector), Fragment(html), {
        childrenOnly: true,
      })
    } catch (e) {}
  }

  const initialize = ({
    email,
    onSave: onSaveCallback = () => {},
    onClose: onCloseCallback = () => {},
  }) => {

    onSave = onSaveCallback
    onClose = onCloseCallback

    History.clear()

    // existing email not using blocks
    if (email.ID) {

      // must convert to blocks
      if (email.context.editor_type !== 'blocks') {
        switch (email.context.editor_type) {
          case 'legacy_blocks':

            let elements = htmlToElements(email.data.content)

            // Using for...of
            for (const node of elements.values()) {
              if (node.classList.contains('row')) {
                email.meta.blocks = convertProEditorToBlocks(email.data.content)
                break
              }
            }

            break
          case 'legacy_plain':

            email.meta.blocks = [
              createBlock('text', {
                content: email.data.content,
              }),
            ]

            break
        }
      }

    }
    // Creating a new email
    else {

      email.data = {
        title: 'My new email',
        subject: '',
        pre_header: '',
        from_user: 0,
        status: 'draft',
      }

      email.meta = {
        blocks: [
          createBlock('text'),
        ],
        alignment: 'left',
        backgroundColor: '',
        frameColor: '',
        message_type: 'marketing',
        width: 600,
        custom_headers: {},
      }

      setState({
        page: 'templates',
      })
    }

    if (!email.meta.template) {
      email.meta.template = 'boxed'
    }

    let preview = ''

    if (email.context?.built) {
      preview = email.context.built
    }

    setState({
      activeBlock: null,
      openPanels: {},
      blockControlsTab: 'block',
      emailControlsTab: 'email',
      isGeneratingHTML: false,
      email,
      preview,
    })

    setBlocks(email.meta.blocks, false)

    renderEditor()
  }

  const renderBlocksCSS = (blocks) => {
    return blocks.filter(b => b.type).map(b => BlockRegistry.css(b)).join('')
  }

  const renderBlocksHTML = (blocks) => {
    setIsGeneratingHTML(true)
    let html = Table({
      cellpadding: '0',
      cellspacing: '0',
      width: '100%',
    }, blocks.filter(b => b.type).map(block => BlockHTML(block))).outerHTML
    setIsGeneratingHTML(false)
    return html
  }

  const renderBlocksPlainText = (blocks) => {
    setIsGeneratingHTML(true)
    let plain = blocks.filter(b => b.type).map(block => {
      try {
        return BlockRegistry.get(block.type).plainText(block)
      } catch (e) {
        return ''
      }
    }).filter(text => text.length > 0).join('\n\n').replaceAll(/(\n|\r\n|\r){3,}/g, '\n\n')
    // console.log( plain.match(/(\n|\r\n|\r){3,}/g) )
    setIsGeneratingHTML(false)
    return plain
  }

  const morphBlocks = () => morph('#builder-content', BlockEditorContent())
  const removeControls = () => morph('#controls-panel', Div())
  const morphControls = () => morph('#controls-panel', ControlsPanel())
  const morphBlockEditor = () => morph('#email-block-editor', BlockEditor())
  const morphEmailEditor = () => morph('#email-editor', EmailEditor())
  const morphHeader = () => morph('#email-header', Header())
  const updateStyles = () => $('#builder-style').text(`#block-editor-content-wrap{ \n\n${renderBlocksCSS(getBlocks())}\n\n }`)

  const renderEditor = () => {
    morphEmailEditor()
    updateStyles()
  }

  const convertProEditorToBlocks = (oldHtml) => {

    let blocks = []

    let nodes = htmlToElements(oldHtml)

    nodes.forEach(node => {

      // Skip text nodes
      if (node.nodeType === Node.TEXT_NODE) {
        return
      }

      let oldBlockType = node.dataset.block

      // Get from classList of first child
      if (!oldBlockType) {
        let blockContainer = node.firstElementChild
        oldBlockType = blockContainer.classList[blockContainer.classList.length - 1]
      }

      let block, img, a, button, el, spacer, divider, html, text

      switch (oldBlockType) {
        case 'image':
        case 'image_block':

          img = node.querySelector('img')
          a = node.querySelector('a')

          block = createBlock('image', {
            src: img.src,
            alt: img.alt,
            title: img.title,
            width: img.width,
            link: a.href,
          })

          break
        case 'text':
        case 'text_block':

          let textContainer = node.querySelector('.text_block').firstElementChild

          text = textContainer.innerHTML

          let props = {
            content: text,
            p: fontDefaults({
              fontSize: parseInt(textContainer.style.fontSize),
              fontFamily: textContainer.style.fontFamily,
            }),
          }

          const setFontProps = (tag) => {
            el = textContainer.querySelector(tag)

            if (!el) {
              return
            }

            props[tag] = fontDefaults({
              fontSize: parseInt(el.style.fontSize),
              fontFamily: el.style.fontFamily,
            })
          }

          setFontProps('h1')
          setFontProps('h2')
          setFontProps('h3')

          block = createBlock('text', props)

          break
        case 'button':
        case 'button_block':

          button = node.querySelector('td.email-button')
          a = button.querySelector('a')

          block = createBlock('button', {
            text: a.innerHTML,
            link: a.href,
            style: {
              backgroundColor: button.getAttribute('bgcolor'),
              color: a.style.color,
              fontSize: parseInt(a.style.fontSize),
              fontWeight: a.style.fontWeight,
              fontFamily: a.style.fontFamily,
            },
          })

          break
        case 'spacer':
        case 'spacer_block':

          spacer = node.querySelector('td.spacer')

          block = createBlock('spacer', {
            height: spacer.height,
          })

          break
        case 'divider':
        case 'divider_block':

          divider = node.querySelector('hr')

          block = createBlock('divider', {
            height: parseInt(divider.style.borderTopWidth),
            width: parseInt(divider.style.width),
            color: divider.style.borderTopColor,
          })

          break
        case 'html':
        case 'html_block':

          html = node.querySelector('.inner-content').innerHTML

          block = createBlock('html', {
            content: html,
          })

          break
      }

      blocks.push(block)

    })

    return blocks
  }

  $('head').append(`<style id="builder-style" type="text/css"></style>`)

  if (isEmailEditorPage()) {
    window.addEventListener('beforeunload', e => {

      e.preventDefault()

      if (getState().hasChanges) {
        let msg = __('You have unsaved changes, are you sure you want to leave?')
        e.returnValue = msg
        return msg
      }

      return null
    })
  }

  let {
    colorPalette = [],
    globalFonts = [],
    blockDefaults = {},
  } = _BlockEditor

  // Fill global fonts if none defined
  if (!globalFonts || !Array.isArray(globalFonts) || !globalFonts.length) {
    GlobalFonts.fonts = [
      {
        name: 'Paragraph',
        id: uuid(),
        style: fontDefaults({}),
      },
      {
        name: 'Heading 1',
        id: uuid(),
        style: fontDefaults({
          fontSize: 42,
        }),
      },
      {
        name: 'Heading 2',
        id: uuid(),
        style: fontDefaults({
          fontSize: 36,
        }),
      },
      {
        name: 'Heading 3',
        id: uuid(),
        style: fontDefaults({
          fontSize: 24,
        }),
      },
    ]
  } else {
    GlobalFonts.fonts = globalFonts
  }

  // Fill color palette if not custom
  if (!colorPalette || !Array.isArray(colorPalette) || !colorPalette.length) {
    // Default WordPress colors
    colorPalette = ['#000', '#fff', '#dd3333', '#DD9933', '#EEEE22', '#81D742', '#1E73BE', '#8224E3']
  }

  console.log(colorPalette)

  if (isEmailEditorPage()) {

    let {
      email = null,
    } = _BlockEditor

    if (email) {

      EmailsStore.itemsFetched([email])

      window.addEventListener('load', () => {
        initialize({
          email,
        })
      })
    }

  }

  Groundhogg.EmailEditor = initialize

})(jQuery)