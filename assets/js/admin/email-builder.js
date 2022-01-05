(($) => {

  const {
    el, objectToStyle, icons, inputWithReplacements, uuid, tinymceElement,
    specialChars,
    improveTinyMCE,
    textarea,
    modal,
    input,
    clickedIn,
    select,
    copyObject,
  } = Groundhogg.element
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { __, _x, _n, _nx, sprintf } = wp.i18n
  const { linkPicker } = Groundhogg.pickers
  const { api } = Groundhogg

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

  let MAX_EMAIL_WITH = 600

  const createBlock = (type, props) => {
    return {
      id: uuid(),
      type,
      ...BlockRegistry.blocks[type].defaults,
      ...props
    }
  }

  const BlockEditor = (el, {
    email,
    onChange,
    emailControls,
    emailControlsOnMount,
    scrollDepth,
    onScroll,
    onMount
  }) => ({

    $el: $(el),
    email,

    init () {

      $('head').append(`<style id="builder-style" type="text/css"></style>`)
      this.blocks = email.meta.blocks ? email.meta.blocks : [createBlock('text', {
        content: email.data.content
      })]
      this.scrollDepth = scrollDepth
      this.render()
    },

    editingBlock: false,

    templates: {

      css: () => {

      },

      blockControls: (block) => {

        //language=HTML
        return `
			<div class="block-controls">
				<h3>${BlockRegistry.get(block.type).name}</h3>
				${BlockRegistry.controls(block)}
			</div>`
      },

      block: ({ type, name, svg }) => {
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
      },

      editor: () => {
        // language=HTML
        return `
			<div id="email-block-editor">
				<!-- BLOCKS -->
				<div id="blocks-panel"></div>
				<!-- CONTENT -->
				<div id="content" class="gh-panel">
					<div class="inside">
						<div class="inline-label">
							<label for="subject">${__('Subject:', 'groundhogg')}</label>
							${inputWithReplacements({
								id: 'subject',
								name: 'subject',
								placeholder: 'Subject line...',
								value: email.data.subject || '',
							})}
						</div>
						<div class="inline-label">
							<label for="preview-text">${__('Preview:', 'groundhogg')}</label>
							${inputWithReplacements({
								id: 'preview-text',
								name: 'pre_header',
								placeholder: 'Preview text...',
								value: email.data.pre_header || '',
							})}
						</div>
					</div>
					<div id="builder-content-wrap">
						<div id="builder-content"
						     class="inside sortable-blocks ${email.meta.alignment === 'center' ? 'center' : ''}"></div>
					</div>
				</div>
				<!-- CONTROLS -->
				<div id="controls-panel"></div>
			</div>`
      }
    },

    render () {

      this.$el.html(this.templates.editor.call(this))
      this.onMount()

    },
    onMount () {

      /**
       * Add a block to the main column structure
       *
       * @param type the block type
       * @param index
       * @param parent
       * @param column
       */
      const addBlock = (type, index = 0, parent = false, column = 0) => {

        let newBlock = {
          id: uuid(),
          type,
          ...BlockRegistry.blocks[type].defaults
        }

        __insertBlock(newBlock, index, this.blocks, parent, column)

        emailUpdated('addBlock')

        this.render()
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

        let block = __findBlock(blockId, this.blocks)

        __deleteBlock(blockId, this.blocks)

        __insertBlock(block, index, this.blocks, parent, column)

        emailUpdated('moveBlock')

        this.render()
      }

      /**
       * Duplicate a block
       */
      const duplicateBlock = (blockId) => {

        let block = __findBlock(blockId, this.blocks)

        __insertAfter(__replaceId(copyObject(block)), blockId, this.blocks)

        emailUpdated('duplicateBlock')

        this.render()
      }

      /**
       * When any email update change occurs
       */
      const emailUpdated = (where) => {

        console.log(where)

        onChange({
          css: renderBlocksCSS(this.blocks),
          html: renderBlocks(this.blocks, false),
          blocks: this.blocks
        })

      }

      /**
       * Update the blocks with the edited block
       *
       * todo support blocks in columns
       */
      const __updateBlocks = (blocks, edited) => {
        return blocks.map(block => {

          if (block.id === edited.id) {
            return edited
          }

          if (block.type === 'columns') {
            block.columns = block.columns.map(column => __updateBlocks(column, edited))
          }

          return block

        })

      }

      /**
       * Update a block arbitrarily
       *
       * @param blockId
       * @param props
       * @private
       */
      const __updateBlock = (blockId, props) => {

        let block = __findBlock(blockId, this.blocks)

        block = {
          ...block,
          ...props
        }

        this.blocks = __updateBlocks(this.blocks, block)
      }

      /**
       * Update the current block being edited
       *
       * todo support blocks in columns
       *
       * @param props
       * @param reRenderBlocks
       * @param reRenderEditor
       */
      const updateBlock = (props, reRenderBlocks = true, reRenderEditor = false) => {
        this.editingBlock = {
          ...this.editingBlock,
          ...props
        }

        this.blocks = __updateBlocks(this.blocks, this.editingBlock)

        if (reRenderBlocks) {
          renderBlockEditorBlocks()
        }

        if (reRenderEditor) {
          this.render()
        }

        emailUpdated('updateBlock')
      }

      /**
       * Retrieves the current block being edited
       *
       * @return {boolean}
       */
      const curBlock = () => {
        return this.editingBlock
      }

      /**
       * Set the block to show in the controls panel
       *
       * @param blockId
       */
      const editBlock = (blockId) => {

        this.editingBlock = __findBlock(blockId, this.blocks)

        this.render()
      }

      /**
       * Get a block id from an target's dataset given an event
       *
       * @param e
       * @return {string}
       */
      const getBlockId = (e) => {
        return e.currentTarget.dataset.id
      }

      /**
       * Delete a block
       */
      const deleteBlock = (blockId) => {

        __deleteBlock(blockId, this.blocks)

        if (blockId === this.editingBlock.id) {
          this.editingBlock = null
        }

        emailUpdated('deleteBlock')

        this.render()
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

          if (block.columns) {
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

        if (block.columns) {

          console.log(block)

          block.columns = block.columns.map(column => column.map(_block => __replaceId(_block)))

          console.log(block)

        }

        return {
          ...block,
          id: uuid()
        }

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

          if (block.columns) {
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
          if (block.id === parent && block.columns) {
            return __insertBlock(newBlock, index, block.columns[column])
          }

          if (block.columns) {
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

          if (block.columns) {
            for (let column of block.columns) {
              let found = __findBlock(blockId, column)
              if (found) {
                return found
              }
            }
          }
        }

        return false

      }

      /**
       * Get a block from the main structure given and ID
       *
       * @param id
       * @return {(*&{id: *, type: string}) | (*&{id: *, type: string}) | (*&{id: *, type: string}) | (*&{id: *, type: string})}
       */
      const getBlock = (id) => {
        return __findBlock(id, this.blocks)
      }

      this.$el.find('#blocks-panel').html(Object.values(BlockRegistry.blocks).map(b => this.templates.block(b)))

      /**
       * Render the drag and drop builder and setup any event listeners
       */
      const renderBlockEditorBlocks = () => {
        $('#builder-style').text(renderBlocksCSS(this.blocks))

        const sortableHelper = (e, $el) => {
          let blockId = $el.data('id')
          let columnBlockId = $el.closest('.email-column').data('id')
          let column = $el.closest('.email-column').data('col')

          let block = getBlock(blockId, columnBlockId, column)

          return `
			<div class="block gh-panel" data-id="${blockId}">
				<div class="icon">
					${BlockRegistry.blocks[block.type].svg}
				</div>
			</div>`
        }

        this.$el.find('#builder-content').html(renderBlocks(this.blocks, this.editingBlock ? this.editingBlock : null))

        this.$el.find('#builder-content-wrap').on('click', (e) => {
          if (!clickedIn(e, '.builder-block')) {
            editBlock(false)
          }
        })

        $('.sortable-blocks').sortable({

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
            top: 5
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

        /**
         * When block is clicked make editing
         */
        this.$el.find('#builder-content .builder-block').on('click', (e) => {

          let blockId = getBlockId(e)

          if (this.editingBlock && blockId === this.editingBlock.id) {
            return
          }

          if (clickedIn(e, 'td.column .builder-block') && $(e.currentTarget).data('type') === 'columns') {
            return
          }

          editBlock(blockId)

        })

        $(document).on('click', '#builder-content a', e => {
          e.preventDefault()
        })

        if (this.editingBlock) {
          BlockRegistry.blocks[this.editingBlock.type].editOnMount({
            ...this.editingBlock,
            updateBlock,
            curBlock
          })
        }
      }

      this.$el.find('#blocks-panel .block').draggable({
        connectToSortable: '.sortable-blocks',
        helper: 'clone',
        revert: 'invalid',
        revertDuration: 0,
        start: (e, ui) => {
          ui.helper.addClass('dragging')
        }
      })

      renderBlockEditorBlocks()

      $('.block-toolbar .delete-block').on('click', (e) => {
        deleteBlock($(e.target).closest('.builder-block').data('id'))
      })

      $('.block-toolbar .duplicate-block').on('click', (e) => {
        duplicateBlock($(e.target).closest('.builder-block').data('id'))
      })

      if (this.editingBlock) {

        this.$el.find('#controls-panel').html(this.templates.blockControls(this.editingBlock))

        BlockRegistry.controlsOnMount({
          ...this.editingBlock,
          updateBlock,
          curBlock
        })
      } else {
        this.$el.find('#controls-panel').html(emailControls())
        emailControlsOnMount()
      }

      $('.control-group > .control-group-header').on('click', e => {
        $('.control-group:not(.closed)').toggleClass('closed').toggleClass('gh-panel')
        $(e.currentTarget).parent().toggleClass('closed').toggleClass('gh-panel')
      })

      this.$el.find('#content').on('scroll', e => {
        this.scrollDepth = $(e.target).scrollTop()
        onScroll(this.scrollDepth)
      })

      if (this.scrollDepth > 0) {
        this.$el.find('#content').scrollTop(this.scrollDepth)
      }

      onMount()

    },

  })

  const BlockRegistry = {

    isDynamic (block) {
      return this.get(block.type).dynamic
    },

    get (type) {
      return this.blocks[type]
    },

    css (block) {
      return this.get(block.type).css({
        ...this.defaults(block),
        ...block
      })
    },

    edit (block, editing) {

      if (this.isDynamic(block)) {
        this.fetchDynamicContent(block)
      }

      return this.get(block.type).edit({
        ...this.defaults(block),
        ...block
      }, editing)
    },

    defaults ({ type }) {
      return this.get(type).defaults
    },

    editOnMount ({ type, ...props }) {
      return this.get(type).editOnMount(props)
    },

    __fetchDynamicContent (block) {
      return api.post(`${api.routes.v4.emails}/blocks/${block.type}`, {
        props: block
      })
    },

    fetchDynamicContent (block) {

      if (block.html) {
        return
      }

      this.__fetchDynamicContent(block).then(({ html, css = '' }) => {
        block.html = html
        block.css = css

        $(`[data-id="${block.id}"]`).html(html)
      })
    },

    html (block, editing) {

      if (this.isDynamic(block)) {
        this.fetchDynamicContent(block)
      }

      return this.get(block.type).html({
        ...this.defaults(block),
        ...block
      }, editing)
    },

    controls (block) {
      return this.get(block.type).controls({
        ...this.defaults(block),
        ...block
      })
    },

    controlsOnMount ({ type, ...props }) {
      return this.get(type).controlsOnMount({
        type,
        ...this.defaults({ type }),
        ...props
      })
    },

    collections: {
      core: 'Groundhogg'
    },

    blocks: {},

  }

  const blockToolbar = () => {
    // language=HTML

    return `
		<div class="block-toolbar">
			<button class="gh-button secondary small text icon move-block" style="color: #fff">${icons.move}</button>
			<button class="gh-button secondary small text icon duplicate-block" style="color: #fff">${icons.duplicate}
			</button>
			<button class="gh-button secondary small text icon delete-block" style="color: #fff">${icons.close}</button>
		</div>`
  }

  const blockWrapper = (block, editing) => {
    // language=HTML
    return `
		<div class="builder-block" data-id="${block.id}" data-type="${block.type}">
			${BlockRegistry.html(block, editing)}
			${editing === false ? '' : blockToolbar()}
		</div>`
  }

  const editBlockWrapper = (block, editing) => {
    // language=HTML
    return `
		<div class="builder-block is-editing" data-id="${block.id}" data-type="${block.type}">
			${BlockRegistry.edit(block, editing)}
			${blockToolbar()}
		</div>`
  }

  const renderBlocksCSS = (blocks) => {
    return blocks.filter(b => b.type).map(b => BlockRegistry.css(b)).join('')
  }

  const renderBlocks = (blocks, editing) => {
    return blocks.filter(b => b.type).map(b => editing && editing.id === b.id ? editBlockWrapper(b, editing) : blockWrapper(b, editing)).join('')
  }

  /**
   * Register a new block
   *
   * @param type
   * @param name
   * @param block
   * @param collection
   */
  const registerBlock = (type, name, block, collection = 'core') => {

    BlockRegistry.blocks[type] = {
      type,
      name,
      collection,
      ...block
    }

  }

  const registerDynamicBlock = (type, name, block, collection = 'core') => {

    const {
      svg,
      controls,
      controlsOnMount,
      defaults,
    } = block

    BlockRegistry.blocks[type] = {
      type,
      name,
      collection,
      svg,
      dynamic: true,
      edit: ({ html = '' }) => html,
      editOnMount: ({}) => {},
      controls: (props) => {
        return controls(props)
      },
      controlsOnMount: ({ updateBlock, curBlock, ...rest }) => {
        controlsOnMount({
          ...rest,
          curBlock,
          updateBlock: (props, a, b) => {

            BlockRegistry.__fetchDynamicContent({
              ...curBlock(),
              ...props
            }).then(({ html, css = '' }) => {
              updateBlock({
                html,
                css,
                ...props
              }, a, b)
            })
          }
        })
      },
      css: () => '',
      /**
       * When rendering the final version editing will be false and we'll use the comment for server side replacement
       * Rather than the HTML content
       *
       * @param id the id of the block
       * @param html cached content from the API
       * @param editing will be false when rendering the final version
       * @return {string|*}
       */
      html: ({ id, html }, editing) => editing === false ? `<!-- ${type}:${id} -->` : html,
      defaults: {
        html: '',
        css: '',
        ...defaults
      },

    }
  }

  /**
   * Register a new block collection
   *
   * @param collection
   * @param name
   */
  const registerBlockCollection = (collection, name) => {
    BlockRegistry.collections[collection] = name
  }

  const Column = ({ blocks, editing, width, col, padding }) => {
    //language=HTML
    return `
		<td align="center" valign="top" width="${width}%">
			<div class="column sortable-blocks ${blocks.length ? '' : 'empty'}"
			     data-col="${col}">
				${renderBlocks(blocks, editing)}
			</div>
		</td>`
  }

  const columnLayouts = {
    three_columns: (columns, editing) => {
      //language=HTML
      return [0, 1, 2].map(i => Column({
        blocks: columns[i],
        editing,
        width: 33.3333,
        col: i
      })).join('')
    },
    two_columns: (columns, editing) => {
      return [0, 1].map(i => Column({
        blocks: columns[i],
        editing,
        width: 50,
        col: i
      })).join('')
    },
    two_columns_right: (columns, editing) => {
      return [66.666, 33.333].map((w, i) => Column({
        blocks: columns[i],
        editing,
        width: w,
        col: i
      })).join('')
    },
    two_columns_left: (columns, editing) => {
      return [33.333, 66.666].map((w, i) => Column({
        blocks: columns[i],
        editing,
        width: w,
        col: i
      })).join('')
    },
  }

  registerBlock('columns', __('Columns'), {
    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.667 426.667"
		     style="enable-background:new 0 0 426.667 426.667" xml:space="preserve"><path d="M384 21.333h-42.667c-23.552 0-42.667 19.157-42.667 42.667v298.667c0 23.531 19.115 42.667 42.667 42.667H384c23.552 0 42.667-19.136 42.667-42.667V64c0-23.509-19.115-42.667-42.667-42.667zM234.667 21.333H192c-23.552 0-42.667 19.157-42.667 42.667v298.667c0 23.531 19.115 42.667 42.667 42.667h42.667c23.552 0 42.667-19.136 42.667-42.667V64c-.001-23.509-19.115-42.667-42.667-42.667zM85.333 21.333H42.667C19.136 21.333 0 40.491 0 64v298.667c0 23.531 19.136 42.667 42.667 42.667h42.667c23.531 0 42.667-19.136 42.667-42.667V64C128 40.491 108.864 21.333 85.333 21.333z"/></svg>`,
    controls: ({ layout = 'two_columns' }) => {

      const layoutChoices = {
        three_columns: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="23.085 13.971 499.999 150"><path d="M28.085 13.971h143.333a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H28.085a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5ZM201.418 13.971h143.333a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H201.418a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5ZM374.751 13.971h143.333a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H374.751a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5Z" transform="matrix(1 0 0 .6 0 5.588)"/></svg>`,
        two_columns: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="569.217 10.755 500 150"><path d="M574.217 10.755h230a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5h-230a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5ZM834.217 10.755h230a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5h-230a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5Z" transform="matrix(1 0 0 .6 0 4.302)"/></svg>`,
        two_columns_right: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="24.417 277.63 499.999 150"><path d="M29.417 277.63h316.667a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H29.417a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5ZM376.083 277.63h143.333a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H376.083a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5Z" transform="matrix(1 0 0 .6 0 111.052)"/></svg>`,
        two_columns_left: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="568.076 279.146 500 150"><path d="M746.409 279.146h316.667a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H746.409a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5ZM573.076 279.146h143.333a5 5 0 0 1 5 5v240a5 5 0 0 1-5 5H573.076a5 5 0 0 1-5-5v-240a5 5 0 0 1 5-5Z" transform="matrix(1 0 0 .6 0 111.658)"/></svg>`,
      }

      //language=HTML
      return `
		  <div class="control-group gh-panel">
			  <div class="control-group-header space-between">
				  <h4 class="control-group-name">${__('Layout')}</h4>
				  <span class="dashicons dashicons-arrow-down-alt2"></span>
			  </div>
			  <div class="controls">
				  <div class="control layouts">
					  ${Object.keys(layoutChoices).map(k => `<button class="layout-choice ${layout === k ? 'selected' : ''}" data-layout="${k}">${layoutChoices[k]}</button>`).join('')}
				  </div>
			  </div>
		  </div>
      `
    },
    controlsOnMount: ({ updateBlock }) => {

      $('.layout-choice').on('click', (e) => {
        updateBlock({
          layout: e.currentTarget.dataset.layout
        }, false, true)
      })

    },
    edit: ({ id, columns, style, layout = 'two_columns' }, editing) => {
      //language=HTML
      return `
		  <table class="email-columns" border="0" cellpadding="0" cellspacing="0" width="100%">
			  <tr>
				  ${columnLayouts[layout](columns, editing)}
			  </tr>
		  </table>
      `
    },
    css: ({ columns }) => {
      //language=CSS
      return `${columns.map(col => col.length ? renderBlocksCSS(col) : '').join('')}`
    },
    editOnMount: () => {},
    html: ({ id, columns, style, layout = 'two_columns' }, editing) => {

      //language=HTML
      return `
		  <table class="email-columns" border="0" cellpadding="0" cellspacing="0" width="100%">
			  <tr>
				  ${columnLayouts[layout](columns, editing)}
			  </tr>
		  </table>
      `
    },
    defaults: {
      layout: 'two_columns',
      columns: [
        [],
        [],
        []
      ]
    }
  })

  registerBlock('text', __('Text'), {
    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" style="enable-background:new 0 0 977.7 977.7" xml:space="preserve"
		     viewBox="0 0 977.7 977.7"><path d="M770.7 930.6v-35.301c0-23.398-18-42.898-41.3-44.799-17.9-1.5-35.8-3.1-53.7-5-34.5-3.6-72.5-7.4-72.5-50.301L603 131.7c136-2 210.5 76.7 250 193.2 6.3 18.7 23.8 31.3 43.5 31.3h36.2c24.9 0 45-20.1 45-45V47.1c0-24.9-20.1-45-45-45H45c-24.9 0-45 20.1-45 45v264.1c0 24.9 20.1 45 45 45h36.2c19.7 0 37.2-12.6 43.5-31.3 39.4-116.5 114-195.2 250-193.2l-.3 663.5c0 42.9-38 46.701-72.5 50.301-17.9 1.9-35.8 3.5-53.7 5-23.3 1.9-41.3 21.4-41.3 44.799v35.3c0 24.9 20.1 45 45 45h473.8c24.8 0 45-20.199 45-45z"/></svg>`,
    controls: ({ p, h1, h2, h3 }) => {
      // language=HTML

      const textControlGroup = (name, tag, style) => {

        //language=HTML
        return `
			<div class="control-group closed">
				<div class="control-group-header space-between">
					<h4 class="control-group-name">${name}</h4>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</div>
				<div class="controls">
					<div class="space-between">
						<label for="font-size" class="control-label">${__('Font Size', 'groundhogg')}</label>
						${input({
							type: 'number',
							id: 'font-size',
							name: 'fontSize',
							className: 'font-control control-input',
							dataTag: tag,
							value: style.fontSize
						})}
					</div>
					<div class="space-between">
						<label for="font-weight" class="control-label">${__('Font Weight', 'groundhogg')}</label>
						${select({
							id: 'font-weight',
							name: 'fontWeight',
							className: 'font-control control-input',
							dataTag: tag,
						}, fontWeights.map(i => ({ value: i, text: i })), style.fontWeight)}
					</div>
					<div class="space-between">
						<label id="font-family" class="control-label">${__('Font Family', 'groundhogg')}</label>
						${select({
							id: 'font-family',
							name: 'fontFamily',
							className: 'font-control control-input',
							dataTag: tag,
						}, fontFamilies, style.fontFamily)}
					</div>
				</div>
			</div>`
      }

      return `${textControlGroup(__('Paragraphs'), 'p', p)}
      ${textControlGroup(__('Heading 1'), 'h1', h1)}
      ${textControlGroup(__('Heading 2'), 'h2', h2)}
      ${textControlGroup(__('Heading 3'), 'h3', h3)}`
    },
    controlsOnMount: ({ updateBlock, curBlock }) => {

      $('.font-control').on('change', ({ target }) => {

        let tag = target.dataset.tag

        updateBlock({
          [tag]: {
            ...curBlock()[tag],
            [target.name]: target.value
          }
        })
      })

    },
    edit: ({ id, content }) => {

      // language=HTML
      return `
		  <div class="maybe-edit-text" style="text-align: left">
			  ${content}
			  <button class="gh-button primary edit-text-content">${__('Edit Content', 'groundhogg')}</button>
		  </div>`
    },
    editOnMount: ({ id, content, updateBlock, curBlock }) => {

      $(`[data-id=${id}] .edit-text-content`).on('click', (e) => {
        modal({
          content: textarea({
            value: curBlock().content,
            id: `text-${id}`
          }),
          width: 600,
          onOpen: () => {
            tinymceElement(`text-${id}`, {
              tinymce: true,
              quicktags: true,
              settings: {
                height: 800,
              },
            }, (content) => {
              updateBlock({
                content
              })
            })
          },
          beforeClose: () => {

            wp.editor.remove(`text-${id}`)

            return true
          }
        })
      })
    },
    css: ({ id, p, h1, h2, h3 }) => {

      //language=CSS
      return `
          [data-id="${id}"] * {
              ${objectToStyle(p)}
          }

          [data-id="${id}"] ul {
              list-style: disc;
              padding-left: 20px;
          }

          [data-id="${id}"] h1 {
              ${objectToStyle(h1)}
          }

          [data-id="${id}"] h2 {
              ${objectToStyle(h2)}
          }

          [data-id="${id}"] h3 {
              ${objectToStyle(h3)}
          }
      `
    },
    html: ({ content }) => {
      // language=HTML
      return `
		  <div style="text-align: left">
			  ${content}
		  </div>`
    },
    defaults: {
      content: `<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin egestas dolor non nulla varius, id fermentum ante euismod. Ut a sodales nisl, at maximus felis. Suspendisse potenti. Etiam fermentum magna nec diam lacinia, ut volutpat mauris accumsan. Nunc id convallis magna. Ut eleifend sem aliquet, volutpat sapien quis, condimentum leo.</p>`,
      p: {
        fontSize: 14,
        fontWeight: 'normal',
        fontFamily: 'Arial, sans-serif'
      },
      h1: {
        fontSize: 32,
        fontWeight: '500',
        fontFamily: 'Arial, sans-serif'
      },
      h2: {
        fontSize: 24,
        fontWeight: '500',
        fontFamily: 'Arial, sans-serif'
      },
      h3: {
        fontSize: 18,
        fontWeight: '500',
        fontFamily: 'Arial, sans-serif'
      }
    }
  })

  registerBlock('image', __('Image'), {
    //language=HTML
    svg: icons.image,
    controls: ({ src, width, alt, title, align = 'center' }) => {
      //language=HTML
      return `
		  <div class="control-group gh-panel">
			  <div class="control-group-header space-between">
				  <h4 class="control-group-name">${__('Content')}</h4>
				  <span class="dashicons dashicons-arrow-down-alt2"></span>
			  </div>
			  <div class="controls">
				  <div class="control">
					  <label for="image-src" class="control-label">${__('Image SRC', 'groundhogg')}</label>
					  <div class="gh-input-group">
						  ${input({
							  type: 'text',
							  id: 'image-src',
							  value: src,
							  className: 'control full-width',
							  name: 'src'
						  })}
						  <button class="gh-button secondary icon" id="select-image">
							  ${icons.image}
						  </button>
					  </div>
				  </div>
				  <div class="control">
					  <label for="image-alt" class="control-label">${__('Alt Text', 'groundhogg')}</label>
					  ${input({
						  type: 'text',
						  id: 'image-alt',
						  className: 'control full-width',
						  name: 'alt',
						  value: alt
					  })}
				  </div>
				  <div class="control">
					  <label for="image-title" class="control-label">${__('Title', 'groundhogg')}</label>
					  ${input({
						  type: 'text',
						  id: 'image-title',
						  className: 'control full-width',
						  name: 'title',
						  value: title
					  })}
				  </div>
				  <div class="control space-between">
					  <label class="">${__('Alignment', 'groundhogg')}</label>
					  <div class="gh-input-group">
						  <button id="align-left" data-alignment="left"
						          class="change-alignment gh-button ${
							          align === 'left' ? 'primary' : 'secondary'
						          }">
							  ${icons.alignLeft}
						  </button>
						  <button id="align-center" data-alignment="center"
						          class="change-alignment gh-button ${
							          align === 'center' ? 'primary' : 'secondary'
						          }">
							  ${icons.alignCenter}
							  <button id="align-right" data-alignment="center"
							          class="change-alignment gh-button ${
								          align === 'right' ? 'primary' : 'secondary'
							          }">
								  ${icons.alignRight}
							  </button>
					  </div>
				  </div>
			  </div>
		  </div>
      `
    },
    css: () => '',
    controlsOnMount: ({
      updateBlock
    }) => {

      $('#align-left').on('click', () => {
        updateBlock({
          align: 'left'
        }, false, true)
      })

      $('#align-center').on('click', () => {
        updateBlock({
          align: 'center'
        }, false, true)
      })

      $('#align-right').on('click', () => {
        updateBlock({
          align: 'right'
        }, false, true)
      })

      $('#image-src,#image-alt,#image-title').on('change', e => {
        updateBlock({
          [e.target.name]: e.target.value
        })
      })

      // Uploading files
      var file_frame

      $('#select-image').on('click', (event) => {

        var picker = $(this)

        event.preventDefault()
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
          multiple: false	// Set to true to allow multiple files to be selected

        })
        // When an image is selected, run a callback.
        file_frame.on('select', function () {
          // We set multiple to false so only get one image from the uploader
          var attachment = file_frame.state().get('selection').first().toJSON()

          console.log(attachment)

          let { height, width } = attachment

          if (width > MAX_EMAIL_WITH) {
            let ratio = height / width
            width = MAX_EMAIL_WITH
            height = width * ratio
          }

          updateBlock({
            src: attachment.url,
            alt: attachment.alt,
            title: attachment.title,
            width: width,
            // height: height,
          }, false, true)
        })
        // Finally, open the modal
        file_frame.open()
      })

    },
    edit: ({ src, width, height, alt = '', title = '', align = 'center' }) => {
      // language=HTML
      return `
		  <div class="img-container" style="width: 100%;text-align: ${align}">
			  <img alt="${specialChars(alt)}" title="${specialChars(title)}" width="${width}" height="auto"
			       src="${src}" style="vertical-align: bottom"/>
		  </div>`
    },
    editOnMount: ({ id, updateBlock }) => {
      let $img = $(`[data-id=${id}] img`)

      // Delay because of weird height 0 thing
      setTimeout(() => {
        $img.resizable({
          aspectRatio: true,
          maxWidth: 600,
          stop: (e, ui) => {
            updateBlock({
              width: ui.size.width,
              // height: ui.size.height
            })
          }
        })
      }, 100)

    },
    html: ({ src, width, height, alt = '', title = '', align = 'center' }) => {
      // language=HTML
      return `
		  <div class="img-container" style="text-align: ${align}">
			  <img alt="${specialChars(alt)}" title="${specialChars(title)}" width="${width}" height="auto"
			       src="${src}" style="vertical-align: bottom"/>
		  </div>`
    },
    defaults: {
      src: 'http://via.placeholder.com/600x338',
      alt: 'placeholder image',
      title: 'placeholder image',
      width: MAX_EMAIL_WITH,
      height: 338,
      align: 'center',
    }

  })

  registerBlock('button', __('Button'), {
    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
			<path fill="#444"
			      d="m15.7 5.3-1-1c-.2-.2-.4-.3-.7-.3H1c-.6 0-1 .4-1 1v5c0 .3.1.6.3.7l1 1c.2.2.4.3.7.3h13c.6 0 1-.4 1-1V6c0-.3-.1-.5-.3-.7zM14 10H1V5h13v5z"/>
		</svg>`,
    controls: ({ text, link, style, align, size }) => {
      //language=HTML
      return `
		  <div class="control-group gh-panel">
			  <div class="control-group-header space-between">
				  <h4 class="control-group-name">${__('Content')}</h4>
				  <span class="dashicons dashicons-arrow-down-alt2"></span>
			  </div>
			  <div class="controls">
				  <div class="control">
					  <label class="control-label">${__('Button Text', 'groundhogg')}</label>
					  ${inputWithReplacements({
						  type: 'text',
						  id: 'button-text',
						  className: 'full-width',
						  value: text
					  })}
				  </div>
				  <div class="control">
					  <label class="control-label">${__('Button Link', 'groundhogg')}</label>
					  ${inputWithReplacements({
						  type: 'text',
						  id: 'button-link',
						  className: 'full-width',
						  value: link
					  })}
				  </div>
				  <div class="space-between">
					  <label class="control-label">${__('Button Size', 'groundhogg')}</label>
					  ${select({
						  name: 'size',
						  id: 'button-size',
					  }, {
						  sm: __('Small'),
						  md: __('Medium'),
						  lg: __('Large'),
					  }, size)}
				  </div>
				  <div class="control space-between">
					  <label class="">${__('Alignment', 'groundhogg')}</label>
					  <div class="gh-input-group">
						  <button id="align-left" data-alignment="left"
						          class="change-alignment gh-button ${
							          align === 'left' ? 'primary' : 'secondary'
						          }">
							  ${icons.alignLeft}
						  </button>
						  <button id="align-center" data-alignment="center"
						          class="change-alignment gh-button ${
							          align === 'center' ? 'primary' : 'secondary'
						          }">
							  ${icons.alignCenter}
							  <button id="align-right" data-alignment="right"
							          class="change-alignment gh-button ${
								          align === 'right' ? 'primary' : 'secondary'
							          }">
								  ${icons.alignRight}
							  </button>
					  </div>
				  </div>
			  </div>
		  </div>
		  <div class="control-group closed">
			  <div class="control-group-header space-between">
				  <h4 class="control-group-name">${__('Button Style')}</h4>
				  <span class="dashicons dashicons-arrow-down-alt2"></span>
			  </div>
			  <div class="controls">
				  <div class="space-between">
					  <label class="control-label">${__('Button Color', 'groundhogg')}</label>
					  ${input({
						  type: 'text',
						  id: 'button-color',
						  value: style.backgroundColor,
					  })}
				  </div>
				  <div class="space-between">
					  <label class="control-label">${__('Text Color', 'groundhogg')}</label>
					  ${input({
						  type: 'text',
						  id: 'text-color',
						  value: style.color,
					  })}
				  </div>
			  </div>
		  </div>
		  <div class="control-group closed">
			  <div class="control-group-header space-between">
				  <h4 class="control-group-name">${__('Font Style')}</h4>
				  <span class="dashicons dashicons-arrow-down-alt2"></span>
			  </div>
			  <div class="controls">
				  <div class="space-between">
					  <label for="font-size" class="control-label">${__('Font Size', 'groundhogg')}</label>
					  ${input({
						  type: 'number',
						  id: 'font-size',
						  name: 'fontSize',
						  className: 'font-control control-input',
						  value: style.fontSize
					  })}
				  </div>
				  <div class="space-between">
					  <label for="font-weight" class="control-label">${__('Font Weight', 'groundhogg')}</label>
					  ${select({
						  id: 'font-weight',
						  name: 'fontWeight',
						  className: 'font-control control-input',
					  }, fontWeights.map(i => ({ value: i, text: i })), style.fontWeight)}
				  </div>
				  <div class="space-between">
					  <label for="font-family" class="control-label">${__('Font Family', 'groundhogg')}</label>
					  ${select({
						  id: 'font-family',
						  name: 'fontFamily',
						  className: 'font-control control-input',
					  }, fontFamilies, style.fontFamily)}
				  </div>
			  </div>
		  </div>`
    },

    controlsOnMount: ({
      updateBlock,
      curBlock
    }) => {

      $('#align-left').on('click', () => {
        updateBlock({
          align: 'left'
        }, false, true)
      })

      $('#align-center').on('click', () => {
        updateBlock({
          align: 'center'
        }, false, true)
      })

      $('#align-right').on('click', () => {
        updateBlock({
          align: 'right'
        }, false, true)
      })

      $('#button-color').wpColorPicker({
        width: 200,
        change: (e, ui) => {
          updateBlock({
            style: {
              ...curBlock().style,
              backgroundColor: ui.color.toString()
            }
          })
        }
      })

      $('#text-color').wpColorPicker({
        width: 200,
        change: (e, ui) => {
          updateBlock({
            style: {
              ...curBlock().style,
              color: ui.color.toString()
            }
          })
        }
      })

      $('#button-text').on('change', e => {
        updateBlock({
          text: e.target.value
        })
      })

      linkPicker('#button-link').on('change', e => {
        updateBlock({
          link: e.target.value
        })
      })

      $('#button-size').on('change', e => {
        updateBlock({
          size: e.target.value
        })
      })

      $('.font-control').on('change', ({ target }) => {
        updateBlock({
          style: {
            ...curBlock().style,
            [target.name]: target.value
          }
        })
      })

    },
    edit: ({ text, align, style, size }) => {

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

      //language=HTML
      return `
		  <table width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tbody>
			  <tr>
				  <td align="${align}">
					  <table border="0" cellspacing="0" cellpadding="0">
						  <tbody>
						  <tr>
							  <td class="email-button" bgcolor="${style.backgroundColor}"
							      style="padding: ${padding}; border-radius:3px" align="center">
								  <a contenteditable="true" target="_blank"
								     style="font-size: ${style.fontSize}px; font-family: ${style.fontFamily}; font-weight: ${style.fontWeight}; color: ${style.color}; text-decoration: none !important; display: inline-block;">${text}</a>
							  </td>
						  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
			  </tbody>
		  </table>`
    },
    editOnMount: ({ id, updateStyle, updateBlock }) => {

      $(`[data-id=${id}] .email-button a`).on('keyup keydown', (e) => {
        updateBlock({
          text: e.currentTarget.textContent
        }, false)
      })

    },
    css: ({ id, style }) => {

      let {
        color, backgroundColor
      } = style

      // language=CSS
      return `

          [data-id="${id}"] a {
              ${objectToStyle({
                  display: 'inline-block',
                  color,
                  backgroundColor,
                  padding: '8px 12px'
              })}
          }

      `
    },
    html: ({ link, size, text, align = 'center', style = {} }) => {

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

      //language=HTML
      return `
		  <table width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tbody>
			  <tr>
				  <td align="${align}">
					  <table border="0" cellspacing="0" cellpadding="0">
						  <tbody>
						  <tr>
							  <td class="email-button" bgcolor="${style.backgroundColor}"
							      style="padding: ${padding}; border-radius:3px" align="center"><a href="${link}"
							                                                                       target="_blank"
							                                                                       style="font-size: ${style.fontSize}px; font-family: ${style.fontFamily}; font-weight: ${style.fontWeight}; color: ${style.color}; text-decoration: none !important; display: inline-block;">${text}</a>
							  </td>
						  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
			  </tbody>
		  </table>`
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
        fontFamily: 'Arial, sans-serif'
      }
    },
  })

  registerDynamicBlock('posts', __('Posts'), {
    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 193.826 193.826"
		     style="enable-background:new 0 0 193.826 193.826" xml:space="preserve"><path d="M191.495 55.511 137.449 1.465a4.998 4.998 0 0 0-7.07 0l-.229.229a17.43 17.43 0 0 0-5.14 12.406c0 3.019.767 5.916 2.192 8.485l-56.55 48.533c-4.328-3.868-9.852-5.985-15.703-5.985a23.444 23.444 0 0 0-16.689 6.913l-.339.339a4.998 4.998 0 0 0 0 7.07l32.378 32.378-31.534 31.533c-.631.649-15.557 16.03-25.37 28.27-9.345 11.653-11.193 13.788-11.289 13.898a4.995 4.995 0 0 0 .218 6.822 4.987 4.987 0 0 0 3.543 1.471c1.173 0 2.349-.41 3.295-1.237.083-.072 2.169-1.885 13.898-11.289 12.238-9.813 27.619-24.74 28.318-25.421l31.483-31.483 30.644 30.644c.976.977 2.256 1.465 3.535 1.465s2.56-.488 3.535-1.465l.339-.339a23.446 23.446 0 0 0 6.913-16.689 23.43 23.43 0 0 0-5.985-15.703l48.533-56.55a17.434 17.434 0 0 0 8.485 2.192c4.687 0 9.093-1.825 12.406-5.14l.229-.229a5 5 0 0 0 0-7.072z"/></svg>`,
    controls: ({ query = {} }) => {

      const { numberposts = 5 } = query

      //language=HTML
      return `
		  <div class="control-group gh-panel">
			  <div class="control-group-header space-between">
				  <h4 class="control-group-name">${__('Query')}</h4>
				  <span class="dashicons dashicons-arrow-down-alt2"></span>
			  </div>
			  <div class="controls">
				  <div class="control">
					  <div class="space-between">
						  <label for="number-posts" class="control-label">${__('Number of posts', 'groundhogg')}</label>
						  ${input({
							  type: 'number',
							  id: 'number-posts',
							  value: numberposts,
							  className: 'control-input query-control',
							  name: 'numberposts'
						  })}
					  </div>
				  </div>
			  </div>
		  </div>
      `
    },
    controlsOnMount: ({ updateBlock, curBlock }) => {
      $('.query-control').on('change', e => {
        updateBlock({
          query: {
            ...curBlock().query,
            [e.target.name]: e.target.value
          }
        })
      })
    },
    defaults: {
      query: {
        numberposts: 5
      }
    },
  })

  Groundhogg.EmailBlockEditor = BlockEditor

})(jQuery)