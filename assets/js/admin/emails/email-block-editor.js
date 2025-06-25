( ($) => {

  const {
    rawHandler,
    serialize,
  } = wp.blocks

  const {
    initializeEditor,
  } = wp.editPost

  const {
    Div,
    An,
    Pg,
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
    Img,
    htmlToElements,
    Dashicon,
    ButtonToggle,
    ToolTip,
    Autocomplete,
  } = MakeEl

  const {
    el,
    objectToStyle,
    icons,
    uuid,
    tinymceElement,
    improveTinyMCE,
    textarea,
    modal,
    clickedIn,
    copyObject,
    moreMenu,
    dialog,
    confirmationModal,
    loadingModal,
    dangerConfirmationModal,
    adminPageURL,
    isValidEmail,
  } = Groundhogg.element

  const {
    formatNumber,
    formatTime,
    formatDate,
    formatDateTime,
  } = Groundhogg.formatting
  const {
    __,
    _x,
    _n,
    _nx,
    sprintf,
  } = wp.i18n
  const { linkPicker } = Groundhogg.pickers
  const {
    get,
    post,
    patch,
    ajax,
  } = Groundhogg.api
  const {
    emails   : EmailsStore,
    campaigns: CampaignsStore,
  } = Groundhogg.stores
  const {
    base64_json_encode,
    jsonCopy,
  } = Groundhogg.functions

  const {
    ImageInput,
    ImagePicker,
  } = Groundhogg.components

  let {
    colorPalette = [],
    globalFonts = [],
    globalSocials = [],
    blockDefaults = {},
    imageSizes = [],
  } = _BlockEditor

  improveTinyMCE({})

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

  let fonts = [
    'system-ui, sans-serif',
    'Arial, sans-serif', // Web, Mobile, Desktop
    'Arial Black, Arial, sans-serif', // Web
    'Arial Narrow, Arial, sans-serif', // Web
    'Times New Roman, Times, serif', // All
    'Georgia, serif',
    'Courier New, Courier, monospace',
    'Verdana, Geneva, sans-serif',
    'Tahoma, sans-serif',
    'Trebuchet MS, sans-serif',
    'Calibri, sans-serif',
    'Century Gothic, sans-serif',
    'Palatino, Times New Roman, Times, serif',
    'Garamond, Palatino, Times New Roman, Times, serif',
    'Book Antiqua, Palatino, serif',
    'Lucida Grande, sans-serif',
    'Lucida Sans, Lucida Grande, Arial, sans-serif',
    'Impact, Arial Black, sans-serif',
    'Copperplate, sans-serif',
    'Copperplate Gothic Light, Copperplate, Century Gothic, Arial, sans-serif',
    'Futura, Calibri, Arial, sans-serif',
  ]

  const subFonts = fonts.reduce((fonts, font) => {
      let subFonts = font.split(',').map(f => f.trim())
      subFonts.forEach(f => {
        if (!fonts.includes(f)) {
          fonts.push(f)
        }
      })

      return fonts
    },
    [])

  const subFontsWithSpaces = subFonts.filter(font => font.includes(' '))

  const removeQuotes = data => {
    return data.replaceAll(/"|'|(&quot;)/g, '')
  }

  const removeFontQuotesFromCommentData = data => data.replaceAll(new RegExp(`["'](${ subFontsWithSpaces.join('|') })["']`,
      'g'),
    '$1')

  const fontFamilies = {}

  const fontName = font => font.split(',')[0]

  fonts.sort((a, b) => fontName(a).localeCompare(fontName(b))).forEach(font => {
    fontFamilies[font] = fontName(font)
  })

  function onlyUnique (value, index, array) {
    return array.indexOf(value) === index
  }

  const InspectorBlock = (block, depth = 0) => {

    let type = BlockRegistry.get(block.type)

    return Button({
        id: `inspector-${ block.id }`,

        dataId   : block.id,
        className: `inspector-block ${ isActiveBlock(block.id) ? 'active' : '' }`,

        style       : {
          paddingLeft: `${ 12 * depth }px`,

        },
        onClick     : e => {
          setActiveBlock(block.id)
          document.getElementById(`edit-${ block.id }`).scrollIntoView(true)
          morph(BlockInspector())
        },
        onMouseenter: e => {
          document.getElementById(`edit-${ block.id }`).classList.add('inspector-hover')
        },
        onMouseleave: e => {
          document.getElementById(`edit-${ block.id }`).classList.remove('inspector-hover')
        },
      },
      [
        type.svg,
        type.name,
      ])

  }

  const inspectorSortable = el => {
    $(el).sortable({
      // placeholder: 'inspector-placeholder',
      connectWith: '.inspector-column-sortable, #block-inspector', // handle: '.move-block',
      // helper: sortableHelper,
      cancel : '',
      update : (e, ui) => {

        let blockId = ui.item.data('id')
        let index = ui.item.index()

        let $sortable = $(e.target)

        // No longer in this sortable
        if (!$sortable.has(`#inspector-${ blockId },
#inspector-${ blockId }-columns`).length) {
          return
        }

        // moving block
        let parent = $sortable.is('.inspector-column-sortable') ? $sortable.data('parent') : false
        let column = parent ? $sortable.closest('.inspector-column').index() - 1 : 0

        if (blockId) {
          moveBlock(blockId, index, parent, column)
        }
      },
      receive: (e, ui) => {

        let $sortable = $(e.target)

        // moving block
        let parent = $sortable.is('.inspector-column-sortable') ? $sortable.data('parent') : false
        let column = parent ? $sortable.closest('.inspector-column').index() - 1 : 0

        let blockId = ui.item.data('id')
        let index = ui.item.index()

        if (blockId) {
          moveBlock(blockId, index, parent, column)
        }
      },

    })
  }

  const InspectorColumn = (parent, blocks, depth = 1, label = 'Column') => Div({
      className: 'inspector-column',
    },
    [
      Div({
          className: 'column-header',
          style    : {
            paddingLeft: `${ 12 * depth }px`,

          },
        },
        label),
      Div({
          className : 'inspector-column-sortable',
          dataParent: parent,
          onCreate  : inspectorSortable,
        },
        [...blocks.map(block => InspectorBlockWrapper(block, depth + 1))]),

    ])

  const InspectorBlockWrapper = (block, depth = 1) => {

    if (block.children && Array.isArray(block.children)) {
      return Div({
          id       : `inspector-${ block.id }-parent`,
          className: 'inspector-parent',
          dataId   : block.id,
        },
        [
          InspectorBlock(block, depth),
          InspectorColumn(block.id, block.children, depth + 1, 'Children'),
        ])
    }

    if (block.type === 'columns' && block.columns && Array.isArray(block.columns)) {
      return Div({
          id       : `inspector-${ block.id }-columns`,
          className: 'inspector-columns',
          dataId   : block.id,
        },
        [
          InspectorBlock(block, depth),
          ...block.columns.filter(blocks => blocks.length > 0).
            map(blocks => InspectorColumn(block.id, blocks, depth + 1)),

        ])
    }

    return InspectorBlock(block, depth)
  }

  const BlockInspector = () => {
    return Div({
        className: 'block-inspector',
        id       : 'block-inspector',
        onCreate : inspectorSortable,
      },
      getBlocks().map(block => InspectorBlockWrapper(block)))
  }

  const BOXED = 'boxed'
  const FULL_WIDTH = 'full_width'
  const FULL_WIDTH_CONTAINED = 'full_width_contained'

  const DesignTemplates = [
    {
      id  : BOXED,
      name: __('Boxed'),
      html: (blocks) => {

        const {
          width = 640,
          alignment = 'left',
          backgroundColor = 'transparent',
          backgroundImage = '',
          backgroundPosition = '',
          backgroundSize = '',
          backgroundRepeat = '',
          direction = 'ltr',
        } = getEmailMeta()

        let style = {
          backgroundColor,
        }

        if (backgroundImage) {
          delete style.backgroundColor
          style.background = `url('${ backgroundImage }') ${ backgroundPosition } / ${ backgroundSize } ${ backgroundRepeat } ${ backgroundColor }`
        }

        return Div({
            className: 'template-bg',
            style,
          },
          Div({
              className: `template-boxed ${ alignment }`,
              dir      : direction,
              style    : {
                maxWidth: `${ width || 640 }px`,

              },
            },
            blocks))
      },
    },
    {
      id  : FULL_WIDTH_CONTAINED,
      name: __('Full-Width Contained'),
      html: (blocks) => {
        const {
          backgroundColor = 'transparent',
          backgroundImage = '',
          backgroundPosition = '',
          backgroundSize = '',
          backgroundRepeat = '',
          direction = 'ltr',
        } = getEmailMeta()

        let style = {
          backgroundColor,
        }

        if (backgroundImage) {
          delete style.backgroundColor
          style.background = `url('${ backgroundImage }') ${ backgroundPosition } / ${ backgroundSize } ${ backgroundRepeat } ${ backgroundColor }`
        }

        return Div({
            className: `template-full-width-contained`,
            dir      : direction,
            style,
          },
          blocks)
      },
    },
    {
      id  : FULL_WIDTH,
      name: __('Full-Width'),
      html: (blocks) => {
        const {
          backgroundColor = 'transparent',
          backgroundImage = '',
          backgroundPosition = '',
          backgroundSize = '',
          backgroundRepeat = '',
          direction = 'ltr',
        } = getEmailMeta()

        let style = {
          backgroundColor,
        }

        if (backgroundImage) {
          delete style.backgroundColor
          style.background = `url('${ backgroundImage }') ${ backgroundPosition } / ${ backgroundSize } ${ backgroundRepeat } ${ backgroundColor }`
        }

        return Div({
            className: `template-full-width`,
            dir      : direction,
            style,
          },
          blocks)
      },
    },
  ]

  const GlobalFonts = {
    fonts: [],

    add () {

      let font = {
        id   : uuid(),
        name : 'New font',
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
      this.fonts = this.fonts.map(font => font.id === id ? {
        ...font,
        style: { ...font.style, ...style },
      } : font)
    },

    save () {
      return Groundhogg.stores.options.patch({
        gh_email_editor_global_fonts: this.fonts,
      })
    },
  }

  const History = {

    // The size of the stack to maintain
    stackSize: 50,
    // Where we are in the history
    pointer: 0,
    // The changes we've made
    changes: [],

    timeout: null,

    // Add a state to the history
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
          if (this.changes.length > this.stackSize) {
            this.changes.shift()
          }

          // Set the pointer to the end of the changelist
          this.pointer = this.changes.length - 1

          // Update the undo/redo buttons
          let el = document.getElementById('undo-and-redo')
          if (el) {
            morphdom(el, UndoRedo())
          }
        },
        100)
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
    if (e.key === 'Z' && ( e.ctrlKey || e.metaKey ) && e.shiftKey) {
      History.redo()
    }

    if (e.key === 'z' && ( e.ctrlKey || e.metaKey ) && !e.shiftKey) {
      History.undo()
    }

    if (e.key === 'y' && ( e.ctrlKey || e.metaKey )) {
      History.redo()
    }
  })

  let State = {
    email               : {},
    changes             : {},
    activeBlock         : null,
    openPanels          : {},
    blockControlsTab    : 'block',
    emailControlsTab    : 'email',
    isGeneratingHTML    : false,
    hasChanges          : false,
    preview             : '',
    page                : 'editor',
    templateSearch      : '',
    responsiveDevice    : 'desktop',
    testType            : 'design',
    blocks              : [],
    blockInspector      : false,
    editDefaults        : false,
    savingEditorSettings: false,
  }

  const setState = newState => {

    const {
      editDefaults        : wasEditingDefaults,
      savingEditorSettings: wasSavingEditorSettings,
    } = State

    State = {
      ...State, ...newState,
      id: uuid(),
    }

    if (wasSavingEditorSettings !== State.savingEditorSettings) {
      morphControls()
    }

    // was editing defaults, but now we're not
    if (wasEditingDefaults && !State.editDefaults) {
      morphEmailEditor()
    }
  }

  const getState = () => State
  const getStateCopy = () => jsonCopy(getState())

  let onSave = () => {
  }
  let onClose = () => {
  }

  const saveEditorSettings = () => {

    setState({
      savingEditorSettings: true,
    })

    return Groundhogg.stores.options.patch({
      gh_email_editor_color_palette         : colorPalette,
      gh_email_editor_global_fonts          : GlobalFonts.fonts,
      gh_email_editor_global_social_accounts: globalSocials,
      gh_email_editor_block_defaults        : blockDefaults,
    }).finally(() => {

      setState({
        savingEditorSettings: false,
      })
    })
  }

  /**
   * Saves the email
   *
   * @return {Promise<*>}
   */
  const saveEmail = () => {

    // Save editor settings
    saveEditorSettings()

    // No ID, creating the email
    if (isCreating()) {
      return EmailsStore.create(State.changes).then(email => {
        dialog({
          message: 'Email created!',
        })

        if (isEmailEditorPage()) {
          window.history.pushState({},
            `${ email.data.title } &lsaquo; ${ __('Edit') }`,
            adminPageURL('gh_emails', {
              action: 'edit',
              email : email.ID,
            }))
          // more menu should become visible at this point
          morphHeader()
        }

        setState({
          email,
          changes   : {},
          hasChanges: false,
        })

        onSave(email)
      }).catch(err => {
        dialog({
          message: 'Oops, something went wrong. Try refreshing the page.',
          type   : 'error',
        })
      })

    }

    return EmailsStore.patch(State.email.ID, State.changes).then(email => {
      dialog({
        message: 'Email updated!',
      })

      setState({
        email,
        changes   : {},
        hasChanges: false,
      })

      onSave(email)
    }).catch(err => {
      dialog({
        message: 'Oops, something went wrong. Try refreshing the page.',
        type   : 'error',
      })
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
        }
        else {
          this.controller.abort()
          reset()
        }

        let endpoint = getEmail().ID ? `${ EmailsStore.route }/${ getEmail().ID }/preview` : `${ EmailsStore.route }/preview`

        return post(endpoint, {
            data: getEmailData(),
            meta: getEmailMeta(),

          },
          {
            signal: this.signal,
          }).then(({ item }) => {
          setState({
            preview         : item.context.built,
            previewPlainText: item.context.plain,

            previewFromName  : item.context.from_name,
            previewFromAvatar: item.context.from_avatar,
            previewFromEmail : item.context.from_email,
            previewSubject   : item.context.subject,

            previewLoading: false,
          })

          morphHeader()

        })
      },
      1000)
  }

  const setActiveBlock = (idOrNull) => {

    // Hack to remove the editor when closing a block
    // When the editor is removed ahead of any state changes grammarly actually saves
    if (hasActiveBlock() && getActiveBlock().type === 'text') {
      let editorId = `text-${ getActiveBlock().id }`
      wp.editor.remove(editorId)
    }

    setState({
      activeBlock : idOrNull,
      editDefaults: false,
    })
    morphBlocks()
    // Completely remove controls before changing to new block
    removeControls()
    morphControls()

    if (getState().blockInspector) {
      morph(BlockInspector())
    }

    if (idOrNull) {
      document.getElementById(`edit-${ idOrNull }`).focus()
    }
  }

  /**
   * Copy the active block to the clipboard
   */
  const copyActiveBlock = () => copyBlock(getActiveBlock())

  /**
   * Copy a block to the clipboard
   *
   * @param block
   */
  const copyBlock = (block) => {
    let input = document.createElement('input')
    input.classList.add('hidden')

    setIsGeneratingHTML(true)
    input.value = renderBlocksHTML([block])
    setIsGeneratingHTML(false)
    document.body.append(input)
    input.select()
    navigator.clipboard.writeText(input.value)
    input.remove()
    dialog({
      message: 'Block copied!',
    })
  }

  /**
   * Paste a block from the clipboard if there is one
   *
   * @returns {Promise<string>}
   */
  const pasteBlock = () => navigator.clipboard.readText().then(copiedText => {
    let blocks
    try {
      blocks = parseBlocksFromContent(copiedText)
    }
    catch (e) {
      dialog({
        message: 'No block was copied',
        type   : 'error',
      })
      return
    }

    if (!blocks || !blocks.length) {
      dialog({
        message: 'No block was copied',
        type   : 'error',
      })
      return
    }

    let newBlock

    blocks.forEach(block => {
      newBlock = __replaceId(block)
      insertBlockAfter(newBlock, getActiveBlock().id)
      setActiveBlock(newBlock.id)
    })

    dialog({ message: 'Block pasted!' })
  })

  const isBlockEditor = () => State.page === 'editor'
  const isHTMLEditor = () => State.page === 'html-editor'

  /**
   * IF the block is active
   *
   * @param id
   * @return {boolean}
   */
  const isActiveBlock = (id) => State.activeBlock === id
  const hasActiveBlock = () => State.activeBlock !== null
  const getActiveBlock = () => {

    if (State.editDefaults) {
      return getDefaultBlock()
    }

    return __findBlock(State.activeBlock, getBlocks())
  }
  const getBlockControlsTab = () => State.blockControlsTab
  const getEmailControlsTab = () => State.emailControlsTab
  const setBlockControlsTab = (tab) => setState({
    blockControlsTab: tab,
    editDefaults    : false,
  })
  const setEmailControlsTab = (tab) => setState({
    emailControlsTab: tab,
    editDefaults    : false,
  })
  const getBlocks = () => State.blocks
  const getBlocksCopy = () => JSON.parse(JSON.stringify(State.blocks))
  const isEditing = () => Boolean(getEmailId())
  const isCreating = () => !isEditing()
  const getEmail = () => State.email
  const getEmailId = () => State.email.ID
  const getEmailData = () => State.email.data
  const getEmailMeta = () => State.email.meta
  const getEmailWidth = () => getEmailMeta().width || 640
  const templateIs = template => getEmailMeta().template === template
  const getParentBlocks = () => {
  }
  const isGeneratingHTML = () => State.isGeneratingHTML
  const setIsGeneratingHTML = isGenerating => State.isGeneratingHTML = isGenerating
  const setEmailData = (
    data = {},
    hasChanges = true) => {
    State.email.data = {
      ...State.email.data, ...data,
    }

    State.changes.data = {
      ...State.changes.data || {},
      ...data,
    }

    if (hasChanges) {
      setState({
        hasChanges: true,
      })
    }
  }

  const setEmailMeta = (
    meta = {},
    hasChanges = true) => {
    State.email.meta = {
      ...State.email.meta, ...meta,
    }

    State.changes.meta = {
      ...State.changes.meta || {},
      ...meta,
    }

    if (hasChanges) {
      setState({
        hasChanges: true,
      })
    }
  }

  /**
   * The email's current campagins
   *
   * @return {*|*[]}
   */
  const getCampaigns = () => State.email.campaigns || []

  /**
   * Override the campaigns
   *
   * @param campaigns
   */
  const setCampaigns = campaigns => {
    State.email.campaigns = campaigns.map(id => CampaignsStore.get(id))

    State.changes.campaigns = [...campaigns]
  }

  /**
   * Update the settings of the email
   *
   * @param reRender
   * @param newSettings
   */
  const updateSettings = ({
    reRender = false,
    ...newSettings
  }) => {
    setEmailMeta(newSettings)

    if (reRender) {
      morphEmailEditor()
    }

    updatePreview()

    History.addChange(getStateCopy()) // updateSettings, email meta basically
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

  const setHTML = (content, hasChanges = true) => {

    content = cleanHTML(content, true)

    let plain_text = extractPlainText(content)
    plain_text = plain_text.replaceAll(/(\s*\n|\s*\r\n|\s*\r){3,}/g, '\n\n')
    plain_text = plain_text.replace(/^\s+/, '')

    setEmailData({
      content,
      plain_text,
    })

    setEmailMeta({
      blocks: false,
      type  : 'html',
    })

    if (hasChanges) {
      updatePreview()
    }

    History.addChange(getStateCopy())
  }

  const setBlocks = (blocks = [], hasChanges = true) => {

    if (isHTMLEditor()) {
      return
    }

    setState({
      blocks,
    })

    if (hasChanges) {

      setIsGeneratingHTML(true)
      let css = renderBlocksCSS(blocks)
      let content = renderBlocksHTML(blocks)
      let plain_text = renderBlocksPlainText(blocks)
      setIsGeneratingHTML(false)

      setEmailData({
        content,
        plain_text,
      }, hasChanges)

      setEmailMeta({
        css,
        blocks: true,
        type  : 'blocks',
      }, hasChanges)

      updatePreview()
    }

    if (getState().blockInspector) {
      morph(BlockInspector())
    }

    // Log a new state in history whenever we set the blocks
    History.addChange(getStateCopy())
  }

  function extractPlainText (content) {
    const parser = new DOMParser()
    const doc = parser.parseFromString(content, 'text/html')
    return __extractPlainText(doc.body)
  }

  /**
   * Parse HTML content to make better plain text emails
   *
   * @param node
   * @return {string|*}
   */
  function __extractPlainText (node) {

    if (node.nodeType === Node.TEXT_NODE) {

      // These are likely just newlines and should be excluded
      if ([
        'ol',
        'ul',
      ].includes(node.parentNode.tagName.toLowerCase())) {
        return ''
      }

      return node.textContent
    }
    else if (node.nodeType === Node.ELEMENT_NODE) {
      const tagName = node.tagName.toLowerCase()

      let text = ''
      let index = Array.from(node.parentNode.childNodes).
        filter(node => node.nodeType === Node.ELEMENT_NODE).
        indexOf(node)

      for (const childNode of node.childNodes) {
        text += __extractPlainText(childNode)
      }

      if (tagName === 'a') {
        return `[${ text }](${ node.getAttribute('href') })`
      }

      if (tagName === 'br') {
        return '  \n'
      }

      if (tagName === 'img') {
        return `![${ node.alt || 'image' }](${ node.src })`
      }

      if (tagName === 'li') {

        if (node.parentNode.tagName.toLowerCase() === 'ol') {
          return `\n${ index + 1 }. ${ text }`
        }

        return `\n- ${ text }`
      }

      if ([
        'del',
        'strike',
      ].includes(tagName)) {
        return `~~${ text }~~`
      }

      if ([
        'p',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
      ].includes(tagName)) {
        let headingPrefix = '#'.repeat(parseInt(tagName.substr(1)))
        if (headingPrefix) {
          headingPrefix += ' '
        }
        return `${ index > 0 ? '\n\n' : '\n' }${ headingPrefix }${ text }`
      }

      if (tagName === 'b' || tagName === 'strong') {
        return `**${ text }**`
      }

      if (tagName === 'i' || tagName === 'em') {
        return `*${ text }*`
      }

      if (tagName === 'hr') {
        return '\n---'
      }

      if (tagName === 'code') {
        return `\`${ text }\``
      }

      if ([
        'ul',
        'ol',
      ].includes(tagName) && index > 0) {
        return `\n${ text }`
      }

      return text
    }

    return ''
  }

  const NumberControl = ({
    step = 1,
    id,
    unit = null,
    value,
    onChange,
    ...rest
  }) => Div({
      id,
      className: 'gh-input-group number-control',
    },
    [
      Button({
          id       : `minus-${ id }`,
          className: 'gh-button grey small',
          onClick  : e => {
            let input = document.getElementById(`input-${ id }`)
            input.stepDown()
            input.dispatchEvent(new Event('input'))
          },
        },
        Dashicon('minus')),
      Input({
        id     : `input-${ id }`,
        type   : `number`,
        value,
        step,
        min    : 0,
        onInput: e => onChange(e),
        ...rest,
      }),
      unit ? Div({
          className: 'unit',
        },
        unit) : null,
      Button({
          id       : `add-${ id }`,
          className: 'gh-button grey small',
          onClick  : e => {
            let input = document.getElementById(`input-${ id }`)
            input.stepUp()
            input.dispatchEvent(new Event('input'))
          },
        },
        Dashicon('plus-alt2')),

    ])

  const TopRightBottomLeft = ({
    id = '',
    values = {},
    onChange = (values) => {
    },
  }) => {

    values = {
      top   : '',
      right : '',
      bottom: '',
      left  : '',
      linked: true, ...values,
    }

    const setValue = (which, val) => {

      if (values.linked) {
        values = {
          ...values,
          top   : val,
          right : val,
          bottom: val,
          left  : val,
        }
      }
      else {
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
      },
      [
        Input({
          type: 'number',
          id  : `${ id }-top`,

          name     : 'top',
          value    : values.top,
          className: `design-attr full-width`,

          placeholder: 'Top',
          min        : 0,
          onInput    : e => setValue('top', e.target.value),

        }),
        Input({
          type: 'number',
          id  : `${ id }-right`,

          name     : 'right',
          value    : values.right,
          className: `design-attr full-width`,

          placeholder: 'Right',
          min        : 0,
          onInput    : e => setValue('right', e.target.value),

        }),
        Input({
          type: 'number',
          id  : `${ id }-bottom`,

          name     : 'bottom',
          value    : values.bottom,
          className: `design-attr full-width`,

          placeholder: 'Bottom',
          min        : 0,
          onInput    : e => setValue('bottom', e.target.value),

        }),
        Input({
          type: 'number',
          id  : `${ id }-left`,

          name     : 'left',
          value    : values.left,
          className: `design-attr full-width`,

          placeholder: 'Left',
          min        : 0,
          onInput    : e => setValue('left', e.target.value),

        }),
        Button({
            id: `${ id }-link-toggle`,

            className: `gh-button ${ values.linked ? 'primary' : 'secondary' } icon small`,

            value     : values.linked ? 'linked' : 'not-linked',
            dataLinked: values.linked,
            onClick   : e => toggleLinked(),

          },
          icons.link),

      ])
  }

  const ColorPicker = ({
    id = '',
    onChange = color => {
    },
    value = '',
    ...attributes
  }) => {

    if (!value) {
      value = ''
    }

    return Div({
        className: 'gh-color-picker',
        id,
      },
      [
        Div({
          id       : `${ id }-current`,
          className: `current-color ${ value ? '' : 'not-set' }`,
          style    : {
            '--color': value,
          },
        }),
        Button({
            id       : `${ id }-open-picker-${ value.substr(1) }`,
            className: 'gh-button secondary small',
            onClick  : e => {

              MiniModal({
                  selector: `#${ id }`,
                  onOpen  : () => {
                    let $picker = $(`#${ id }-picker`)

                    $picker.iris({
                      hide    : false,
                      border  : false,
                      color   : value,
                      palettes: colorPalette,
                      change  : (e, ui) => {
                        const color = ui.color.toString()

                        const preview = document.getElementById(`${ id }-current`)
                        preview.style.setProperty('--color', color)
                        preview.classList.remove('not-set')

                        onChange(color)
                      },
                    })
                  },
                },
                Div({
                    className: 'gh-color-picker-grid',
                  },
                  [
                    Input({
                      type     : 'text',
                      id       : `${ id }-picker`,
                      className: 'full-width code',
                      style    : {
                        marginBottom: '10px',
                      },
                      value,
                    }),
                    Button({
                        id       : `${ id }-clear`,
                        className: 'gh-button secondary small clear-color',
                        onClick  : e => {

                          let $picker = $(`#${ id }-picker`)
                          $picker.val('')
                          $picker.iris('color', '')

                          const preview = document.getElementById(`${ id }-current`)
                          preview.style.setProperty('--color', '')
                          preview.classList.add('not-set')

                          onChange('')
                        },
                      },
                      'Clear'),

                  ]))

            },
          },
          'Select Color'),

      ])
  }

  const ImageControls = ({
    id = '',
    maxWidth = 0,
    src = '',
    alt = '',
    width = '',
    onChange = ({
      src,
      alt,
      width,
    }) => {
    },
    supports = {
      alt  : true,
      width: true,
    },
  }) => {

    return Fragment([
      Control({
          label  : 'Image',
          stacked: true,
        },
        ImageInput({
          value   : src ?? '',
          id,
          onChange: (src, attachment = null) => {
            if (attachment) {

              let {
                width,
              } = attachment

              if (maxWidth) {
                width = Math.min(maxWidth, width)
              }

              onChange({
                src: attachment.url,
                alt: attachment.alt, // title: attachment.title,
                width,
              })
            }
            else {
              onChange({
                src,
              })
            }
          },
        }),
      ),
      supports.width ? Control({
          label: 'Width',
        },
        NumberControl({
          id: `${ id }-width`,

          className: 'control-input',
          max      : maxWidth,
          value    : width,
          unit     : 'px',
          step     : 10,
          onChange : e => {
            onChange({
              width: parseInt(e.target.value),

            })
          },
        })) : null,
      supports.alt ? Control({
          label: 'Alt Text',
        },
        Input({
          id       : `${ id }-alt`,
          className: 'input',
          value    : alt,
          onChange : e => {
            onChange({
              alt: e.target.value,
            })
          },
        })) : null,
    ])
  }

  const BackgroundImageControls = ({
    id = '',
    backgroundImage = '',
    backgroundPosition = '',
    backgroundRepeat = '',
    backgroundSize = '',
    onChange = () => {
    },
  }) => {

    return Fragment([
      Control({
          label  : 'Background Image',
          stacked: true,
        },
        InputGroup([
          Input({
            type: 'text',
            id  : `${ id }-src`,

            value    : backgroundImage,
            className: 'control full-width',
            name     : 'src',
            onChange : e => {
              onChange({
                backgroundImage: e.target.value,
              })
            },
          }),
          Button({
              id       : `${ id }-select`,
              className: 'gh-button secondary icon',
              onClick  : e => {

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
                  title   : __('Select a image to upload'),
                  button  : {
                    text: __('Use this image'),

                  },
                  multiple: false,	// Set to true to allow multiple files to be selected

                })
                // When an image is selected, run a callback.
                file_frame.on('select', function () {
                  // We set multiple to false so only get one image from the uploader
                  let attachment = file_frame.state().get('selection').first().toJSON()

                  onChange({
                    backgroundImage: attachment.url,
                  })
                })
                // Finally, open the modal
                file_frame.open()
              },
            },
            icons.image),

        ])),
      Control({
          label: 'Position',
        },
        Select({
          id      : `${ id }-position`,
          options : {
            'left top'     : 'Left Top',
            'left center'  : 'Left Center',
            'left bottom'  : 'Left Bottom',
            'right top'    : 'Right Top',
            'right center' : 'Right Center',
            'right bottom' : 'Right Bottom',
            'center top'   : 'Center Top',
            'center center': 'Center Center',
            'center bottom': 'Center Bottom',
          },
          selected: backgroundPosition,
          onChange: e => {
            onChange({
              backgroundPosition: e.target.value,
            })
          },
        })),
      Control({
          label: 'Repeat',
        },
        Select({
          id      : `${ id }-repeat`,
          options : {
            ''         : 'Default',
            'no-repeat': 'No Repeat',
            'repeat'   : 'Repeat',
            'repeat-x' : 'Repeat X',
            'repeat-y' : 'Repeat Y',
          },
          selected: backgroundRepeat,
          onChange: e => {
            onChange({
              backgroundRepeat: e.target.value,
            })
          },
        })),
      Control({
          label: 'Size',
        },
        Select({
          id      : `${ id }-size`,
          options : {
            ''       : 'Default',
            'auto'   : 'Auto',
            'cover'  : 'Cover',
            'contain': 'Contain',
          },
          selected: backgroundSize,
          onChange: e => {
            onChange({
              backgroundSize: e.target.value,
            })
          },
        })),
      `<p><i>Background images do not function in any Windows desktop client. Always set the background color as a fallback.</i></p>`,

    ])
  }

  const AlignmentButtons = ({
    id = '',
    alignment = 'left',
    onChange = alignment => {
    },
    directions = [
      'left',
      'center',
      'right',
    ],
  }) => {

    return ButtonToggle({
      id,
      options: directions.map(direction => ( {
        id  : direction,
        text: Dashicon(direction === 'justify' ? 'editor-justify' : `editor-align${ direction }`),
      } )),

      selected: alignment,
      onChange,
    })
  }

  const BorderControls = ({
    borderStyle = 'none',
    borderWidth = {},
    borderColor = '',
    borderRadius = {},
    onChange = style => {
    },
  }) => Fragment([
    Control({
        label: 'Style',
      },
      Select({
        id      : 'border-style',
        options : {
          none: __('None', 'groundhogg'),

          solid: __('Solid', 'groundhogg'),

          dashed: __('Dashed', 'groundhogg'),

          dotted: __('Dotted', 'groundhogg'),

          double: __('Double', 'groundhogg'),

          ridge: __('Ridge', 'groundhogg'),

          groove: __('Groove', 'groundhogg'),

          inset: __('Inset', 'groundhogg'),

          outset: __('Outset', 'groundhogg'),

        },
        selected: borderStyle,
        onChange: e => onChange({ borderStyle: e.target.value }),

      })),
    Control({
        label: __('Color', 'groundhogg'),

      },
      ColorPicker({
        type    : 'text',
        id      : 'border-color',
        value   : borderColor,
        onChange: borderColor => onChange({
          borderColor,
        }),

      })),
    Control({
        label  : 'Width',
        stacked: true,
      },
      TopRightBottomLeft({
        id      : 'border-width',
        values  : borderWidth,
        onChange: borderWidth => {
          onChange({
            borderWidth,
          })
        },
      })),
    Control({
        label  : 'Radius',
        stacked: true,
      },
      TopRightBottomLeft({
        id      : 'border-radius',
        values  : borderRadius,
        onChange: borderRadius => {
          onChange({
            borderRadius,
          })
        },
      })),

  ])

  const BorderControlGroup = ({
    borderStyle = 'none',
    borderWidth = {},
    borderColor = '',
    borderRadius = {},
    onChange = style => {
    },
  }) => {

    return ControlGroup({ name: 'Border' },
      BorderControls({
        borderStyle,
        borderWidth,
        borderColor,
        borderRadius,
        onChange,
      }))
  }

  const extract4 = ({
    top = 0,
    right = 0,
    bottom = 0,
    left = 0,
  }) => {
    const usePixel = num => {
      num = isNaN(parseInt(num)) ? 0 : parseInt(num)
      return num !== 0 ? `${ num }px` : num
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

  /**
   * Extract a property into top, right, bottom, left
   *
   * @param attr
   * @param style
   * @return {{top: string, left: string, bottom: string, right: string}}
   */
  const parse4FromStyle = (attr, style) => {
    let value = {
      top: parseInt(style.getPropertyValue(sprintf(attr, 'top'))),

      right: parseInt(style.getPropertyValue(sprintf(attr, 'right'))),

      bottom: parseInt(style.getPropertyValue(sprintf(attr, 'bottom'))),

      left: parseInt(style.getPropertyValue(sprintf(attr, 'left'))),

    }

    value.linked = Object.values(value).every(v => v === value.top)

    return value
  }

  /**
   * Given a CSS declaration, extract the border style into usable fragments
   *
   * @param style
   * @return {{borderColor: string, borderRadius: {top: string, left: string, bottom: string, right: string},
   *   borderWidth: {top: string, left: string, bottom: string, right: string},
   borderStyle: string}}
   */
  const parseBorderStyle = style => ( {
    borderStyle: style.getPropertyValue('border-style'),

    borderColor: style.getPropertyValue('border-color'),

    borderWidth: parse4FromStyle('border-%s-width', style),

    borderRadius: ( style => {
      let value = {
        top: parseInt(style.getPropertyValue('border-top-left-radius')),

        right: parseInt(style.getPropertyValue('border-top-right-radius')),

        bottom: parseInt(style.getPropertyValue('border-bottom-right-radius')),

        left: parseInt(style.getPropertyValue('border-bottom-left-radius')),

      }

      value.linked = Object.values(value).every(v => v === value.top)

      return value
    } )(style),

  } )

  /**
   * Extract style given CSS declaration
   *
   * @param style
   * @return {{fontFamily: string, color: string, lineHeight: string, fontSize: number, fontStyle: string, fontWeight:
   *   string, textTransform: string}}
   */
  const parseFontStyle = style => ( {
    color: style.getPropertyValue('color'),

    fontFamily: removeQuotes(style.getPropertyValue('font-family')),

    lineHeight: style.getPropertyValue('line-height'),

    fontWeight: style.getPropertyValue('font-weight'),

    fontStyle: style.getPropertyValue('font-style'),

    fontSize: parseInt(style.getPropertyValue('font-size')),

    textTransform: style.getPropertyValue('text-transform'),

  } )

  const AdvancedStyleControls = {
    getInlineStyle: block => {
      const {
        advancedStyle = {},
        id,
        selector,
      } = block
      const {
        width = '',
        padding = {
          top   : 5,
          right : 5,
          left  : 5,
          bottom: 5,
        },
// margin = {},
        borderStyle = 'none',
        borderColor = 'transparent',
        borderWidth = {},
        borderRadius = {},
        backgroundColor = 'transparent',
        backgroundImage = '',
        backgroundSize = '',
        backgroundRepeat = '',
        backgroundPosition = '',
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
        },
        style)

      if (backgroundColor !== 'transparent') {
        style.backgroundColor = backgroundColor
      }

      if (width) {
        // style.width = `${width}px`
      }

      if (backgroundImage) {
        delete style.backgroundColor
        style.background = `url(${ backgroundImage }) ${ backgroundPosition } / ${ backgroundSize } ${ backgroundRepeat } ${ backgroundColor }`
      }

      return style
    },
    parse         : el => {

      const attributeMap = {
        width  : (style, el) => {

          let innerWidthTd = el.querySelector(`td#${ el.id }-inner`)
          if (innerWidthTd) {
            return parseInt(innerWidthTd.getAttribute('width'))
          }

          return null
        },
        padding: style => parse4FromStyle('padding-%s', style),

        backgroundColor: (style, el) => el.getAttribute('bgcolor'),

        backgroundImage: (style, el) => el.getAttribute('background'),

        backgroundSize: style => style.getPropertyValue('background-size'),

        backgroundRepeat: style => style.getPropertyValue('background-repeat'),

        backgroundPosition: style => style.getPropertyValue('background-position'),

      }

      let style = {}

      for (let attribute in attributeMap) {
        let value = attributeMap[attribute](el.style, el)
        if (value) {
          style[attribute] = value
        }
      }

      style = {
        ...style, ...parseBorderStyle(el.style),

      }

      // console.log( style )

      return style

    },
    css           : (block) => {
      const { selector } = block

      let style = objectToStyle(AdvancedStyleControls.getInlineStyle(block))

      if (!style) {
        return ''
      }

      //language=CSS
      return `
          ${ selector } {
              ${ objectToStyle(AdvancedStyleControls.getInlineStyle(block)) }
          }
      `
    },
    render        : ({
      id,
      advancedStyle = {},
      updateBlock,
    }) => {

      const updateStyle = ({
        morphControls = false,
        morphBlocks = true,
        ...changes
      }) => {

        advancedStyle = copyObject({
          ...advancedStyle, ...changes,
        })

        updateBlock({
          advancedStyle,
          morphControls,
          morphBlocks,
        })
      }

      let {
        backgroundImage = '',
        backgroundPosition = '',
        backgroundSize = '',
        backgroundRepeat = '',
        width = '',
      } = advancedStyle

      if (!width) {
        width = getEmailWidth()
      }

      return Fragment([
        ControlGroup({
            name: 'Layout',
          },
          [
            templateIs(FULL_WIDTH_CONTAINED) ? Control({
                label  : 'Width',
                stacked: false,
              },
              NumberControl({
                type       : 'number',
                min        : 0,
                step       : 10,
                id         : 'advanced-width',
                value      : width,
                placeholder: getEmailWidth(),

                name   : 'advanced_width',
                onInput: e => updateStyle({
                  width      : e.target.value,
                  morphBlocks: true,
                }),

              })) : null,
            Control({
                label  : 'Padding',
                stacked: true,
              },
              TopRightBottomLeft({
                id      : 'padding',
                values  : advancedStyle.padding,
                onChange: padding => {
                  updateStyle({
                    padding,
                    morphControls: true,
                  })
                },
              })),

          ]),
        BorderControlGroup({
          ...advancedStyle,
          onChange: style => updateStyle({
            ...style,
            morphControls: true,
          }),

        }),
        ControlGroup({
            name: 'Background',
          },
          [
            Control({
                label: 'Color',
              },
              ColorPicker({
                type    : 'text',
                id      : 'background-color',
                value   : advancedStyle.backgroundColor,
                onChange: backgroundColor => updateStyle({
                  backgroundColor,
                  morphControls: true,
                }),

              })),
            `<hr/>`,
            BackgroundImageControls({
              id      : 'background-image',
              backgroundImage,
              backgroundSize,
              backgroundRepeat,
              backgroundPosition,
              onChange: (props) => {
                updateStyle({
                  ...props,
                  morphControls: true,
                })
              },
            }),

          ]),

      ])
    },
  }

  const BlockRegistry = {

    get (type) {
      return this.blocks[type]
    },

    css (block) {

      let css = []

      try {
        css.push(this.get(block.type).css({
          ...this.defaults(block),
          ...block,
          selector: `#b-${ block.id }`,

        }))
      }
      catch (e) {
        // console.log(e)
      }

      if (block.css) {
        css.push(block.css.replaceAll(/selector/g, `#b-${ block.id }`))
      }

      return css.filter(css => css && css.length > 0).join('\n')
    },

    edit (block, editing) {
      return this.get(block.type).edit({
          ...this.defaults(block),
          ...block,
        },
        editing)
    },

    defaults ({ type }) {
      return copyObject({
        ...this.get(type).defaults,
        ...blockDefaults[type] ?? {},
      })
    },

    html (block, editing) {

      let blockType = this.get(block.type)

      return blockType.html({
          ...this.defaults(block),
          ...block,
        },
        editing)
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

  const fetchPropsWithDynamicReplacements = Groundhogg.functions.debounce((block, cacheKey, render) => {
    get(`${ EmailsStore.route }/blocks/replacements?props=${ cacheKey }`).then(r => {
      dynamicContentCache.set(cacheKey, r.props)
      morph(`#dynamic-replacements-${ block.id }`, render({
        ...block,
        ...r.props,
      }), false)
    })
  }, 1000)

  /**
   * Wrapper to enable showing dynamic replacements in the editor
   *
   * @param render
   * @param supportedProps
   * @returns {(function(*): (*))|*}
   */
  const withDynamicReplacements = (render, supportedProps = []) => {

    if (!supportedProps || !supportedProps.length) {
      return render
    }

    return (block) => {

      // Generating, do not dynamically replace content
      if (isGeneratingHTML()) {
        return render(block)
      }

      let props = {}

      // get props that support dynamic replacement code content
      supportedProps.forEach(prop => {
        // only do replacements if the prop contains at least one
        if (block[prop] && block[prop].match(/{([A-Za-z_0-9][^{}\n]+)}/)) {
          props[prop] = block[prop]
        }
      })

      // No props contain replacement codes
      if (!Object.keys(props).length) {
        return render(block)
      }

      let cacheKey = base64_json_encode(props)

      if (dynamicContentCache.has(cacheKey)) {
        return render({
          ...block,
          ...dynamicContentCache.get(cacheKey),
        })
      }

      fetchPropsWithDynamicReplacements(block, cacheKey, render)

      return Div({ id: `dynamic-replacements-${ block.id }` }, Div({ className: 'dynamic-content-loader' }, render(block)))
    }
  }

  /**
   * Register a new block
   *
   * @param type
   * @param name
   * @param edit function
   * @param html function
   * @param attributes
   * @param replacementsSupport
   * @param block
   */
  const registerBlock = (type, name, {
    edit = false,
    html = false,
    attributes = {},
    replacementsSupport = {}, // { src: { edit: true, html: false } }
    ...block
  }) => {

    // If edit is undefined copy the html method
    if (!edit) {
      edit = html
    }

    let supportedProps = Object.keys(replacementsSupport)

    // dynamically fetch replacements for the supported properties
    if (supportedProps.length) {
      html = withDynamicReplacements(html, supportedProps.filter(prop => replacementsSupport[prop].html))
      edit = withDynamicReplacements(edit, supportedProps.filter(prop => replacementsSupport[prop].edit))
    }

    BlockRegistry.blocks[type] = {
      type,
      name,
      collection: 'core',
      edit,
      html,
      attributes,
      replacementsSupport,
      ...block,
    }
  }

  const createCache = () => ( {
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
  } )

  const dynamicContentCache = createCache()

  /**
   * Register a dynamic block
   *
   * @param type
   * @param name
   * @param attributes
   * @param ignoreForRefresh
   * @param parseContent
   * @param block
   */
  const registerDynamicBlock = (type, name, {
    ignoreForRefresh = [],
    parseContent = content => content,
    html = () => '',
    edit = () => '',
    ...block
  }) => {

    /**
     * Extracts attributes from the block given a key list
     *
     * @param block
     * @param keys
     * @return {{}}
     */
    const extractFromBlock = (block, keys) => {
      const props = {}
      keys.forEach(attr => {
        props[attr] = block[attr]
      })
      return props
    }

    /**
     * Generates a unique key based on the block attributes
     *
     * @param block
     * @return {string}
     */
    const generateCacheKey = (block) => {
      return base64_json_encode(extractFromBlock(block, Object.keys(block).filter(a => !ignoreForRefresh.includes(a))))
    }

    /**
     * Gets the dynamic content from the API
     *
     * @param block
     */
    const fetchDynamicContent = Groundhogg.functions.debounce(async (block) => {

      let cacheKey = generateCacheKey(block)

      // don't if already has data in the cache
      if (dynamicContentCache.has(cacheKey)) {
        return
      }

      setIsGeneratingHTML(true)
      let blockContent = renderBlocksHTML([block])
      setIsGeneratingHTML(false)

      let { content = '' } = await post(`${ EmailsStore.route }/block-preview/`, {
        html: blockContent,
      })

      // extract rendered content from within wrapper <td>.
      content = Div({}, content).querySelector(`td#b-${ block.id }`).innerHTML
      content = parseContent(content, block)
      dynamicContentCache.set(block.id, content)
      dynamicContentCache.set(cacheKey, content)
      morphContent()
    }, 1000)

    /**
     * Shows the preview of the dynamic content in the editor, or a placeholder animation
     *
     * @param block
     * @return {*}
     */
    const DynamicContent = (block) => {

      let cacheKey = generateCacheKey(block)

      if (dynamicContentCache.has(cacheKey)) {
        return parseContent(dynamicContentCache.get(cacheKey), block)
      }

      // fetch the dynamic content
      fetchDynamicContent(block)

      // use most recent cached value instead
      return Div({
        className: 'dynamic-content-loader',
      }, dynamicContentCache.get(block.id, null))
    }

    registerBlock(type, name, {
      ...block,
      edit: (block) => edit({
        ...block,
        DynamicContent: () => DynamicContent(block),
      }),
      html: (block) => html({
        ...block,
        DynamicContent: () => DynamicContent(block),
      }),
    })
  }

  const Control = ({
    label = '',
    stacked = false,
    tooltip = false,
    ...rest
  }, control) => {

    let labelProps = {
      className: 'control-label',
    }

    if (!Array.isArray(control) && control) {

      if (control.id) {
        labelProps.for = control.id
      }
      else {
        let inputElement = control.querySelector('select, input, textarea')

        if (inputElement && inputElement.id) {
          labelProps.for = inputElement.id
        }
      }
    }

    return Div({
        className: stacked ? 'display-flex column gap-10' : 'space-between',
        dataFor  : hasActiveBlock() ? getActiveBlock().id : null, ...rest,
      },
      [
        makeEl('label', labelProps, [
          label,
          tooltip ? Dashicon('editor-help', ToolTip(tooltip, 'top')) : null,
        ]),
        control,
      ])
  }

  const ControlGroup = (
    {
      id = '',
      name = '',
      closable = true,
    },
    controls) => {

    let panel = ''
    let panelId = id ? id : name.toLowerCase().replaceAll(' ', '-')

    let blockType

    if (hasActiveBlock()) {
      blockType = getActiveBlock().type
    }
    else if (State.editDefaults) {
      blockType = State.editDefaults
    }

    if (blockType) {
      panel = `${ blockType }-${ getBlockControlsTab() }-${ panelId }`

      // Check to see if the block has no open panels
      if (!Object.keys(getState().openPanels).
        some(panelId => panelId.startsWith(`${ blockType }-${ getBlockControlsTab() }`) && State.openPanels[panelId])) {
        // Open this one by default
        openPanel(panel)
      }

    }
    else {

      panel = `email-${ getEmailControlsTab() }-${ panelId }`

      // Check to see if the block has no open panels
      if (!Object.keys(getState().openPanels).
        some(panelId => panelId.startsWith(`email-${ getEmailControlsTab() }`) && State.openPanels[panelId])) {
        // Open this one by default
        openPanel(panel)
      }
    }

    return Div({
        className: `gh-panel control-group ${ isPanelOpen(panel) || !closable ? 'open' : 'closed' }`,

        dataFor: getActiveBlock().id,
        id     : panel,
      },
      [
        Div({
            className: 'gh-panel-header',
            onClick  : e => {
              if (closable) {
                openPanel(panel)
                morphControls()
              }
            },
          },
          [
            `<h2>${ name }</h2>`,
            closable ? Button({
              className: 'toggle-indicator',
            }) : null,
          ]),
        Div({
            className: 'inside controls display-flex column gap-10',
          },
          controls),

      ])
  }

  /**
   * The block toolbar
   *
   * @param block
   * @param duplicateBlock
   * @param deleteBlock
   * @return {*}
   * @constructor
   */
  const BlockToolbar = ({
    block,
    duplicateBlock,
    deleteBlock,
  }) => {

    return Div({
        className: 'block-toolbar',
      },
      [
        Span({ className: 'block-type' },
          BlockRegistry.get(block.type).name),
        Button({
            className: 'move-block',
          },
          icons.move),
        Div({
            className: 'block-buttons',
          },
          [
            Button({
                className: 'gh-button primary small icon duplicate-block',
                id       : `duplicate-${ block.id }`,
                onClick  : e => {
                  duplicateBlock(block.id)
                },
              },
              [
                Dashicon('admin-page'),
                ToolTip('Duplicate'),
              ]),
            Button({
                className: 'gh-button primary small delete-block',
                id       : `delete-${ block.id }`,
                onClick  : e => {
                  deleteBlock(block.id)
                },
              },
              [
                Dashicon('trash'),
                ToolTip('Delete'),
              ]),

          ]),

      ])
  }

  /**
   * The eidt view of a block
   *
   * @param block
   * @return {*}
   * @constructor
   */
  const BlockEdit = (block) => {
    return BlockRegistry.edit({
      ...block,
      updateBlock,
    })
  }

  const BlockStartComment = block => `<!-- ${ block.type }:${ block.id } ${ blockCommentProps(block) } -->`
  const BlockEndComment = block => `<!-- /${ block.type }:${ block.id } -->`

  /**
   * Removes parseable attributes from the json comment
   *
   * @param block
   * @return {string}
   */
  const blockCommentProps = block => {

    let props = {}

    for (let prop in block) {

      if ([
        'advancedStyle',
        'type',
        'id',
        'hide_on_mobile',
        'hide_on_desktop',
        'hide_in_browser',
      ].includes(prop)) {
        continue
      }

      if (BlockRegistry.get(block.type).attributes.hasOwnProperty(prop)) {
        continue
      }

      props[prop] = block[prop]
    }

    return JSON.stringify(props)
  }

  /**
   * The final HTML of the block as rendered for the email content
   *
   * @param block
   * @return {*}
   * @constructor
   */
  const BlockHTML = (block) => {

    let { advancedStyle = {} } = block
    let {
      backgroundColor = '',
      backgroundImage = '',
      width = '',
    } = advancedStyle

    if (!width) {
      width = getEmailWidth()
    }

    let html = BlockRegistry.html(block)

    if (isTopLevelBlock(block.id) && templateIs(FULL_WIDTH_CONTAINED)) {
      html = Table({
          cellspacing: '0',
          cellpadding: '0',
          role       : 'presentation',
          align      : 'center',
          className  : 'email-columns responsive',
        },
        Tr({
            className: 'email-columns-row',
          },
          Td({
              width,
              id       : `b-${ block.id }-inner`,
              className: 'email-columns-cell',
              style    : {
                width: `${ width }px`,

              },
            },
            html)))
    }

    let classes = []

    if (block.hide_on_mobile) {
      classes.push('hide-on-mobile')
    }

    if (block.hide_on_desktop) {
      classes.push('hide-on-desktop')
    }

    if (block.hide_in_browser) {
      classes.push('hide-in-browser')
    }

    // MSO bg image compat
    if (backgroundImage) {

      let el = document.getElementById(`b-${ block.id }`)

      if (el) {
        let computed = getComputedStyle(el)
        let vmlWidth = computed.width // contains px
        let vmlHeight = computed.height // contains px

        html = Fragment([
          `<!--[if gte mso 9]>
      <v:rect
        xmlns:v="urn:schemas-microsoft-com:vml"
        fill="true"
        stroke="false"
        style="width:${ vmlWidth }; height:${ vmlHeight };"
      >
        <v:fill
          type="frame"
          src="${ backgroundImage }"
          color="${ backgroundColor }"
        />
        <v:textbox inset="0,0,0,0">
      <![endif]-->`,
          html,
          `<!--[if gte mso 9]>
        </v:textbox>
      </v:rect>
      <![endif]-->`,
        ])
      }
    }

    return Tr({},
      [
        BlockStartComment(block),
        Td({
            id        : `b-${ block.id }`,
            className : classes.join(' '),
            style     : {
              ...AdvancedStyleControls.getInlineStyle(block),
              overflow: 'hidden',
            },
            bgcolor   : backgroundColor,
            background: backgroundImage,
            valign    : 'top',
          },

          html),
        BlockEndComment(block),

      ])
  }

  /**
   * The html of a block as shown in the editor
   *
   * @param block
   * @return {*}
   * @constructor
   */
  const EditBlockWrapper = (block) => {

    let {
      advancedStyle = {},
    } = block

    let {
      width = '',
      a,
    } = advancedStyle

    if (!width) {
      width = getEmailWidth()
    }

    let html = isActiveBlock(block.id) ? BlockEdit(block) : BlockRegistry.html(block)

    if (isTopLevelBlock(block.id) && templateIs(FULL_WIDTH_CONTAINED)) {
      html = Div({
          className: 'block-inner-content',
          style    : {
            width: `${ width }px`,

          },
        },
        html)
    }

    let classes = []

    if (block.hide_on_mobile) {
      classes.push('hide-on-mobile')
    }

    if (block.hide_on_desktop) {
      classes.push('hide-on-desktop')
    }

    if (block.hide_in_browser) {
      classes.push('hide-in-browser')
    }

    return Div({
        id: `edit-${ block.id }`,

        className: `builder-block ${ isActiveBlock(block.id) ? 'is-editing' : '' }`,
        dataId   : block.id,
        dataType : block.type,
        tabindex : 0,
        onClick  : e => {
          e.preventDefault()

          // do nothing
          if (State.editDefaults) {
            e.stopPropagation()
            return
          }

          // Using the toolbar
          if (clickedIn(e, '.delete-block, .duplicate-block')) {
            return
          }

          // It's the block we're editing
          if (isActiveBlock(block.id)) {
            return
          }

          // We clicked in a block INSIDE this one
          if (e.target.closest('.builder-block').dataset.id !== block.id) {
            return
          }

          setActiveBlock(block.id)
          e.stopPropagation()
        },
        onKeydown: e => {
          if (e.key === 'Delete' && isActiveBlock(block.id)) {
            deleteBlock(block.id)
          }

          if (e.key === 'd' && e.ctrlKey && isActiveBlock(block.id)) {
            duplicateBlock(block.id)
            e.preventDefault()
          }

          if (e.key === 'c' && e.ctrlKey && isActiveBlock(block.id)) {
            copyActiveBlock()
          }

          if (e.key === 'v' && e.ctrlKey && isActiveBlock(block.id)) {
            pasteBlock()
          }
        },
      },
      [
        Div({
            id       : `b-${ block.id }`,
            className: classes.join(' '),
            style    : {
              ...AdvancedStyleControls.getInlineStyle(block),

            },
          },
          [html]),
        block.filters_enabled ? Div({
            className: 'filters-enabled',
          },
          icons.eye) : null,
        // don't show toolbar if editing the block defaults
        State.editDefaults ? null : BlockToolbar({
          block,
          duplicateBlock,
          deleteBlock,
        }),

      ])
  }

  /**
   * Creates a block object
   *
   * @param type
   * @param props
   * @return {*&{advancedStyle: {},
   id: *, type}}
   */
  const createBlock = (type, props = {}) => {

    return {
      id           : uuid(),
      type,
      advancedStyle: {},
      ...BlockRegistry.defaults({ type }),
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

    let parentBlocks = blocks.filter(b => b.children && Array.isArray(b.children))

    for (let parentBlock of parentBlocks) {
      if (__findParent(blockId, parentBlock.children, parentBlock)) {
        return parentBlock
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

      if (block.children && Array.isArray(block.children)) {
        let found = __findBlock(blockId, block.children)
        if (found) {
          return found
        }
      }

      if (block.columns && Array.isArray(block.columns)) {
        try {
          for (let column of block.columns) {
            let found = __findBlock(blockId, column)
            if (found) {
              return found
            }
          }
        }
        catch (e) {
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

      if (block.id === parent && block.children && Array.isArray(block.children)) {
        return __insertBlock(newBlock, index, block.children)
      }

      if (block.id === parent && block.columns && Array.isArray(block.columns)) {
        return __insertBlock(newBlock, index, block.columns[column])
      }

      if (block.children && Array.isArray(block.children)) {
        let inserted = __insertBlock(newBlock, index, block.children, parent, column)
        if (inserted) {
          return true
        }
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

      if (block.children && Array.isArray(block.children)) {
        if (__insertAfter(newBlock, blockId, block.children)) {
          return true
        }
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
        blocks.splice(blocks.findIndex(b => b.id === blockId),
          1)
        return true
      }

      if (block.children && Array.isArray(block.children)) {
        if (__deleteBlock(blockId, block.children)) {
          return true
        }
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

    if (block.children && Array.isArray(block.children)) {
      block.children = block.children.map(_block => __replaceId(_block))
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

      if (block.children && Array.isArray(block.children)) {
        block.children = __updateBlocks(block.children, edited)
      }

      return block

    })

  }

  const isTopLevelBlock = (blockId) => {
    return getBlocks().some(block => block.id === blockId)
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
    insertBlock(newBlock, index, parent, column)
  }

  /**
   * Insert block at given index
   *
   * @param newBlock
   * @param index
   * @param parent
   * @param column
   */
  const insertBlock = (newBlock, index = 0, parent = false, column = 0) => {
    let tempBlocks = getBlocksCopy()

    __insertBlock(newBlock, index, tempBlocks, parent, column)

    setBlocks(tempBlocks)
    setActiveBlock(newBlock.id)
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
    let block = __findBlock(blockId, getBlocks())
    let newBlock = __replaceId(copyObject(block))

    insertBlockAfter(newBlock, blockId)

    setActiveBlock(newBlock.id)
  }

  /**
   * Insert a block after another one
   *
   * @param newBlock
   * @param after
   */
  const insertBlockAfter = (newBlock, after) => {
    let tempBlocks = getBlocksCopy()

    __insertAfter(newBlock, after, tempBlocks)

    setBlocks(tempBlocks)
    morphBlocks()
    updateStyles()
  }

  /**
   * Update the active block with new settings
   *
   * @param morphBlocks
   * @param morphControls
   * @param newSettings
   */
  const updateBlock = ({
    morphBlocks  : _morphBlocks = true,
    morphControls: _morphControls = false,
    ...newSettings
  }) => {

    let tempBlocks = getBlocksCopy()

    setBlocks(__updateBlocks(tempBlocks, {
      ...getActiveBlock(),
      ...newSettings,
    }))

    if (_morphBlocks) {
      morphBlocks()
    }

    if (_morphControls) {
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
			<div class="block gh-panel" data-id="${ blockId }">
				<div class="icon">
					${ BlockRegistry.blocks[blockType].svg }
				</div>
			</div>`
    }

    $(el).sortable({
      placeholder: 'block-placeholder',
      connectWith: '.sortable-blocks',
      handle     : '.move-block',
      helper     : sortableHelper,
      cancel     : '',
      tolerance  : 'pointer',
      start      : (e, ui) => {
        ui.helper.width(50)
        ui.helper.height(50)
      },
      cursorAt   : {
        left: 70,
        top : 5,
      },
      receive    : (e, ui) => {

        let $sortable = $(e.target)

        // moving block
        let parent = $sortable.is('.column,.children') ? $sortable.closest('.builder-block').data('id') : false
        let column = parseInt(e.target.dataset.col)

        console.log({
          parent,
          column,
        })

        // adding block
        if (ui.item.is('.new-block')) {

          let type = ui.item.data('type')
          let index = ui.helper.index()

          addBlock(type, index, parent, column)
          return
        }

        let blockId = ui.item.data('id')
        let index = ui.item.index()

        if (blockId) {
          moveBlock(blockId, index, parent, column)
        }
      },
      update     : (e, ui) => {

        let blockId = ui.item.data('id')
        let index = ui.item.index()

        let $sortable = $(e.target)

        // No longer in this sortable
        if (!$sortable.has(`#edit-${ blockId }`).length) {
          return
        }

        // moving block
        let parent = $sortable.is('.column,.children') ? $sortable.closest('.builder-block').data('id') : false
        let column = parseInt(e.target.dataset.col)

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
        id       : 'builder-content',
        className: 'sortable-blocks',
        onCreate : el => {
          makeSortable(el)
        },
      },
      getBlocks().filter(b => b.type).map(block => EditBlockWrapper(block)))

  }

  /**
   * Draggable component from the left hand side of the editor
   *
   * @constructor
   */
  const Block = ({
    type,
    name,
    svg,
  }) => {

    return Div({
        className : 'block-wrap',
        id        : `add-${ type }`,
        title     : name,
        onDblclick: e => {

          let newBlock = createBlock(type)

          if (hasActiveBlock()) {
            insertBlockAfter(newBlock, getActiveBlock().id)
            return
          }

          insertBlock(newBlock, getBlocks().length)

          setActiveBlock(newBlock.id)
          document.getElementById(`edit-${ newBlock.id }`).scrollIntoView(true)
        },
      },
      [// language=HTML
        `
            <div class="block new-block gh-panel" data-type="${ type }">
                <div class="icon">
                    ${ svg }
                </div>
            </div>
            <div class="block-name">${ name }</div>
        `,

      ])
  }

  /**
   * List of addable blocks to the editor
   *
   * @return {*[]}
   * @constructor
   */
  const Blocks = () => {
    return Div({
        id      : 'blocks-panel',
        onCreate: el => {
          $(el).find('.block').draggable({
            connectToSortable: '.sortable-blocks',
            helper           : 'clone',
            revert           : 'invalid',
            revertDuration   : 0,
            start            : (e, ui) => {
              ui.helper.addClass('dragging')
            },
          })
        },
      },
      Div({ className: 'block-grid' },
        Object.values(BlockRegistry.blocks).map(b => Block(b))))
  }

  const AdvancedBlockControls = () => {
    return Fragment([
      ControlGroup({
          name: 'Responsive',
        },
        [
          Control({
              label: 'Hide on mobile',
            },
            Toggle({
              id      : 'hide-on-mobile',
              checked : getActiveBlock().hide_on_mobile || false,
              onChange: e => updateBlock({ hide_on_mobile: e.target.checked }),

            })),
          Control({
              label: 'Hide on desktop',
            },
            Toggle({
              id      : 'hide-on-desktop',
              checked : getActiveBlock().hide_on_desktop || false,
              onChange: e => updateBlock({ hide_on_desktop: e.target.checked }),

            })),

        ]),
      ControlGroup({
          name: 'Conditional Visibility',
        },
        [
          Control({
              label: 'Enable contact filters',
            },
            Toggle({
              id      : 'toggle-filters',
              checked : getActiveBlock().filters_enabled || false,
              onChange: e => updateBlock({
                filters_enabled: e.target.checked,
                morphControls  : true,
              }),

            })),
          getActiveBlock().filters_enabled ? Div({
            id      : 'block-include-filters',
            onCreate: el => {
              setTimeout(() => {
                Groundhogg.filters.functions.createFilters('#block-include-filters', getActiveBlock().include_filters, (include_filters) => {
                  updateBlock({
                    include_filters,
                    morphBlocks: false,
                  })
                }).init()
              })
            },
          }) : null,
          getActiveBlock().filters_enabled ? Div({
            id      : 'block-exclude-filters',
            onCreate: el => {
              setTimeout(() => {
                Groundhogg.filters.functions.createFilters('#block-exclude-filters', getActiveBlock().exclude_filters, (exclude_filters) => {
                  updateBlock({
                    exclude_filters,
                    morphBlocks: false,
                  })
                }).init()
              })
            },
          }) : null,
          `<hr/>`,
          Control({
              label: 'Hide in browser view',
            },
            Toggle({
              id      : 'hide-in-browser',
              checked : getActiveBlock().hide_in_browser || false,
              onChange: e => updateBlock({ hide_in_browser: e.target.checked }),

            })),

        ]),
      ControlGroup({ name: 'Custom CSS' },
        [
          Textarea({
            id      : 'code-css-editor',
            value   : getActiveBlock().css || '',
            onCreate: el => {

              // Wait for add to dom
              setTimeout(() => {

                  let editor = wp.codeEditor.initialize('code-css-editor', {
                    ...wp.codeEditor.defaultSettings,
                    codemirror: {
                      ...wp.codeEditor.defaultSettings.codemirror,
                      mode   : 'text/css',
                      gutters: ['CodeMirror-lint-markers'],
                    },
                  }).codemirror

                  editor.on('change', instance => updateBlock({
                    css: instance.getValue(),

                  }))

                  editor.setSize(null, 300)
                },
                100)
            },
          }),

          `<p>Use the <code>selector</code> tag to target elements within the current block.</p>`,

          `<p>CSS entered here may not be universally supported by email clients. Check your <a href="https://www.campaignmonitor.com/css/" target="_blank">CSS compatibility</a>.</p>`,

        ]),

    ])
  }

  const BlockControls = ({
    updateBlock = () => {},
    getActiveBlock = () => {},
  }) => {
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

    return Fragment([controls])
  }

  const syncReplacementCodes = () => {
    let emailReplacements = getEmailMeta().replacements || {}

    // remove all replacements under this_email from the replacements object
    // re-add replacements direct from meta

    // Filter out keys in Groundhogg.replacements that start with "this_email"
    Groundhogg.replacements.codes = Object.entries(Groundhogg.replacements.codes).reduce((acc, [key, value]) => {
      if (value.group !== 'this_email') {
        acc[key] = value
      }
      return acc
    }, {})

    Groundhogg.replacements.groups.this_email = 'This Email'

    for (const [key, value] of Object.entries(emailReplacements)) {
      Groundhogg.replacements.codes[`__this_email_${ key }`] = {
        code  : `this_email.${ key }`,
        desc  : '',
        name  : key,
        group : 'this_email',
        insert: `{this_email.${ key }}`,
      }
    }
  }

  const AdvancedEmailControls = () => {

    let customHeaders = getEmailMeta().custom_headers || {}
    let emailReplacements = getEmailMeta().replacements || {}

    return Fragment([
      ControlGroup({
          name: 'Email Replacements',
        },
        [
          InputRepeater({
            id  : 'email-replacements-editor',
            rows: Object.keys(emailReplacements).
              map(k => ( [
                k,
                emailReplacements[k],
              ] )),

            cells   : [
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

              emailReplacements = {}

              rows.forEach(([key, val]) => emailReplacements[key] = val)

              setEmailMeta({
                replacements: emailReplacements,
              })

              syncReplacementCodes()
            },
          }),
          `<p>${ __('Define custom replacements that are only used in the context of this email. Usage is <code>{this_email.replacement_key}</code>.') }</p>`,
        ]),

      ControlGroup({
          id  : 'utm',
          name: 'UTM Parameters',
        },
        [
          `<p>${ __(
            'Automatically add UTM parameters to links that direct to your site. <a href="https://help.groundhogg.io/article/903-utm-parameters-in-emails">About UTM</a>.',
            'groundhogg') }</p>`,
          `<p>${ __('Replacements are currently <b>NOT</b> supported. Empty values are ignored.', 'groundhogg') }</p>`,
          Control({
              label  : 'Campaign Source',
              stacked: true,
            },
            Input({
              name   : 'utm_source',
              id     : 'utm-source',
              value  : getEmail().meta.utm_source ?? '',
              onInput: e => setEmailMeta({ utm_source: e.target.value }),

            })),
          Control({
              label  : 'Campaign Medium',
              stacked: true,
            },
            Input({
              name   : 'utm_medium',
              id     : 'utm-medium',
              value  : getEmail().meta.utm_medium ?? '',
              onInput: e => setEmailMeta({ utm_medium: e.target.value }),

            })),
          Control({
              label  : 'Campaign Name',
              stacked: true,
            },
            Input({
              name   : 'utm_campaign',
              id     : 'utm-campaign',
              value  : getEmail().meta.utm_campaign ?? '',
              onInput: e => setEmailMeta({ utm_campaign: e.target.value }),

            })),
          Control({
              label  : 'Campaign Term',
              stacked: true,
            },
            Input({
              name   : 'utm_term',
              id     : 'utm-term',
              value  : getEmail().meta.utm_term ?? '',
              onInput: e => setEmailMeta({ utm_term: e.target.value }),

            })),
          Control({
              label  : 'Campaign Content',
              stacked: true,
            },
            Input({
              name   : 'utm_content',
              id     : 'utm-content',
              value  : getEmail().meta.utm_content ?? '',
              onInput: e => setEmailMeta({ utm_content: e.target.value }),

            })),

        ]),
      ControlGroup({
          name: 'Custom Headers',
        },
        [
          InputRepeater({
            id  : 'custom-headers-editor',
            rows: Object.keys(customHeaders).
              map(k => ( [
                k,
                customHeaders[k],
              ] )),

            cells   : [
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

          `<p>${ __('You can define custom email headers and override existing ones.') }</p>`,

          `<p>${ __('For example <code>X-Custom-Header</code> <code>From</code> <code>Bcc</code> <code>Cc</code>') }</p>`,

        ]),
      ControlGroup({
          name: 'Custom CSS',
        },
        [
          Textarea({
            id      : 'code-css-editor',
            value   : getEmailMeta().template_css || '',
            onCreate: el => {

              // Wait for add to dom
              setTimeout(() => {

                  let editor = wp.codeEditor.initialize('code-css-editor', {
                    ...wp.codeEditor.defaultSettings,
                    codemirror: {
                      ...wp.codeEditor.defaultSettings.codemirror,
                      mode   : 'text/css',
                      gutters: ['CodeMirror-lint-markers'],
                    },
                  }).codemirror

                  editor.on('change', instance => {
                    setEmailMeta({
                      template_css: instance.getValue(),
                    })
                    updateStylesDebounced()
                  })

                  editor.setSize(null, 400)
                },
                100)
            },
          }),

          `<p>CSS entered here may not be universally supported by email clients. Check your <a href="https://www.campaignmonitor.com/css/" target="_blank">CSS compatibility</a>.</p>`,
        ]),
    ])
  }

  const TemplateControls = () => {

    let {
      alignment = 'left',
      direction = 'ltr',
      width = 600,
      backgroundColor = 'transparent',
      backgroundImage = '',
      backgroundPosition = '',
      backgroundSize = '',
      backgroundRepeat = '',
    } = getEmailMeta()

    return ControlGroup({
        name: 'Template Settings',
      },
      [
        Control({
            label: 'Template',
          },
          Select({
            id     : 'select-template',
            options: DesignTemplates.map(({
              id,
              name,
            }) => ( {
              value: id,
              text : name,
            } )),

            selected: getTemplate().id,
            onChange: e => {
              updateSettings({
                template: e.target.value,
                reRender: true,
              })
            },
          })),
        templateIs(FULL_WIDTH) ? null : Control({
            label: 'Email Width',
          },
          NumberControl({
            id     : 'email-width',
            name   : 'width',
            value  : width,
            step   : 10,
            unit   : 'px',
            onInput: e => {
              updateSettings({
                width   : parseInt(e.target.value),
                reRender: true,
              })
            },
          })),
        templateIs(BOXED) ? Control({
            label: 'Body Alignment',
          },
          AlignmentButtons({
            id        : 'template-align',
            alignment,
            onChange  : alignment => updateSettings({
              reRender: true,
              alignment,
            }),
            directions: [
              'left',
              'center',
            ],
          })) : null,
        Control({
            label: 'Text Direction',
          },
          AlignmentButtons({
            id        : 'text-direction',
            alignment : direction === 'rtl' ? 'right' : 'left',
            onChange  : direction => {
              updateSettings({
                reRender : true,
                direction: direction === 'right' ? 'rtl' : 'ltr',
              })
            },
            directions: [
              'left',
              'right',
            ],
          })),
        `<hr/>`,
        Control({
            label: 'Background Color',
          },
          ColorPicker({
            type    : 'text',
            id      : 'background-color',
            value   : backgroundColor,
            onChange: backgroundColor => {
              updateSettings({
                backgroundColor,
                reRender: true,
              })
            },
          })),
        `<hr/>`,
        BackgroundImageControls({
          id      : 'background-image',
          backgroundImage,
          backgroundPosition,
          backgroundSize,
          backgroundRepeat,
          onChange: (props) => {
            updateSettings({
              ...props,
              morphControls: true,
              reRender     : true,
            })
          },
        }),

      ])

  }

  const BasicEmailControls = () => {
    let {
      reply_to_override = '',
      browser_view = false,
    } = getEmailMeta()

    let {
      from_select = 0,
      message_type = 'marketing',
      is_template = 0,
    } = getEmailData()

    let fromOptions = [
      {
        id  : 0,
        text: __('Contact Owner'),
      },
      {
        id  : 'default',
        text: `${ Groundhogg.defaults.from_name } &lt;${ Groundhogg.defaults.from_email }&gt;`,

      },
      ...Groundhogg.filters.owners.map(({
        data,
        ID,
      }) => ( {
        id  : ID,
        text: `${ data.display_name } &lt;${ data.user_email }&gt;`,

      } )),

    ]

    let replyToOptions = [
      Groundhogg.defaults.from_email,
      ...Groundhogg.filters.owners.map(({ data }) => data.user_email),
    ].filter(onlyUnique)

    return Fragment([
      ControlGroup({
          name    : 'Email Settings',
          closable: false,
        },
        [
          isHTMLEditor() ? Control({
              label  : 'Subject line',
              stacked: true,
            },
            InputWithReplacements({
              type     : 'text',
              id       : 'subject-line',
              className: 'full-width',
              value    : getEmailData().subject,
              onInput  : e => setEmailData({ subject: e.target.value }),

            })) : null,
          Control({
              label  : 'Send this email from...',
              stacked: true,
            },
            ItemPicker({
              id              : 'from-user',
              multiple        : false,
              placeholder     : 'Search for a sender...',
              noneSelected    : 'Pick a sender...',
              isValidSelection: id => true,
              fetchOptions    : search => Promise.resolve(fromOptions.filter(item => item.text.includes(search))),

              selected: fromOptions.find(opt => from_select === opt.id),

              onChange: item => {

                if (item.id === 'default') {
                  setEmailData({
                    from_user  : 0,
                    from_select: item.id,
                  })

                  setEmailMeta({
                    use_default_from: true,
                  })
                }
                else {
                  setEmailData({
                    from_user  : item.id,
                    from_select: item.id,
                  })

                  setEmailMeta({
                    use_default_from: false,
                  })
                }

                History.addChange(getStateCopy()) // after change from

                updatePreview()
              },
            })),
          Control({
              label  : 'Send replies to...',
              stacked: true,
            },
            ItemPicker({
              id              : 'reply-to',
              multiple        : false,
              tags            : true,
              isValidSelection: id => isValidEmail(id),

              placeholder : 'Type an email address...',
              noneSelected: getEmail().context?.from_email,
              fetchOptions: search => Promise.resolve(replyToOptions.filter(item => item.includes(search)).map(em => ( {
                id  : em,
                text: em,
              } ))),

              selected: reply_to_override ? {
                id  : reply_to_override,
                text: reply_to_override,
              } : [],
              onChange: item => {
                setEmailMeta({ reply_to_override: item ? item.id : '' })
                History.addChange(getStateCopy()) // after change reply-to
              },
            })),
          Control({
              label  : 'Message type',
              tooltip: '<a href="https://help.groundhogg.io/article/523-what-is-transactional-email">Transactional</a> emails bypass contact marketability.<br><b>Marketing</b> emails respect contact marketability.',
            },
            Select({
              id      : 'message-type',
              options : {
                marketing    : 'Marketing',
                transactional: 'Transactional',
              },
              selected: message_type,
              onChange: e => {
                setEmailData({
                  message_type: e.target.value,
                })
                if (isBlockEditor()) {
                  // This may update the footer block
                  setBlocks(getBlocks())
                  morphBlocks()
                }
              },
            })),
          isBlockEditor() ? Control({
              label: 'Enable browser view',
            },
            Toggle({
              id      : 'enable-browser-view',
              checked : Boolean(browser_view),
              onChange: e => {
                setEmailMeta({
                  browser_view: e.target.checked,
                })
              },
            })) : null,
          Control({
              label: 'Show in templates when creating new emails',
            },
            Toggle({
              id      : 'save-as-template',
              checked : Boolean(is_template),
              onChange: e => {
                setEmailData({
                  is_template: e.target.checked,
                })
              },
            })),

        ]),
      isBlockEditor() ? TemplateControls() : null,
      ControlGroup({
          id  : 'campaigns',
          name: 'Campaigns',
        },
        [
          `<p>Use <b>campaigns</b> to organize your emails. Use terms like <code>Black Friday</code> or <code>Sales</code>.</p>`,
          ItemPicker({
            id          : 'pick-campaigns',
            noneSelected: 'Add a campaign...',
            tags        : true,
            selected    : getCampaigns().
              map(({
                ID,
                data,
              }) => ( {
                id  : ID,
                text: data.name,
              } )),

            fetchOptions: async (search) => {
              let campaigns = await CampaignsStore.fetchItems({
                search,
                limit: 20,
              })

              return campaigns.map(({
                ID,
                data,
              }) => ( {
                id  : ID,
                text: data.name,
              } ))
            },
            createOption: async (id) => {
              let campaign = await CampaignsStore.create({
                data: {
                  name: id,
                },
              })

              return {
                id  : campaign.ID,
                text: campaign.data.name,
              }
            },
            onChange    : items => setCampaigns(items.map(item => item.id)),

          }),

        ]),
      isHTMLEditor() ? ControlGroup({ name: 'HTML Editor Info' },
        HTMLEditorNotice()) : null,
    ])
  }

  const EditorControls = () => {

    const DisplayFont = font => Div({
        id       : `font-${ font.id }`,
        className: 'font space-between',
      },
      [
        Span({
            style: {
              ...fillFontStyle(font.style),
              margin: '0',
            },
          },
          font.name),
        Div({
            className: 'display-flex',
          },
          [
            Button({
                id       : `delete-${ font.id }`,
                className: 'gh-button danger text icon small',
                onClick  : e => {

                  dangerConfirmationModal({
                    //language=HTML
                    alert: `<p>${ __('You\'re about to delete a global font! This cannot be undone.') }</p>
                    <p>${ __('Any blocks currently using this font will inherit the font settings.') }</p>`,

                    confirmText: 'Delete',
                    onConfirm  : () => {
                      GlobalFonts.delete(font.id)
                      morphControls()
                      // close()
                    },
                  })
                },
              },
              Dashicon('trash')),
            Button({
                id       : `edit-${ font.id }`,
                className: 'gh-button secondary text small icon',
                onClick  : e => {
                  MiniModal({
                      selector: `#font-${ font.id }`,

                    },
                    ({ close }) => Div({
                        className: 'display-flex column gap-10',
                      },
                      [
                        Input({
                          className: 'full-width',
                          id       : `font-name`,

                          value   : font.name,
                          padding : 'Font name...',
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

                      ]))
                },
              },
              Dashicon('edit')),

          ]),

      ])

    return Fragment([// ControlGroup({ name: 'Buttons' }),

      ControlGroup({ name: 'Global Fonts' },
        [
          ...GlobalFonts.fonts.map(f => DisplayFont(f)),
          `<hr/>`,
          Button({
              id       : 'add-new-font',
              className: 'gh-button grey',
              onClick  : e => {
                let font = GlobalFonts.add()
                morphControls()
                document.getElementById(`edit-${ font.id }`).click()
              },
            },
            'Add Font'),

        ]),
      ControlGroup({
          id  : 'global-socials',
          name: 'Social Accounts',
        },
        [
          `<p>Choose your default/global social account links for the Socials block.</p>`,
          SocialLinksRepeater({
            socials : globalSocials,
            theme   : 'brand-boxed',
            onChange: socials => {
              globalSocials = socials
              morphBlocks()
            },
          }),
        ]),
      ControlGroup({ name: 'Color Palettes' },
        [
          `<p>Choose up to 8 colors for the color picker.</p>`,
          InputRepeater({
            id      : 'global-colors',
            rows    : colorPalette.map(color => [
              '',
              color,
            ]),
            maxRows : 8,
            cells   : [
              ({
                onChange,
                value,
                setValue,
                name,
                ...props
              }, row) => Div({
                style: {
                  width          : '33px',
                  flexShrink     : 0,
                  border         : 'solid white',
                  borderWidth    : '3px 0 3px 3px',
                  borderRadius   : '5px 0 0 5px',
                  backgroundColor: row[1],
                },
                ...props,
              }),
              ({
                setValue,
                ...props
              }) => Input({
                ...props,
                placeholder: '#FFFFFF',
              }),
            ],
            onChange: rows => {
              colorPalette = rows.map(r => r[1])
            },
          }),

        ]),
      ControlGroup({
          id  : 'block-defaults',
          name: 'Block Defaults',
        },
        [
          `<p>Change the default appearance of your blocks.</p>`,
          ...Object.keys(BlockRegistry.blocks).map(type => {

            const Block = BlockRegistry.get(type)

            return An({
              id     : `edit-defaults-${ type }`,
              onClick: e => {
                e.preventDefault()
                setState({
                  editDefaults    : type,
                  blockControlsTab: 'block',
                })
                morphBlockEditor()
              },
            }, Block.name)

          }),
        ]),
      Button({
        id       : 'save-editor-settings',
        className: `gh-button primary ${ State.savingEditorSettings ? 'loading-dots' : '' }`,
        disabled : State.savingEditorSettings,
        style    : {
          margin: '30px auto',
        },
        onClick  : e => {

          saveEditorSettings().then(r => {
            dialog({
              message: 'Settings saved!',
            })
          })

        },
      }, State.savingEditorSettings ? 'Saving' : 'Save Editor Settings'),
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

    return Fragment([controls])
  }

  const Navigation = () => {

    let nav

    if (hasActiveBlock()) {

      nav = Div({
          className: 'gh-button-nav',
        },
        [
          Button({
              className: `tab ${ getBlockControlsTab() === 'block' ? 'active' : 'inactive' }`,
              onClick  : e => {
                setBlockControlsTab('block')
                removeControls()
                morphControls()
              },
            },
            __('Block')),
          Button({
              className: `tab ${ getBlockControlsTab() === 'advanced' ? 'active' : 'inactive' }`,
              onClick  : e => {
                setBlockControlsTab('advanced')
                removeControls()
                morphControls()
              },
            },
            __('Advanced')),

        ])

    }
    else {

      nav = Div({
          className: 'gh-button-nav',
        },
        [
          Button({
              className: `tab ${ getEmailControlsTab() === 'email' ? 'active' : 'inactive' }`,
              onClick  : e => {
                setEmailControlsTab('email')
                morphControls()
              },
            },
            __('Settings')),
          Button({
              className: `tab ${ getEmailControlsTab() === 'advanced' ? 'active' : 'inactive' }`,
              onClick  : e => {
                setEmailControlsTab('advanced')
                morphControls()
              },
            },
            __('Advanced')),
          isBlockEditor() ? Button({
              className: `gh-button secondary text small ${ getEmailControlsTab() === 'editor' ? 'active' : 'inactive' }`,

              onClick: e => {
                setEmailControlsTab('editor')
                morphControls()
              },
            },
            [
              Dashicon('admin-settings'),
              ToolTip('Editor Controls', 'bottom-right'),
            ]) : null,
        ])

    }

    const breadcrumbs = [
      Span({
          onClick: e => {
            setActiveBlock(null)
          },
        },
        'Email'),
    ]

    if (hasActiveBlock()) {
      breadcrumbs.push(Span({
          className: 'slash',
        },
        '/'))
      breadcrumbs.push(Span({},
        BlockRegistry.get(getActiveBlock().type).name))
    }

    return Div({
        className: 'controls-nav',
      },
      [
        makeEl('h2', {
            className: 'breadcrumbs',
          },
          breadcrumbs),
        nav,
      ])

  }

  // todo working here
  const getDefaultBlock = () => {

    let type = State.editDefaults

    let block = {
      id: 'the-default-block',
      type,
      ...BlockRegistry.defaults({ type }),
    }

    switch (block.type) {
      case 'text':
        //language=HTML
        block.content = `<h1>Lorem ipsum dolor sit amet</h1>
        <h2>Consectetur adipiscing elit.</h2>
        <h3>Proin egestas dolor non nulla varius</h3>
        <p>Id fermentum ante euismod. Ut a sodales nisl, at maximus felis. Suspendisse potenti. Etiam fermentum <a href="#">magna nec diam lacinia</a>, ut
            volutpat mauris accumsan.</p>
        <p>Nunc id convallis magna. Ut eleifend sem aliquet, volutpat sapien quis, <a href="#">condimentum leo.</a></p>`
        break
      case 'columns':

        let spacerProps = {
          height       : 40,
          advancedStyle: {
            backgroundColor: '#e5e5e5',
          },
        }

        block.columns = [
          [createBlock('spacer', spacerProps)],
          [createBlock('spacer', spacerProps)],
          [createBlock('spacer', spacerProps)],
          [createBlock('spacer', spacerProps)],
          [createBlock('spacer', spacerProps)],
          [createBlock('spacer', spacerProps)],
        ]
        break
    }

    return block

  }

  function isObject (val) {
    return val && typeof val === 'object' && !Array.isArray(val)
  }

  function deepMerge (target, source) {
    const output = { ...target }
    if (isObject(target) && isObject(source)) {
      Object.keys(source).forEach(key => {
        const srcVal = source[key]
        const tgtVal = target[key]
        if (isObject(srcVal) && isObject(tgtVal)) {
          output[key] = deepMerge(tgtVal, srcVal)
        }
        else {
          output[key] = srcVal
        }
      })
    }
    return output
  }

  const updateBlockDefaults = ({
    morphBlocks  : _morphBlocks = true,
    morphControls: _morphControls = false,
    ...newSettings
  }) => {

    const type = State.editDefaults

    blockDefaults[type] = deepMerge(BlockRegistry.defaults(( { type } )), newSettings)

    morphContent()

    if (_morphControls) {
      morphControls()
    }
  }

  const ControlsPanel = () => {

    let controls

    if (State.editDefaults) {
      controls = Fragment([
        BlockControls({
          getActiveBlock: getDefaultBlock,
          updateBlock   : updateBlockDefaults,
        }),
        Button({
          id       : 'save-block-defaults',
          className: `gh-button primary ${ State.savingEditorSettings ? 'loading-dots' : '' }`,
          disabled : State.savingEditorSettings,
          style    : {
            margin: '30px auto 10px auto',
          },
          onClick  : e => {

            saveEditorSettings().then(r => {
              dialog({
                message: 'Block defaults saved!',
              })
            })

          },
        }, State.savingEditorSettings ? 'Saving' : `Save ${ BlockRegistry.get(State.editDefaults).name } Defaults`),
        An({
          style  : {
            textAlign: 'center',
          },
          onClick: e => {
            e.preventDefault()
            setState({
              editDefaults: false,
            })
          },
        }, '&larr; Back to email'),
      ])
    }
    else if (hasActiveBlock()) {
      controls = BlockControls({
        getActiveBlock,
        updateBlock,
      })
    }
    else {
      controls = EmailControls()
    }

    return Div({
        id       : 'controls-panel',
        className: 'display-flex column',
      },
      [
        Navigation(),
        controls,
      ])
  }

  const ContentEditor = () => {

    if (State.editDefaults) {

      return Div({
          id: 'content',
          // className: 'gh-panel',
        },
        [
          Div({
            id       : 'block-editor-content-wrap',
            className: 'gh-panel',
            style    : {
              padding: '30px',
            },
          }, Div({
            style: {
              maxWidth: '600px',
              width   : '100%',
            },
          }, EditBlockWrapper(getDefaultBlock()))),

        ])
    }

    return Div({
        id: 'content',
        // className: 'gh-panel',
        onClick: e => {

          if (State.editDefaults) {
            return
          }

          if (!clickedIn(e, '#builder-content') && !clickedIn(e, '.block-toolbar')) {
            setActiveBlock(null)
          }
        },
      },
      [// Toolbar
        BlockEditorToolbar(),
        // Subject & Preview
        Div({
          className: 'gh-panel',
        }, [
          Div({
              className: 'inside',
            },
            [
              Div({
                  className: 'inline-label',
                },
                [
                  `<label for="subject">${ __('Subject:', 'groundhogg') }</label>`,
                  InputWithReplacements({
                    id         : 'subject-line',
                    placeholder: 'Subject line...',
                    value      : getEmailData().subject,
                    onChange   : e => {
                      setEmailData({
                        subject: e.target.value,
                      })
                    },
                  }),

                ]),
              Div({
                  className: 'inline-label',
                },
                [
                  `<label for="preview-text">${ __('Preview:', 'groundhogg') }</label>`,
                  InputWithReplacements({
                    id         : 'preview-text',
                    name       : 'pre_header',
                    placeholder: 'Preview text...',
                    value      : getEmailData().pre_header,
                    onChange   : e => {
                      setEmailData({
                        pre_header: e.target.value,
                      })
                    },
                  }),

                ]),

            ]),
          // Block Editor
          Div({
              id       : 'block-editor-content-wrap',
              className: getState().responsiveDevice,
            },
            [
              getTemplate().html(BlockEditorContent()),
            ]),
          Div({
            className: 'wp-clearfix',
          }),
        ]),

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
        id       : 'admin-title-edit',
        value    : getEmailData().title,
        onCreate : el => {
          setTimeout(() => {
            el.focus()
          })
        },
        onInput  : e => {
          setEmailData({ title: e.target.value })
        },
        onBlur   : e => {
          stopEditing()
        },
        onKeydown: e => {
          if (e.key === 'Enter') {
            stopEditing()
          }
        },
      })
    }
    else {

      title = Fragment([
        __('Now editing '),
        Span({
            className: 'admin-title',
            id       : 'admin-title',
            onClick  : e => {
              startEditing()
            },
          },
          getEmailData().title || '_'.repeat(20)),

      ])
    }

    return Div({
        className: 'admin-title-wrap',
      },
      title)

  }

  const UndoRedo = () => {
    return Div({
        className: 'gh-input-group',
        id       : 'undo-and-redo',
      },
      [
        Button({
            id       : 'editor-undo',
            className: 'gh-button secondary text',
            disabled : !History.canUndo(),
            onClick  : e => {
              History.undo()
            },
          },
          Dashicon('undo')),
        Button({
            id       : 'editor-redo',
            className: 'gh-button secondary text',
            disabled : !History.canRedo(),
            onClick  : e => {
              History.redo()
            },
          },
          Dashicon('redo')),

      ])
  }

  const SubjectAndFromPreview = (close) => Div({
      className: 'from-preview display-flex gap-20 has-box-shadow',
    },
    [
      makeEl('img', {
        src      : getState().previewFromAvatar,
        className: 'from-avatar',
        height   : 40,
        width    : 40,
        style    : {
          borderRadius: '50%',
        },
      }),
      Div({
          className: 'subject-and-from',
        },
        [// Subject Line
          `<h2>${ getState().previewSubject }</h2>`,
          // From Name & Email
          `<span class="from-name">${ getState().previewFromName }</span> <span class="from-email">&lt;${ getState().previewFromEmail }&gt;</span>`,

        ]),
      Button({
          className: 'gh-button secondary icon text',
          style    : {
            marginLeft: 'auto',
          },
          onClick  : close,
        },
        Dashicon('no-alt')),

    ])

  const PreviewButtons = () => {

    return Div({
        className: `gh-input-group ${ getState().previewLoading ? 'flashing' : '' }`,

      },
      [
        Button({
            id       : 'preview-desktop',
            className: 'gh-button secondary icon',
            disabled : !Boolean(getState().preview),

            onClick: e => {

              let width = Math.min(1200, window.innerWidth * 0.8)
              let height = window.innerHeight * 0.85

              ModalFrame({},
                ({ close }) => Div({
                    className: 'preview desktop',
                    style    : {
                      width : `${ width }px`,
                      height: `${ height }px`,

                    },
                  },
                  [
                    SubjectAndFromPreview(close),
                    Iframe({
                        id: 'desktop-preview-iframe',
                        width,
                      },
                      getState().preview),

                  ]))
            },
          },
          [
            icons.desktop,
            ToolTip('Desktop Preview'),
          ]),
        Button({
            id       : 'preview-mobile',
            className: 'gh-button secondary icon',
            disabled : !Boolean(getState().preview),

            onClick: e => {

              let width = 412
              let height = Math.min(915, window.innerHeight * 0.85)

              ModalFrame({},
                ({ close }) => Div({
                    className: 'preview mobile',
                    style    : {
                      width : `${ width }px`,
                      height: `${ Math.min(915, window.innerHeight * 0.85) }px`,

                    },
                  },
                  [
                    SubjectAndFromPreview(close),
                    Iframe({
                        id: 'mobile-desktop-iframe',
                        width,
                      },
                      getState().preview),

                  ]))
            },
          },
          [
            icons.smartphone,
            ToolTip('Mobile Preview'),
          ]),
        Button({
            id       : 'preview-plain-text',
            className: 'gh-button secondary icon',
            disabled : !Boolean(getState().preview),

            onClick: e => {
              Modal({},
                makeEl('p', {
                    className: 'code',
                  },
                  getState().previewPlainText.replaceAll('\n', '<br/>')))
            },
          },
          [
            icons.text,
            ToolTip('Plain Text Preview'),
          ]),
        Button({
            id       : 'send-test-email',
            className: 'gh-button secondary icon',
            disabled : !Boolean(getState().preview),

            onClick: e => {

              const TestTypeExplanation = () => {

                let content

                switch (getState().testType) {
                  default:
                  case 'design':
                    // language=HTML
                    content = `<p>Design tests are <b>only</b> for verifying your design in the inbox.</p>
                    <p>This test will use data from <b>your contact record</b>, but send only to the email
                        addresses you selected.</p>
                    <p>
                        ✅ <b>[TEST]</b> in subject line.<br/>
                        ❌ Conditional blocks (all blocks will be visible).<br/>
                        ❌ Tracking links.<br/>
                        ❌ Unsubscribe links.<br/>
                        ❌ View in browser links.<br/>
                        ❌ Superlinks.<br/>
                        ❌ Open tracking.
                    </p>`
                    break
                  case 'functional':
                    // language=HTML
                    content = `<p>Functional tests simulate sending the email to a contact record from a
                        broadcast or a funnel.</p>
                    <p>The email will be sent to your selected email addresses, using information from the
                        associated contact records. If no contact exists, a new
                        one will be created.</p>
                    <p>When testing links, we recommend opening them in an incognito window or different
                        browser. See our <a target="_blank"
                                            href="https://help.groundhogg.io/article/888-testing-funnels-and-emails">recommended
                            testing procedures.</a></p>
                    <p>
                        ❌ <b>[TEST]</b> in subject line.<br/>
                        ✅ Conditional blocks.<br/>
                        ✅ Tracking links.<br/>
                        ✅ Unsubscribe links.<br/>
                        ✅ View in browser links.<br/>
                        ✅ Superlinks.<br/>
                        ✅ Open tracking.
                    </p>`
                    break
                }

                return Div({ id: 'test-type-explanation' },
                  content)
              }

              Modal({
                  onOpen: ({ modal }) => {
                    modal.querySelector('.gh-modal-dialog').style.width = '500px'
                  },
                  width : '500px',
                },
                ({
                  close,
                  morph,
                }) => Fragment([
                  `<h2>Send a test email!</h2>`,
                  ButtonToggle({
                    id      : 'test-type',
                    options : [
                      {
                        id  : 'design',
                        text: 'Design only',
                      },
                      {
                        id  : 'functional',
                        text: 'Functional',
                      },
                    ],
                    selected: getState().testType ?? 'design',
                    onChange: type => {
                      setState({
                        testType: type,
                      })
                      morph('#test-type-explanation', TestTypeExplanation(),
                        false)
                    },
                  }),
                  TestTypeExplanation(),
                  Div({
                      className: 'display-flex gap-10 align-bottom',
                    },
                    [
                      ItemPicker({
                        id              : 'test-email-addresses',
                        isValidSelection: isValidEmail,
                        noneSelected    : __('Type an email address...'),

                        selected: Groundhogg.user_test_emails.map(email => ( {
                          id  : email,
                          text: email,
                        } )),

                        fetchOptions: (search) => Promise.resolve(
                          Groundhogg.filters.owners.filter(user => user.data.user_email.includes(search)).map(user => ( {
                            id  : user.data.user_email,
                            text: user.data.user_email,
                          } ))),

                        onChange: items => {
                          Groundhogg.user_test_emails = items.map(({ id }) => id)
                        },
                        tags    : true,
                        style   : {
                          minWidth: '300px',
                          maxWidth: '500px',
                        },
                      }),
                      Button({
                          id       : 'send-test',
                          className: 'gh-button primary',
                          onClick  : async e => {

                            e.currentTarget.innerHTML = `<span class="gh-spinner"></span>`

                            if (!getEmail().ID) {
                              await saveEmail()
                            }

                            let endpoint = getEmail().ID ? `${ EmailsStore.route }/${ getEmailData().ID }/test` : `${ EmailsStore.route }/test`

                            post(endpoint, {
                              to: Groundhogg.user_test_emails.join(','),

                              data: getEmailData(),

                              meta: getEmailMeta(),

                              type: getState().testType,
                            }).then((r) => {
                              dialog({
                                message: __('Test sent!'),

                              })
                              close()
                            }).catch(err => {
                              dialog({
                                message: 'Oops, something went wrong. Try refreshing the page.',
                                type   : 'error',
                              })
                              morph()
                            })

                          },
                        },
                        'Send'),

                    ]),

                ]))
            },
          },
          [
            icons.email,
            ToolTip('Send a test email'),
          ]),

      ])
  }

  const PublishActions = () => {

    const isDraft = () => getEmailData().status === 'draft'
    const isReady = () => getEmailData().status === 'ready'

    return Div({
        className: 'publish-actions display-flex gap-10',
      },
      [
        isDraft() ? Button({
            id       : 'save-draft',
            className: 'gh-button secondary text',
            onClick  : e => {
              saveEmail()
            },
          },
          'Save draft') : Button({
            id       : 'switch-to-draft',
            className: 'gh-button danger text',
            onClick  : e => {
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
          },
          'Move to draft'),
        isDraft() ? Button({
            id       : 'publish-email',
            className: 'gh-button action',
            onClick  : e => {

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
          },
          'Publish') : Button({
            id       : 'update-email',
            className: 'gh-button primary',
            onClick  : e => {
              e.currentTarget.innerHTML = `<span class="gh-spinner"></span>`
              saveEmail().then(morphHeader)
            },
          },
          'Update'),

      ])

  }

  const Header = () => {

    return Div({
        id       : 'email-header',
        className: 'gh-header sticky',
      },
      [
        Groundhogg.isWhiteLabeled ? Span({ className: 'white-label-icon' }, Groundhogg.whiteLabelName) : icons.groundhogg,
        Title(),
        UndoRedo(),
        PreviewButtons(),
        PublishActions(),
        getEmail().ID ? Button({
            id       : 'email-more-menu',
            className: 'gh-button secondary text icon',
            onClick  : e => {
              moreMenu('#email-more-menu', [
                {
                  key     : 'broadcast',
                  text    : 'Broadcast',
                  onSelect: e => {

                    if (getEmailData().status !== 'ready') {
                      dialog({
                        message: 'This email must be published before it can be sent!',
                        type   : 'error',
                      })
                      return
                    }

                    Modal({},
                      () => Groundhogg.BroadcastScheduler({
                        object: EmailsStore.get(getEmailId()),

                      }))

                  },
                },
                {
                  key     : 'export',
                  text    : 'Export',
                  onSelect: e => {

                    window.open(adminPageURL('gh_emails', {
                      action  : 'export',
                      email   : getEmail().ID,
                      _wpnonce: Groundhogg.nonces._wpnonce,
                    }))

                  },
                },
                {
                  key     : 'html',
                  text    : 'Export as HTML',
                  onSelect: e => {

                    const blob = new Blob([getState().preview], { type: 'text/html' })

                    // Create a temporary <a> element
                    const link = document.createElement('a')
                    link.href = URL.createObjectURL(blob)

                    // Set the file name
                    link.download = getEmail().data.title + '.html'

                    // Append the link to the body and trigger the download
                    document.body.appendChild(link)
                    link.click()

                    // Clean up
                    document.body.removeChild(link)
                    URL.revokeObjectURL(link.href)

                  },
                },
                {
                  key     : 'embed',
                  text    : __('Embed', 'groundhogg'),
                  onSelect: e => {
                    Modal({},
                      Div({
                          className: 'display-flex column gap-10',
                        },
                        [
                          `<label for="embed-with-iframe"><b>${ __('Embed with iFrame', 'groundhogg') }</b></label>`,

                          Input({
                            id   : 'embed-with-iframe',
                            value: sprintf('[gh_email id=%d]', getEmailId()),

                            onFocus: e => e.target.select(),

                            readonly : true,
                            className: 'code',
                          }),

                          `<p style="margin-top: 0;">${ __(
                            'Formatting and styling will be <b>exactly</b> as shown. The content will not be indexed by search engines.',
                            'groundhogg') }</p>`,

                          `<label for="embed-with-html"><b>${ __('Embed with HTML', 'groundhogg') }</b></label>`,

                          Input({
                            id   : 'embed-with-html',
                            value: sprintf('[gh_email id=%d iframe=false]', getEmailId()),

                            onFocus: e => e.target.select(),

                            readonly : true,
                            className: 'code',
                          }),

                          `<p style="margin: 0;">${ __(
                            'Theme styles <b>may</b> impact the design and layout. The content will be indexed by search engines.',
                            'groundhogg') }</p>`,

                        ]))
                  },
                },
                isHTMLEditor() ? null : {
                  key     : 'gutenberg',
                  text    : __('Convert to post', 'groundhogg'),
                  onSelect: e => {

                    let content = renderBlocksGutenberg(getBlocks())

                    let { post_id = false } = getEmailMeta()

                    const createNewPost = () => {

                      const { close } = loadingModal()

                      return post(Groundhogg.api.routes.posts, {
                        status: 'draft',
                        title : getEmailData().title,
                        content,
                      }).then(post => {

                        post_id = post.id

                        // save the post ID to the email meta
                        setEmailMeta({
                          post_id,
                        })

                        // save
                        saveEmail().then(() => {
                          // redirect to the post edit screen
                          close()

                          window.open(`${ Groundhogg.url.admin }post.php?post=${ post_id }&action=edit`, '_blank')
                        })

                      })
                    }

                    // check if we've already generated a post, give option to update it
                    if (post_id) {

                      // todo check to make sure the post exists
                      confirmationModal({
                        alert           : `<p>A post was already generated from this email, would you like to overwrite it?</p>`,
                        confirmText     : __('Overwrite'),
                        onConfirm       : () => {

                          // if yes, update the post content
                          const { close } = loadingModal()

                          patch(Groundhogg.api.routes.posts + `/${ post_id }`, {
                            title: getEmailData().title,
                            content,
                          }).then(post => {
                            close()
                            window.open(`${ Groundhogg.url.admin }post.php?post=${ post_id }&action=edit`, '_blank')
                          })

                        },
                        closeText       : __('No, create a new post'),
                        cancelButtonType: 'secondary',
                        onCancel        : () => createNewPost(),
                      })

                      return
                    }

                    // create a new post via the API with the title of the email and the gutenberg content
                    createNewPost()
                  },
                },
                {
                  key     : 'delete',
                  text    : `<span class="gh-text danger">${ __('Delete') }</span>`,
                  onSelect: e => {

                    dangerConfirmationModal({
                      alert: `<p>Are you sure you want to delete this email?</p><p>This action cannot be undone.</p>`,

                      onConfirm: () => {
                        window.open(adminPageURL('gh_emails', {
                            action  : 'delete',
                            email   : getEmail().ID,
                            _wpnonce: Groundhogg.nonces._wpnonce,
                          }),
                          '_self')
                      },
                    })
                  },
                },
              ])
            },
          },
          icons.verticalDots) : null,
        CloseButton(),

      ])
  }

  const CloseButton = () => !isEmailEditorPage() ? Button({
      id       : 'close-editor',
      className: 'gh-button secondary text icon',
      onClick  : e => {

        if (getState().hasChanges) {
          dangerConfirmationModal({
            alert: `<p>You have unsaved changes! Are you sure you want to leave?</p>`,

            onConfirm  : onClose,
            confirmText: __('Discard changes', 'groundhogg'),

          })
          return
        }

        onClose()
      },
    },
    Dashicon('no-alt')) : null

  let codeMirror
  let codeMirrorSize = 100

  const resizeCodeMirror = () => {

    let newSize = document.getElementById('email-html-editor').getBoundingClientRect().height

    if (newSize === codeMirrorSize) {
      // Have to set size small first otherwise doesn't get smaller :/
      codeMirror.setSize(null, 100)
      newSize = document.getElementById('email-html-editor').getBoundingClientRect().height
    }

    codeMirror.setSize(null, newSize)
    codeMirrorSize = newSize
  }

  window.addEventListener('resize', e => {
    if (isHTMLEditor() && codeMirror) {
      resizeCodeMirror()
    }
  })

  // language=HTML
  const HTMLEditorNotice = () => `
      <p>
          ${ __('You can now import HTML email templates from third party platforms! Simply copy and paste the HTML code into the editor.',
                  'groundhogg-pro') }</p>
      <p><b>${ __('Here\'s what you need to know:', 'groundhogg-pro') }</b></p>
      <p>
          ${ __('The HTML you provide will not be validated or sanitized. So make sure you are using templates from trusted sources only.',
                  'groundhogg-pro') }
      </p>
      <p>${ __('You will need to manually add any information required for compliance:', 'groundhogg-pro') }</p>
      <ul class="styled">
          <li>${ __('Your physical business location.', 'groundhogg-pro') }
              <code>{business_address}</code></li>
          <li>${ __('Your business phone number.', 'groundhogg-pro') } <code>{business_phone}</code>
          </li>
          <li>${ __('Links to your terms of service and privacy policy.', 'groundhogg-pro') }</li>
          <li>${ __('The link to unsubscribe.', 'groundhogg-pro') } <code>{unsubscribe_link}</code>
          <li>${ __('The link to view in browser.', 'groundhogg-pro') } <code>{view_in_browser_link}</code>
          </li>
      </ul>
      <p>${ __('Any links will still be automatically be converted to tracking links.', 'groundhogg-pro') }</p>
      <p>${ __('Replacement codes and shortcodes will also still work as normal.', 'groundhogg-pro') }</p>`

  const HTMLEditor = () => {
    return Div({
        id: 'email-html-editor',
      },
      [

        // Code
        Textarea({
          id   : 'code-block-editor',
          value: html_beautify(getEmailData().content, { indent_with_tabs: true }),

          onCreate: el => {
            // Wait for add to dom
            setTimeout(() => {
                codeMirror = wp.codeEditor.initialize('code-block-editor', {
                  ...wp.codeEditor.defaultSettings,
                  codemirror: {
                    ...wp.codeEditor.defaultSettings.codemirror,
                    lineWrapping: false,
                  },
                }).codemirror

                codeMirror.on('change', instance => setHTML(instance.getValue()))
                // codeMirror.autoFormatRange()

                resizeCodeMirror()
              },
              100)
          },
        }),

        // Controls
        ControlsPanel(),

      ])
  }

  const EmailEditor = () => {

    if (getState().page === 'templates') {

      return Div({
          id: 'email-editor', // className: 'gh-fixed-ui',
        },
        [// Header
          Div({
              id       : 'email-header',
              className: 'gh-header is-sticky',
            },
            [
              Groundhogg.isWhiteLabeled ? Span() : icons.groundhogg,
              Div({
                  className: 'admin-title-wrap',
                  style    : {
                    marginRight: 'auto',
                  },
                },
                __('Select a template...')),
              InputGroup([// Select({
                //   name: 'template-campaign',
                //   id: 'template-campaign',
                //   options: {
                //     '': 'Filter by campaign',
                //   },
                // }),

                Input({
                  type       : 'search',
                  name       : 'template-search',
                  id         : 'template-search',
                  placeholder: __('Search for a template...'),

                  value  : getState().templateSearch,
                  onInput: e => {
                    setState({
                      templateSearch: e.target.value,
                    })
                    morphEmailEditor()
                  },
                }),

              ]),
              Div({
                  className: 'gh-input-group',
                },
                [
                  Button({
                      id       : 'import-html',
                      className: 'gh-button secondary',
                      onClick  : e => {
                        Modal({},
                          ({ close }) => Div({},
                            [
                              `<h2>Select a file to import</h2>`,
                              `<p>${ __('If you have an HTML file, you can upload it below 👇') }</p>`,
                              Input({
                                type    : 'file',
                                accept  : 'text/html',
                                id      : 'import-email-file',
                                onChange: e => {
                                  let file = e.target.files[0]

                                  let reader = new FileReader()
                                  reader.onload = function (e) {

                                    let contents = e.target.result

                                    if (!contents) {
                                      dialog({
                                        type   : 'error',
                                        message: __('Invalid import. Choose another file.'),

                                      })
                                      return
                                    }

                                    let title

                                    try {
                                      const parser = new DOMParser()
                                      const doc = parser.parseFromString(contents, 'text/html')

                                      // no title? invalid
                                      title = doc.head.querySelector('title')?.innerText
                                    }
                                    catch (e) {
                                      dialog({
                                        type   : 'error',
                                        message: __('Invalid import. Choose another file.'),

                                      })
                                      return
                                    }

                                    setEmailData({
                                      title,
                                      subject     : title,
                                      preview_text: '',
                                    })

                                    setState({
                                      page: 'html-editor',
                                    })

                                    setHTML(contents, true)

                                    renderEditor()
                                    close()
                                  }

                                  reader.readAsText(file)
                                },
                              }),

                            ]))

                      },
                    },
                    [
                      Dashicon('media-code'),
                      'Import HTML',
                    ]),
                  Button({
                      id       : 'import-email',
                      className: 'gh-button secondary',
                      onClick  : e => {
                        Modal({},
                          ({ close }) => Div({},
                            [
                              `<h2>Select a template to import</h2>`,

                              `<p>${ __('If you have a template JSON file, you can upload it below 👇') }</p>`,

                              Input({
                                type    : 'file',
                                accept  : 'application/json',
                                id      : 'import-email-file',
                                onChange: e => {
                                  let file = e.target.files[0]

                                  let reader = new FileReader()
                                  reader.onload = function (e) {

                                    let contents = e.target.result
                                    let email = JSON.parse(contents)

                                    if (!email) {
                                      dialog({
                                        type   : 'error',
                                        message: __('Invalid import. Choose another file.'),

                                      })
                                      return
                                    }

                                    if (!email.ID) {
                                      dialog({
                                        type   : 'error',
                                        message: __('Invalid import. Choose another file.'),

                                      })
                                      return
                                    }

                                    let {
                                      meta,
                                      data,
                                    } = email

                                    setEmailData({
                                      title       : data.title,
                                      subject     : data.subject,
                                      preview_text: data.preview_text,
                                      message_type: data.message_type,
                                      content     : data.content,
                                      plain_text  : data.plain_text,
                                    })
                                    setEmailMeta(meta)
                                    setState({ page: 'editor' })
                                    setBlocks(parseBlocksFromContent(data.content),
                                      false)
                                    renderEditor()
                                    close()
                                    TitlePrompt()
                                  }

                                  reader.readAsText(file)
                                },
                              }),

                            ]))
                      },
                    },
                    [
                      Dashicon('upload'),
                      'Import a template',
                    ]),
                  Button({
                      id       : 'start-from-scratch',
                      className: 'gh-button secondary',
                      onClick  : e => {
                        setState({
                          page: 'editor',
                        })
                        morphEmailEditor()
                        TitlePrompt()
                      },
                    },
                    'Start from scratch'),

                ]),
              CloseButton(),

            ]),
          // Templates
          TemplatePicker(),

        ])
    }

    if (isHTMLEditor()) {
      return Div({
          id       : 'email-editor',
          className: 'gh-fixed-ui',
        },
        [// header
          Header(),
          // HTML editor
          HTMLEditor(),

        ])
    }

    return Div({
        id       : 'email-editor',
        className: 'gh-fixed-ui',
      },
      [// header
        Header(),
        // Block editor
        BlockEditor(),

      ])
  }

  const TitlePrompt = () => MakeEl.ModalWithHeader({
    header: 'Name this email',
    onOpen: () => {
      let input = document.getElementById('prompt-email-title')
      input.focus()
      input.select()
    },
  }, ({ close }) => MakeEl.Form({
    onSubmit: e => {
      e.preventDefault()
      let fd = new FormData(e.currentTarget)
      let title = fd.get('email_title')
      setEmailData({
        title,
      })
      morphHeader()
      close()
    },
  }, [
    Div({
      className: 'display-flex gap-5',
    }, [
      Input({
        id   : 'prompt-email-title',
        name : 'email_title',
        value: getEmailData().title,
      }),
      Button({
        className: 'gh-button primary',
        type     : 'submit',
      }, 'Save'),
    ]),
  ]))

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
    // doc.body.style.padding = '20px'
    doc.head.querySelector('style#responsive')?.remove()

    return Div({
        className: 'template span-4',
      },
      Div({
          id          : `template-${ ID }`,
          className   : 'gh-panel',
          onClick     : e => {
            setEmailData({
              title       : data.title,
              from_user   : data.from_user,
              message_type: data.message_type,
              content     : data.content,
              plain_text  : data.plain_text || '',
            })
            setEmailMeta({
              ...meta,
            })
            setBlocks(parseBlocksFromContent(data.content))
            setState({ page: 'editor' })
            renderEditor()
            TitlePrompt()
          },
          onMouseenter: e => {
            const iframe = document.getElementById(`preview-${ ID }`)
            // *0.51 for zoom
            iframe.style.height = ( iframe.contentWindow.document.body.offsetHeight * 0.51 ) + 'px'
          },
          onMouseleave: e => {
            const iframe = document.getElementById(`preview-${ ID }`)
            iframe.style.height = 500 + 'px'
          },
        },
        [
          `<div class="overlay"></div>`,
          Iframe({
              id       : `preview-${ ID }`,
              className: 'template-preview',
              style    : {
                // zoom: '80%',
                // width: '100%',
                backgroundColor: '#fff',
              },
            },
            doc.documentElement.outerHTML),
          `<p>${ data.title }</p>`,

        ]))
  }

  const TemplatePicker = () => {

    const Grid = items => Div({
        id       : 'template-grid',
        className: 'display-grid gap-20',
      },
      items)

    let items

    if (getState().templates) {

      let {
        templates,
        templateSearch = '',
      } = getState()

      if (templateSearch) {
        templates = templates.filter(t => t.data.title.match(new RegExp(templateSearch, 'i')))
      }

      return Grid(templates.map(t => Template(t)))
    }

    // Has templates
    EmailsStore.fetchItems({
      is_template     : 1,
      status          : 'ready',
      remote_templates: true,
    }).then(items => {
      setState({
        templates: items,
      })
      morphEmailEditor()
    })

    items = [...Array(9).keys()].map(k => Div({
        className: 'gh-panel span-4',
      },
      [
        Div({
          className: 'skeleton-loading',
          style    : {
            height: '500px',
          },
        }),
        makeEl('p', {},
          Span({
              className: 'skeleton-loading',
              style    : { width: '100px' },
            },
            '&nbsp;'.repeat(30))),

      ]))

    return Grid(items)
  }

  /**
   * Block Inspector
   * Mobile Responsive View
   * Desktop Responsive View
   *
   * @return {*}
   * @constructor
   */
  const BlockEditorToolbar = () => {
    return Div({
        id: 'block-editor-toolbar',
      },
      Div({ className: 'display-flex column buttons' },
        [
          Button({
              className: `gh-button ${ getState().blockInspector ? 'primary' : 'secondary' } text icon`,

              id     : 'open-block-inspector',
              onClick: e => {
                setState({
                  blockInspector: !getState().blockInspector,
                })
                morphBlockEditor()
              },
            },
            [
              icons.inspect,
              ToolTip('Block Inspector', 'left'),
            ]),
          Button({
              className: `gh-button ${ getState().responsiveDevice === 'desktop' ? 'primary' : 'secondary' } text icon`,

              id     : 'set-responsive-desktop',
              onClick: e => {
                setState({
                  responsiveDevice: 'desktop',
                })
                morphBlockEditor()
              },
            },
            [
              icons.desktop,
              ToolTip('Desktop', 'left'),
            ]),
          Button({
              className: `gh-button ${ getState().responsiveDevice === 'mobile' ? 'primary' : 'secondary' } text icon`,

              id     : 'set-responsive-mobile',
              onClick: e => {
                setState({
                  responsiveDevice: 'mobile',
                })
                morphBlockEditor()
              },
            },
            [
              icons.smartphone,
              ToolTip('Mobile', 'left'),
            ]),

        ]))
  }

  /**
   * The main block editor component
   *
   * @return {*}
   * @constructor
   */
  const BlockEditor = () => {

    if (State.editDefaults) {
      return Div({
          id: 'email-block-editor',
        },
        [
          // Content
          ContentEditor(),
          ControlsPanel(),
        ])
    }

    return Div({
        id: 'email-block-editor',
      },
      [// Blocks
        Blocks(),
        // Content
        ContentEditor(),
        // Inspector
        getState().blockInspector ? BlockInspector() : null, // Controls
        ControlsPanel(),

      ])
  }

  const ResponsiveControls = () => {
    return ButtonToggle({
      id      : 'responsive-controls',
      options : [
        {
          id  : 'desktop',
          text: icons.desktop,
        },
        {
          id  : 'mobile',
          text: icons.smartphone,
        },
      ],
      selected: getState().responsiveDevice,
      onChange: value => {
        setState({
          responsiveDevice: value,
        })
        morphBlockEditor()
        morph('#responsive-controls', ResponsiveControls())
      },
    })
  }

  const ColumnGap = (gap = 10, height = 0, content = '') => gap > 0 ? Td({
    className: 'email-columns-cell gap',
    style    : {
      width    : `${ gap }px`,
      height   : `${ height || gap }px`,
      textAlign: 'center',
    },
    height   : height || gap,
    width    : gap,
    align    : 'center',
  }, !content ? '&nbsp;'.repeat(gap > 5 ? 3 : 1) : content) : null

  const Column = ({
    blocks = [],
    col,
    className,
    style = {},
    columnStyle = {},
    verticalAlign = 'top',
    ...props
  }) => {

    style = {
      ...style,
      verticalAlign,
    }

    let bgcolor = ''

    // Only apply column style to non-empty columns
    if (blocks.length) {

      let {
        padding = {},
        backgroundColor = '',
      } = columnStyle

      if (backgroundColor) {
        style.backgroundColor = backgroundColor
        bgcolor = backgroundColor
      }

      style.padding = extract4(padding)

      style = addBorderStyle(columnStyle, style)
    }

    if (isGeneratingHTML()) {
      return Td({
          className: `email-columns-cell ${ className }`,

          bgcolor,
          style,
          ...props,
        },
        [
          `<!-- START:COLUMN -->`,
          Table({
              className: `column ${ blocks.length ? '' : 'empty' }`,

              cellpadding: '0',
              cellspacing: '0',
              role       : 'presentation',
              width      : '100%',
            },
            blocks.map(b => BlockHTML(b))),
          `<!-- END:COLUMN -->`,

        ])
    }

    return Td({
        className: `email-columns-cell ${ className }`,

        bgcolor,
        style,
        ...props,
      },
      Div({
          dataCol  : col,
          className: `column sortable-blocks ${ blocks.length ? '' : 'empty' }`,

          onCreate: el => makeSortable(el),

        },
        blocks.map(b => EditBlockWrapper(b))))
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
      col   : i,
      verticalAlign,
      ...col,
      ...rest,
    }))

    return cols
  }

  let columnProps = {
    oneThird     : {
      className: 'one-third',
      width    : '33%',
      style    : {
        width: '33%',
      },
    },
    twoThirds    : {
      className: 'two-third',
      width    : '66%',
      style    : {
        width: '66%',
      },
    },
    oneHalf      : {
      className: 'one-half',
      width    : '50%',
      style    : {
        width: '50%',
      },
    },
    oneQuarter   : {
      className: 'one-fourth',
      width    : '25%',
      style    : {
        width: '25%',
      },
    },
    threeQuarters: {
      className: 'three-quarters',
      width    : '75%',
      style    : {
        width: '75%',
      },
    },
    full         : {
      className: 'full',
      width    : '100%',
      style    : {
        width: '100%',
      },
    },
    custom       : {
      className: 'custom-width',
      style    : {
        width: 'auto',
      },
    },
  }

  const makeColumns = (cols, props) => {
    return cols.reduce((cols, col, i) => cellReducer(cols, col, props, i), [])
  }

  const columnLayouts = {
    one_column          : [columnProps.full],
    two_columns         : [
      columnProps.oneHalf,
      columnProps.oneHalf,
    ],
    three_columns       : [
      columnProps.oneThird,
      columnProps.oneThird,
      columnProps.oneThird,
    ],
    four_columns        : [
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
    three_columns_left  : [
      columnProps.oneHalf,
      columnProps.oneQuarter,
      columnProps.oneQuarter,
    ],
    three_columns_right : [
      columnProps.oneQuarter,
      columnProps.oneQuarter,
      columnProps.oneHalf,
    ],
    two_columns_right   : [
      columnProps.oneThird,
      columnProps.twoThirds,
    ],
    two_columns_left    : [
      columnProps.twoThirds,
      columnProps.oneThird,
    ],
    custom_columns      : [
      {},
      {},
      {},
      {},
    ],
  }

  const getColumnCellsWithAttributesAndLayout = ({
    layout = 'two_columns',
    customColumns = [],
  }) => {
    let columnCells = columnLayouts[layout]
    let tableLayout = 'fixed'

    if (layout === 'custom_columns') {

      if (customColumns.every(width => !width)) {
        tableLayout = 'auto'
      }

      columnCells = customColumns.map(width => {

        let props = {
          className: `custom-width-${ width ? 'fixed' : 'auto' }`,
        }

        if (width) {
          width = `${ width }%`
          props.width = width
          props.style = {
            width,
          }
        }

        return props
      })
    }

    return [
      columnCells,
      tableLayout,
    ]
  }

  function adjustArrayToSum100 (arr) {
    // Calculate the initial sum of the array
    let sum = arr.reduce((a, b) => a + b, 0)

    // If the sum is less than or equal to 100, return the original array
    if (sum <= 100) {
      return arr
    }

    // Calculate the amount to subtract proportionally from each element
    const excess = sum - 100
    const factor = excess / sum

    // Create a new array with adjusted values
    let adjustedArray = arr.map(num => num - num * factor)

    // Floor all elements except the last one
    adjustedArray = adjustedArray.map((num, index) => {
      return index === adjustedArray.length - 1 ? num : Math.floor(num)
    })

    // Adjust the last element to ensure the sum is exactly 100
    let flooredSum = adjustedArray.slice(0, -1).reduce((a, b) => a + b, 0)
    adjustedArray[adjustedArray.length - 1] = 100 - flooredSum

    return adjustedArray
  }

// Example usage
  let arr = [
    30,
    40,
    50,
  ]
  let adjustedArr = adjustArrayToSum100(arr)

  // Register the columns block
  registerBlock('columns', 'Columns', {
    attributes: {
      layout       : el => el.querySelector(`table.email-columns:not(:has(#${ el.id }-inner))`).classList.item(1),
      verticalAlign: (el, { layout = '' }) => el.querySelector(`table.email-columns.${ layout } td.email-columns-cell:has(table.column)`).
        style.
        getPropertyValue('vertical-align'),
      responsive   : (el, { layout = '' }) => el.querySelector(`table.email-columns.${ layout }`).
        classList.
        contains('responsive'),
      columnStyle  : el => {

        let col = el.querySelector(`table.email-columns td.email-columns-cell:has(table.column)`)

        const attributeMap = {
          padding: style => parse4FromStyle('padding-%s', style),

          backgroundColor: (style, el) => el.getAttribute('bgcolor') ?? '',
        }

        let style = {}

        for (let attribute in attributeMap) {
          let value = attributeMap[attribute](col.style, col)
          if (value) {
            style[attribute] = value
          }
        }

        style = {
          ...style, ...parseBorderStyle(col.style),

        }

        return style
      },
      columns      : (el, { layout = '' }) => {

        let columns = []
        let table = el.querySelector(`table.email-columns.${ layout }`)
        let cells = table.querySelector('tr.email-columns-row').childNodes

        for (let cell of cells) {

          if (cell.nodeType !== Node.ELEMENT_NODE || cell.classList.contains('gap')) {
            continue
          }

          columns.push(parseBlocksFromTable(cell.querySelector('table.column')))
        }

        // Polyfill columns to avoid missing blocks if layout changes
        while (columns.length < 4) {
          columns.push([])
        }

        return columns
      },
      customColumns: (el, { layout = '' }) => {
        let columns = []
        let table = el.querySelector(`table.email-columns.${ layout }`)
        let cells = table.querySelector('tr.email-columns-row').childNodes

        for (let cell of cells) {

          if (cell.nodeType !== Node.ELEMENT_NODE || cell.classList.contains('gap')) {
            continue
          }

          columns.push(parseInt(cell.getAttribute('width')))
        }

        return adjustArrayToSum100(columns)
      },
    },
//language=HTML
    svg: `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.667 426.667"
             style="enable-background:new 0 0 426.667 426.667" xml:space="preserve"><path 
        fill="currentColor"
        d="M384 21.333h-42.667c-23.552 0-42.667 19.157-42.667 42.667v298.667c0 23.531 19.115 42.667 42.667 42.667H384c23.552 0 42.667-19.136 42.667-42.667V64c0-23.509-19.115-42.667-42.667-42.667zM234.667 21.333H192c-23.552 0-42.667 19.157-42.667 42.667v298.667c0 23.531 19.115 42.667 42.667 42.667h42.667c23.552 0 42.667-19.136 42.667-42.667V64c-.001-23.509-19.115-42.667-42.667-42.667zM85.333 21.333H42.667C19.136 21.333 0 40.491 0 64v298.667c0 23.531 19.136 42.667 42.667 42.667h42.667c23.531 0 42.667-19.136 42.667-42.667V64C128 40.491 108.864 21.333 85.333 21.333z"/></svg>`,

    controls : ({
      layout = 'two_columns',
      gap = 0,
      verticalAlign = 'top',
      columnStyle = {},
      updateBlock,
      responsive = true,
      customColumns = [],
    }) => {

      const LayoutChoice = l => Button({
        className: `layout-choice ${ l } ${ layout === l ? 'selected' : '' }`,

        dataLayout: l,
        id        : `layout-${ l }`,

        onClick: e => {
          updateBlock({
            layout       : l,
            morphControls: true,
          })
        },
      }, columnLayouts[l].map((col, i) => `<span class="col col-${ i + 1 }"></span>`))

      return [
        ControlGroup({
            name: 'Layout',
          },
          [
            Div({
                className: 'layouts',
              },
              [...Object.keys(columnLayouts).map(k => LayoutChoice(k))]),
            layout === 'custom_columns' ? `<hr/>` : null,
            layout === 'custom_columns' ? Control({
                label  : 'Custom column widths',
                stacked: true,
              },
              InputRepeater({
                id      : 'custom-columns-editor',
                rows    : customColumns?.map(width => ( [width] )) ?? [],
                cells   : [
                  props => Input({
                    type       : 'number',
                    placeholder: 'auto',
                    min        : 0,
                    max        : 100,
                    ...props,
                  }),
                ],
                onChange: rows => {
                  updateBlock({
                    customColumns: adjustArrayToSum100(rows.map(([width]) => parseInt(width))),
                    columns      : [
                      ...getActiveBlock().columns,
                      rows.length > getActiveBlock().columns.length ? Array(rows.length - getActiveBlock().columns.length).fill([]) : [],
                    ],
                  })
                },
              })) : null,
            `<hr/>`,
            Control({
                label: 'Gap',
              },
              NumberControl({
                id      : 'column-gap',
                step    : 5,
                value   : gap,
                unit    : 'px',
                onChange: e => updateBlock({ gap: e.target.value }),

              })),
            Control({
                label: 'Vertical Alignment',
              },
              Select({
                id      : 'column-vertical-alignment',
                selected: verticalAlign,
                options : {
                  top   : 'Top',
                  middle: 'Middle',
                  bottom: 'Bottom',
                },
                onChange: e => updateBlock({ verticalAlign: e.target.value }),
              })),
            Control({ label: 'Responsive' },
              Toggle({
                id      : 'columns-responsive',
                checked : responsive,
                onChange: e => updateBlock({ responsive: e.target.checked }),
                value   : 1,
              })),

          ]),
        // Card style
        ControlGroup({
            id  : 'columns-style',
            name: 'Column Style',
          },
          [
            // Padding
            Control({
                label  : 'Column Padding',
                stacked: true,
              },
              TopRightBottomLeft({
                id      : 'content-padding',
                values  : columnStyle.padding,
                onChange: padding => {
                  updateBlock({
                    columnStyle  : {
                      ...getActiveBlock().columnStyle,
                      padding,
                    },
                    morphControls: true,
                  })
                },
              })),
            makeEl('hr'),
            // Background Color
            Control({
                label: 'Background Color',
              },
              ColorPicker({
                type    : 'text',
                id      : 'background-color',
                value   : columnStyle.backgroundColor,
                onChange: backgroundColor => updateBlock({
                  columnStyle  : {
                    ...getActiveBlock().columnStyle,
                    backgroundColor,
                  },
                  morphControls: true,
                }),

              })),
            makeEl('hr'),
            // Border
            Control({
                stacked: true,
                label  : 'Border',
              },
              BorderControls({
                ...columnStyle,
                onChange: newStyle => updateBlock({
                  columnStyle  : {
                    ...getActiveBlock().columnStyle, ...newStyle,
                  },
                  morphControls: true,
                }),
              })),
          ]),
      ]
    },
    html     : ({
      columns,
      layout = 'two_columns',
      gap = 0,
      verticalAlign = 'top',
      responsive = true,
      columnStyle = {},
      customColumns = [],
    }) => {

      let [columnCells, tableLayout] = getColumnCellsWithAttributesAndLayout({
        customColumns,
        layout,
      })

      return Table({
          className  : `email-columns ${ layout } ${ responsive ? 'responsive' : '' }`,
          cellspacing: '0',
          cellpadding: '0',
          width      : '100%',
          role       : 'presentation',
          style      : {
            // borderCollapse: 'collapse',
            tableLayout,
            borderSpacing: 0,
            width        : '100%',
          },
        },
        Tr({ className: 'email-columns-row' },
          makeColumns(columnCells, {
            columns,
            gap,
            verticalAlign,
            columnStyle,
          })))
    },
    plainText: ({ columns }) => {
      return columns.map(column => renderBlocksPlainText(column)).join('\n\n')
    },
    gutenberg: ({
      columns,
      customColumns,
      layout,
    }) => {

      let [columnCells] = getColumnCellsWithAttributesAndLayout({
        customColumns,
        layout,
      })

      return Div({}, [
        `<!-- wp:columns -->`,
        Div({
          className: 'wp-block-columns',
        }, columnCells.map((cell, i) => Fragment([
          `<!-- wp:column {"width":"${ cell.width }"} -->`,
          Div({
            className: 'wp-block-column',
            style    : {
              flexBasis: cell.width,
            },
          }, renderBlocksGutenberg(columns[i] || [])),
          `<!-- /wp:column -->`,
        ]))),
        `<!-- /wp:columns -->`,
      ]).innerHTML

    },
    css      : ({
      selector,
      id,
      columns,
      gap = 10,
    }) => {
      //language=CSS
      return `
          ${ columns.map(col => col.length ? renderBlocksCSS(col) : '').join('') }
      `
    },
    defaults : {
      layout       : 'two_columns',
      columns      : [
        [],
        [],
        [],
        [],
      ],
      customColumns: [
        '',
        '',
        '',
      ],
      gap          : 10,
      responsive   : true,
    },
  })

  const getTemplateMceCSS = () => {
    let bodyStyle = {}

    let {
      backgroundColor,
    } = getEmailMeta()

    if (backgroundColor) {
      bodyStyle.backgroundColor = backgroundColor
    }

    // language=CSS
    return `body {
        ${ objectToStyle(bodyStyle) }
    }`
  }

  const tinyMceCSS = () => {

    let {
      p,
      h1,
      h2,
      h3,
      a,
      css = '',
    } = getActiveBlock()

    let bodyStyle = {}

    let backgroundColor = getBlockBackgroundColor(getActiveBlock().id)

    if (backgroundColor) {
      bodyStyle.backgroundColor = backgroundColor
    }

    // For the a tags
    let {
      fontSize,
      lineHeight,
      fontWeight,
      fontStyle: aFontStyle,
      ...aStyle
    } = fillFontStyle(a)

    // language=CSS
    return `
        ${ getTemplateMceCSS() }
        body {
            ${ objectToStyle(bodyStyle) }
        }

        p, li {
            ${ fontStyle(p) }
        }

        a {
            ${ objectToStyle(aStyle) }
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
            ${ fontStyle(h1) }
        }

        h2 {
            ${ fontStyle(h2) }
        }

        h3 {
            ${ fontStyle(h3) }
        }
    `
  }

  const fontDefaults = style => ( {
    lineHeight   : '1.4',
    fontFamily   : 'system-ui, sans-serif',
    fontWeight   : 'normal', // color: '#1a1a1a',
    fontSize     : 13,
    fontStyle    : 'normal',
    textTransform: 'none', ...style,
  } )

  /**
   * Given a font style object, convert it to CSS.
   *
   * @param style
   * @returns {string|*}
   */
  const fontStyle = style => {
    return objectToStyle(fillFontStyle(style))
  }

  /**
   * Given a font object, make sure that defaults are set or that it's correctly using a global font
   */
  const fillFontStyle = ({
    use = 'custom',
    color = '',
    fontFamily = '',
    fontSize = 16,
    ...style
  }) => {

    // global font
    if (GlobalFonts.has(use)) {
      let font = GlobalFonts.get(use).style
      if (font) {
        return fillFontStyle({
          ...font,
          color,
          fontFamily,
        })
      }
    }

    return {
      ...fontDefaults(style),
      color,
      fontSize  : `${ fontSize }px`,
      fontFamily: removeQuotes(fontFamily),

    }
  }

  /**
   * Font controls
   */
  const FontControls = (
    style = {},
    onChange = style => {
    },
    supports = {}) => {

    supports = {
      fontSize     : true,
      fontFamily   : true,
      fontWeight   : true,
      lineHeight   : true,
      fontStyle    : true,
      textTransform: true, ...supports,
    }

    let {
      fontSize = '14',
      fontFamily = '',
      fontWeight = 'normal',
      fontStyle = 'normal',
      textTransform = 'none',
      lineHeight = '1.4',
    } = fontDefaults(style)

    const fontDisplay = font => Span({ style: { fontFamily: font } },
      fontFamilies[font])

    fontFamily = removeQuotes(fontFamily)

    return Div({
        className: 'font-controls display-flex column gap-10',
      },
      [
        !supports.fontFamily ? null : Control({
            label  : __('Font Family', 'groundhogg'),
            stacked: true,
          },
          ItemPicker({
            id: `font-family`,

            multiple    : false,
            selected    : {
              id  : fontFamily,
              text: fontDisplay(fontFamily),
            },
            fetchOptions: search => Promise.resolve(Object.keys(fontFamilies).
              filter(font => fontFamilies[font].toLowerCase().includes(search.toLowerCase())).
              map(font => ( {
                id  : font,
                text: fontDisplay(font),
              } ))),

            onChange: item => {
              onChange({ fontFamily: item.id })
              // document.getElementById('font-family').click()
            },
          })),
        !supports.fontSize ? null : Control({ label: __('Font Size', 'groundhogg') },
          NumberControl({
            id: `font-size`,

            name: `font_size`,

            className: 'font-control control-input',
            unit     : 'px',
            min      : 0,
            value    : fontSize,
            onInput  : e => onChange({ fontSize: e.target.value }),

          })),
        !supports.lineHeight ? null : Control({ label: __('Line Height', 'groundhogg') },
          NumberControl({
            id: `line-height`,

            name: `line_height`,

            className: 'font-control control-input',
            value    : lineHeight,
            step     : 0.1,
            max      : 10,
            min      : 0,
            onInput  : e => onChange({ lineHeight: e.target.value }),

          })),
        !supports.fontWeight ? null : Control({ label: __('Font Weight', 'groundhogg') },
          Select({
            id: `font-weight`,

            name: `font_weight`,

            className: 'font-control control-input',
            selected : fontWeight,
            options  : fontWeights.map(i => ( {
              value: i,
              text : i,
            } )),

            onChange: e => onChange({ fontWeight: e.target.value }),

          })),
        !supports.fontStyle ? null : Control({ label: __('Font Style', 'groundhogg') },
          Select({
            id: `font-style`,

            name: `font_style`,

            className: 'font-control control-input',
            selected : fontStyle,
            options  : {
              normal : 'Normal',
              italic : 'Italic',
              oblique: 'Oblique',
            },
            onChange : e => onChange({ fontStyle: e.target.value }),

          })),
        !supports.textTransform ? null : Control({ label: __('Transform', 'groundhogg') },
          Select({
            id: `text-transform`,

            name: `text_transform`,

            className: 'font-control control-input',
            selected : textTransform,
            options  : {
              none      : 'None',
              capitalize: 'Capitalize',
              uppercase : 'Uppercase',
              lowercase : 'Lowercase',
            },
            onChange : e => onChange({ textTransform: e.target.value }),

          })),

      ])
  }

  /**
   * Create a font control group panel
   */
  const TagFontControlGroup = (name, tag = '', style = {},
    updateBlock = () => {
    },
    supports = {}) => {

    let {
      use = 'global',
      color = '',
    } = style

    const updateStyle = (newStyle) => {
      style = {
        ...getActiveBlock()[tag], ...newStyle,
      }

      updateBlock({
        [tag]: style,
      })
    }

    const DisplayFont = (font, selected, close) => {
      return Div({
          className: `select-font ${ selected ? 'selected' : '' }`,
          id       : font.id,
          onClick  : e => {
            use = font.id
            updateStyle({
              use, ...font.style,
            })
            morphControls()
            close()
          },
        },
        Span({
            style: {
              ...fillFontStyle(font.style),
              // margin: 0
            },
          },
          font.name))
    }

    return ControlGroup({
        name,
        id: tag,
      },
      [

        Control({
            label: 'Font',
          },
          Div({
              className: 'gh-input-group',
            },
            [
              Button({
                  id       : `${ tag }-use-global`,
                  className: `gh-button small ${ GlobalFonts.has(use) ? 'primary' : 'secondary' }`,
                  onClick  : e => {
                    MiniModal({
                        selector     : `#${ tag }-use-global`,
                        dialogClasses: 'no-padding',
                      },
                      ({ close }) => Div({
                          className: 'display-flex column global-font-select',
                        },
                        [...GlobalFonts.fonts.map(font => DisplayFont(font, use === font.id, close))]))
                  },
                },
                Dashicon('admin-site')),
              Button({
                  id       : `${ tag }-use-custom`,
                  className: `gh-button small ${ !GlobalFonts.has(use) ? 'primary' : 'secondary' }`,
                  onClick  : e => {

                    updateStyle({
                      use: 'custom',
                    })

                    morphControls()

                    MiniModal({
                        dialogClasses: 'no-padding',
                        selector     : `#${ tag }-use-custom`,
                        // onClose: () => morphControls(),

                      },
                      Div({
                          className: 'display-flex column gap-10',
                        },
                        [
                          FontControls(style, style => {
                              updateStyle(style)
                            },
                            supports),
                        ]))
                  },
                },
                Dashicon('edit')),

            ])),
        Control({ label: __('Color', 'groundhogg') },
          ColorPicker({
            id      : `${ tag }-font-color`,
            value   : color,
            onChange: color => updateStyle({ color }),

          })),

      ])
  }

  /**
   * Add inline styles to a specific tag within a dom document
   */
  const inlineStyle = (doc, selector, style = {},
    inherit = true) => {
    if (inherit) {
      style = fillFontStyle(style)
    }
    doc.querySelectorAll(selector).forEach(el => {
      for (let attr in style) {
        el.style[attr] = style[attr]
      }
    })
  }

  /**
   * Add inline styles to all the nodes in any given text content
   */
  const textContent = ({
    content,
    p,
    h1,
    h2,
    h3,
    a,
  }) => {

    if (!content) {
      return Div({
        className: 'text-content-wrap',
      }, '')
    }

    const parser = new DOMParser()
    const doc = parser.parseFromString(content, 'text/html')

    inlineStyle(doc, 'p', {
      ...p,
      margin: '1em 0',
    })
    inlineStyle(doc, 'li', p)
    inlineStyle(doc, 'h1', h1)
    inlineStyle(doc, 'h2', h2)
    inlineStyle(doc, 'h3', h3)
    inlineStyle(doc, 'a', a, false)

    inlineStyle(doc, 'b,strong', {
        fontWeight: 'bold',
      },
      false)

    inlineStyle(doc, 'ul', {
        listStyle  : 'disc',
        paddingLeft: '30px',
      },
      false)

    inlineStyle(doc, 'ol', {
        paddingLeft: '30px',
      },
      false)

    if (doc.body.firstElementChild) {
      doc.body.firstElementChild.style.marginTop = 0
    }

    if (doc.body.lastElementChild) {
      doc.body.lastElementChild.style.marginBottom = 0
    }

    return Div({
        className: 'text-content-wrap',
      },
      doc.body.childNodes)
  }

  const LinkPicker = props => Autocomplete({
    ...props,
    fetchResults: async search => {
      let pages = await ajax({
        action             : 'wp-link-ajax',
        _ajax_linking_nonce: groundhogg_nonces._ajax_linking_nonce,
        term               : search,
      })

      return pages.map(({
        title,
        permalink,
      }) => ( {
        id  : permalink,
        text: title,
      } ))
    },
  })

  // Register the text block
  registerBlock('text', 'Text', {
    attributes: {
      content: el => el.querySelector('.text-content-wrap').innerHTML,
    },
    //language=HTML
    svg: `
        <svg xmlns="http://www.w3.org/2000/svg" style="enable-background:new 0 0 977.7 977.7" xml:space="preserve"
             viewBox="0 0 977.7 977.7">
        <path fill="currentColor"
              d="M770.7 930.6v-35.301c0-23.398-18-42.898-41.3-44.799-17.9-1.5-35.8-3.1-53.7-5-34.5-3.6-72.5-7.4-72.5-50.301L603 131.7c136-2 210.5 76.7 250 193.2 6.3 18.7 23.8 31.3 43.5 31.3h36.2c24.9 0 45-20.1 45-45V47.1c0-24.9-20.1-45-45-45H45c-24.9 0-45 20.1-45 45v264.1c0 24.9 20.1 45 45 45h36.2c19.7 0 37.2-12.6 43.5-31.3 39.4-116.5 114-195.2 250-193.2l-.3 663.5c0 42.9-38 46.701-72.5 50.301-17.9 1.9-35.8 3.5-53.7 5-23.3 1.9-41.3 21.4-41.3 44.799v35.3c0 24.9 20.1 45 45 45h473.8c24.8 0 45-20.199 45-45z"/></svg>`,

    controls : ({
      p = {},
      a = {},
      h1 = {},
      h2 = {},
      h3 = {},
      content = '',
      updateBlock,
      curBlock,
    }) => {

      // If the element does not exist, this block was just clicked
      if (!document.getElementById('text-block-h1')) {
        const parser = new DOMParser()
        const doc = parser.parseFromString(content, 'text/html')
        let firstEl = doc.body.firstElementChild

        if (firstEl) {
          let tag = firstEl.tagName.toLowerCase()
          switch (tag) {
            case 'h1':
            case 'h2':
            case 'h3':
            case 'p':
            case 'a':
              openPanel(`text-block-${ tag }`)
              break
            case 'ul':
            case 'ol':
              openPanel(`text-block-p`)
              break
          }
        }
      }

      const usurpUpdateBlock = (settings) => {
        updateBlock({
          ...settings,
          morphBlocks: false,
        })

        if (tinyMCE.activeEditor) {
          // Update the content style...
          tinyMCE.activeEditor.iframeElement.contentDocument.getElementsByTagName('style')[1].innerHTML = tinyMceCSS()
          // Replace the content after formatting
          tinyMCE.activeEditor.setContent(textContent({
            ...getActiveBlock(),
          }).innerHTML)
        }
      }

      return Fragment([
        TagFontControlGroup(__('Paragraphs'), 'p', p, usurpUpdateBlock),
        TagFontControlGroup(__('Links'), 'a', a, usurpUpdateBlock, {
          fontSize  : false,
          lineHeight: false,
          fontWeight: false,
          fontStyle : false,
        }),
        TagFontControlGroup(__('Heading 1'), 'h1', h1, usurpUpdateBlock),
        TagFontControlGroup(__('Heading 2'), 'h2', h2, usurpUpdateBlock),
        TagFontControlGroup(__('Heading 3'), 'h3', h3, usurpUpdateBlock),
      ])
    },
    edit     : ({
      id,
      selector,
      content,
      updateBlock,
      ...block
    }) => {

      let editorId = `text-${ id }`
      wp.editor.remove(editorId)

      let blockEl = document.getElementById(`b-${ id }`)
      let height = 200

      if (blockEl) {
        height = blockEl.getBoundingClientRect().height
        let iframe = blockEl.querySelector('iframe')
        if (iframe) {
          height = iframe.getBoundingClientRect().height
        }
      }

      return Div(( {
          id   : 'mce-editor-wrap',
          style: {
            height: `${ height }px`,
          },
        } ),
        [
          Textarea({
            onCreate: el => {
              setTimeout(() => {
                tinymceElement(editorId, {
                    replacements: true,
                    savedReplies: true,
                    posttags    : blockEl.closest('[data-type="queryloop"]') && true,
                    tinymce     : {
                      content_style : tinyMceCSS(),
                      height, // inline: true,
                      directionality: getEmailMeta().direction ?? 'ltr',
                    },
                    quicktags   : true,
                  },
                  (newContent) => {
                    content = newContent
                    updateBlock({
                      content,
                      morphBlocks: false,
                    })
                  })

                document.getElementById('mce-editor-wrap').style.removeProperty('height')
              })
            },
            value   : textContent({
              content, ...block,
            }).innerHTML,
            id      : editorId,
            onInput : e => {
              updateBlock({
                content    : e.target.value,
                morphBlocks: false,
              })
            },
          }),
        ])
    },
    html     : ({
      id,
      ...block
    }) => textContent(block),
    css      : ({
      selector = '',
      content,
      ...props
    }) => {

      let rules = []

      const tagBlock = (tag, style) => {

        // separate support for li
        if (tag === 'p') {
          return `${ selector } p, ${ selector } li{${ fontStyle(style) }}`
        }

        return `${ selector } ${ tag }{${ fontStyle(style) }}`
      }

      let tags = [
        'h1',
        'h2',
        'h3',
        'p',
        'a',
      ]

      tags.forEach(tag => {
        // only include the CSS if the tag is actually present
        if (content.match(new RegExp(`<${tag} [^>]*>`))) {
          rules.push(tagBlock(tag, props[tag] ?? {}))
        }
      })

      return rules.join(' ')
    },
    plainText: ({ content }) => extractPlainText(content),
    gutenberg: ({ content }) => {
      content = convertToGutenbergBlocks(content)

      return Div({}, [
        `<!-- wp:group ${ JSON.stringify({
          ghReplacements: true,
        }) } -->`,
        Div({
          className: 'wp-block-group',
        }, [
          content,
        ]),
        `<!-- /wp:group -->`,
      ]).innerHTML

    },
    defaults : {
      content: `<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin egestas dolor non nulla varius, id fermentum ante euismod. Ut a sodales nisl, at maximus felis. Suspendisse potenti. Etiam fermentum magna nec diam lacinia, ut volutpat mauris accumsan. Nunc id convallis magna. Ut eleifend sem aliquet, volutpat sapien quis, condimentum leo.</p>`,
      p      : fontDefaults({
        fontSize: 14,
      }),
      a      : {
        color: '#488aff',
      },
      h1     : fontDefaults({
        fontSize  : 42,
        fontWeight: '500',
      }),
      h2     : fontDefaults({
        fontSize  : 24,
        fontWeight: '500',
      }),
      h3     : fontDefaults({
        fontSize  : 20,
        fontWeight: '500',
      }),
    },
  })

  // Register the button block
  registerBlock('button', 'Button', {
    attributes: {
      text           : el => el.querySelector('a').innerText,
      link           : el => el.querySelector('a').getAttribute('href'),
      align          : el => el.querySelector('td[align]').getAttribute('align'),
      borderStyle    : el => parseBorderStyle(el.querySelector('td.email-button').style),
      backgroundColor: el => el.querySelector('td.email-button').getAttribute('bgcolor'),
      style          : el => {
        return parseFontStyle(el.querySelector('a').style)
      },
    },
//language=HTML
    svg: `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
            <path fill="currentColor"
                  d="m15.7 5.3-1-1c-.2-.2-.4-.3-.7-.3H1c-.6 0-1 .4-1 1v5c0 .3.1.6.3.7l1 1c.2.2.4.3.7.3h13c.6 0 1-.4 1-1V6c0-.3-.1-.5-.3-.7zM14 10H1V5h13v5z"/>
        </svg>`,

    controls : ({
      text,
      link,
      style,
      align,
      size,
      backgroundColor,
      borderStyle = {},
      updateBlock = () => {
      },
    }) => {
      return [
        ControlGroup({
            name: 'Button',
          },
          [
            Control({
                label  : 'Text',
                stacked: true,
              },
              InputWithReplacements({
                type     : 'text',
                id       : 'button-text',
                className: 'full-width',
                value    : text,
                onInput  : e => updateBlock({ text: e.target.value }),
              })),
            Control({
                label  : 'Link',
                stacked: true,
              },
              InputWithReplacements({
                inputCallback: LinkPicker,
                type         : 'text',
                id           : 'button-link',
                className    : 'full-width',
                value        : link,
                onChange     : e => updateBlock({ link: e.target.value }),
              })),
            Control({
                label: 'Size',
              },
              Select({
                id      : 'button-size',
                options : {
                  sm: __('Small'),
                  md: __('Medium'),
                  lg: __('Large'),
                },
                selected: size,
                onChange: e => updateBlock({ size: e.target.value }),
              })),
            Control({
                label: 'Alignment',
              },
              AlignmentButtons({
                id        : 'button-align',
                alignment : align,
                directions: [
                  'left',
                  'center',
                  'right',
                  'justify',
                ],
                onChange  : align => updateBlock({
                  align,
                  morphControls: true,
                }),
              })),
            Control({
                label: __('Button Color', 'groundhogg'),

              },
              ColorPicker({
                type    : 'text',
                id      : 'button-color',
                value   : backgroundColor,
                onChange: backgroundColor => updateBlock({
                  backgroundColor,
                }),
              })),
          ]),
        BorderControlGroup({
          ...borderStyle,
          onChange: newStyle => updateBlock({
            borderStyle  : {
              ...getActiveBlock().borderStyle, ...newStyle,
            },
            morphControls: true,
          }),
        }),
        TagFontControlGroup('Font', 'style', style, updateBlock),
      ]
    },
    html     : ({
      text,
      align,
      style,
      size,
      link,
      backgroundColor,
      borderStyle = {},
    }) => {

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

      let width = align === 'justify' ? '100%' : 'auto'

      return Table({
          width      : '100%',
          border     : '0',
          cellspacing: '0',
          cellpadding: '0',
          role       : 'presentation',
        },
        [
          Tr({},
            Td({
                align,
              },
              Table({
                border     : '0',
                cellspacing: '0',
                cellpadding: '0',
                role       : 'presentation',
                width,
              }, Tr({},
                Td({
                    className: 'email-button',
                    bgcolor  :
                    backgroundColor,
                    width,
                    style    : {
                      padding,
                      width,
                      borderRadius: '3px',
                      ...addBorderStyle(borderStyle),
                      backgroundColor,
                    },
                    align    : 'center',
                  },
                  makeEl('a', {
                      href : link,
                      style: {
                        ...style,
                        fontSize: `${ style.fontSize }px`,

                        textDecoration: 'none',
                        display       : 'inline-block',
                        verticalAlign : 'middle',
                        backgroundColor,
                      },
                    },
                    text)))))),
        ])
    },
    edit     : ({
      text,
      align,
      style,
      size,
      backgroundColor,
      updateBlock,
      borderStyle = {},
    }) => {

      let width = align === 'justify' ? '100%' : 'auto'

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
          text         : e.currentTarget.textContent,
          morphControls: true,
        })
      }

      return Table({
          width      : '100%',
          border     : '0',
          cellspacing: '0',
          cellpadding: '0',
          role       : 'presentation',
        },
        [
          Tr({},
            Td({
                align,
              },
              Table({
                  border     : '0',
                  cellspacing: '0',
                  cellpadding: '0',
                  role       : 'presentation',
                  width,
                },
                Tr({},
                  Td({
                      className: 'email-button',
                      bgcolor  : backgroundColor,
                      width,
                      style    : {
                        width,
                        padding,
                        borderRadius: '3px',
                        backgroundColor,
                        ...addBorderStyle(borderStyle),
                      },
                      align    : 'center',
                    },
                    makeEl('a', {
                        id             : `text-edit-link`,
                        contenteditable: true,
                        style          : {
                          ...style,
                          fontSize      : `${ style.fontSize }px`,
                          textDecoration: 'none !important',
                          display       : 'inline-block',
                          verticalAlign : 'middle',
                          backgroundColor,
                        },
                        eventHandlers  : {
                          'input': textUpdate,
                        },
                      },
                      text)))))),

        ])

    },
    plainText: ({
      text,
      link = '',
    }) => {
      return `[${ text }](${ link })`
    },
    gutenberg: ({
      text,
      align,
      link,
    }) => {
      return Div({}, [
        `<!-- wp:buttons ${ JSON.stringify({
          layout        : {
            type          : 'flex',
            justifyContent: align === 'justify' ? 'center' : align,
          },
          ghReplacements: true,
        }) } -->`,
        Div({
          className: 'wp-block-buttons',
        }, [
          `<!-- wp:button -->`,
          Div({
            className: 'wp-block-button',
          }, An({
            className: 'wp-block-button__link wp-element-button',
            href     : link || '#',
          }, text)),
          `<!-- /wp:button -->`,
        ]),
        `<!-- /wp:buttons -->`,
      ]).innerHTML
    },
    defaults : {
      link           : '',
      align          : 'center',
      text           : 'Click me!',
      size           : 'md',
      backgroundColor: '#dd3333',
      style          : {
        color     : '#ffffff',
        fontSize  : 20,
        fontWeight: '600',
        fontFamily: 'Arial, sans-serif',
      },
    },
  })

  registerBlock('menu', 'Menu', {
    attributes: {
      links    : el => {
        return [...el.querySelectorAll('a')].map(a => ( [
          a.innerHTML,
          a.getAttribute('href'),
        ] ))
      },
      fontStyle: el => parseFontStyle(el.querySelector('a').style),
      gap      : el => parseInt(el.querySelector('td.gap')?.width),
      align    : el => el.querySelector('td[align]').getAttribute('align'),
    },
    // language=HTML
    svg     : `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>`,
    controls: ({
      links = [],
      align = 'center',
      fontStyle = {},
      gap = 10,
      separator = '',
      updateBlock,
    }) => {
      return [
        ControlGroup({
          name: 'Menu',
        }, [
          Control({
            label  : 'Menu links',
            stacked: true,
          }, InputRepeater({
            id      : 'menu-link-editor',
            rows    : links,
            sortable: true,
            cells   : [
              props => Input({
                ...props,
                placeholder: 'Link Text',
              }),
              props => LinkPicker(props),
            ],
            onChange: links => updateBlock({
              links,
            }),
          })),
          Control({
              label: 'Separator',
            },
            Select({
              id      : 'menu-separator',
              selected: separator,
              options : {
                ''      : 'None',
                '&vert;': '&vert;',
                '&Vert;': '&Vert;',
                // '&tilde;' : '&tilde;',
                '&middot;': '&middot;',
                '&bull;'  : '&bull;',
              },
              onChange: e => updateBlock({
                separator: e.target.value,
              }),
            })),
          Control({
              label: 'Alignment',
            },
            AlignmentButtons({
              id        : 'button-align',
              alignment : align,
              directions: [
                'left',
                'center',
                'right',
                'justify',
              ],
              onChange  : align => updateBlock({
                align,
                morphControls: true,
              }),
            })),
          Control({
              label: 'Gap',
            },
            NumberControl({
              id      : 'menu-item-gap',
              step    : 5,
              value   : gap,
              unit    : 'px',
              onChange: e => updateBlock({ gap: e.target.value }),

            })),
        ]),
        TagFontControlGroup('Font', 'fontStyle', fontStyle, updateBlock),
      ]
    },
    html    : ({
      links = [],
      fontStyle = {},
      gap = 10,
      align = 'center',
      separator = '',
    }) => {

      const menuItem = (text, href) => Td({
        style: {
          textAlign: 'center',
        },
      }, makeEl('a', {
        href,
        style: {
          ...fontStyle,
          fontSize      : `${ fontStyle.fontSize }px`,
          textDecoration: 'none',
        },
      }, text))

      let cells = links.reduce((cells, [text, href], i) => {

        if (i > 0) {

          let Gap = ColumnGap(gap, 10, separator)
          Gap.style.color = fontStyle.color

          cells.push(Gap)
        }

        cells.push(menuItem(text, href))

        return cells
      }, [])

      let width = align === 'justify' ? '100%' : 'auto'

      return Table({
          width      : '100%',
          border     : '0',
          cellspacing: '0',
          cellpadding: '0',
          role       : 'presentation',
          style      : {
            borderCollapse: 'collapse',
          },
        },
        [
          Tr({},
            Td({
                align,
              },
              Table({
                border     : '0',
                cellspacing: '0',
                cellpadding: '0',
                role       : 'presentation',
                style      : {
                  borderCollapse: 'collapse',
                },
                width,
              }, Tr({}, cells)))),
        ])
    },
    // edit: () => {},
    plainText: ({ links = [] }) => {
      return links.map(([text, href]) => `[${ text }](${ href })`).join(' | ')
    },
    gutenberg: ({
      links,
      align,
      separator,
    }) => {
      return Div({}, [
        `<!-- wp:paragraph ${ JSON.stringify({
          ghReplacements: true,
        }) } -->`,
        Pg({
          style: {
            textAlign: align,
          },
        }, [
          links.map(([text, href]) => An({ href }, text).outerHTML).join(` ${ separator } `),
        ]),
        `<!-- /wp:paragraph -->`,
      ]).innerHTML
    },
    defaults : {
      links    : [
        [
          'Link 1',
          '',
        ],
        [
          'Link 2',
          '',
        ],
        [
          'Link 3',
          '',
        ],
      ],
      align    : 'center',
      fontStyle: {
        fontSize  : 13,
        fontFamily: 'system-ui, sans-serif',
        fontWeight: 400,
      },
      gap      : 20,
      separator: '',
    },
  })

  // Register the image block
  registerBlock('image', 'Image', {
    attributes         : {
      src        : el => el.querySelector('img').getAttribute('src'),
      height     : el => parseInt(el.querySelector('img').height),
      width      : el => parseInt(el.querySelector('img').width),
      alt        : el => el.querySelector('img').alt,
      link       : el => el.querySelector('a')?.getAttribute('href'),
      borderStyle: el => parseBorderStyle(el.querySelector('img').style),
      align      : el => el.querySelector('.img-container').style.getPropertyValue('text-align'),
    },
    replacementsSupport: {
      src: {
        edit: true,
        html: true,
      },
    },
    svg                : icons.image,
    controls           : ({
      id,
      src,
      link = '',
      width,
      height,
      alt = '',
      align = 'center',
      updateBlock,
      borderStyle = {},
    }) => {

      let imgBlockEl = document.getElementById(`b-${ id }`)
      let maxWidth = imgBlockEl ? imgBlockEl.getBoundingClientRect().width : getEmailWidth()

      return Fragment([
        ControlGroup({
            name: 'Image',
          },
          [
            ImageControls({
              id      : 'image',
              src,
              alt,
              width,
              maxWidth,
              onChange: image => {
                updateBlock({
                  ...image,
                  morphControls: true,
                })
              },
            }),
            Control({
                label: 'Alignment',
              },
              AlignmentButtons({
                id       : 'image-align',
                alignment: align,
                onChange : align => {
                  updateBlock({
                    align,
                    morphControls: true,
                  })
                },
              })),
            Control({
                label  : 'Link',
                stacked: true,
              },
              InputWithReplacements({
                inputCallback: LinkPicker,
                type         : 'text',
                id           : 'image-link',
                className    : 'full-width',
                value        : link,
                onChange     : e => updateBlock({ link: e.target.value }),
              })),
          ]),
        BorderControlGroup({
          ...borderStyle,
          onChange: newStyle => updateBlock({
            borderStyle  : {
              ...getActiveBlock().borderStyle, ...newStyle,
            },
            morphControls: true,
          }),
        }),
      ])
    },
    edit               : ({
      src,
      width,
      height,
      alt = '',
      align = 'center',
      updateBlock,
      borderStyle = {},
    }) => {

      return Div({
          className: 'img-container full-width',
          style    : {
            textAlign: align,
          },
        },
        makeEl('img', {
          className: 'resize-me',
          onCreate : el => {
            setTimeout(() => {
                let $el = $('img.resize-me')
                $el.resizable({
                  aspectRatio: true,
                  maxWidth   : $el.parent().width(),
                  stop       : (e, ui) => {
                    updateBlock({
                      width        : Math.ceil(ui.size.width),
                      morphControls: true,
                      morphBlocks  : false,
                    })
                  },
                })
              },
              100)
          },
          src,
          alt, // title,
          width,
          height   : 'auto',
          style    : {
            verticalAlign: 'bottom',
            height       : 'auto',
            width, ...addBorderStyle(borderStyle),
          },
        }))
    },
    html               : ({
      src,
      width,
      height,
      link = '',
      alt = '',
      align = 'center',
      borderStyle = {},
    }) => {

      let img = makeEl('img', {
        src   : src,
        alt, // title,
        width,
        height: 'auto',
        style : {
          boxSizing    : 'border-box',
          verticalAlign: 'bottom',
          height       : 'auto',
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
        style    : {
          textAlign: align,
        },
      }, img)
    },
    plainText          : ({
      src = '',
      alt = '',
      link = '',
    }) => {
      return `${ link ? '[' : '' }![${ alt || 'image' }](${ src })${ link ? `](${ link })` : '' }`
    },
    gutenberg          : ({
      src,
      width,
      link = '',
      alt = '',
      align = 'center',
    }) => {

      let image = Img({
        src,
        alt,
      })

      if (link) {
        image = An({ href: link }, image)
      }

      return Div({}, [
        `<!-- wp:image ${ JSON.stringify({
          sizeSlug       : 'full',
          linkDestination: link ? 'custom' : 'none',
          align,
          ghReplacements : true,
        }) } -->`,
        makeEl('figure', {
          className: `wp-block-image size-full align${ align }`,
        }, [
          image,
        ]),
        `<!-- /wp:image -->`,
      ]).innerHTML
    },
    defaults           : {
      src   : 'https://placehold.co/600x338?text=Image',
      alt   : 'placeholder image',
      title : 'placeholder image',
      width : 600,
      height: 338,
      align : 'center',
    },
  })

  // Register the spacer block
  registerBlock('spacer', 'Spacer', {
    attributes: {
      height: el => parseInt(el.querySelector('td[height]').getAttribute('height')),
    },
    svg       : `
        <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 512 512">
  <path fill="currentColor"
        d="M352 384h-48V128h48a16 16 0 0 0 11-27L267 5c-6-7-16-7-22 0l-96 96c-5 4-6 11-4 17 3 6 9 10 15 10h48v256h-48a16 16 0 0 0-11 27l96 96c6 7 16 7 22 0l96-96a16 16 0 0 0-11-27z"/>
</svg>`,
    controls  : ({
      height = 10,
      updateBlock,
    }) => {
      return ControlGroup({
          name: 'Spacer',
        },
        [
          Control({
              label: 'Height',
            },
            NumberControl({
              id       : 'spacer-height',
              className: 'control-input',
              value    : height,
              onChange : e => {
                updateBlock({
                  height: parseInt(e.target.value),

                })
              },
            })),
        ])
    },
    html      : ({ height = 20 }) => Table({
        cellspacing: '0',
        cellpadding: '0',
        role       : 'presentation',
      },
      Tr({},
        Td({
          height,
          style: {
            height: `${ height }px`,
          },
        }))),
    gutenberg : ({ height }) => {
      // language=HTML
      return `<!-- wp:spacer ${ JSON.stringify({ height: `${ height }px` }) } -->
      <div style="height:${ height }px" aria-hidden="true" class="wp-block-spacer"></div>
      <!-- /wp:spacer -->`
    },
    defaults  : {
      height: 20,
    },
  })

  // register the divider block
  registerBlock('divider', 'Divider', {
    attributes: {
      height   : el => parseInt(el.querySelector('hr').style.getPropertyValue('border-top-width')),
      width    : el => parseInt(el.querySelector('hr').style.getPropertyValue('width')),
      color    : el => el.querySelector('hr').style.getPropertyValue('border-top-color'),
      lineStyle: el => el.querySelector('hr').style.getPropertyValue('border-style'),
    },
    //language=HTML
    svg      : `
        <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 409.6 409.6">
  <path fill="currentColor" d="M393 188H17a17 17 0 1 0 0 34h376a17 17 0 1 0 0-34z"/>
</svg>`,
    controls : ({
      height = 10,
      width = 80,
      color,
      updateBlock,
      lineStyle,
    }) => {
      return ControlGroup({
          name: 'Divider',
        },
        [
          Control({
              label: 'Style',
            },
            Select({
              id      : 'line-style',
              options : {
                solid : __('Solid', 'groundhogg'),
                dashed: __('Dashed', 'groundhogg'),
                dotted: __('Dotted', 'groundhogg'),
                double: __('Double', 'groundhogg'),
                ridge : __('Ridge', 'groundhogg'),
                groove: __('Groove', 'groundhogg'),
              },
              selected: lineStyle,
              onChange: e => updateBlock({ lineStyle: e.target.value }),

            })),
          Control({
              label: 'Height',
            },
            NumberControl({
              className: 'control-input',
              value    : height,
              id       : 'divider-height',
              unit     : 'px',
              onChange : e => {
                updateBlock({
                  height: parseInt(e.target.value),

                })
              },
            })),
          Control({
              label: 'Width',
            },
            NumberControl({
              className: 'control-input',
              value    : width,
              id       : 'divider-width',
              unit     : '%',
              step     : 10,
              max      : 100,
              onChange : e => {
                updateBlock({
                  width: parseInt(e.target.value),

                })
              },
            })),
          Control({
              label: 'Color',
            },
            ColorPicker({
              id      : 'divider-color',
              value   : color,
              onChange: color => {
                updateBlock({
                  color,
                })
              },
            })),

        ])
    },
    html     : ({
      height,
      width,
      color,
      lineStyle = 'solid',
    }) => makeEl('hr', {
      className: 'divider',
      style    : {
        borderWidth: `${ height }px 0 0 0`,
        width      : `${ width }%`,
        borderColor: color,
        borderStyle: lineStyle,
      },
    }),
    plainText: () => '---',
    gutenberg: () => {
      return Div({}, [
        `<!-- wp:separator -->`,
        makeEl('hr', {
          className: 'wp-block-separator has-alpha-channel-opacity',
        }),
        `<!-- /wp:separator -->`,
      ]).innerHTML
    },
    defaults : {
      height   : 3,
      color    : '#ccc',
      width    : 100,
      lineStyle: 'solid',
    },
  })

  /**
   * Remove bad tags and attributes from the HTML of the email
   *
   * @param html
   * @return {string}
   */
  const cleanHTML = (html, wholeDoc = false) => {
    const parser = new DOMParser()
    const doc = parser.parseFromString(html, 'text/html')

    const unsupportedTags = [
      'script',
      'form',
      'button',
      'input',
      'textarea',
      'menu',
      'iframe',
      'audio',
      'video',
      'embed',
    ]

    // Remove bad HTML
    doc.querySelectorAll(unsupportedTags.join(', ')).forEach(el => el.remove())

    if (!wholeDoc) {
      return doc.body.innerHTML
    }

    return new XMLSerializer().serializeToString(doc)
  }

  // Register the html block
  registerBlock('html', 'HTML', {
    attributes         : {
      content: el => el.innerHTML,
    },
    replacementsSupport: {
      content: {
        edit: true,
        html: true,
      },
    },
// language=HTML
    svg      : `
        <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 512 512">
  <path fill="currentColor"
        d="M507 243 388 117a19 19 0 1 0-28 27l106 112-106 112a19 19 0 0 0 28 27l119-126c7-7 7-19 0-26zM152 368 46 256l106-112a19 19 0 0 0-28-27L5 243c-7 7-7 19 0 26l119 126a19 19 0 0 0 27 0c7-7 8-19 1-27zM287 53c-10-2-20 5-22 16l-56 368a19 19 0 0 0 38 6l56-368c2-11-5-21-16-22z"/>
</svg>`,
    controls : ({
      content = '',
      updateBlock,
    }) => {
      return Fragment([
        ControlGroup({
            name    : 'HTML',
            closable: false,
          },
          [
            Textarea({
              id      : 'code-block-editor',
              value   : html_beautify(content),
              onCreate: el => {

                // Wait for add to dom
                setTimeout(() => {
                    let editor = wp.codeEditor.initialize('code-block-editor', {
                      ...wp.codeEditor.defaultSettings,
                    }).codemirror

                    editor.on('change', instance => updateBlock({
                      content: cleanHTML(instance.getValue()),
                    }))

                    editor.setSize(null, 500)
                  },
                  100)
              },
            }),
            `<p>Not all HTML or CSS works in email. Check your <a href="https://www.campaignmonitor.com/css/" target="_blank">HTML and CSS compatibility</a>.</p>`,
            `<p>Some elements such as <code>script</code> and <code>form</code> elements will be stripped out automatically.</p>`,
          ]),

      ])
    },
    html     : ({ content }) => {
      return cleanHTML(content)
    },
    plainText: ({ content }) => extractPlainText(content),
    gutenberg: ({ content }) => {
      return Div({}, [
        `<!-- wp:html ${ JSON.stringify({ ghReplacements: true }) } -->`,
        content,
        `<!-- /wp:html -->`,
      ]).innerHTML
    },
    defaults : {
      content: '<p>HTML CODE</p>',
    },
  })

  // Register the shortcode block
  registerDynamicBlock('shortcode', 'Shortcode', {
    attributes: ['shortcode'],
    svg       : `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96.24 96.24">
  <path fill="currentColor" d="M48.122 0C21.587 0 .001 21.585.001 48.118c0 26.535 21.587 48.122 48.12 48.122 26.532 0 48.117-21.587 48.117-48.122C96.239 21.586 74.654 0 48.122 0zM4.857 48.118a43.085 43.085 0 0 1 3.746-17.606l20.638 56.544C14.81 80.042 4.857 65.243 4.857 48.118zm43.265 43.267c-4.247 0-8.346-.623-12.222-1.763l12.98-37.719 13.301 36.433c.086.215.191.411.308.596a43.204 43.204 0 0 1-14.367 2.453zm5.961-63.551c2.604-.137 4.953-.412 4.953-.412 2.33-.276 2.057-3.701-.277-3.564 0 0-7.007.549-11.532.549-4.25 0-11.396-.549-11.396-.549-2.332-.137-2.604 3.427-.273 3.564 0 0 2.208.275 4.537.412l6.74 18.469-9.468 28.395-15.752-46.863c2.608-.136 4.952-.412 4.952-.412 2.33-.275 2.055-3.702-.278-3.562 0 0-7.004.549-11.53.549a94.6 94.6 0 0 1-2.784-.052C19.709 12.611 33.008 4.856 48.122 4.856c11.265 0 21.519 4.306 29.215 11.357-.187-.01-.368-.035-.562-.035-4.248 0-7.264 3.702-7.264 7.679 0 3.564 2.055 6.582 4.248 10.146 1.647 2.882 3.567 6.585 3.567 11.932 0 3.704-1.422 8-3.293 13.986l-4.315 14.421zm15.788 57.682 13.215-38.208c2.471-6.171 3.29-11.106 3.29-15.497 0-1.591-.104-3.07-.292-4.449a43.011 43.011 0 0 1 5.301 20.758c-.001 15.96-8.653 29.896-21.514 37.396z"/>
</svg>`,

    controls    : ({
      shortcode,
      updateBlock,
    }) => Fragment([
      ControlGroup({
          id      : 'shortcode',
          name    : 'Shortcode',
          closable: false,
        },
        [
          Control({
              label  : 'Shortcode',
              stacked: true,
            },
            Textarea({
              className  : 'code',
              value      : shortcode,
              id         : 'shortcode-paste',
              placeholder: '[your-shortcode]',
              onChange   : e => {
                updateBlock({
                  shortcode: e.target.value,
                })
              },
            })),
          `<p>Only basic or email specific shortcodes will work.</p>`,
          `<p>Not all HTML or CSS works in email. Check your <a href="https://www.campaignmonitor.com/css/" target="_blank">HTML and CSS compatibility</a>.</p>`,
          `<p>Some elements such as <code>script</code> and <code>form</code> elements will be stripped out automatically.</p>`,
          `<p>Shortcodes will <b>not</b> load any JavaScript or CSS dependencies. You can add style using custom CSS in the advanced tab.</p>`,
        ]),
    ]),
    gutenberg   : ({ content }) => {
      return Div({}, [
        `<!-- wp:shortcode ${ JSON.stringify({ ghReplacements: true }) } -->`,
        content,
        `<!-- /wp:shortcode -->`,
      ]).innerHTML
    },
    parseContent: content => cleanHTML(content),
    defaults    : {
      shortcode: '',
    },
  })

  function createIncrementer () {
    let count = 0
    return function () {
      count += 1
      return count
    }
  }

  const getQueryId = createIncrementer()

  const QueryControls = ({
    number,
    offset,
    post_type = 'post',
    queryId = '',
    include = [],
    includedPosts = [],
    exclude = [],
    excludedPosts = [],
    terms = {},
    updateBlock,
  }) => {

    const postTypeOptions = {}

    for (let type in _BlockEditor.post_types) {
      postTypeOptions[type] = _BlockEditor.post_types[type].labels.name
    }

    const currentPostType = _BlockEditor.post_types[post_type]

    const termControls = []

    for (let tax in currentPostType.taxonomies) {
      const taxonomy = currentPostType.taxonomies[tax]

      if (!taxonomy.public || !taxonomy.show_in_rest) {
        continue
      }

      let selected = terms[tax] ?? []
      let selectedCache = terms[`${ tax }_cache`] ?? []
      let tax_rel = terms[`${ tax }_rel`] ?? 'any'

      termControls.push(...[
        `<hr>`,

        // Terms
        Control({
            label  : taxonomy.label,
            stacked: true,
          },
          ItemPicker({
            id          : `select-${ tax }`,
            selected    : selectedCache,
            tags        : false,
            fetchOptions: async (search) => {
              let terms = await get(`${ Groundhogg.api.routes.wp.v2 }/${ taxonomy.rest_base || tax }/`,
                {
                  search,
                  per_page: 20,
                  orderby : 'count',
                  order   : 'desc',
                })
              terms = terms.map(({
                id,
                name,
              }) => ( {
                id,
                text: name,
              } ))
              return terms
            },
            onChange    : selected => {

              terms = {
                ...terms,
                [`${ tax }_cache`]: selected,
                [tax]             : selected.map(opt => opt.id),
              }

              updateBlock({
                terms,
              })

              if (terms.length > 1) {

              }
              else {

              }
            },
          })),

        // Terms Any || All
        Control({
            label  : 'Relationship',
            tooltip: 'If the post should have at least one, or all of the terms.',
          },
          ButtonToggle({
            id: `${ tax }-rel`,

            selected: tax_rel,
            options : [
              {
                id  : 'any',
                text: 'Any',
              },
              {
                id  : 'all',
                text: 'All',
              },
            ],
            onChange: rel => {
              terms = {
                ...terms,
                [`${ tax }_rel`]: rel,
              }

              updateBlock({
                terms,
                morphControls: true,
              })
            },
          })),
      ])
    }

    return ControlGroup({
        name: 'Query',
      },
      [

        // Post type
        Control({
            label: 'Post Type',
          },
          Select({
            id      : 'post-type',
            selected: post_type,
            options : postTypeOptions,
            onChange: e => updateBlock({
              post_type    : e.target.value,
              morphControls: true,

              // Clear these when changing the post type
              include      : [],
              includedPosts: [],
              exclude      : [],
              excludedPosts: [],
            }),
          })),

        // Number of posts
        Control({
            label: 'Number of posts',
          },
          Input({
            type     : 'number',
            id       : 'number-of-posts',
            className: 'control-input',
            value    : number,
            onChange : e => updateBlock({ number: e.target.value }),
          })),

        // Query offset
        Control({
            label  : 'Offset',
            tooltip: 'The number of posts to skip',
          },
          Input({
            type     : 'number',
            id       : 'posts-offset',
            className: 'control-input',
            value    : offset,
            onChange : e => updateBlock({ offset: e.target.value }),
          })),

        // Term controls
        ...termControls,

        // Include Ids
        `<hr/>`,
        Control({
            label  : `Include these ${ currentPostType.labels.name.toLowerCase() }`,
            stacked: true,
          },
          ItemPicker({
            id          : `${ post_type }-includes`,
            selected    : includedPosts,
            tags        : false,
            fetchOptions: async (search) => {
              let posts = await get(`${ Groundhogg.api.routes.wp.v2 }/${ currentPostType.rest_base || post_type }`,
                {
                  search,
                  per_page: 20,
                  orderby : search ? 'relevance' : 'modified',
                  order   : 'desc',
                })
              posts = posts.map(({
                id,
                title,
              }) => ( {
                id,
                text: title.rendered,
              } ))
              return posts
            },
            onChange    : selected => {
              updateBlock({
                includedPosts: selected,
                include      : selected.map(opt => opt.id),
              })
            },
          })),
        `<p>Limit result set to specific IDs.</p>`,

        // Exclude Ids
        `<hr/>`,
        Control({
            label  : `Exclude these ${ currentPostType.labels.name.toLowerCase() }`,
            stacked: true,
          },
          ItemPicker({
            id: `${ post_type }-excludes`,

            selected    : excludedPosts,
            tags        : false,
            fetchOptions: async (search) => {
              let posts = await get(`${ Groundhogg.api.routes.wp.v2 }/${ currentPostType.rest_base || post_type }`,
                {
                  search,
                  per_page: 20,
                  orderby : 'relevance',
                  order   : 'desc',
                })
              posts = posts.map(({
                id,
                title,
              }) => ( {
                id,
                text: title.rendered,
              } ))
              return posts
            },
            onChange    : selected => {
              updateBlock({
                excludedPosts: selected,
                exclude      : selected.map(opt => opt.id),
              })
            },
          })),
        `<p>Ensure result set excludes specific IDs.</p>`,

        // Query ID
        `<hr/>`,
        Control({
            label  : 'Query ID',
            stacked: true,
          },
          Input({
            id      : 'query-id',
            name    : 'query_id',
            value   : queryId,
            onChange: e => updateBlock({ queryId: e.target.value }),
          })),
        `<p>This allows you to filter this specific query with additional parameters.</p>`,

      ])

  }

  // Register the post block
  registerDynamicBlock('posts', 'Posts', {
    ignoreForRefresh: [
      'headingStyle',
      'cardStyle',
      'excerptStyle',
    ], //language=HTML
    svg             : `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 193.826 193.826"
             style="enable-background:new 0 0 193.826 193.826" xml:space="preserve">
        <path fill="currentColor"
              d="M191.495 55.511 137.449 1.465a4.998 4.998 0 0 0-7.07 0l-.229.229a17.43 17.43 0 0 0-5.14 12.406c0 3.019.767 5.916 2.192 8.485l-56.55 48.533c-4.328-3.868-9.852-5.985-15.703-5.985a23.444 23.444 0 0 0-16.689 6.913l-.339.339a4.998 4.998 0 0 0 0 7.07l32.378 32.378-31.534 31.533c-.631.649-15.557 16.03-25.37 28.27-9.345 11.653-11.193 13.788-11.289 13.898a4.995 4.995 0 0 0 .218 6.822 4.987 4.987 0 0 0 3.543 1.471c1.173 0 2.349-.41 3.295-1.237.083-.072 2.169-1.885 13.898-11.289 12.238-9.813 27.619-24.74 28.318-25.421l31.483-31.483 30.644 30.644c.976.977 2.256 1.465 3.535 1.465s2.56-.488 3.535-1.465l.339-.339a23.446 23.446 0 0 0 6.913-16.689 23.43 23.43 0 0 0-5.985-15.703l48.533-56.55a17.434 17.434 0 0 0 8.485 2.192c4.687 0 9.093-1.825 12.406-5.14l.229-.229a5 5 0 0 0 0-7.072z"/></svg>`,

    controls    : ({
      layout = '',
      featured = false,
      excerpt = false,
      thumbnail = true,
      thumbnail_size = '',
      thumbnail_position = 'above',
      gap = 20,
      excerptStyle = {},
      headingStyle = {},
      updateBlock,
      cardStyle = {},
      ...query
    }) => {

      const Supports = {
        featured         : ['cards'].includes(layout),
        card             : ['cards'].includes(layout),
        excerpts         : [
          'cards',
          'h1',
          'h2',
          'h3',
          'h4',
          'h5',
        ].includes(layout),
        headingStyle     : [
          'cards',
          'h1',
          'h2',
          'h3',
          'h4',
          'h5',
        ].includes(layout),
        thumbnails       : [
          'cards',
          'h1',
          'h2',
          'h3',
          'h4',
          'h5',
        ].includes(layout),
        thumbnailPosition: [
          'h1',
          'h2',
          'h3',
          'h4',
          'h5',
        ].includes(layout),
        gap              : [
          'cards',
          'h1',
          'h2',
          'h3',
          'h4',
          'h5',
        ].includes(layout),
      }

      return Fragment([
        ControlGroup({
            name: 'Layout',
          },
          [

            // layout
            Control({
                label: 'Layout',
              },
              Select({
                options : {
                  cards: 'Cards',
                  ol   : 'Numbered List',
                  ul   : 'Unordered List',
                  h1   : 'H1',
                  h2   : 'H2',
                  h3   : 'H3',
                  h4   : 'H4',
                  h5   : 'H5',
                },
                selected: layout,
                onChange: e => updateBlock({
                  layout       : e.target.value,
                  morphControls: true,
                }),

              })),

            // Features
            Supports.featured ? Control({
                label: 'Featured',
              },
              Toggle({
                id      : 'toggle-featured',
                checked : featured,
                onChange: e => updateBlock({ featured: e.target.checked }),
              })) : null,

            // Excerpt
            Supports.excerpts ? Control({
                label: 'Excerpts',
              },
              Toggle({
                id      : 'toggle-excerpt',
                checked : excerpt,
                onChange: e => updateBlock({
                  excerpt      : e.target.checked,
                  morphControls: true,
                }),
              })) : null,

            // Thumbnails
            Supports.thumbnails ? Control({
                label: 'Thumbnails',
              },
              Toggle({
                id      : 'toggle-thumbnails',
                checked : thumbnail,
                onChange: e => updateBlock({
                  thumbnail    : e.target.checked,
                  morphControls: true,
                }),
              })) : null,

            // Gap
            Supports.gap ? Control({
                label: 'Gap',
              },
              NumberControl({
                id       : 'column-gap',
                className: 'control-input',
                value    : gap,
                step     : 5,
                unit     : 'px',
                onInput  : e => updateBlock({ gap: e.target.value }),
              })) : null,
          ]),

        // Card style
        Supports.card ? ControlGroup({
            id  : 'post-cards',
            name: 'Card Style',
          },
          [// Padding
            Control({
                label  : 'Content Padding',
                stacked: true,
              },
              TopRightBottomLeft({
                id      : 'content-padding',
                values  : cardStyle.padding,
                onChange: padding => {
                  updateBlock({
                    cardStyle    : {
                      ...getActiveBlock().cardStyle,
                      padding,
                    },
                    morphControls: true,
                  })
                },
              })),
            makeEl('hr'),
            // Background Color
            Control({
                label: 'Background Color',
              },
              ColorPicker({
                type    : 'text',
                id      : 'background-color',
                value   : cardStyle.backgroundColor,
                onChange: backgroundColor => updateBlock({
                  cardStyle    : {
                    ...getActiveBlock().cardStyle,
                    backgroundColor,
                  },
                  morphControls: true,
                }),
              })),
            makeEl('hr'),
            // Border
            Control({
                stacked: true,
                label  : 'Border',
              },
              BorderControls({
                ...cardStyle,
                onChange: newStyle => updateBlock({
                  cardStyle    : {
                    ...getActiveBlock().cardStyle, ...newStyle,
                  },
                  morphControls: true,
                }),
              })),

          ]) : null,

        // Heading Style
        TagFontControlGroup([
                              'ol',
                              'ul',
                            ].includes(layout) ? __('Font') : __('Heading'),
          'headingStyle', headingStyle, updateBlock),

        // Excerpt Style
        excerpt && Supports.excerpts ? TagFontControlGroup(__('Excerpt'),
          'excerptStyle', excerptStyle, updateBlock) : null,

        // Thumbnail controls
        thumbnail && Supports.thumbnails ? ControlGroup({ name: 'Thumbnail' },
          [
            Control({
                label: 'Thumbnail Size',
              },
              Select({
                id      : 'thumbnail-size',
                style   : {
                  width: '115px',
                },
                selected: thumbnail_size,
                options : imageSizes.map(size => ( {
                  value: size,
                  text : size,
                } )),

                onChange: e => updateBlock({ thumbnail_size: e.target.value }),

              })),
            Supports.thumbnailPosition ? Control({
                label: 'Position',
              },
              Select({
                id      : 'thumbnail-position',
                selected: thumbnail_position,
                options : {
                  above: 'Above Headline',
                  below: 'Below Headline',
                  left : 'Left',
                  right: 'Right',
                },
                onChange: e => updateBlock({ thumbnail_position: e.target.value }),

              })) : null,
          ]) : null,

        // Query
        QueryControls({
          updateBlock,
          ...query,
        }),
      ])
    },
    parseContent: (content, {
      layout,
      headingStyle = {},
      excerptStyle = {},
      cardStyle = {},
    }) => {
      const parser = new DOMParser()
      const doc = parser.parseFromString(content, 'text/html')

      inlineStyle(doc, 'h1,h2,h3,h4,h5,li', headingStyle)
      inlineStyle(doc, 'p', excerptStyle)

      if (layout === 'cards') {

        let {
          padding = {},
          backgroundColor = '',
        } = cardStyle

        let style = {}

        if (backgroundColor) {
          style.backgroundColor = backgroundColor
        }

        addBorderStyle(cardStyle, style)

        doc.querySelectorAll('.post-card').forEach(el => {
          for (let attr in style) {
            el.style[attr] = style[attr]
          }
        })

        doc.querySelectorAll('.post-card .card-content td').forEach(el => {
          el.style['padding'] = extract4(padding)
        })
      }

      return doc.body.innerHTML
    },
    css         : ({
      selector,
      layout = '',
      headingStyle = {},
      excerptStyle = {},
      cardStyle = {},
    }) => {

      let {
        padding = {},
        backgroundColor = '',
      } = cardStyle

      let style = {}

      if (backgroundColor) {
        style.backgroundColor = backgroundColor
      }

      addBorderStyle(cardStyle, style)

      //language=CSS
      return `

          ${ selector } .post-card {
              ${ layout === 'cards' ? objectToStyle(style) : '' }
          }

          ${ selector } .post-card .card-content td {
              ${ layout === 'cards' ? objectToStyle({
                  padding: extract4(padding),

              }) : '' }
          }

          ${ selector } li,
          ${ selector } h1,
          ${ selector } h2,
          ${ selector } h3,
          ${ selector } h4,
          ${ selector } h5 {
              ${ fontStyle(headingStyle) }
          }

          ${ selector } p {
              ${ fontStyle(excerptStyle) }
          }
      `
    },
    html        : ({ DynamicContent }) => isGeneratingHTML() ? '' : DynamicContent(),
    edit        : ({ DynamicContent }) => DynamicContent(),
    plainText   : () => '',
    gutenberg   : ({
      excerpt = false,
      number,
      offset,
      post_type = 'post',
      queryId = '',
      include = [],
      exclude = [],
      terms = {},
    }) => {

      let postQueryProps = {
        queryId  : getQueryId(),
        query    : {
          perPage : number,
          pages   : 0,
          offset,
          postType: post_type,
          // exclude,
          // include,
          // taxQuery: terms
        },
        namespace: 'core/posts-list',
      }

      return Div({}, [
        `<!-- wp:query ${ JSON.stringify(postQueryProps) } -->`,
        Div({
          className: 'wp-block-query',
        }, [
          `<!-- wp:post-template -->`,
          `<!-- wp:post-featured-image /-->`,
          `<!-- wp:post-title /-->`,
          `<!-- wp:post-date /-->`,
          excerpt ? `<!-- wp:post-excerpt /-->` : null,
          `<!-- /wp:post-template -->`,
          //language=HTML
          `<!-- wp:query-pagination -->
          <!-- wp:query-pagination-previous /-->
          <!-- wp:query-pagination-numbers /-->
          <!-- wp:query-pagination-next /-->
          <!-- /wp:query-pagination -->
          <!-- wp:query-no-results -->
          <!-- wp:paragraph -->
          <p>No posts were found.</p>
          <!-- /wp:paragraph -->
          <!-- /wp:query-no-results -->
          `,
        ]),
        `<!-- /wp:query -->`,
      ]).innerHTML

    },
    defaults    : {
      layout        : 'cards',
      featured      : true,
      excerpt       : false,
      thumbnail     : true,
      thumbnail_size: 'thumbnail',
      columns       : 2,
      gap           : 20,
      cardStyle     : {},
      headingStyle  : fontDefaults({
        fontSize: 24,
      }),
      excerptStyle  : fontDefaults({
        fontSize: 16,
      }),
      queryId       : '',
      post_type     : 'post',
      number        : 5,
      offset        : 0,
    },
  })

  const ChildBlocks = ({ children }) => Div({
    className: `children sortable-blocks ${ children.length ? '' : 'empty' }`,
    onCreate : el => {
      makeSortable(el)
    },
  }, [
    ...children.map(b => EditBlockWrapper(b)),
  ])

  const QueryLoopContent = ({
    DynamicContent,
    ...block
  }) => {

    let dynamicContent = Div({}, DynamicContent())
    let childBlockPlace = dynamicContent.querySelector('.replace-with-child-blocks')

    if (!childBlockPlace) {
      return dynamicContent.firstElementChild
    }

    childBlockPlace.replaceWith(ChildBlocks(block))

    return Fragment([
      dynamicContent.firstElementChild,
    ])
  }

  const PostTagReference = [
    {
      tag : 'the_title',
      desc: 'The post title',
    },
    {
      tag : 'the_excerpt',
      desc: 'The post excerpt',
    },
    {
      tag : 'the_url',
      desc: 'The link to the post',
    },
    {
      tag : 'the_thumbnail',
      desc: 'The thumbnail image',
    },
    {
      tag : 'the_thumbnail_url',
      desc: 'The thumbnail image URL',
    },
    {
      tag : 'the_content',
      desc: 'The post content',
    },
    {
      tag : 'the_id',
      desc: 'The post ID',
    },
    {
      tag : 'the_date',
      desc: 'The post publish date',
    },
    {
      tag : 'the_author',
      desc: 'The post author',
    },
    {
      tag : 'read_more',
      desc: 'A "Read more" link to the post',
    },
  ]

  registerDynamicBlock('queryloop', 'Query Loop', {
    svg       : `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <path fill="currentColor" d="M18 7a7.669 7.669 0 0 0-6 3.19A7.669 7.669 0 0 0 6 7c-3.687 0-5 2.583-5 5 0 3.687 2.583 5 5 5a7.669 7.669 0 0 0 6-3.19A7.669 7.669 0 0 0 18 17c2.417 0 5-1.313 5-5 0-2.417-1.313-5-5-5ZM6 15a2.689 2.689 0 0 1-3-3 2.689 2.689 0 0 1 3-3c2.579 0 4.225 2.065 4.837 3-.612.935-2.258 3-4.837 3Zm12 0c-2.579 0-4.225-2.065-4.837-3 .612-.935 2.258-3 4.837-3a2.689 2.689 0 0 1 3 3 2.689 2.689 0 0 1-3 3Z"/>
</svg>`,
    attributes: {
      children: (el) => {
        return parseBlocksFromTable(el.firstElementChild)
      },
    },
    controls  : ({
      gap = 10,
      columns = 2,
      thumbnail_size = 'post-thumbnail',
      updateBlock,
      ...query
    }) => {

      return Fragment([
        ControlGroup({
          id  : 'queryloop-layout',
          name: 'Layout',
        }, [
          Control({
              label: 'Columns',
            },
            NumberControl({
              id       : 'columns',
              className: 'control-input',
              value    : columns,
              step     : 1,
              onInput  : e => updateBlock({ columns: e.target.value }),
            })),
          Control({
              label: 'Gap',
            },
            NumberControl({
              id       : 'column-gap',
              className: 'control-input',
              value    : gap,
              step     : 5,
              unit     : 'px',
              onInput  : e => updateBlock({ gap: e.target.value }),
            })),
          Control({
              label: 'Thumbnail Size',
            },
            Select({
              id      : 'thumbnail-size',
              style   : {
                width: '115px',
              },
              selected: thumbnail_size,
              options : imageSizes.map(size => ( {
                value: size,
                text : size,
              } )),

              onChange: e => updateBlock({ thumbnail_size: e.target.value }),

            })),
        ]),
        QueryControls({
          updateBlock,
          ...query,
        }),
        ControlGroup({
          id  : 'queryloop-reference',
          name: 'Reference',
        }, [
          Pg({}, 'Use the below merge tags to merge post data within the query loop.'),
          ...PostTagReference.map(tag => Div({ className: 'display-flex space-between' }, [
            makeEl('code', {
              className: 'copy-text',
            }, `#${ tag.tag }#`),
            Span({ className: 'tag-desc' }, tag.desc),
          ])),
        ]),
      ])
    },
    edit      : QueryLoopContent,
    html      : ({
      DynamicContent,
      ...block
    }) => {

      if (isGeneratingHTML()) {
        return renderBlocksHTML(block.children)
      }

      return QueryLoopContent({ DynamicContent, ...block })
    },
    plainText : ({ children = [] }) => renderBlocksPlainText(children),
    defaults  : {
      children: [
        createBlock('text', {
          content: `<p>#the_thumbnail#</p>\n<h2>#the_title#</h2>\n<p>#the_excerpt#</p>\n<p>#read_more#</p>`,
        }),
      ],
      columns : 2,
      layout  : 'grid', // grid or list
      // query defaults
      queryId       : '',
      post_type     : 'post',
      number        : 6,
      offset        : 0,
      thumbnail_size: 'post-thumbnail',
    },

  })

  const socialIcons = {
    facebook   : 'Facebook',
    instagram  : 'Instagram',
    linkedin   : 'LinkedIn',
    pinterest  : 'Pinterest',
    reddit     : 'Reddit',
    threads    : 'Threads',
    tiktok     : 'TikTok',
    tumblr     : 'Tumblr',
    twitch     : 'Twitch',
    twitter    : 'X',
    vimeo      : 'Vimeo',
    whatsapp   : 'WhatsApp',
    wordpress  : 'WordPress',
    youtube    : 'YouTube', // email: 'Email',
    github     : 'GitHub',
    truthsocial: 'Truth Social',
    odysee     : 'Odysee',
    discord    : 'Discord',
    rumble     : 'Rumble',
  }

  const socialIconThemes = {
    'brand-boxed'     : 'Brand Colors Square',
    'brand-circle'    : 'Brand Colors Circular',
    'brand-icons'     : 'Brand Colors Icons',
    'black-boxed'     : 'Black Boxed',
    'black-circle'    : 'Black Circular',
    'black-icons'     : 'Black Icons',
    'dark-grey-boxed' : 'Gray Boxed',
    'dark-grey-circle': 'Gray Circle',
    'dark-grey-icons' : 'Gray Icons',
    'grey-boxed'      : 'Gray Boxed',
    'grey-circle'     : 'Gray Circle',
    'grey-icons'      : 'Gray Icons',
    'white-boxed'     : 'White Boxed',
    'white-circle'    : 'White Circular',
    'white-icons'     : 'White Icons',
  }

  /**
   * A Social Icon component
   */
  const SocialIcon = (icon, theme = 'brand-circle', size = 20) => makeEl('img', {
    src: `${ Groundhogg.assets.images }/social-icons/${ theme }/${ icon || 'facebook' }.png`,

    alt   : socialIcons[icon],
    height: size,
    width : size,
    style : {
      verticalAlign: 'bottom',
    },
  })

  /**
   * A Social icon theme component
   */
  const SocialIconTheme = (theme, selected, updateBlock) => {

    let themeIcons = [
      'facebook',
      'instagram',
      'twitter',
    ]

    let {
      use = 'global',
      socials = [],
    } = getActiveBlock()

    if (use === 'global' && globalSocials.length >= 3) {
      themeIcons = globalSocials.map(([social]) => social).slice(0, 3)
    }
    else if (use === 'custom' && socials.length >= 3) {
      themeIcons = socials.map(([social]) => social).slice(0, 3)
    }

    return Button({
        id: `select-${ theme }`,

        title    : socialIconThemes[theme],
        className: `gh-button ${ theme === selected ? 'primary' : 'secondary text' } social-icon-theme ${ theme }`,

        onClick: e => updateBlock({
          theme        : theme,
          morphControls: true,
        }),

      },
      themeIcons.map(icon => SocialIcon(icon, theme, 20)))
  }

  /**
   * Selectable Social Icon Theme
   */
  const SocialLinksRepeater = ({
    socials,
    theme,
    onChange,
  }) => InputRepeater({
    id      : 'social-links',
    rows    : socials,
    cells   : [
      ({
        setValue,
        value,
        id,
        ...props
      }) => Button({
          className: 'gh-button grey icon',
          id,
          onClick  : e => {

            theme = hasActiveBlock() ? getActiveBlock().theme : 'brand-boxed'

            MiniModal({
                selector: `#${ id }`,

              },
              ({ close }) => Div({
                  className: `display-grid social-icon-picker ${ theme }`,

                },
                [
                  ...Object.keys(socialIcons).map(social => Button({
                      title: socialIcons[social],
                      id   : `${ id }-${ social }`,

                      className: 'gh-button secondary text dashicon span-3',
                      onClick  : e => {
                        setValue(social)
                        close()
                      },
                    },
                    SocialIcon(social, theme))),

                ]))
          },
        },
        SocialIcon(value || 'facebook', theme)),
      (
        {
          setValue,
          ...props
        },
        [icon]) => Input({
        type       : 'url',
        placeholder: `https://${ icon }.com/your-profile/`,
        ...props,
      }),

    ],
    sortable: true,
    onChange,
  })

  // Register the socials block
  registerBlock('social', 'Socials', {
    attributes: {
      size: el => parseInt(el.querySelector('img')?.width),

      gap: el => parseInt(el.querySelector('td.gap')?.width),

      theme: el => el.querySelector('img')?.src.split('/').at(-2),

      socials: el => Array.from(el.querySelectorAll('a')).map(el => {
        let png = el.firstElementChild.src.split('/').at(-1)
        return [
          png.substr(0, png.indexOf('.png')),
          el.href,
        ]
      }),

    },
//language=HTML
    svg      : `
        <svg viewBox="-33 0 512 512.001" xmlns="http://www.w3.org/2000/svg">
            <path fill="currentColor"
                  d="M361.824 344.395c-24.531 0-46.633 10.593-61.972 27.445l-137.973-85.453A83.321 83.321 0 0 0 167.605 256a83.29 83.29 0 0 0-5.726-30.387l137.973-85.457c15.34 16.852 37.441 27.45 61.972 27.45 46.211 0 83.805-37.594 83.805-83.805C445.629 37.59 408.035 0 361.824 0c-46.21 0-83.804 37.594-83.804 83.805a83.403 83.403 0 0 0 5.726 30.386l-137.969 85.454c-15.34-16.852-37.441-27.45-61.972-27.45C37.594 172.195 0 209.793 0 256c0 46.21 37.594 83.805 83.805 83.805 24.53 0 46.633-10.594 61.972-27.45l137.97 85.454a83.408 83.408 0 0 0-5.727 30.39c0 46.207 37.593 83.801 83.804 83.801s83.805-37.594 83.805-83.8c0-46.212-37.594-83.805-83.805-83.805zm-53.246-260.59c0-29.36 23.887-53.246 53.246-53.246s53.246 23.886 53.246 53.246c0 29.36-23.886 53.246-53.246 53.246s-53.246-23.887-53.246-53.246zM83.805 309.246c-29.364 0-53.25-23.887-53.25-53.246s23.886-53.246 53.25-53.246c29.36 0 53.242 23.887 53.242 53.246s-23.883 53.246-53.242 53.246zm224.773 118.95c0-29.36 23.887-53.247 53.246-53.247s53.246 23.887 53.246 53.246c0 29.36-23.886 53.246-53.246 53.246s-53.246-23.886-53.246-53.246zm0 0"/>
        </svg>`,
    controls : ({
      socials = [],
      gap = 10,
      size = 24,
      theme = 'brand-circle',
      align = 'center',
      use = 'global',
      updateBlock = () => {},
    }) => {

      if (socials.length === 0) {
        socials = JSON.parse(JSON.stringify(globalSocials))
      }

      return Fragment([
        ControlGroup({
            name: 'Social Media',
          },
          [
            Control({
                label  : 'Theme',
                stacked: true,
              },
              Div({
                  className: 'social-icon-themes-grid',
                },
                [...Object.keys(socialIconThemes).map(t => SocialIconTheme(t, theme, updateBlock))])),
            Control({ label: 'Social Accounts' },
              ButtonToggle({
                id      : 'socials-use',
                options : [
                  {
                    id  : 'global',
                    text: 'Global',
                  },
                  {
                    id  : 'custom',
                    text: 'Custom',
                  },
                ],
                selected: use,
                onChange: use => updateBlock({
                  use,
                  morphControls: true,
                }),

              })),
            use === 'custom' ? SocialLinksRepeater({
              socials,
              theme,
              onChange: socials => updateBlock({
                socials,
              }),

            }) : null,
            use === 'global' ? makeEl('a', {
                href   : '#',
                onClick: e => {
                  setActiveBlock(null)
                  setEmailControlsTab('editor')
                  openPanel('email-editor-global-socials')
                  morphControls()
                },
              },
              'Edit global social accounts') : null,
            Control({
                label: 'Alignment',
              },
              AlignmentButtons({
                id       : 'socials-align',
                alignment: align,
                onChange : align => updateBlock({
                  align,
                  morphControls: true,
                }),

              })),
            Control({
                label: 'Icon Size',
              },
              NumberControl({
                id      : 'icon-size',
                value   : size,
                unit    : 'px',
                onChange: e => updateBlock({ size: e.target.value }),

              })),
            Control({
                label: 'Gap',
              },
              NumberControl({
                id      : 'gap',
                step    : 2,
                value   : gap,
                unit    : 'px',
                onChange: e => updateBlock({ gap: e.target.value }),

              })),

          ]),

      ])

    },
    html     : ({
      align = 'center',
      theme = 'brand-circle',
      socials = [],
      gap = 10,
      size = 24,
      use = 'global',
    }) => {

      if (use === 'global') {
        socials = globalSocials
      }

      if (socials.length === 0) {
        return ''
      }

      socials = socials.map(([icon, link]) => makeEl('a', { href: link },
        SocialIcon(icon, theme, size)))

      let cells = socials.reduce((cells, social, index) => {

          if (index > 0) {
            cells.push(Td({
              className: 'gap',
              width    : gap,
              style    : { width: `${ gap }px` },
            }))
          }

          cells.push(Td({},
            social))

          return cells
        },
        [])

      return Table({
          className  : 'socials',
          cellpadding: 0,
          cellspacing: 0,
          role       : 'presentation',
          width      : '100%',
          style      : {
            width: '100%',
          },
        },
        Tr({},
          Td({
              align,
            },
            Table({
                className  : 'socials',
                cellpadding: 0,
                cellspacing: 0,
                role       : 'presentation',
              },
              Tr({},
                cells)))))
    },
    plainText: ({
      socials = [],
      use = 'global',
    }) => {

      if (use === 'global') {
        socials = globalSocials
      }

      if (socials.length === 0) {
        return ''
      }

      return socials.map(([social, url]) => `[${ social }](${ url })`).join(' | ')
    },
    defaults : {
      align  : 'center',
      theme  : 'brand-circle',
      socials: [],
      gap    : 10,
      size   : 24,
      use    : 'global',
    },
    gutenberg: ({
      socials,
      align,
      use = 'global',
    }) => {

      if (use === 'global') {
        socials = globalSocials
      }

      if (socials.length === 0) {
        return ''
      }

      return Div({}, [
        `<!-- wp:social-links ${ JSON.stringify({
          layout: {
            type          : 'flex',
            justifyContent: align,
          },
        }) } -->`,
        makeEl('ul', { className: 'wp-block-social-links' }, [
          ...socials.map(([service, url]) => `<!-- wp:social-link ${ JSON.stringify({
            url,
            service,
          }) } /-->`),
        ]),
        `<!-- /wp:social-links -->`,
      ]).innerHTML
    },
  })

  // Register the footer block
  registerBlock('footer', 'Footer', {
    // language=HTML
    svg      : `
        <svg id="fi_3596176" enable-background="new 0 0 24 24" height="512" viewBox="0 0 24 24" width="512"
             xmlns="http://www.w3.org/2000/svg">
            <path fill="currentColor"
                  d="m21.5 24h-19c-1.379 0-2.5-1.121-2.5-2.5v-19c0-1.379 1.121-2.5 2.5-2.5h19c1.379 0 2.5 1.121 2.5 2.5v19c0 1.379-1.121 2.5-2.5 2.5zm-19-23c-.827 0-1.5.673-1.5 1.5v19c0 .827.673 1.5 1.5 1.5h19c.827 0 1.5-.673 1.5-1.5v-19c0-.827-.673-1.5-1.5-1.5z"></path>
            <path fill="currentColor"
                  d="m19.5 21h-15c-.827 0-1.5-.673-1.5-1.5v-4c0-.827.673-1.5 1.5-1.5h15c.827 0 1.5.673 1.5 1.5v4c0 .827-.673 1.5-1.5 1.5zm-15-6c-.275 0-.5.225-.5.5v4c0 .275.225.5.5.5h15c.275 0 .5-.225.5-.5v-4c0-.275-.225-.5-.5-.5z"></path>
        </svg>`,
    controls : ({
      style = {},
      linkStyle = {},
      alignment = 'left',
      privacyPolicy = true,
      terms = true,
      tel = true,
      updateBlock,
    }) => {

      return Fragment([
        ControlGroup({ name: 'Footer' },
          [
            Control({ label: 'Alignment' },
              AlignmentButtons({
                id      : 'footer-align',
                alignment,
                onChange: alignment => updateBlock({
                  alignment,
                  morphControls: true,
                }),
              })),
            Control({ label: 'Phone Number' },
              Toggle({
                id      : 'include-tel',
                checked : tel,
                onChange: e => updateBlock({
                  tel: e.target.checked,
                }),
              })),
            Control({ label: 'Privacy Policy' },
              Toggle({
                id      : 'include-privacy-policy',
                checked : privacyPolicy,
                onChange: e => updateBlock({
                  privacyPolicy: e.target.checked,
                }),
              })),
            Control({ label: 'Terms' },
              Toggle({
                id      : 'include-terms',
                checked : terms,
                onChange: e => updateBlock({
                  terms: e.target.checked,
                }),
              })),
          ]),
        TagFontControlGroup('Font Style', 'style', style, updateBlock),
        TagFontControlGroup(__('Link Style'),
          'linkStyle', linkStyle, updateBlock, {
            fontSize  : false,
            lineHeight: false,
          }),

      ])
    },
    html     : ({
      style = {},
      linkStyle = {},
      alignment = 'left',
      tel = true,
      terms = true,
      privacyPolicy = true,
    }) => {

      const footerLine = (content) => makeEl('p', {
          style: {
            ...fillFontStyle(style),
            textAlign: alignment,
            margin   : '0.5em 0',
          },
        },
        content)

      let {
        business_name,
        address,
        links,
        unsubscribe,
      } = _BlockEditor.footer

      let useLinks = []

      if (links.tel && tel) {
        useLinks.push(links.tel)
      }

      if (links.privacy && privacyPolicy) {
        useLinks.push(links.privacy)
      }

      if (links.terms && terms) {
        useLinks.push(links.terms)
      }

      let footer = Div({
          id       : 'footer',
          className: 'footer',
        },
        [
          footerLine(`&copy; ${ business_name }`),

          footerLine(address),

          footerLine(useLinks.join(' | ')),

          getEmailData().message_type !== 'transactional' ? footerLine(unsubscribe) : null,
        ])

      linkStyle = fillFontStyle({
        ...style, ...linkStyle,
      })

      footer.querySelectorAll('a').forEach(el => {
        for (let attr in linkStyle) {
          el.style[attr] = linkStyle[attr]
        }
      })

      return footer
    },
    plainText: ({
      tel = true,
      terms = true,
      privacyPolicy = true,
    }) => {

      let {
        business_name,
        address,
        links,
        unsubscribe,
      } = _BlockEditor.footer

      let useLinks = []

      if (links.tel && tel) {
        useLinks.push(links.tel)
      }

      if (links.privacy && privacyPolicy) {
        useLinks.push(links.privacy)
      }

      if (links.terms && terms) {
        useLinks.push(links.terms)
      }

      return [
        `Copyright ${ business_name }`,
        address,
        extractPlainText(useLinks.join(' | ')),
        extractPlainText(unsubscribe),

      ].join('  \n')
    },
    defaults : {
      style        : fontDefaults({
        fontSize  : 13,
        color     : '#999',
        lineHeight: 1,
      }),
      linkStyle    : {
        color: '#488aff',
      },
      tel          : true,
      terms        : true,
      privacyPolicy: true,
    },
  })

  /**
   *
   * @param blockId
   */
  const getBlockBackgroundColor = (blockId) => {

    let block = __findBlock(blockId, getBlocks())

    // if using the columns block the column style
    if (block.type === 'columns' && block.columnStyle?.backgroundColor) {
      return block.columnStyle.backgroundColor
    }

    if (block.advancedStyle?.backgroundColor) {
      return block.advancedStyle.backgroundColor
    }

    let parent = __findParent(blockId, getBlocks())

    if (parent) {
      return getBlockBackgroundColor(parent.id, getBlocks())
    }

    return ''
  }

  /**
   * Given a selector and new element, morph the dom tree found from the selector to the new element tree
   */
  const morph = (selector, html = null, childrenOnly = true) => {

    try {
      if (html === null) {
        return morphdom(document.getElementById(selector.id),
          selector)
      }

      morphdom(document.querySelector(selector),
        Fragment(html),
        {
          childrenOnly,
        })
    }
    catch (e) {
    }
  }

  /**
   * Initializes the editor with the given email
   *
   * @param email
   * @param onSaveCallback
   * @param onCloseCallback
   */
  const initialize = ({
    email,
    onSave : onSaveCallback = () => {},
    onClose: onCloseCallback = () => {
    },
  }) => {

    onSave = onSaveCallback
    onClose = onCloseCallback

    History.clear()

    let blocks
    let page = 'editor'

    let preview, previewPlainText, previewFromName, previewFromEmail, previewFromAvatar, previewSubject = ''

    if (email?.context?.built) {
      ;( {
        built      : preview,
        plain      : previewPlainText,
        from_name  : previewFromName,
        from_email : previewFromEmail,
        from_avatar: previewFromAvatar,
        subject    : previewSubject,
      } = email.context )
    }

    // existing email not using blocks
    if (email.ID) {

      switch (email.context.editor_type) {

        case 'blocks':

          // back compat for us, public will never use this
          if (Array.isArray(email.meta.blocks) && email.meta.blocks.length) {
            blocks = email.meta.blocks
            setEmailMeta({
                blocks: true,
              },
              false)
          }
          else {
            blocks = parseBlocksFromContent(email.data.content)
          }

          break
        case 'legacy_blocks':
          blocks = parseBlocksFromContent(email.data.content)
          break
        case 'legacy_plain':

          blocks = [
            createBlock('text', {
              content: email.data.content,
            }),

          ]

          break
        case 'html':

          setState({
            page: 'html-editor',
            email,

            preview,
            previewPlainText,
            previewFromName,
            previewFromEmail,
            previewFromAvatar,
            previewSubject,
          })

          setHTML(email.data.content, false)

          renderEditor()

          syncReplacementCodes()

          return
      }

    }
    // Creating a new email
    else {

      email.data = {
        title       : 'My new email',
        subject     : '',
        pre_header  : '',
        from_select : 0,
        status      : 'draft',
        message_type: 'marketing',
      }

      email.meta = {
        alignment      : 'left',
        backgroundColor: '',
        frameColor     : '',
        width          : 600,
        custom_headers : {},
      }

      blocks = [
        createBlock('text', {
          //language=HTML
          content: `<p>Hi {first::there},</p>
          <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin egestas dolor non nulla varius, id fermentum ante euismod. Ut a sodales nisl, at
              maximus felis. Suspendisse potenti. Etiam fermentum magna nec diam lacinia, ut volutpat mauris accumsan. Nunc id convallis magna.</p>
          <p>Regards,</p>
          <p>{owner_signature}</p>`,
        }),
        createBlock('footer'),
      ]

      page = 'templates'
    }

    if (!email.meta.template) {
      email.meta.template = BOXED
    }

    setState({
      page,
      activeBlock     : null,
      openPanels      : {},
      blockControlsTab: 'block',
      emailControlsTab: 'email',
      isGeneratingHTML: false,
      email,

      // preview stuff
      preview,
      previewPlainText,
      previewFromName,
      previewFromEmail,
      previewFromAvatar,
      previewSubject,
    })

    setBlocks(blocks, false)

    syncReplacementCodes()

    renderEditor()
  }

  function optimizeCSS (css) {
    const ruleMap = {}

    // Basic rule splitting
    css.replace(/\/\*[\s\S]*?\*\//g, '') // Remove comments
      .split('}').forEach(rule => {
      const [selectors, declarations] = rule.split('{')
      if (!selectors || !declarations) {
        return
      }

      const cleaned = declarations.trim().replace(/\s+/g, ' ').replace(/;$/, '')
      if (!cleaned) {
        return
      }

      const key = cleaned
      ruleMap[key] = ruleMap[key] || []
      ruleMap[key].push(selectors.trim())
    })

    // Rebuild optimized CSS
    let optimized = ''
    for (const [declarations, selectors] of Object.entries(ruleMap)) {
      optimized += `${ selectors.join(',') }{${ declarations }}`
    }

    return optimized
  }

  /**
   * Compiles the CSS rules for each of the blocks
   *
   * @param blocks
   * @return {*}
   */
  const renderBlocksCSS = (blocks) => {
    let css = blocks.map(b => BlockRegistry.css(b)).join('\n').replaceAll(/(\s*\n|\s*\r\n|\s*\r){1,}/g, '\n')
    css = optimizeCSS(css)
    return css
  }

  /**
   * Add replacement protocol to trick wp_kses
   *
   * @returns {*|string}
   * @param el
   * @param tag
   * @param attr
   */
  const maybeAddReplacementProtocols = (el, tag, attr) => {
    el.querySelectorAll(tag).forEach(el => {

      let value = el.getAttribute(attr)
      if (!value || !value.startsWith('{')) {
        return
      }

      el.setAttribute(attr, `replacement:${ value }`)
    })
  }

  function fixStyleQuotes (htmlString) {
    return htmlString.replace(
      // capture style="…"
      /(\bstyle\s*=\s*")([^"]*)(")/gi,
      (_, prefix, contents, suffix) =>
        // replace inside only the contents
        prefix + contents.replace(/&quot;/g, '\'') + suffix,
    )
  }

  /**
   * Renders the blocks in their final HTML format
   *
   * @param blocks
   * @return {string}
   */
  const renderBlocksHTML = (blocks) => {
    let htmlEl = Table({
        cellpadding: '0',
        cellspacing: '0',
        width      : '100%',
        role       : 'presentation',
      },
      blocks.filter(b => b.type).map(block => BlockHTML(block)))

    maybeAddReplacementProtocols(htmlEl, 'img', 'src')
    maybeAddReplacementProtocols(htmlEl, 'a', 'href')

    let html = htmlEl.outerHTML
    html = html.replaceAll(new RegExp(`&quot;(${ subFontsWithSpaces.join('') })&quot;`,
        'g'),
      '\'$1\'')

    html = fixStyleQuotes(html)

    return html
  }

  /**
   * Renders the blocks in their final HTML format
   *
   * @param blocks
   * @return {string}
   */
  const renderBlocksGutenberg = (blocks) => {
    return blocks.filter(b => b.type).map(block => {

      let text

      let {
        hide_on_desktop = false,
      } = block

      if (hide_on_desktop) {
        return ''
      }

      try {
        text = BlockRegistry.get(block.type).gutenberg(block)
      }
      catch (e) {
        text = ''
      }

      return text
    }).filter(text => text.length > 0).join('\n\n').replaceAll(/(\n|\r\n|\r){3,}/g, '\n\n')
  }

  /**
   * Renders the blocks as plain text
   *
   * @param blocks
   * @return {string}
   */
  const renderBlocksPlainText = (blocks) => {
    return blocks.filter(b => b.type).map(block => {

      let text

      let {
        hide_on_desktop = false,
      } = block

      if (hide_on_desktop) {
        return ''
      }

      try {
        text = BlockRegistry.get(block.type).plainText(block)
        return [
          BlockStartComment(block),
          text,
          BlockEndComment(block),
        ].join('')
      }
      catch (e) {
        return ''
      }
    }).filter(text => text.length > 0).join('\n\n').replaceAll(/(\n|\r\n|\r){3,}/g, '\n\n')
  }

  // These functions help render various UI components

  const morphBlocks = () => {
    if (isHTMLEditor()) {
      return
    }

    morph('#builder-content', BlockEditorContent())
  }
  const removeControls = () => morph('#controls-panel', Div())
  const morphControls = () => morph('#controls-panel', ControlsPanel())
  const morphContent = () => morph('#content', ContentEditor())
  const morphBlockEditor = () => morph('#email-block-editor', BlockEditor())
  const morphEmailEditor = () => {
    morph('#email-editor', EmailEditor(),
      false)
    window.dispatchEvent(new Event('resize'))
  }
  const morphHeader = () => morph('#email-header', Header())
  const updateStyles = () => {
    $('#builder-style').text(`#block-editor-content-wrap{ \n\n${ renderBlocksCSS(getBlocks()) }\n\n${ getEmailMeta().template_css ?? '' }\n\n }`)
  }

  const updateStylesDebounced = Groundhogg.functions.debounce(updateStyles, 300)

  const renderEditor = () => {
    morphEmailEditor()
    updateStyles()
  }

  /**
   * Converts HTML from the legacy block editor to the new block editor
   *
   * @param nodes
   * @return {*[]}
   */
  const parseBlocksFromLegacyBlockEditor = (nodes) => {

    let blocks = []

    let timeout

    const parseBlock = node => {
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
            src  : img.src,
            alt  : img.alt,
            title: img.title,
            width: img.width,
            link : a.href,
          })

          break
        case 'text':
        case 'text_block':

          let textContainer = node.querySelector('.text_block').firstElementChild

          text = textContainer.innerHTML

          let props = {
            content: text,
            p      : fontDefaults({
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
              fontSize  : parseInt(el.style.fontSize),
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
            text           : a.innerHTML,
            link           : a.href,
            backgroundColor: button.getAttribute('bgcolor'),
            style          : {
              color   : a.style.color,
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

      return block
    }

    nodes.forEach(node => {

      // Skip text nodes
      if (node.nodeType === Node.TEXT_NODE) {
        return
      }

      try {
        blocks.push(parseBlock(node))
      }
      catch (e) {

        clearTimeout(timeout)

        setTimeout(() => {
            dialog({
              type   : 'error',
              message: 'Some blocks could not be converted to the new editor.',
            })
          },
          100)

      }
    })

    return blocks
  }

  /**
   * Given some content, parse the blocks from it
   * @param content
   */
  const parseBlocksFromContent = content => {
    const parser = new DOMParser()
    const doc = parser.parseFromString(content, 'text/html')

    const parsers = [
      doc => parseBlocksFromTable(doc.body.firstElementChild),
      doc => parseBlocksFromLegacyBlockEditor(doc.body.childNodes),
    ]

    let blocks = []

    for (let parser of parsers) {
      try {
        blocks = parser(doc)

        if (blocks && blocks.length) {
          return blocks
        }

      }
      catch (e) {
        // console.log(e)
      }
    }

    return []
  }

  /**
   * Given a table, parse the blocks in it
   *
   * @param table
   * @return {*[]}
   */
  const parseBlocksFromTable = table => {

    let blocks = []

    let rows = table.querySelector('tbody')?.childNodes

    if (!rows) {
      return []
    }

    for (let row of rows) {

      let block = parseBlockFromRow(row)

      if (block) {
        blocks.push(block)
      }
    }

    return blocks
  }

  /**
   * Given a TR which contains the block, as well as the comments, return a block
   *
   * @param tr
   * @return {any}
   */
  const parseBlockFromRow = tr => {
    let comment = tr.firstChild
    let commentData = removeFontQuotesFromCommentData(comment?.nodeValue?.trim())

    if (!commentData) {
      return null
    }

    let attributes = {}
    let unused, type, id, json

    try {
// has json
      if (commentData.indexOf('{') > -1) {
        [
          unused,
          type,
          id,
          json,
        ] = commentData.match(/^([a-z]+):([a-zA-Z0-9\-]+) ({.*})$/)
        attributes = JSON.parse(json)
      }
      else {
        [
          unused,
          type,
          id,
        ] = commentData.match(/^([a-z]+):([a-zA-Z0-9\-]+)$/)
      }

    }
    catch (e) {
      // console.log({
      //   err: e,
      //   commentData,
      //   type,
      //   id,
      // })
    }

    const BlockType = BlockRegistry.get(type)

    const getAttributes = BlockType.attributes
    const el = tr.querySelector(`td#b-${ id }`)

    let block = {
      type,
      id, ...attributes,
    }

    for (let getter in getAttributes) {
      try {
        block[getter] = getAttributes[getter](el, block)
      }
      catch (e) {
        block[getter] = BlockType.defaults[getter]
      }
    }

    // backwards compatibility for old dynamic block format
    let matches = el.innerText.trim().match(/^\[([a-z]+):([a-zA-Z0-9\-]+):dynamicContent ({.*})\/\]$/)
    if (matches && matches.length) {
      let compatDynamicAttrs = JSON.parse(matches[3])
      if (compatDynamicAttrs) {
        block = {
          ...block,
          ...compatDynamicAttrs,
        }
      }
    }

    block.advancedStyle = AdvancedStyleControls.parse(el)
    block.hide_on_mobile = el.classList.contains('hide-on-mobile')
    block.hide_on_desktop = el.classList.contains('hide-on-desktop')
    block.hide_in_browser = el.classList.contains('hide-in-browser')

    return block
  }

  const convertToGutenbergBlocks = content => {

    if (!getState().gutenbergInitialized) {
      setState({
        gutenbergInitialized: true,
      })

      let gutenbergContainer = Div({
        id   : 'gutenberg-temp',
        style: {
          display: 'none',
        },
      })

      document.querySelector('body').append(gutenbergContainer)

      initializeEditor('gutenberg-temp')
    }

    let blocks = rawHandler({ HTML: content })

    return serialize(blocks)
  }

  // Add the builder style to the document <head>
  $('head').append(`<style id="builder-style" type="text/css"></style>`)

  // If we're in the email editor, and not the funnel editor add the unload message
  if (isEmailEditorPage()) {
    window.addEventListener('beforeunload', e => {

      if (getState().hasChanges) {
        e.preventDefault()
        let msg = __('You have unsaved changes, are you sure you want to leave?', 'groundhogg')
        e.returnValue = msg
        return msg
      }

      return null
    })
  }

  // Fill global fonts if none defined
  if (!globalFonts || !Array.isArray(globalFonts) || !globalFonts.length) {
    GlobalFonts.fonts = [
      {
        name : 'Paragraph',
        id   : uuid(),
        style: fontDefaults({}),

      },
      {
        name : 'Heading 1',
        id   : uuid(),
        style: fontDefaults({
          fontSize: 42,
        }),

      },
      {
        name : 'Heading 2',
        id   : uuid(),
        style: fontDefaults({
          fontSize: 36,
        }),

      },
      {
        name : 'Heading 3',
        id   : uuid(),
        style: fontDefaults({
          fontSize: 24,
        }),

      },
    ]
  }
  else {
    GlobalFonts.fonts = globalFonts
  }

  // Fill color palette if not custom
  if (!colorPalette || !Array.isArray(colorPalette) || !colorPalette.length) {
    // Default WordPress colors
    colorPalette = [
      '#000',
      '#fff',
      '#dd3333',
      '#DD9933',
      '#EEEE22',
      '#81D742',
      '#1E73BE',
      '#8224E3',
    ]
  }

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

  function selectText (node) {

    if (document.body.createTextRange) {
      const range = document.body.createTextRange()
      range.moveToElementText(node)
      range.select()
    }
    else if (window.getSelection) {
      const selection = window.getSelection()
      const range = document.createRange()
      range.selectNodeContents(node)
      selection.removeAllRanges()
      selection.addRange(range)
    }
    else {
      console.warn('Could not select text in node: Unsupported browser.')
    }
  }

  $(document).on('click', 'code.copy-text', e => {
    selectText(e.currentTarget)
    navigator.clipboard.writeText(e.currentTarget.innerText)
    dialog({
      message: 'Copied to clipboard!',
    })
  })

  Groundhogg.EmailEditor = initialize
  Groundhogg.emailEditor = {
    PostTagReference,
    registerBlock,
    registerDynamicBlock,
    getActiveBlock,
    isActiveBlock,
    isEditing,
    isCreating,
    isBlockEditor,
    isHTMLEditor,
    functions : {
      optimizeCSS,
    },
    components: {
      Control,
      ControlGroup,
      NumberControl,
      ImageControls,
      FontControls,
      ColorPicker,
      BorderControlGroup,
      BorderControls,
      TopRightBottomLeft,
    },
  }

} )(jQuery)
