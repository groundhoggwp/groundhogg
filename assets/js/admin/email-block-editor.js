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
    el, objectToStyle, icons, inputWithReplacements, uuid, tinymceElement,
    specialChars,
    improveTinyMCE,
    textarea,
    modal,
    miniModal,
    input,
    clickedIn,
    select,
    copyObject,
    codeEditor,
    dialog,
    confirmationModal,
    adminPageURL,
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
    'Arial Black, Arial, sans-serif': 'Arial Black',
    'Century Gothic, Times, serif': 'Century Gothic',
    'Courier, monospace': 'Courier',
    'Courier New, monospace': 'Courier New',
    'Geneva, Tahoma, Verdana, sans-serif': 'Geneva',
    'Georgia, Times, Times New Roman, serif': 'Georgia',
    'Helvetica, Arial, sans-serif': 'Helvetica',
    'Lucida, Geneva, Verdana, sans-serif': 'Lucida',
    'Tahoma, Verdana, sans-serif': 'Tahoma',
    'Times, Times New Roman, Baskerville, Georgia, serif': 'Times',
    'Times New Roman, Times, Georgia, serif': 'Times New Roman',
    'Verdana, Geneva, sans-serif': 'Verdana',
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
    {
      id: 'framed',
      name: __('Framed'),
      controls: () => {

        let {
          width = 600,
          frameColor = '#EDF5FF',
          backgroundColor = '#fff',
          footerFontColor = '#000',
          logo = {},
        } = getEmailMeta()

        return Fragment([
          ControlGroup({
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
              label: 'Background Color',
            }, ColorPicker({
              id: 'background-color',
              value: backgroundColor,
              onChange: backgroundColor => {
                updateSettings({
                  backgroundColor,
                  reRender: true,
                })
              },
            })),
            Control({
              label: 'Frame Color',
            }, ColorPicker({
              id: 'frame-color',
              value: frameColor,
              onChange: frameColor => {
                updateSettings({
                  frameColor,
                  reRender: true,
                })
              },
            })),
            Control({
              label: 'Footer Font Color',
            }, ColorPicker({
              id: 'footer-font-color',
              value: footerFontColor,
              onChange: footerFontColor => {
                updateSettings({
                  footerFontColor,
                  // reRender: false,
                })
              },
            })),
          ]),
          ControlGroup({ name: 'Template Logo' }, [
            ImageControls({
              id: 'logo',
              image: logo,
              maxWidth: width,
              onChange: image => {
                updateSettings({
                  logo: image,
                  reRender: true,
                })
              },
            }),
          ]),
        ])
      },
      html: (blocks) => {

        const {
          logo = {},
          width = 640,
          frameColor = '#EDF5FF',
          backgroundColor = '#fff',
        } = getEmailMeta()

        let {
          src = '',
          width: logoWidth = 320,
          alt = '',
          title = '',
        } = logo

        return Div({
          className: `template-framed`,
          style: {
            backgroundColor,
          },
        }, [
          makeEl('img', {
            className: 'template-logo',
            src,
            width: logoWidth,
            height: 'auto',
            alt,
            title,
          }),
          Div({
            className: 'inner-content',
            style: {
              maxWidth: `${width || 640}px`,
              backgroundColor: frameColor,
            },
          }, blocks),
        ])
      },
      mceCss: () => {

        let bodyStyle = {}

        let {
          frameColor,
        } = getEmailMeta()

        if (frameColor) {
          bodyStyle.backgroundColor = frameColor
        }

        // language=CSS
        return `body {
            ${objectToStyle(bodyStyle)}
        }`
      },
    },
  ]

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

    const reset = () => {
      this.controller = new AbortController()
      this.signal = this.controller.signal
    }

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
  }

  const setActiveBlock = (idOrNull) => {
    State.activeBlock = idOrNull
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

  const openPanel = panel => State.openPanels[panel] = true
  const closePanel = panel => State.openPanels[panel] = false
  const togglePanel = panel => State.openPanels[panel] = !State.openPanels[panel]
  const isPanelOpen = panel => State.openPanels[panel]
  const getTemplate = () => DesignTemplates.find(t => t.id === getEmailMeta().template)

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
    values = { top: '', right: '', bottom: '', left: '', linked: true },
    onChange = (values) => {},
  }) => {

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
                palettes: ['#000', '#fff', '#dd3333', '#DD9933', '#EEEE22', '#81D742', '#1E73BE', '#8224E3'],
                change: (e, ui) => {
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
      Control({
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
      })),
      Control({
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
      })),
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
      } = advancedStyle

      return {
        borderStyle,
        borderColor,
        backgroundColor,
        padding: extract4(padding),
        // margin: extract4(margin),
        borderWidth: extract4(borderWidth),
        borderRadius: extract4(borderRadius),
      }
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
    render: ({ advancedStyle = {}, updateBlock }) => {

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
          Control({
            label: 'Width',
            stacked: false,
          }, Input({
            type: 'number',
            // max: getEmailMeta().width,
            min: 0,
            id: 'advanced-width',
            value: advancedStyle.width || '',
            name: 'advanced_width',
            onInput: e => updateStyle({
              width: e.target.value,
            }),
          })),
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
        ControlGroup({ name: 'Border' }, [
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
            selected: advancedStyle.borderStyle,
            onChange: e => updateStyle({ borderStyle: e.target.value }),
          })),
          Control({
            label: __('Color', 'groundhogg'),
          }, ColorPicker({
            type: 'text',
            id: 'border-color',
            value: advancedStyle.borderColor,
            onChange: borderColor => updateStyle({
              borderColor,
              reRenderControls: true,
            }),
          })),
          Control({
            label: 'Width',
            stacked: true,
          }, TopRightBottomLeft({
            id: 'border-width',
            values: advancedStyle.borderWidth,
            onChange: borderWidth => {
              updateStyle({
                borderWidth,
                reRenderControls: true,
              })
            },
          })),
          Control({
            label: 'Radius',
            stacked: true,
          }, TopRightBottomLeft({
            id: 'border-radius',
            values: advancedStyle.borderRadius,
            onChange: borderRadius => {
              updateStyle({
                borderRadius,
                reRenderControls: true,
              })
            },
          })),
        ]),
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
      panel = `email-${name.toLowerCase().replaceAll(' ', '-')}`
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
            togglePanel(panel)
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
      }, BlockRegistry.html(block)),
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
      }, [
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
    } = getEmailMeta()

    return Fragment([
      ControlGroup({
        name: 'Email Settings',
        closable: false,
      }, [
        Control({
          label: 'Send this email from...',
          stacked: true,
        }, Select({
          id: 'from-user',
          name: 'from_user',
          options: [
            { value: 0, text: __('Contact Owner') },
            { value: 'default', text: `${Groundhogg.defaults.from_name} &lt;${Groundhogg.defaults.from_email}&gt;` },
            ...Groundhogg.filters.owners.map(({ data, ID }) => ({
              value: ID,
              text: `${data.display_name} &lt;${data.user_email}&gt;`,
            })),
          ],
          selected: getEmailData().from_user,
          onChange: e => setEmailData({ from_user: e.target.value }),
        })),
        Control({
          label: 'Send replies to...',
          stacked: true,
        }, Input({
          id: 'reply-to',
          type: 'email',
          value: reply_to_override,
          onInput: e => setEmailMeta({ reply_to_override: e.target.value }),
          onChange: e => setEmailMeta({ reply_to_override: e.target.value }),
          onCreate: el => {

            let source = Groundhogg.filters.owners.map(({ data }) => ({
              value: data.user_email,
              label: data.display_name,
            }))

            // $(el).autocomplete({
            //   source,
            // }).autocomplete('instance')._renderItem = function (ul, item) {
            //   return $('<li>').append(`<div><b>${item.label}</b><br/>${item.value}</div>`).appendTo(ul)
            // }

          },
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

  const EmailControls = () => {

    let controls

    switch (getEmailControlsTab()) {
      case 'email':
        controls = BasicEmailControls()
        break
      case 'advanced':
        controls = AdvancedEmailControls()
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
          confirmationModal({
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
      !isEmailEditorPage() ? Button({
        id: 'close-editor',
        className: 'gh-button secondary text icon',
        onClick: e => {

          if (getState().hasChanges) {
            confirmationModal({
              alert: `<p>You have unsaved changes! Are you sure you want to leave?</p>`,
              onConfirm: onClose,
            })
            return
          }

          onClose()
        },
      }, Dashicon('no-alt')) : null,
    ])
  }

  const EmailEditor = () => {

    return Div({
      id: 'email-editor',
    }, [
      // header
      Header(),
      // Block editor
      BlockEditor(),
    ])
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
    },
    width: gap,
  })

  const Column = ({ blocks, col, className, style = {}, verticalAlign = 'top', ...props }) => {

    if (isGeneratingHTML()) {
      return Td({
        className: `email-columns-cell ${className}`,
        style: {
          verticalAlign,
          ...style,
        },
        ...props,
      }, Table({
        className: `column ${blocks.length ? '' : 'empty'}`,
        cellpadding: '0',
        cellspacing: '0',
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

  const cellReducer = (cells, props, i, columns, ...more) => {

    let [
      gap = 10,
      verticalAlign = 'top',
    ] = more

    if (i > 0) {
      cells.push(ColumnGap(gap))
    }

    cells.push(Column({
      blocks: columns[i],
      col: i,
      verticalAlign,
      ...props,
    }))

    return cells
  }

  const columnLayouts = {
    three_columns: (columns, ...more) => {
      return [
        {
          className: 'one-third',
          width: '33%',
          style: {
            width: '33%',
          },
        },
        {
          className: 'one-third',
          width: '33%',
          style: {
            width: '33%',
          },
        },
        {
          className: 'one-third',
          width: '33%',
          style: {
            width: '33%',
          },
        },
      ].reduce(
        (cells, props, i) => cellReducer(cells, props, i, columns, ...more), [])
    },
    two_columns: (columns, ...more) => {
      // console.log(more)
      return [
        {
          className: 'one-half',
          width: '50%',
          style: {
            width: '50%',
          },
        },
        {
          className: 'one-half',
          width: '50%',
          style: {
            width: '50%',
          },
        },
      ].reduce((cells, props, i) => cellReducer(cells, props, i, columns, ...more),
        [])
    },
    two_columns_right: (columns, ...more) => {
      return [
        {
          className: 'two-third',
          width: '66%',
          style: {
            width: '66%',
          },
        },
        {
          className: 'one-third',
          width: '33%',
          style: {
            width: '33%',
          },
        },
      ].reduce((cells, props, i) => cellReducer(cells, props, i, columns, ...more),
        [])
    },
    two_columns_left: (columns, ...more) => {
      return [
        {
          className: 'one-third',
          width: '33%',
          style: {
            width: '33%',
          },
        },
        {
          className: 'two-third',
          width: '66%',
          style: {
            width: '66%',
          },
        },
      ].reduce((cells, props, i) => cellReducer(cells, props, i, columns, ...more),
        [])
    },
    one_column: (columns, ...more) => {
      let [gap, verticalAlign] = more
      return Column({
        blocks: columns[0],
        col: 0,
        gap: 0,
        verticalAlign,
        width: '100%',
        style: {
          width: '100%',
        },
      })
    },
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
    color: '#1a1a1a',
    fontSize: 13,
    ...style,
  })

  const fillFontStyle = ({ fontSize = 16, ...style }) => ({
    lineHeight: '1.4',
    fontFamily: 'system-ui, sans-serif',
    fontWeight: 'normal',
    color: '#000',
    fontSize: `${fontSize}px`,
    ...style,
  })

  const fontStyle = style => {
    return objectToStyle(fillFontStyle(style))
  }

  const TagFontControlGroup = (name, tag, style, updateBlock, supports = {}) => {

    supports = {
      fontSize: true,
      fontFamily: true,
      fontWeight: true,
      color: true,
      lineHeight: true,
      ...supports,
    }

    let {
      fontSize = '14',
      fontFamily = '',
      fontWeight = 'normal',
      color = '',
      lineHeight = '1.4',
    } = style

    const updateStyle = (newStyle) => {
      style = {
        ...style,
        ...newStyle,
      }

      updateBlock({
        [tag]: style,
      })
    }

    return ControlGroup({
      name,
    }, [
      !supports.fontSize ? null : Control({ label: __('Font Size', 'groundhogg') }, Input({
        type: 'number',
        id: `${tag}-font-size`,
        name: `${tag}_font_size`,
        className: 'font-control control-input',
        value: style.fontSize,
        onInput: e => updateStyle({ fontSize: e.target.value }),
      })),
      !supports.lineHeight ? null : Control({ label: __('Line Height', 'groundhogg') }, Input({
        type: 'number',
        id: `${tag}-line-height`,
        name: `${tag}_line_height`,
        className: 'font-control control-input',
        value: style.lineHeight,
        step: '0.1',
        max: 10,
        onInput: e => updateStyle({ lineHeight: e.target.value }),
      })),
      !supports.fontWeight ? null : Control({ label: __('Font Weight', 'groundhogg') }, Select({
        id: `${tag}-font-weight`,
        name: `${tag}_font_weight`,
        className: 'font-control control-input',
        selected: style.fontWeight,
        options: fontWeights.map(i => ({ value: i, text: i })),
        onChange: e => updateStyle({ fontWeight: e.target.value }),
      })),
      !supports.fontFamily ? null : Control({ label: __('Font Family', 'groundhogg') }, Select({
        id: `${tag}-font-family`,
        name: `${tag}_font_family`,
        className: 'font-control control-input',
        selected: style.fontFamily,
        options: fontFamilies,
        onChange: e => updateStyle({ fontFamily: e.target.value }),
      })),
      !supports.color ? null : Control({ label: __('Font Color', 'groundhogg') }, ColorPicker({
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
		     style="enable-background:new 0 0 426.667 426.667" xml:space="preserve"><path d="M384 21.333h-42.667c-23.552 0-42.667 19.157-42.667 42.667v298.667c0 23.531 19.115 42.667 42.667 42.667H384c23.552 0 42.667-19.136 42.667-42.667V64c0-23.509-19.115-42.667-42.667-42.667zM234.667 21.333H192c-23.552 0-42.667 19.157-42.667 42.667v298.667c0 23.531 19.115 42.667 42.667 42.667h42.667c23.552 0 42.667-19.136 42.667-42.667V64c-.001-23.509-19.115-42.667-42.667-42.667zM85.333 21.333H42.667C19.136 21.333 0 40.491 0 64v298.667c0 23.531 19.136 42.667 42.667 42.667h42.667c23.531 0 42.667-19.136 42.667-42.667V64C128 40.491 108.864 21.333 85.333 21.333z"/></svg>`,
    controls: ({ layout = 'two_columns', gap = 0, verticalAlign = 'top', updateBlock }) => {

      const layoutChoices = {
        one_column: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="16.549 60.934 499.999 150"><path d="M511.548 60.934c2.761 0 5 1.343 5 3v144c0 1.656-2.239 3-5 3H21.549c-2.761 0-5-1.344-5-3v-144c0-1.657 2.239-3 5-3Z"/></svg>`,
        three_columns: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="23.085 13.971 499.999 150"><path d="M28.085 13.971h143.333a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H28.085a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5ZM201.418 13.971h143.333a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H201.418a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5ZM374.751 13.971h143.333a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H374.751a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5Z" transform="matrix(1 0 0 .6 0 5.588)"/></svg>`,
        two_columns: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="569.217 10.755 500 150"><path d="M574.217 10.755h230a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5h-230a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5ZM834.217 10.755h230a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5h-230a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5Z" transform="matrix(1 0 0 .6 0 4.302)"/></svg>`,
        two_columns_right: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="24.417 277.63 499.999 150"><path d="M29.417 277.63h316.667a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H29.417a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5ZM376.083 277.63h143.333a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H376.083a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5Z" transform="matrix(1 0 0 .6 0 111.052)"/></svg>`,
        two_columns_left: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="568.076 279.146 500 150"><path d="M746.409 279.146h316.667a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H746.409a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5ZM573.076 279.146h143.333a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H573.076a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5Z" transform="matrix(1 0 0 .6 0 111.658)"/></svg>`,
      }

      return [
        ControlGroup({
          name: 'Layout',
        }, [
          Div({
            className: 'layouts',
          }, [
            ...Object.keys(layoutChoices).map(k => Button({
              className: `layout-choice ${layout === k ? 'selected' : ''}`,
              dataLayout: k,
              id: `layout-${k}`,
              onClick: e => updateBlock({ layout: k, reRenderControls: true }),
            }, layoutChoices[k])),
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
        Tr({ className: 'email-columns-row' }, columnLayouts[layout](columns, gap, verticalAlign)))
    },
    plainText: ({ columns }) => {
      return columns.map(column => renderBlocksPlainText(column)).join('\n\n')
    },
    css: ({ selector, id, columns, gap = 10 }) => {
      //language=CSS
      return `

          ${selector} .email-columns .email-columns-cell.gap {
              width: ${gap}px;
              height: ${gap}px;
          }

          ${selector} .email-columns .email-columns-cell.one-third {
              width: ${(1 / 3) * 100}%;
          }

          ${columns.map(col => col.length ? renderBlocksCSS(col) : '').join('')}
      `
    },
    defaults: {
      layout: 'two_columns',
      columns: [
        [],
        [],
        [],
      ],
      gap: 10,
    },
  })

  const textContent = ({ content, p, h1, h2, h3, a }) => {

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

    doc.body.firstElementChild.style.marginTop = 0
    doc.body.lastElementChild.style.marginBottom = 0

    return Div({
      className: 'text-content-wrap',
    }, doc.body.childNodes)
  }

  registerBlock('text', 'Text', {
    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" style="enable-background:new 0 0 977.7 977.7" xml:space="preserve"
		     viewBox="0 0 977.7 977.7"><path d="M770.7 930.6v-35.301c0-23.398-18-42.898-41.3-44.799-17.9-1.5-35.8-3.1-53.7-5-34.5-3.6-72.5-7.4-72.5-50.301L603 131.7c136-2 210.5 76.7 250 193.2 6.3 18.7 23.8 31.3 43.5 31.3h36.2c24.9 0 45-20.1 45-45V47.1c0-24.9-20.1-45-45-45H45c-24.9 0-45 20.1-45 45v264.1c0 24.9 20.1 45 45 45h36.2c19.7 0 37.2-12.6 43.5-31.3 39.4-116.5 114-195.2 250-193.2l-.3 663.5c0 42.9-38 46.701-72.5 50.301-17.9 1.9-35.8 3.5-53.7 5-23.3 1.9-41.3 21.4-41.3 44.799v35.3c0 24.9 20.1 45 45 45h473.8c24.8 0 45-20.199 45-45z"/></svg>`,
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

        modal({
          content: textarea({
            value: content,
            id: editorId,
          }),
          width: 600,
          onOpen: () => {
            wp.editor.remove(editorId)
            tinymceElement(editorId, {
              tinymce: {
                content_style: tinyMceCSS(),
              },
              quicktags: true,
              settings: {
                height: 800,
              },
            }, (newContent) => {
              content = newContent
              updateBlock({
                content,
              })
            })
          },
        })
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
			<path fill="#444"
			      d="m15.7 5.3-1-1c-.2-.2-.4-.3-.7-.3H1c-.6 0-1 .4-1 1v5c0 .3.1.6.3.7l1 1c.2.2.4.3.7.3h13c.6 0 1-.4 1-1V6c0-.3-.1-.5-.3-.7zM14 10H1V5h13v5z"/>
		</svg>`,
    controls: ({ text, link, style, align, size, updateBlock }) => {
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
        TagFontControlGroup('Button Font', 'style', style, updateBlock),
      ]
    },
    html: ({ text, align, style, size, link }) => {
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
    edit: ({ text, align, style, size, updateBlock }) => {

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
    css: ({ selector, style }) => {
      //language=CSS
      return `
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
    controls: ({ id, src, link = '', width, height, alt = '', align = 'center', updateBlock }) => {

      return ControlGroup({
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
      ])
    },
    edit: ({ src, width, height, alt = '', align = 'center', updateBlock }) => {

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
        },
      }))
    },
    html: ({ src, width, height, link = '', alt = '', align = 'center' }) => {

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
  <path color="currentColor"
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
  <path d="M393 188H17a17 17 0 1 0 0 34h376a17 17 0 1 0 0-34z"/>
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
  <path
	  d="M507 243 388 117a19 19 0 1 0-28 27l106 112-106 112a19 19 0 0 0 28 27l119-126c7-7 7-19 0-26zM152 368 46 256l106-112a19 19 0 0 0-28-27L5 243c-7 7-7 19 0 26l119 126a19 19 0 0 0 27 0c7-7 8-19 1-27zM287 53c-10-2-20 5-22 16l-56 368a19 19 0 0 0 38 6l56-368c2-11-5-21-16-22z"/>
</svg>`,
    controls: ({ content, updateBlock }) => {
      return Fragment([
        Div({
          id: 'code-block-editor',
          onCreate: el => {

            // Wait for add to dom
            setTimeout(() => {
              let editor = wp.CodeMirror(el, {
                value: getActiveBlock().content,
                mode: 'htmlmixed',
                theme: 'default',
                lineNumbers: true,
                lineWrapping: true,
                continueComments: true,
                direction: 'ltr',
                indentUnit: 4,
                indentWithTabs: true,
                inputStyle: 'contenteditable',
                styleActiveLine: true,
                lint: true,
                autoCloseTags: true,
                matchTags: {
                  bothTags: true,
                },
                gutters: [
                  'CodeMirror-lint-markers',
                ],
              })

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
		     style="enable-background:new 0 0 193.826 193.826" xml:space="preserve"><path d="M191.495 55.511 137.449 1.465a4.998 4.998 0 0 0-7.07 0l-.229.229a17.43 17.43 0 0 0-5.14 12.406c0 3.019.767 5.916 2.192 8.485l-56.55 48.533c-4.328-3.868-9.852-5.985-15.703-5.985a23.444 23.444 0 0 0-16.689 6.913l-.339.339a4.998 4.998 0 0 0 0 7.07l32.378 32.378-31.534 31.533c-.631.649-15.557 16.03-25.37 28.27-9.345 11.653-11.193 13.788-11.289 13.898a4.995 4.995 0 0 0 .218 6.822 4.987 4.987 0 0 0 3.543 1.471c1.173 0 2.349-.41 3.295-1.237.083-.072 2.169-1.885 13.898-11.289 12.238-9.813 27.619-24.74 28.318-25.421l31.483-31.483 30.644 30.644c.976.977 2.256 1.465 3.535 1.465s2.56-.488 3.535-1.465l.339-.339a23.446 23.446 0 0 0 6.913-16.689 23.43 23.43 0 0 0-5.985-15.703l48.533-56.55a17.434 17.434 0 0 0 8.485 2.192c4.687 0 9.093-1.825 12.406-5.14l.229-.229a5 5 0 0 0 0-7.072z"/></svg>`,
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
		<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 512 512">
  <path
	  d="M507 243 388 117a19 19 0 1 0-28 27l106 112-106 112a19 19 0 0 0 28 27l119-126c7-7 7-19 0-26zM152 368 46 256l106-112a19 19 0 0 0-28-27L5 243c-7 7-7 19 0 26l119 126a19 19 0 0 0 27 0c7-7 8-19 1-27zM287 53c-10-2-20 5-22 16l-56 368a19 19 0 0 0 38 6l56-368c2-11-5-21-16-22z"/>
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

    if (html === null) {
      return morphdom(document.getElementById(selector.id), selector)
    }

    morphdom(document.querySelector(selector), Fragment(html), {
      childrenOnly: true,
    })
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
  const updateStyles = () => $('#builder-style').text(renderBlocksCSS(getBlocks()))

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

          text = node.querySelector('.text_block').firstElementChild.innerHTML

          block = createBlock('text', {
            content: text,
          })

          break
        case 'button':
        case 'button_block':

          button = node.querySelector('td.email-button')
          a = button.querySelector('a')

          // console.log(a.style.fontSize, a.style.color)

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

  if (isEmailEditorPage()) {

    let {
      email = null,
    } = _BlockEditor

    if (email) {
      window.addEventListener('load', () => {
        initialize({
          email,
        })
      })
    }

  }

  Groundhogg.EmailEditor = initialize

})(jQuery)