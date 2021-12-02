(($) => {

  const { el, objectToStyle, icons } = Groundhogg.element
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { __, _x, _n, _nx, sprintf } = wp.i18n


  const BlockEditor = ( el, {
    email,
    onChange,
  } ) => ({

    $el: $(el),
    email,

    init ( ) {
      this.render()
    },

    templates: {
      css: () => {

      },

      block: ({type, name, svg}) => {
        // language=HTML
        return `<div class="block" data-type="${type}">
            <div class="icon">
                ${svg}
            </div>
            <span class="block-name">${name}</span>
        </div>`
      },

      editor: () => {
        // language=HTML
        return `<div id="block-editor">
            <!-- BLOCKS -->
            <div id="blocks-panel"></div>
            <!-- CONTENT -->
            <div id="content"></div>
            <!-- CONTROLS -->
            <div id="controls-panel"></div>
        </div>`
      }
    },

    render () {

      this.$el.html( this.templates.editor() )
      this.onMount()

    },
    onMount () {

      this.$el.find('#blocks-panel').html( Object.values(BlockRegistry.blocks).map( b => this.templates.block( b ) ) )

    },

  })

  const BlockRegistry = {

    collections: {
      core: 'Groundhogg'
    },
    blocks: {},

    /**
     * Register a new block
     *
     * @param type
     * @param name
     * @param block
     * @param collection
     */
    registerBlock (type, name, block, collection = 'core') {

      this.blocks[type] = {
        type,
        name,
        collection,
        ...block
      }

    },

    /**
     * Register a new block collection
     *
     * @param collection
     * @param name
     */
    registerBlockCollection (collection, name) {
      this.collections[collection] = name
    }

  }

  const { registerBlock } = BlockRegistry

  registerBlock('text', __('Text'), {
    svg: icons.tag,
    controls: () => {},
    controlsOnMount: () => {},
    edit: () => {},
    editOnMount: () => {},
    html: ({ content, style }) => {
      return content
    }

  })

  registerBlock('button', __('Button'), {
    svg: icons.tag,
    controls: ({link, style}) => {

    },
    controlsOnMount: () => {},
    edit: ({ text, align }) => {
      //language=HTML
      return `
		  <table>
			  <tbody>
			  <tr>
				  <td align="${align}">
              <a class="email-button" contenteditable="true">${text}</a>
          </td>
			  </tr>
			  </tbody>
		  </table>`
    },
    editOnMount: ({id, updateStyle, updateBlock}) => {

      $(`#${id} .email-button`).on('keyup blur', (e) => {
        updateBlock({
          text: e.target.textContent
        })
      })

    },
    css: ({ id, style }) => {

      // language=CSS
      return `#${id} a.email-button {
          ${objectToStyle(style)}
      }`
    },
    html: ({ link, text, align }) => {
      //language=HTML
      return `
		  <table>
			  <tbody>
			  <tr>
				  <td align="${align}">
              <a href="${link}" class="email-button">${text}</a>
          </td>
			  </tr>
			  </tbody>
		  </table>`
    },
  })

  Groundhogg.EmailBlockEditor = BlockEditor

  BlockEditor( '#email-editor-body', {

  } ).init()

})(jQuery)